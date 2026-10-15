<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckLinkRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'input' => ['required', 'string', 'max:4000'],
        ];
    }
}
