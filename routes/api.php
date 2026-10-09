<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LivroController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\ReservaController;
use Illuminate\Support\Facades\Route;

/*
| Rotas da API (prefixo /api). Carregadas em bootstrap/app.php com o grupo
| "web": sessão por cookie + proteção CSRF (cabeçalho X-XSRF-TOKEN).
*/

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
