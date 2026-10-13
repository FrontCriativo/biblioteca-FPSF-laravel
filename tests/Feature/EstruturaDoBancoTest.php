<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstruturaDoBancoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_novas_tabelas_e_colunas_existem(): void
    {
        $this->assertTrue(Schema::hasColumns('turmas', ['id', 'nome', 'ano_letivo', 'ativa', 'criada_em', 'atualizada_em']));
        $this->assertTrue(Schema::hasColumns('usuarios', ['turma', 'turma_id']));
        $this->assertTrue(Schema::hasColumns('favoritos', ['id', 'usuario_id', 'livro_id', 'criado_em']));
        $this->assertTrue(Schema::hasColumns('depoimentos', [
            'id', 'usuario_id', 'nome', 'descricao_autor', 'texto', 'nota', 'status', 'criado_em', 'atualizado_em',
        ]));
        $this->assertTrue(Schema::hasColumns('mensagens_contato', [
            'id', 'usuario_id', 'nome', 'turma', 'email', 'assunto', 'mensagem', 'status', 'criada_em', 'atualizada_em',
        ]));
        $this->assertTrue(Schema::hasColumns('reservas', ['retirada_em', 'devolver_ate', 'renovacoes', 'observacao']));
    }

    public function test_alterar_reservas_mantem_indice_parcial(): void
    {
        $sql = DB::scalar("SELECT sql FROM sqlite_master WHERE name = 'reservas_exemplar_aberta_unique'");

        $this->assertStringContainsString("WHERE status IN ('ativa', 'retirada')", $sql);
    }

    public function test_seeder_liga_alunos_as_turmas_e_cria_depoimentos_aprovados(): void
    {
        $beatriz = Usuario::firstWhere('email', 'beatriz.lima@fpsf.edu.br');

        $this->assertSame('8º ano B', DB::table('turmas')->where('id', $beatriz->turma_id)->value('nome'));
        $this->assertSame(6, DB::table('depoimentos')->where('status', 'aprovado')->count());
        $this->assertSame($beatriz->id, DB::table('depoimentos')->where('nome', 'Beatriz Lima')->value('usuario_id'));
    }

    public function test_mesmo_livro_nao_pode_ser_favoritado_duas_vezes(): void
    {
        $favorito = ['usuario_id' => Usuario::firstWhere('papel', 'aluno')->id, 'livro_id' => DB::table('livros')->value('id')];
        DB::table('favoritos')->insert($favorito);

        $this->expectException(QueryException::class);
        DB::table('favoritos')->insert($favorito);
    }
}
