<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Depoimentos enviados pela página public/depoimentos.html. Só aparecem no
 * site depois de aprovados pela coordenação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depoimentos', function (Blueprint $table) {
            $table->id();
            // Opcional: o site também mostra depoimentos de professores e
            // responsáveis, que não têm login.
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nome', 80);
            $table->string('descricao_autor', 60)->nullable(); // "Aluna do 8º ano", "Professor de Ciências"
            $table->text('texto');
            $table->unsignedTinyInteger('nota')->nullable(); // 1 a 5 estrelas; NULL = sem estrelas
            $table->string('status', 10)->default('pendente'); // pendente | aprovado | recusado
            $table->dateTime('criado_em')->useCurrent();
            $table->dateTime('atualizado_em')->useCurrent();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depoimentos');
    }
};
