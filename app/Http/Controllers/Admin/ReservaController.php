<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReservaResource;
use App\Models\Reserva;
use App\Services\ServicoDeReservas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Balcão da coordenação: todas as reservas, retirada e devolução. */
class ReservaController extends Controller
{
    public function __construct(private ServicoDeReservas $reservas) {}

    /** GET /api/admin/reservas?status=ativa|retirada|devolvida|cancelada|todas */
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $reservas = Reserva::query()
            ->comDetalhes()
            ->when($status && $status !== 'todas', fn ($q) => $q->where('status', $status))
            ->latest('criada_em')
            ->latest('id')
            ->get();

        return ReservaResource::collection($reservas);
    }

    /** POST /api/admin/reservas/{id}/retirar */
    public function retirar(Reserva $reserva): ReservaResource
    {
        return new ReservaResource($this->reservas->mudarStatus($reserva, 'retirada'));
    }

    /** POST /api/admin/reservas/{id}/devolver */
    public function devolver(Reserva $reserva): ReservaResource
    {
        return new ReservaResource($this->reservas->mudarStatus($reserva, 'devolvida'));
    }
}
