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

        // validate() confere a senha sem abrir a sessão; só depois de saber que a
        // conta está ativa o usuário entra de fato (login()).
        $guarda = Auth::guard('web');

        if (! $guarda->validate($credenciais)) {
            abort(401, 'E-mail ou senha incorretos.');
        }

        $usuario = $guarda->getLastAttempted();

        if (! $usuario->ativo) {
            abort(403, 'Conta desativada. Procure a coordenação.');
        }

        $guarda->login($usuario);

        // Novo ID de sessão a cada login (evita session fixation).
        $request->session()->regenerate();

        return new UsuarioResource($usuario);
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
