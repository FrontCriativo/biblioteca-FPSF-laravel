<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Conta ativa ou desativada pela coordenação. Conta desativada não consegue
 * entrar no site nem gerar token na API v1. Quem já existe fica ativo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('ativo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('ativo');
        });
    }
};
