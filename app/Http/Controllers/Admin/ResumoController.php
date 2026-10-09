<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exemplar;
use App\Models\Livro;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

class ResumoController extends Controller
{
    /** GET /api/admin/resumo */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'livros' => Livro::count(),
            'exemplares' => Exemplar::query()->emBomEstado()->count(),
            'reservas_ativas' => Reserva::where('status', 'ativa')->count(),
            'retiradas' => Reserva::where('status', 'retirada')->count(),
            'usuarios' => Usuario::where('papel', 'aluno')->count(),
        ]);
    }
}
