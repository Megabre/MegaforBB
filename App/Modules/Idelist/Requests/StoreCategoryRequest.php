<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Requests;

use App\Http\Requests\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'color' => 'max:7',
            'icon' => 'max:50',
            'sort_order' => 'numeric',
        ];
    }
}
