<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): UsuarioResource
    {
        $credenciais = [
            'email' => mb_strtolower(trim($request->input('email'))),
            'password' => $request->input('senha'),
        ];

        if (! Auth::attempt($credenciais)) {
            abort(401, 'E-mail ou senha incorretos.');
        }

        // Novo ID de sessão a cada login (evita session fixation).
        $request->session()->regenerate();

        return new UsuarioResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    /** Usuário logado, ou `null` quando ninguém entrou. */
    public function eu(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // setData(null) gera o JSON `null`; response()->json(null) geraria `{}`.
        return response()->json()->setData($usuario ? new UsuarioResource($usuario) : null);
    }
}
