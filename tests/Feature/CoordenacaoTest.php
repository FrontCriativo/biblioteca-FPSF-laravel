<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CoordenacaoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    /** @var list<string> */
    private array $capasCriadas = [];

    protected function tearDown(): void
    {
        foreach ($this->capasCriadas as $arquivo) {
            File::delete(public_path("assets/{$arquivo}"));
        }
        parent::tearDown();
    }

    private function admin(): Usuario
    {
        return Usuario::firstWhere('email', 'admin@fpsf.edu.br');
    }

    public function test_aluno_nao_entra_na_area_admin(): void
    {
        $this->actingAs(Usuario::firstWhere('papel', 'aluno'));

        $this->getJson('/api/admin/resumo')->assertForbidden()->assertExactJson(['erro' => 'Acesso restrito à coordenação.']);
        $this->postJson('/api/livros', [])->assertForbidden();
        $this->getJson('/api/admin/usuarios')->assertForbidden();
    }

    public function test_visitante_recebe_401_na_area_admin(): void
    {
        $this->getJson('/api/admin/resumo')->assertUnauthorized();
    }

    public function test_resumo(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/api/admin/resumo')
            ->assertExactJson(['livros' => 8, 'exemplares' => 23, 'reservas_ativas' => 0, 'retiradas' => 0, 'usuarios' => 2]);
    }

    public function test_cria_edita_e_remove_livro(): void
    {
        $this->actingAs($this->admin());

        // O front-end manda os campos do formulário como texto.
        $criado = $this->postJson('/api/livros', [
            'titulo' => 'Memórias Póstumas de Brás Cubas',
            'autor' => 'Machado de Assis',
            'materia_slug' => 'literatura',
            'capa' => '',
            'paginas' => '208',
            'exemplares' => '3',
            'sinopse' => '',
        ])->assertCreated()
            ->assertJsonPath('slug', 'memorias-postumas-de-bras-cubas')
            ->assertJsonPath('paginas', 208)
            ->assertJsonPath('idioma', 'Português')
            ->assertJsonPath('capa', null)
            ->assertJsonPath('total_exemplares', 3)
            ->assertJsonPath('disponiveis', 3)
            ->json();

        $this->postJson('/api/livros', ['titulo' => 'Memórias póstumas de Brás Cubas', 'autor' => 'X', 'materia_slug' => 'literatura'])
            ->assertConflict()
            ->assertExactJson(['erro' => 'Já existe um livro com esse título.']);

        $this->putJson("/api/livros/{$criado['id']}", [
            'titulo' => 'Memórias Póstumas de Brás Cubas',
            'autor' => 'Machado de Assis',
            'materia_slug' => 'literatura',
            'paginas' => 210,
        ])->assertOk()->assertJsonPath('paginas', 210);

        $this->postJson("/api/livros/{$criado['id']}/exemplares", ['quantidade' => 2])
            ->assertOk()->assertExactJson(['ok' => true, 'adicionados' => 2]);
        $this->getJson("/api/livros/{$criado['slug']}")->assertJsonPath('total_exemplares', 5);

        $this->deleteJson("/api/livros/{$criado['id']}")->assertOk()->assertExactJson(['ok' => true]);
        $this->assertNull(Livro::find($criado['id']));
    }

    public function test_validacao_do_livro(): void
    {
        $this->actingAs($this->admin());

        $this->postJson('/api/livros', ['autor' => 'Alguém', 'materia_slug' => 'literatura'])
            ->assertUnprocessable()->assertExactJson(['erro' => 'O campo título é obrigatório.']);

        $this->postJson('/api/livros', ['titulo' => 'X', 'autor' => 'Y', 'materia_slug' => 'astrologia'])
            ->assertUnprocessable()->assertExactJson(['erro' => 'O valor do campo matéria não existe.']);

        $this->postJson('/api/livros', ['titulo' => 'X', 'autor' => 'Y', 'materia_slug' => 'artes', 'paginas' => 'abc'])
            ->assertUnprocessable()->assertExactJson(['erro' => 'O campo páginas deve ser um número inteiro positivo.']);

        $this->postJson('/api/livros', ['titulo' => 'X', 'autor' => 'Y', 'materia_slug' => 'artes', 'capa' => '../../.env'])
            ->assertUnprocessable();
    }

    public function test_nao_remove_livro_com_reserva_aberta(): void
    {
        $livro = Livro::firstWhere('slug', 'vidas-secas');
        $this->actingAs(Usuario::firstWhere('papel', 'aluno'))->postJson('/api/reservas', ['livro_id' => $livro->id]);

        $this->actingAs($this->admin())
            ->deleteJson("/api/livros/{$livro->id}")
            ->assertConflict()
            ->assertExactJson(['erro' => 'Este livro tem reservas ativas e não pode ser removido.']);
    }

    public function test_upload_de_capa(): void
    {
        $this->actingAs($this->admin());
        $png = "\x89PNG\r\n\x1A\n".str_repeat("\0", 32);

        $arquivo = $this->postJson('/api/admin/capas', ['nome' => 'Minha Capa Nova.png', 'dados' => base64_encode($png)])
            ->assertCreated()
            ->json('arquivo');
        $this->capasCriadas[] = $arquivo;
        $this->assertSame('minha-capa-nova.png', $arquivo);
        $this->assertFileExists(public_path("assets/{$arquivo}"));

        // Mesmo nome: ganha sufixo.
        $segundo = $this->postJson('/api/admin/capas', ['nome' => 'Minha Capa Nova.png', 'dados' => base64_encode($png)])->json('arquivo');
        $this->capasCriadas[] = $segundo;
        $this->assertSame('minha-capa-nova-2.png', $segundo);

        // Não é imagem.
        $this->postJson('/api/admin/capas', ['nome' => 'x.png', 'dados' => base64_encode('<?php echo 1;')])
            ->assertStatus(400)
            ->assertExactJson(['erro' => 'Envie uma imagem JPG, PNG ou WEBP.']);
    }

    public function test_lista_e_cadastra_usuarios(): void
    {
        $this->actingAs($this->admin());

        $this->getJson('/api/admin/usuarios')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonStructure([['id', 'nome', 'email', 'papel', 'turma', 'criado_em', 'reservas_ativas']]);

        $this->postJson('/api/admin/usuarios', [
            'nome' => 'Carla Dias', 'email' => 'Carla.Dias@fpsf.edu.br', 'senha' => 'segredo1',
            'turma' => '7º ano C', 'papel' => 'qualquer',
        ])->assertCreated()->assertJson([
            'nome' => 'Carla Dias', 'email' => 'carla.dias@fpsf.edu.br', 'papel' => 'aluno', 'turma' => '7º ano C',
        ])->assertJsonMissingPath('senha');

        $this->postJson('/api/admin/usuarios', ['nome' => 'Outra', 'email' => 'carla.dias@fpsf.edu.br', 'senha' => 'segredo1'])
            ->assertUnprocessable()->assertExactJson(['erro' => 'Já existe um usuário com esse e-mail.']);

        $this->postJson('/api/admin/usuarios', ['nome' => 'Outra', 'email' => 'outra@fpsf.edu.br', 'senha' => '123'])
            ->assertUnprocessable()->assertExactJson(['erro' => 'A senha precisa ter ao menos 6 caracteres.']);

        // A nova aluna consegue entrar.
        auth()->guard('web')->logout();
        $this->postJson('/api/login', ['email' => 'carla.dias@fpsf.edu.br', 'senha' => 'segredo1'])->assertOk();
    }
}
