<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Category;
use App\Models\ContentPermission;
use App\Models\Forum;
use App\Models\GroupPermission;
use App\Models\PermissionDefinition;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasPermissions
 *
 * Implements WoltLab-style layered ACL:
 * 1. Admin Override (Role ID 1)
 * 2. Content-specific permission with inheritance (Forum → Category)
 * 3. Global Group Permission
 * 4. Permission Default Value
 */
trait HasPermissions
{
    /**
     * Check if user has permission.
     *
     * @param string $permissionKey The unique key of the permission (e.g. 'can_view_forum')
     * @param Model|null $content Optional content object (e.g. Forum instance)
     * @return bool
     */
    public function hasPermission(string $permissionKey, ?Model $content = null): bool
    {
        // 1. Super Admin Role (ID: 1) always has full access
        if ($this->role_id === 1) {
            return true;
        }

        // Cache definitions in static property if performance is critical later
        // For now, simpler implementation is safer.
        $definition = PermissionDefinition::where('key', $permissionKey)->first();

        if (!$definition) {
            // Permission not defined -> Deny by default or throw exception?
            // Deny is safer.
            return false;
        }

        $permissionId = $definition->id;

        // 2. Content-specific: check inheritance chain (e.g. Forum then Category)
        if ($content !== null) {
            $chain = $this->getPermissionInheritanceChain($content);
            foreach ($chain as $node) {
                $contentPerm = $this->getContentPermission($permissionId, $node);
                if ($contentPerm !== null) {
                    return (bool) $contentPerm->value;
                }
            }
        }

        // 3. Global Group Permission
        /** @var GroupPermission|null $groupPerm */
        $groupPerm = GroupPermission::query()
            ->where('role_id', $this->role_id)
            ->where('permission_id', $permissionId)
            ->first();

        if ($groupPerm) {
            return (bool) $groupPerm->value;
        }

        // 4. Default Value from Definition
        return (bool) $definition->default_value;
    }

    /**
     * Permission inheritance chain: most specific first (e.g. Forum, then Category).
     *
     * @return Model[]
     */
    private function getPermissionInheritanceChain(Model $content): array
    {
        $chain = [$content];

        if ($content instanceof Forum) {
            $categoryId = (int) ($content->category_id ?? 0);
            if ($categoryId > 0) {
                $category = $content->relationLoaded('category')
                    ? $content->category
                    : Category::find($categoryId);
                if ($category instanceof Category) {
                    $chain[] = $category;
                }
            }
        }

        return $chain;
    }

    /**
     * Get content-level permission for this user/role on the given content, or null if not set.
     */
    private function getContentPermission(int $permissionId, Model $content): ?ContentPermission
    {
        $class = get_class($content);
        $id = $content->getKey();

        /** @var ContentPermission|null $contentPerm */
        $contentPerm = ContentPermission::query()
            ->where('permission_id', $permissionId)
            ->where('content_type', $class)
            ->where('content_id', $id)
            ->where(function ($query) {
                $query->where('user_id', $this->id)
                    ->orWhere('role_id', $this->role_id);
            })
            ->orderByRaw('user_id IS NULL ASC')
            ->first();

        return $contentPerm;
    }
}
