<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Empréstimo: registra quando o livro foi retirado e até quando deve voltar.
 *
 * Regra de negócio (aplicada no código, não no banco): quando status = retirada,
 * a reserva precisa ter devolver_ate. O encerrada_em continua marcando a
 * devolução e o cancelamento.
 *
 * No SQLite só há ADD COLUMN, CREATE INDEX e DROP COLUMN (3.35+), sem recriar a
 * tabela, então o índice parcial reservas_exemplar_aberta_unique e as chaves
 * estrangeiras com cascade ficam intactos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dateTime('retirada_em')->nullable();
            $table->date('devolver_ate')->nullable();
            $table->unsignedTinyInteger('renovacoes')->default(0);
            $table->text('observacao')->nullable(); // uso da coordenação

            // Lista de atrasos: status = retirada e devolver_ate < hoje.
            $table->index(['status', 'devolver_ate']);
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['status', 'devolver_ate']);
            $table->dropColumn(['retirada_em', 'devolver_ate', 'renovacoes', 'observacao']);
        });
    }
};
