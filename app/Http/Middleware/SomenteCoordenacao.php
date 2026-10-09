<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Libera a rota só para usuários com papel "admin". Use depois de "auth". */
class SomenteCoordenacao
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->ehAdmin()) {
            abort(403, 'Acesso restrito à coordenação.');
        }

        return $next($request);
    }
}
