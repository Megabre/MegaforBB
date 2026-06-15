<?php

declare(strict_types=1);

namespace App\Modules\Idelist\Requests;

use App\Http\Requests\FormRequest;

class StoreStatusDefinitionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|max:120',
            'color' => 'nullable|max:7',
            'sort_order' => 'numeric',
        ];
    }
}
