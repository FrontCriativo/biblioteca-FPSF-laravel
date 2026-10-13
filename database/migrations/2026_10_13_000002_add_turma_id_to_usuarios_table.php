<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Liga cada usuário a uma turma e migra os dados do texto livre usuarios.turma:
 * uma turma para cada valor distinto, com o ano letivo corrente.
 *
 * A coluna usuarios.turma continua existindo, porque UsuarioResource,
 * ReservaResource e NovoUsuarioRequest ainda a usam. Ela sai numa migration
 * futura, depois de o código passar a usar turma_id.
 *
 * No SQLite, adicionar a chave estrangeira faz o Laravel recriar a tabela
 * usuarios. Isso é seguro porque o SQLite não roda migrations dentro de
 * transação, então o PRAGMA foreign_keys = off que o Laravel envia vale e o
 * DROP da tabela antiga não apaga as reservas em cascata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('turma_id')->nullable()->after('turma')
                ->constrained('turmas')->nullOnDelete();
        });

        DB::transaction(function () {
            foreach ($this->turmasEmTexto() as $nome) {
                DB::table('turmas')->insertOrIgnore(['nome' => $nome, 'ano_letivo' => now()->year]);

                DB::table('usuarios')
                    ->whereRaw('trim(turma) = ?', [$nome])
                    ->update(['turma_id' => DB::table('turmas')->where('nome', $nome)->value('id')]);
            }
        });
    }

    public function down(): void
    {
        $nomes = $this->turmasEmTexto();

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('turma_id');
        });

        DB::table('turmas')->whereIn('nome', $nomes)->delete();
    }

    /** Valores distintos de usuarios.turma, sem espaços nas pontas. */
    private function turmasEmTexto(): Collection
    {
        return DB::table('usuarios')
            ->selectRaw('distinct trim(turma) as nome')
            ->whereRaw("trim(coalesce(turma, '')) <> ''")
            ->orderBy('nome')
            ->pluck('nome');
    }
};
