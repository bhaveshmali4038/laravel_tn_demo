<?php

namespace App\Http\Requests;

class UpdateBranchRequest extends StoreBranchRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['images'][0] = 'nullable';
        return $rules;
    }
}
