<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Mensagens do formulário de public/contato.html.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagens_contato', function (Blueprint $table) {
            $table->id();
            // Preenchido quando quem escreve está logado.
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nome', 80);
            $table->string('turma', 40)->nullable(); // turma ou função, texto do formulário
            $table->string('email', 120);
            // Sugestão de livro | Dúvida sobre empréstimo | Problema de acesso | Outro assunto
            $table->string('assunto', 30);
            $table->text('mensagem');
            $table->string('status', 12)->default('nova'); // nova | lida | respondida
            $table->dateTime('criada_em')->useCurrent();
            $table->dateTime('atualizada_em')->useCurrent();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens_contato');
    }
};
