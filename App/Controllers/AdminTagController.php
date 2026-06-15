<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Tag;

class AdminTagController extends AdminController
{
    public function index(): string
    {
        $tags = Tag::orderBy('use_count', 'desc')->orderBy('name')->get();
        return $this->view('tags/index', [
            'pageTitle' => lang('admin.tags.title'),
            'tags' => $tags,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function create(): string
    {
        return $this->view('tags/form', [
            'pageTitle' => lang('admin.tags.add_title'),
            'tag' => new Tag(),
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function store(): void
    {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.tags.name_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/tags/create'));
            return;
        }
        $name = mb_substr($name, 0, 100);
        $slug = \Forecor\Core\Str::slug($name) ?: 'tag-' . uniqid();
        $existing = Tag::where('slug', $slug)->first();
        if ($existing) {
            $slug = $slug . '-' . substr(uniqid(), -5);
        }
        Tag::create([
            'name' => $name,
            'slug' => $slug,
            'description' => trim(mb_substr((string)($_POST['description'] ?? ''), 0, 500)) ?: null,
            'use_count' => 0,
        ]);
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/tags'));
    }

    public function edit(string $id): string
    {
        $tag = Tag::findOrFail((int) $id);
        return $this->view('tags/form', [
            'pageTitle' => lang('admin.tags.edit_title'),
            'tag' => $tag,
            'user' => $this->app->auth()->user(),
        ]);
    }

    public function update(string $id): void
    {
        $tag = Tag::findOrFail((int) $id);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $this->app->session()->getFlashBag()->add('error', lang('admin.tags.name_required'));
            $this->redirect(core_url(env('ADMIN_PATH', 'admin') . "/tags/edit/{$id}"));
            return;
        }
        $tag->name = mb_substr($name, 0, 100);
        $tag->description = trim(mb_substr((string)($_POST['description'] ?? ''), 0, 500)) ?: null;
        $tag->save();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/tags'));
    }

    public function delete(string $id): void
    {
        $tag = Tag::findOrFail((int) $id);
        \Illuminate\Database\Capsule\Manager::table('topic_tags')->where('tag_id', $tag->id)->delete();
        $tag->delete();
        $this->redirect(core_url(env('ADMIN_PATH', 'admin') . '/tags'));
    }
}
