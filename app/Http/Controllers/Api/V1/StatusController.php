<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    /** GET /api/v1/status */
    public function index(): JsonResponse
    {
        return $this->sucesso([
            'aplicacao' => 'Biblioteca Virtual FPSF',
            'api' => 'v1',
            'status' => 'online',
            'data_hora' => now()->toIso8601String(),
        ]);
    }
}
