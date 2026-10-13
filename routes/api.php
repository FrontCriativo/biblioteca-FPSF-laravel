<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LivroController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\ReservaController;
use Illuminate\Support\Facades\Route;

/*
| Rotas da API (prefixo /api). Carregadas em bootstrap/app.php com o grupo
| "web": sessão por cookie + proteção CSRF (cabeçalho X-XSRF-TOKEN).
*/

/*
| API pública v1 (/api/v1/...): só leitura, respostas { "success": true, "data": ... }.
| Fica neste arquivo (e não numa chave "api:" no withRouting) para não carregar
| as rotas duas vezes. Documentação em /api/documentacao.
|
| O 3º parâmetro do throttle ("v1") dá à v1 um contador próprio. Sem ele, o
| contador seria o mesmo do throttle:10,1 do login (a chave é só o IP), e quem
| chamasse a v1 algumas vezes ficaria sem conseguir entrar no site.
*/
Route::prefix('v1')->name('api.v1.')->middleware('throttle:60,1,v1')->group(function () {
    // Status da API
    Route::get('/status', [V1\StatusController::class, 'index'])->name('status');
    // Depoimentos
    Route::get('/depoimentos', [V1\DepoimentoController::class, 'index'])->name('depoimentos');
    // Matérias
    Route::get('/materias', [V1\MateriaController::class, 'index'])->name('materias');
    Route::get('/materias/{materia}/livros', [V1\MateriaController::class, 'livros'])->name('materias.livros');
    // Livros
    Route::get('/livros', [V1\LivroController::class, 'index'])->name('livros');
    Route::get('/livros/{livro:slug}', [V1\LivroController::class, 'show'])->name('livros.show');
});

// Sessão
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/eu', [AuthController::class, 'eu']);

// Catálogo (público)
Route::get('/materias', [MateriaController::class, 'index']);
Route::get('/livros', [LivroController::class, 'index']);
Route::get('/livros/{livro:slug}', [LivroController::class, 'show']);

// Reservas do aluno
Route::middleware('auth')->group(function () {
    Route::get('/reservas', [ReservaController::class, 'index']);
    Route::post('/reservas', [ReservaController::class, 'store']);
    Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->whereNumber('reserva');
});

// Coordenação
Route::middleware(['auth', 'coordenacao'])->group(function () {
    Route::post('/livros', [LivroController::class, 'store']);
    Route::put('/livros/{livro}', [LivroController::class, 'update'])->whereNumber('livro');
    Route::delete('/livros/{livro}', [LivroController::class, 'destroy'])->whereNumber('livro');
    Route::post('/livros/{livro}/exemplares', [LivroController::class, 'adicionarExemplares'])->whereNumber('livro');

    Route::prefix('admin')->group(function () {
        Route::get('/resumo', Admin\ResumoController::class);
        Route::post('/capas', [Admin\CapaController::class, 'store']);

        Route::get('/reservas', [Admin\ReservaController::class, 'index']);
        Route::post('/reservas/{reserva}/retirar', [Admin\ReservaController::class, 'retirar'])->whereNumber('reserva');
        Route::post('/reservas/{reserva}/devolver', [Admin\ReservaController::class, 'devolver'])->whereNumber('reserva');

        Route::get('/usuarios', [Admin\UsuarioController::class, 'index']);
        Route::post('/usuarios', [Admin\UsuarioController::class, 'store']);
    });
});
