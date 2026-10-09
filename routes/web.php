<?php

use Illuminate\Support\Facades\Route;

/*
| O front-end é HTML estático em public/ (index.html, catalogo.html, js/, css/,
| assets/...). O servidor web entrega esses arquivos direto; esta rota só cobre
| a raiz "/" quando o servidor manda tudo para o index.php (ex.: php artisan serve).
*/
Route::get('/', fn () => response()->file(public_path('index.html')));
