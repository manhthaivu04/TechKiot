<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_id'       => ['required', 'uuid', 'exists:packages,id'],
            'package_price_id' => ['nullable', 'uuid', 'exists:package_prices,id'],
        ];
    }
}
