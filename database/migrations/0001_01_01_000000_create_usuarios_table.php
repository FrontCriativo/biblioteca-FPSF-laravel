<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            // Guardado sempre em minúsculas (ver App\Models\Usuario), o que deixa
            // o UNIQUE insensível a maiúsculas em qualquer banco.
            $table->string('email')->unique();
            $table->string('senha');
            $table->string('papel', 10)->default('aluno'); // 'aluno' | 'admin'
            $table->string('turma', 40)->nullable();
            $table->dateTime('criado_em')->useCurrent();
        });

        // Sessões de login do Laravel (substitui a antiga tabela `sessoes`).
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('usuarios');
    }
};
