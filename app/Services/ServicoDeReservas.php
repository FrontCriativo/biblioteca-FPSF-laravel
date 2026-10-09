<?php

namespace App\Services;

use App\Models\Livro;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Regras de negócio das reservas: criar, mudar de status e expirar.
 */
class ServicoDeReservas
{
    /** Prazo para o aluno retirar o livro depois de reservar. */
    public const DIAS_PARA_RETIRAR = 3;

    public function reservar(Livro $livro, Usuario $usuario): Reserva
    {
        try {
            $reserva = DB::transaction(function () use ($livro, $usuario) {
                $jaReservado = Reserva::query()->abertas()
                    ->where('usuario_id', $usuario->id)
                    ->whereHas('exemplar', fn (Builder $q) => $q->where('livro_id', $livro->id))
                    ->exists();

                if ($jaReservado) {
                    abort(409, 'Você já tem uma reserva ativa deste livro.');
                }

                $exemplar = $livro->exemplares()->disponiveis()->lockForUpdate()->first();

                if (! $exemplar) {
                    abort(409, 'Não há exemplares disponíveis no momento.');
                }

                return Reserva::create([
                    'exemplar_id' => $exemplar->id,
                    'usuario_id' => $usuario->id,
                    'status' => 'ativa',
                    'expira_em' => now()->addDays(self::DIAS_PARA_RETIRAR),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Duas pessoas pegaram o mesmo exemplar ao mesmo tempo; o índice
            // único do banco barrou a segunda.
            abort(409, 'Este exemplar acabou de ser reservado. Tente novamente.');
        }

        return $reserva->load(['exemplar.livro', 'usuario']);
    }

    public function mudarStatus(Reserva $reserva, string $novoStatus): Reserva
    {
        $permitidos = Reserva::TRANSICOES[$novoStatus] ?? abort(400, 'Status inválido.');

        if (! in_array($reserva->status, $permitidos, true)) {
            abort(409, sprintf('Não é possível mudar de "%s" para "%s".', $reserva->status, $novoStatus));
        }

        $reserva->update([
            'status' => $novoStatus,
            'encerrada_em' => $novoStatus === 'retirada' ? null : now(),
        ]);

        return $reserva->load(['exemplar.livro', 'usuario']);
    }

    /** Cancela as reservas que passaram do prazo de retirada. */
    public function expirarVencidas(): int
    {
        return Reserva::query()
            ->where('status', 'ativa')
            ->where('expira_em', '<', now())
            ->update(['status' => 'cancelada', 'encerrada_em' => now()]);
    }
}
