<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class SyncRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware role:OWNER,MANAGER sẽ handle việc này
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ingredients'                 => 'present|array',
            'ingredients.*.ingredient_id' => 'required|uuid|exists:items,id',
            'ingredients.*.quantity'      => 'required|numeric|min:0.001',
        ];
    }
}
