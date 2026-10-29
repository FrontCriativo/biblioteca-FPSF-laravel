<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\LoginAppRequest;
use App\Http\Requests\LoginTokenRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticação da API v1 por token (Laravel Sanctum).
 *
 * O aplicativo troca e-mail e senha por um token, guarda o token e o envia
 * nas próximas requisições no cabeçalho "Authorization: Bearer <token>".
 */
class AuthController extends Controller
{
    /** POST /api/v1/auth/login */
    public function login(LoginTokenRequest $request): JsonResponse
    {
        [$usuario, $token] = $this->emitirToken(
            $request->validated('email'),
            $request->validated('senha'),
            $request->validated('device_name'),
        );

        return $this->sucessoComMensagem('Login realizado com sucesso.', [
            'token' => $token,
            'usuario' => new UsuarioResource($usuario),
        ]);
    }

    /**
     * POST /api/v1/login — formato do aplicativo Expo: recebe "password" e
     * devolve o token no topo da resposta, { "success": true, "token": "..." }.
     */
    public function loginApp(LoginAppRequest $request): JsonResponse
    {
        [$usuario, $token] = $this->emitirToken(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name'),
        );

        return response()->json([
            'success' => true,
            'mensagem' => 'Login realizado com sucesso.',
            'token' => $token,
            'usuario' => (new UsuarioResource($usuario))->resolve($request),
        ]);
    }

    /** POST /api/v1/auth/logout — invalida só o token usado nesta requisição. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sucessoComMensagem('Logout realizado com sucesso.');
    }

    /** @return array{0: Usuario, 1: string} */
    private function emitirToken(string $email, string $senha, ?string $dispositivo): array
    {
        $usuario = Usuario::firstWhere('email', mb_strtolower(trim($email)));

        // Mesma mensagem para e-mail inexistente e senha errada: a resposta não
        // revela quais e-mails estão cadastrados.
        if (! $usuario || ! Hash::check($senha, $usuario->senha)) {
            abort(401, 'E-mail ou senha incorretos.');
        }

        if (! $usuario->ativo) {
            abort(403, 'Conta desativada. Procure a coordenação.');
        }

        // Um token por dispositivo: entrar de novo no mesmo aparelho substitui o anterior.
        $dispositivo ??= 'app';
        $usuario->tokens()->where('name', $dispositivo)->delete();

        return [$usuario, $usuario->createToken($dispositivo)->plainTextToken];
    }
}
