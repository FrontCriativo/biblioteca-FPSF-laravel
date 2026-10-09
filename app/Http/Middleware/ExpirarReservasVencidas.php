<?php

namespace App\Http\Middleware;

use App\Services\ServicoDeReservas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antes de cada chamada à API, cancela as pré-reservas que passaram do
 * prazo de retirada. Assim a disponibilidade está sempre correta mesmo
 * sem o agendador (cron) do Laravel configurado no servidor.
 */
class ExpirarReservasVencidas
{
    public function __construct(private ServicoDeReservas $reservas) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->reservas->expirarVencidas();

        return $next($request);
    }
}
