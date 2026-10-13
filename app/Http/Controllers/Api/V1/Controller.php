<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base dos controllers da API pública v1.
 *
 * O projeto usa JsonResource::withoutWrapping() (o front-end espera respostas
 * "cruas"), então o envelope { "success": true, "data": ... } da v1 é montado
 * aqui. Os erros da v1 saem como { "success": false, "erro": ... } pelo
 * tratamento de exceções em bootstrap/app.php.
 */
abstract class Controller extends BaseController
{
    protected function sucesso(mixed $dados, int $status = 200): JsonResponse
    {
        if ($dados instanceof JsonResource) {
            $dados = $dados->resolve(request());
        }

        return response()->json(['success' => true, 'data' => $dados], $status);
    }
}
