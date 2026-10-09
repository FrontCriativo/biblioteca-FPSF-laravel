<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservaResource;
use App\Models\Livro;
use App\Models\Reserva;
use App\Services\ServicoDeReservas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Reservas do próprio aluno. */
class ReservaController extends Controller
{
    public function __construct(private ServicoDeReservas $reservas) {}

    /** GET /api/reservas */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservas = $request->user()->reservas()
            ->comDetalhes()
            ->latest('criada_em')
            ->latest('id')
            ->get();

        return ReservaResource::collection($reservas);
    }

    /** POST /api/reservas  { livro_id } */
    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate(['livro_id' => ['required', 'integer', 'min:1']]);
        $livro = Livro::findOrFail($dados['livro_id']);

        $reserva = $this->reservas->reservar($livro, $request->user());

        return (new ReservaResource($reserva))->response()->setStatusCode(201);
    }

    /** POST /api/reservas/{id}/cancelar — aluno cancela a própria; admin, qualquer uma. */
    public function cancelar(Reserva $reserva): ReservaResource
    {
        Gate::authorize('cancelar', $reserva);

        return new ReservaResource($this->reservas->mudarStatus($reserva, 'cancelada'));
    }
}
