<?php

namespace App\Http\Requests;

/**
 * Login da API v1 (POST /api/v1/auth/login). Mesmas regras do login do site,
 * com o e-mail validado como e-mail e o nome do dispositivo que recebe o token.
 */
class LoginTokenRequest extends LoginRequest
{
    public function rules(): array
    {
        $regras = parent::rules();
        $regras['email'][] = 'email';
        $regras['device_name'] = ['nullable', 'string', 'max:100'];

        return $regras;
    }
}
