<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Última etapa da troca do texto livre usuarios.turma por usuarios.turma_id.
 * Os dados já foram copiados para a tabela turmas pela migration
 * 2026_10_13_000002_add_turma_id_to_usuarios_table, e o código (Usuario,
 * UsuarioResource, ReservaResource, Admin\UsuarioController) já usa turma_id.
 *
 * O down() recria a coluna e devolve a ela o nome da turma de cada usuário.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('turma');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('turma', 40)->nullable()->after('papel');
        });

        DB::table('usuarios')
            ->whereNotNull('turma_id')
            ->update(['turma' => DB::raw('(select nome from turmas where turmas.id = usuarios.turma_id)')]);
    }
};
