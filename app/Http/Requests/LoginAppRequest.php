<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login do aplicativo Expo (POST /api/v1/login). Igual ao LoginTokenRequest,
 * mas com o campo "password" no lugar de "senha", que é o que o app envia.
 */
class LoginAppRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
