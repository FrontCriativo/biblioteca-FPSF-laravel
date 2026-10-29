<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Rotas da v1 no formato do aplicativo Expo (login, produto, categoria, cliente/senha). */
class ApiV1AppTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const BEATRIZ = 'beatriz.lima@fpsf.edu.br';

    public function test_login_recebe_password_e_devolve_token_no_topo(): void
    {
        $token = $this->postJson('/api/v1/login', ['email' => self::BEATRIZ, 'password' => 'aluno123'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('usuario.email', self::BEATRIZ)
            ->json('token');

        $this->assertMatchesRegularExpression('/^\d+\|\w{40,}$/', $token);

        $this->getJson('/api/v1/usuario', ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('data.email', self::BEATRIZ);
    }

    public function test_login_com_senha_errada_ou_sem_password(): void
    {
        $this->postJson('/api/v1/login', ['email' => self::BEATRIZ, 'password' => 'errada'])
            ->assertUnauthorized()
            ->assertExactJson(['success' => false, 'erro' => 'E-mail ou senha incorretos.']);

        $this->postJson('/api/v1/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_produto_lista_todos_os_livros_no_formato_do_app(): void
    {
        $resposta = $this->getJson('/api/v1/produto')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(Livro::count(), 'data');

        $livro = collect($resposta->json('data'))->firstWhere('slug', 'dom-quixote');
        $this->assertSame('Dom Quixote', $livro['titulo']);
        $this->assertSame('Miguel de Cervantes', $livro['autor']);
        $this->assertSame(['id_categoria' => 'literatura', 'nome_categoria' => 'Literatura'], $livro['categoria_produto']);
        $this->assertSame('Literatura', $livro['genero']);
        $this->assertSame('domQuixote.jpg', $livro['imagem']);
        $this->assertSame('disponivel', $livro['status']);
        $this->assertSame('ATIVO', $livro['status_produto']);

        foreach ($resposta->json('data') as $produto) {
            $this->assertContains($produto['status'], ['disponivel', 'emprestado']);
            $this->assertContains($produto['livrosDestaque'], ['SIM', 'NAO']);
        }

        // Destaques: os 6 livros cadastrados por último.
        $destaques = collect($resposta->json('data'))->where('livrosDestaque', 'SIM')->pluck('id')->sort()->values();
        $this->assertEquals(Livro::orderByDesc('id')->limit(6)->pluck('id')->sort()->values(), $destaques);
    }

    public function test_produto_sem_exemplar_livre_sai_como_emprestado(): void
    {
        $livro = Livro::firstWhere('slug', 'dom-quixote');
        $livro->exemplares()->update(['estado' => 'manutencao']);

        $this->getJson("/api/v1/produto/{$livro->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $livro->id)
            ->assertJsonPath('data.status', 'emprestado')
            ->assertJsonPath('data.disponiveis', 0);
    }

    public function test_produto_inexistente_responde_404(): void
    {
        $this->getJson('/api/v1/produto/999999')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'erro' => 'Livro não encontrado.']);
    }

    public function test_categoria_lista_as_materias_em_ordem(): void
    {
        $resposta = $this->getJson('/api/v1/categoria')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(Materia::count(), 'data')
            ->assertJsonPath('data.0.ordem_categoria', 1)
            ->assertJsonFragment([
                'id_categoria' => 'literatura', 'nome_categoria' => 'Literatura', 'status_categoria' => 'ATIVO',
            ]);

        $nomes = collect($resposta->json('data'))->pluck('nome_categoria')->all();
        $this->assertSame(Materia::orderBy('nome')->pluck('nome')->all(), $nomes);
    }

    public function test_cliente_senha_exige_token_e_troca_a_senha(): void
    {
        $dados = ['senha_atual' => 'aluno123', 'nova_senha' => 'novaSenha123', 'nova_senha_confirmation' => 'novaSenha123'];

        $this->putJson('/api/v1/cliente/senha', $dados)->assertUnauthorized();

        $token = $this->postJson('/api/v1/login', ['email' => self::BEATRIZ, 'password' => 'aluno123'])->json('token');

        $this->putJson('/api/v1/cliente/senha', $dados, ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('novaSenha123', Usuario::firstWhere('email', self::BEATRIZ)->senha));
    }
}
