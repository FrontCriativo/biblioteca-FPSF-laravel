<?php

use App\Services\ServicoDeReservas;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('reservas:expirar', function (ServicoDeReservas $reservas) {
    $total = $reservas->expirarVencidas();
    $this->info("{$total} reserva(s) vencida(s) cancelada(s).");
})->purpose('Cancela as pré-reservas que passaram do prazo de retirada');

// Opcional: com o cron do Laravel ativo (php artisan schedule:run a cada minuto),
// as reservas vencidas também são canceladas sem depender de alguém usar o site.
Schedule::command('reservas:expirar')->everyFifteenMinutes();

// Apaga da tabela personal_access_tokens os tokens da API v1 vencidos há mais de 24 h
// (a validade é o 'expiration' de config/sanctum.php).
Schedule::command('sanctum:prune-expired --hours=24')->daily();
