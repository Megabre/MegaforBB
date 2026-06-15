<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Requests;

use App\Http\Requests\FormRequest;
use App\Modules\Idelist\Models\IdeaCategory;

class StoreIdeaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'description' => 'required|max:5000',
            'category_id' => 'numeric',
        ];
    }

    public function validate(): bool
    {
        if (!parent::validate()) {
            return false;
        }

        $categoryId = (int) ($this->input('category_id', 0));
        if ($categoryId > 0 && !IdeaCategory::query()->where('id', $categoryId)->exists()) {
            return false;
        }

        return true;
    }
}
