<?php

use App\Http\Middleware\ExpirarReservasVencidas;
use App\Http\Middleware\SomenteCoordenacao;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Não há chave "api:" aqui de propósito: ela carregaria routes/api.php uma
        // segunda vez, no grupo "api" (sem sessão), e o login do site quebraria.
        then: function () {
            // API v1 por token (Sanctum): grupo "api", sem sessão e sem CSRF.
            // O 3º parâmetro do throttle ("v1") dá à v1 um contador próprio; sem
            // ele, a contagem seria a mesma do throttle:10,1 do login do site.
            Route::middleware(['api', ExpirarReservasVencidas::class, 'throttle:60,1,v1'])
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(base_path('routes/api_v1.php'));

            // API do site com sessão por cookie (grupo "web"), como o front-end espera.
            Route::middleware(['web', ExpirarReservasVencidas::class])
                ->prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'coordenacao' => SomenteCoordenacao::class,
        ]);

        // Visitante sem login tentando abrir algo protegido fora da API.
        $middleware->redirectGuestsTo('/login.html');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // /api/documentacao é uma página HTML (Blade), não uma rota da API.
        $ehApi = fn (Request $request) => $request->is('api/*') && ! $request->is('api/documentacao');

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $ehApi($request) || $request->expectsJson(),
        );

        // Na API, todo erro sai como { "erro": "mensagem" }, que é o que
        // public/js/api.js mostra para o usuário. Na API pública v1, o mesmo
        // erro ganha o envelope { "success": false, "erro": "mensagem" }.
        $exceptions->render(function (Throwable $e, Request $request) use ($ehApi) {
            if (! $ehApi($request)) {
                return null;
            }

            [$status, $mensagem] = match (true) {
                $e instanceof ValidationException => [422, $e->validator->errors()->first()],
                $e instanceof AuthenticationException => [401, 'Você precisa entrar para fazer isso.'],
                $e instanceof NotFoundHttpException && $e->getPrevious() instanceof ModelNotFoundException => [
                    404,
                    match (class_basename($e->getPrevious()->getModel())) {
                        'Livro' => 'Livro não encontrado.',
                        'Materia' => 'Matéria não encontrada.',
                        'Reserva' => 'Reserva não encontrada.',
                        default => 'Registro não encontrado.',
                    },
                ],
                $e instanceof NotFoundHttpException => [404, 'Rota não encontrada.'],
                $e instanceof MethodNotAllowedHttpException => [405, 'Método não permitido nesta rota.'],
                $e instanceof HttpExceptionInterface && $e->getStatusCode() === 419 => [
                    419, 'Sua sessão expirou. Recarregue a página e tente novamente.',
                ],
                $e instanceof HttpExceptionInterface && $e->getStatusCode() === 413 => [
                    413, $e->getMessage() ?: 'Corpo da requisição grande demais.',
                ],
                $e instanceof HttpExceptionInterface && $e->getStatusCode() === 429 => [
                    429, 'Muitas tentativas. Aguarde um minuto e tente de novo.',
                ],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage() ?: 'Não foi possível completar a operação.'],
                default => [500, config('app.debug') ? $e->getMessage() : 'Erro interno do servidor.'],
            };

            $cabecalhos = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

            $corpo = $request->is('api/v1', 'api/v1/*')
                ? ['success' => false, 'erro' => $mensagem]
                : ['erro' => $mensagem];

            return response()->json($corpo, $status, $cabecalhos);
        });
    })->create();
