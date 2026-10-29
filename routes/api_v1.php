<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

/*
| API v1 (/api/v1/...). Registrada em bootstrap/app.php com o grupo "api":
| sem sessão e sem CSRF. As rotas protegidas aceitam SOMENTE o token do
| Sanctum (cabeçalho Authorization: Bearer); o cookie de sessão do site não
| vale aqui (config/sanctum.php, 'guard' => []).
|
| Respostas { "success": true, ... }; erros { "success": false, "erro": ... }.
| Documentação em /api/documentacao.
|
| O throttle:60,1,v1 do grupo (bootstrap/app.php) tem contador próprio ("v1").
| O login ainda tem um limite menor, também com contador próprio ("v1-login"),
| para não dividir a contagem com o throttle:10,1 do login do site.
*/

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

// Autenticação: troca e-mail e senha por um token
Route::post('/auth/login', [V1\AuthController::class, 'login'])->middleware('throttle:10,1,v1-login')->name('auth.login');

// Rotas que exigem o token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuario', [V1\UsuarioController::class, 'show'])->name('usuario.show');
    Route::put('/usuario', [V1\UsuarioController::class, 'update'])->name('usuario.update');
    Route::patch('/usuario', [V1\UsuarioController::class, 'update'])->name('usuario.patch');
    Route::put('/usuario/senha', [V1\UsuarioController::class, 'updateSenha'])->name('usuario.senha');
    Route::post('/auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
});

/*
| Aplicativo Expo: as mesmas operações com os nomes de rota e de campo que o
| app usa (src/config/api.ts do app). Mesmo envelope e mesmos erros da v1.
*/
Route::post('/login', [V1\AuthController::class, 'loginApp'])->middleware('throttle:10,1,v1-login')->name('app.login');
Route::get('/produto', [V1\ProdutoController::class, 'index'])->name('app.produto');
Route::get('/produto/{livro}', [V1\ProdutoController::class, 'show'])->whereNumber('livro')->name('app.produto.show');
Route::get('/categoria', [V1\CategoriaController::class, 'index'])->name('app.categoria');

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/cliente/senha', [V1\UsuarioController::class, 'updateSenha'])->name('app.cliente.senha');
});
