<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncPackageFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'feature_ids'   => ['required', 'array'],
            'feature_ids.*' => ['uuid', 'exists:features,id'],
        ];
    }
}
