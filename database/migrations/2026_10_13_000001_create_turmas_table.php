<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Turmas da escola ("8º ano B"). Substitui aos poucos o texto livre em
 * usuarios.turma, que permitia grafias diferentes para a mesma turma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 40)->unique();
            $table->unsignedSmallInteger('ano_letivo');
            $table->boolean('ativa')->default(true);
            $table->dateTime('criada_em')->useCurrent();
            $table->dateTime('atualizada_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
