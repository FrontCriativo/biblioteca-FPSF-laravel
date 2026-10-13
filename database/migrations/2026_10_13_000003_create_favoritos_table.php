<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Lista "Quero ler": livros que o aluno marcou para encontrar depois.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('livro_id')->constrained('livros')->cascadeOnDelete();
            $table->dateTime('criado_em')->useCurrent();

            // O mesmo livro não pode ser favoritado duas vezes pelo mesmo aluno.
            $table->unique(['usuario_id', 'livro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');
    }
};
