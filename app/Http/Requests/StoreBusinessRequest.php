<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone_number' => ['bail', 'required', 'string', 'max:30', 'regex:/^\+?[0-9 ()\-.]{7,30}$/', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\D/', '', $value);
                if (strlen($digits) < 7 || strlen($digits) > 15) {
                    $fail('The phone number must contain 7 to 15 digits.');
                }
            }],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
