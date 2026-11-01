<?php

namespace App\Http\Requests;

class UpdateBusinessRequest extends StoreBusinessRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['logo'][0] = 'nullable';
        return $rules;
    }
}
