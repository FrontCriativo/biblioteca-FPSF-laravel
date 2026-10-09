<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Acervo da biblioteca.
 *
 * Um livro (obra) tem N exemplares (cópias físicas na prateleira).
 * Disponibilidade é a contagem de exemplares sem reserva aberta,
 * e não um booleano no próprio livro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->string('slug', 60)->primary();
            $table->string('nome');
            $table->string('icone');
        });

        Schema::create('livros', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->string('autor');
            $table->string('materia_slug', 60)->index();
            $table->string('capa')->nullable();
            $table->text('sinopse')->nullable();
            $table->unsignedInteger('paginas')->nullable();
            $table->string('idioma', 60)->default('Português');
            $table->dateTime('criado_em')->useCurrent();

            $table->foreign('materia_slug')->references('slug')->on('materias');
        });

        Schema::create('exemplares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            $table->string('codigo')->unique();
            $table->string('estado', 20)->default('ok'); // 'ok' | 'manutencao'
        });

        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exemplar_id')->constrained('exemplares')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('status', 20)->default('ativa'); // ativa | retirada | devolvida | cancelada
            $table->dateTime('criada_em')->useCurrent();
            $table->dateTime('expira_em');
            $table->dateTime('encerrada_em')->nullable();

            $table->index(['status', 'expira_em']);
        });

        // Invariante central: um exemplar só pode ter UMA reserva aberta.
        // No SQLite e no PostgreSQL um índice único parcial faz o próprio banco
        // recusar reserva dupla, mesmo sob requisições simultâneas. O MySQL não
        // tem índice parcial; lá a garantia vem do lockForUpdate() na transação
        // (ver App\Services\ServicoDeReservas).
        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement(
                "CREATE UNIQUE INDEX reservas_exemplar_aberta_unique
                     ON reservas (exemplar_id)
                  WHERE status IN ('ativa', 'retirada')"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
        Schema::dropIfExists('exemplares');
        Schema::dropIfExists('livros');
        Schema::dropIfExists('materias');
    }
};
