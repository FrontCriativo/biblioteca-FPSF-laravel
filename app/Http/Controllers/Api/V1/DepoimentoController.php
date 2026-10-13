<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\DepoimentoResource;
use App\Models\Depoimento;
use Illuminate\Http\JsonResponse;

class DepoimentoController extends Controller
{
    /** GET /api/v1/depoimentos — só os aprovados, mais recentes primeiro. */
    public function index(): JsonResponse
    {
        $depoimentos = Depoimento::query()
            ->aprovados()
            ->orderByDesc('criado_em')
            ->orderByDesc('id')
            ->get();

        return $this->sucesso(DepoimentoResource::collection($depoimentos));
    }
}
