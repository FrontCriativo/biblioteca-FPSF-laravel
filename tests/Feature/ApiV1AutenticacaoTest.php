<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Models\Usuario;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private const BEATRIZ = 'beatriz.lima@fpsf.edu.br';

    /** Faz login na v1 e devolve o token em texto. */
    private function entrar(string $email = self::BEATRIZ, string $senha = 'aluno123', ?string $dispositivo = 'testes'): string
    {
        $token = $this->postJson('/api/v1/auth/login', array_filter([
            'email' => $email, 'senha' => $senha, 'device_name' => $dispositivo,
        ]))->assertOk()->json('data.token');

        $this->novaRequisicao();

        return $token;
    }

    /** @return array<string, string> */
    private function comToken(string $token): array
    {
        return ['Authorization' => "Bearer {$token}"];
    }

    /** O Sanctum guarda o usuário do token entre requisições do mesmo teste; isto o esquece. */
    private function novaRequisicao(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function beatriz(): Usuario
    {
        return Usuario::firstWhere('email', self::BEATRIZ);
    }

    public function test_login_devolve_token_e_usuario(): void
    {
        $resposta = $this->postJson('/api/v1/auth/login', [
            'email' => '  Beatriz.Lima@FPSF.edu.br ', 'senha' => 'aluno123', 'device_name' => 'celular',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('mensagem', 'Login realizado com sucesso.')
            ->assertJsonPath('data.usuario', [
                'id' => 2, 'nome' => 'Beatriz Lima', 'email' => self::BEATRIZ, 'papel' => 'aluno', 'turma' => '8º ano B',
            ]);

        $token = $resposta->json('data.token');
        $this->assertMatchesRegularExpression('/^\d+\|\w{40,}$/', $token);
        $this->assertStringNotContainsString('senha', $resposta->getContent());

        // O banco guarda só o hash SHA-256 do token, nunca o texto.
        $registro = DB::table('personal_access_tokens')->sole();
        $this->assertSame('celular', $registro->name);
        $this->assertSame(hash('sha256', explode('|', $token)[1]), $registro->token);
    }

    public function test_novo_login_no_mesmo_dispositivo_substitui_o_token(): void
    {
        $primeiro = $this->entrar(dispositivo: 'celular');
        $segundo = $this->entrar(dispositivo: 'celular');
        $this->entrar(dispositivo: 'tablet');
        $this->entrar(dispositivo: null); // sem device_name = "app"

        $this->assertSame(['app', 'celular', 'tablet'], $this->beatriz()->tokens()->orderBy('name')->pluck('name')->all());

        $this->getJson('/api/v1/usuario', $this->comToken($primeiro))->assertUnauthorized();
        $this->novaRequisicao();
        $this->getJson('/api/v1/usuario', $this->comToken($segundo))->assertOk();
    }

    public function test_senha_errada_e_email_inexistente_tem_a_mesma_resposta(): void
    {
        $esperado = ['success' => false, 'erro' => 'E-mail ou senha incorretos.'];

        $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ, 'senha' => 'errada'])
            ->assertUnauthorized()->assertExactJson($esperado);
        $this->postJson('/api/v1/auth/login', ['email' => 'ninguem@fpsf.edu.br', 'senha' => 'aluno123'])
            ->assertUnauthorized()->assertExactJson($esperado);

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_validacao_do_login(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ])
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'O campo senha é obrigatório.']);

        $this->postJson('/api/v1/auth/login', ['email' => 'nao-e-email', 'senha' => 'x'])
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'Informe um e-mail válido.']);
    }

    public function test_conta_desativada_nao_recebe_token(): void
    {
        $this->beatriz()->update(['ativo' => false]);

        $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])
            ->assertForbidden()
            ->assertExactJson(['success' => false, 'erro' => 'Conta desativada. Procure a coordenação.']);

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_perfil_exige_token_valido(): void
    {
        $semLogin = ['success' => false, 'erro' => 'Você precisa entrar para fazer isso.'];

        $this->getJson('/api/v1/usuario')->assertUnauthorized()->assertExactJson($semLogin);
        $this->getJson('/api/v1/usuario', $this->comToken('1|tokenquenaoexiste'))->assertUnauthorized()->assertExactJson($semLogin);
        $this->putJson('/api/v1/usuario', ['nome' => 'X'])->assertUnauthorized();
        $this->putJson('/api/v1/usuario/senha')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();

        $token = $this->entrar();
        $this->getJson('/api/v1/usuario', $this->comToken($token))
            ->assertOk()
            ->assertExactJson(['success' => true, 'data' => [
                'id' => 2, 'nome' => 'Beatriz Lima', 'email' => self::BEATRIZ, 'papel' => 'aluno', 'turma' => '8º ano B',
            ]]);
    }

    public function test_token_vence_em_7_dias(): void
    {
        $token = $this->entrar();

        $this->travel(7)->days();
        $this->travel(1)->minutes();

        $this->getJson('/api/v1/usuario', $this->comToken($token))->assertUnauthorized();
    }

    public function test_put_atualiza_so_nome_e_email(): void
    {
        $token = $this->entrar();
        $senhaAntes = $this->beatriz()->senha;

        $this->putJson('/api/v1/usuario', [
            'nome' => 'Beatriz Lima Souza',
            'email' => '  Bia.Lima@FPSF.edu.br ',
            'papel' => 'admin',
            'turma_id' => 2,
            'ativo' => false,
            'senha' => 'hackeada',
        ], $this->comToken($token))
            ->assertOk()
            ->assertJsonPath('mensagem', 'Perfil atualizado com sucesso.')
            ->assertJsonPath('data.nome', 'Beatriz Lima Souza')
            ->assertJsonPath('data.email', 'bia.lima@fpsf.edu.br')
            ->assertJsonPath('data.papel', 'aluno')
            ->assertJsonPath('data.turma', '8º ano B');

        $beatriz = Usuario::find(2);
        $this->assertSame('aluno', $beatriz->papel);
        $this->assertSame(1, $beatriz->turma_id);
        $this->assertTrue($beatriz->ativo);
        $this->assertSame($senhaAntes, $beatriz->senha);
    }

    public function test_put_exige_nome_e_email(): void
    {
        $token = $this->entrar();

        $this->putJson('/api/v1/usuario', ['nome' => 'Só o nome'], $this->comToken($token))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'O campo e-mail é obrigatório.']);

        $this->assertSame('Beatriz Lima', $this->beatriz()->nome);
    }

    public function test_patch_e_parcial(): void
    {
        $token = $this->entrar();

        $this->patchJson('/api/v1/usuario', ['nome' => 'Bia'], $this->comToken($token))
            ->assertOk()->assertJsonPath('data.nome', 'Bia')->assertJsonPath('data.email', self::BEATRIZ);

        $this->novaRequisicao();
        $this->patchJson('/api/v1/usuario', ['email' => 'bia@fpsf.edu.br'], $this->comToken($token))
            ->assertOk()->assertJsonPath('data.nome', 'Bia')->assertJsonPath('data.email', 'bia@fpsf.edu.br');

        // Campo enviado vazio não passa.
        $this->novaRequisicao();
        $this->patchJson('/api/v1/usuario', ['nome' => ''], $this->comToken($token))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'O campo nome é obrigatório.']);

        // Só papel no corpo: nada muda.
        $this->novaRequisicao();
        $this->patchJson('/api/v1/usuario', ['papel' => 'admin'], $this->comToken($token))
            ->assertOk()->assertJsonPath('data.papel', 'aluno');
    }

    public function test_email_unico_ignorando_o_proprio(): void
    {
        $token = $this->entrar();

        $this->patchJson('/api/v1/usuario', ['email' => 'RAFAEL.SOUZA@fpsf.edu.br'], $this->comToken($token))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'Já existe um usuário com esse e-mail.']);

        $this->novaRequisicao();
        $this->putJson('/api/v1/usuario', ['nome' => 'Beatriz Lima', 'email' => self::BEATRIZ], $this->comToken($token))
            ->assertOk();
    }

    public function test_troca_de_senha(): void
    {
        $atual = $this->entrar(dispositivo: 'celular');
        $outro = $this->entrar(dispositivo: 'computador');

        $this->putJson('/api/v1/usuario/senha', [
            'senha_atual' => 'errada', 'nova_senha' => 'novaSenha1', 'nova_senha_confirmation' => 'novaSenha1',
        ], $this->comToken($atual))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'Senha atual incorreta.']);

        $this->novaRequisicao();
        $this->putJson('/api/v1/usuario/senha', [
            'senha_atual' => 'aluno123', 'nova_senha' => 'novaSenha1', 'nova_senha_confirmation' => 'outraCoisa',
        ], $this->comToken($atual))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'A confirmação do campo nova senha não confere.']);

        $this->novaRequisicao();
        $this->putJson('/api/v1/usuario/senha', [
            'senha_atual' => 'aluno123', 'nova_senha' => 'curta', 'nova_senha_confirmation' => 'curta',
        ], $this->comToken($atual))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'A nova senha precisa ter ao menos 8 caracteres.']);

        $this->novaRequisicao();
        $this->putJson('/api/v1/usuario/senha', [
            'senha_atual' => 'aluno123', 'nova_senha' => 'aluno123', 'nova_senha_confirmation' => 'aluno123',
        ], $this->comToken($atual))
            ->assertUnprocessable()->assertExactJson(['success' => false, 'erro' => 'A nova senha precisa ser diferente da senha atual.']);

        $this->novaRequisicao();
        $this->putJson('/api/v1/usuario/senha', [
            'senha_atual' => 'aluno123', 'nova_senha' => 'novaSenha1', 'nova_senha_confirmation' => 'novaSenha1',
        ], $this->comToken($atual))
            ->assertOk()->assertExactJson(['success' => true, 'mensagem' => 'Senha alterada com sucesso.']);

        $hash = $this->beatriz()->senha;
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(Hash::check('novaSenha1', $hash));

        // O token de outro aparelho foi revogado; o usado na troca continua valendo.
        $this->novaRequisicao();
        $this->getJson('/api/v1/usuario', $this->comToken($outro))->assertUnauthorized();
        $this->novaRequisicao();
        $this->getJson('/api/v1/usuario', $this->comToken($atual))->assertOk();

        // Senha antiga não entra mais; a nova entra.
        $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])->assertUnauthorized();
        $this->entrar(senha: 'novaSenha1');
    }

    public function test_logout_invalida_o_token(): void
    {
        $token = $this->entrar(dispositivo: 'celular');
        $outro = $this->entrar(dispositivo: 'computador');

        $this->postJson('/api/v1/auth/logout', [], $this->comToken($token))
            ->assertOk()->assertExactJson(['success' => true, 'mensagem' => 'Logout realizado com sucesso.']);

        $this->novaRequisicao();
        $this->getJson('/api/v1/usuario', $this->comToken($token))->assertUnauthorized();

        // Só o token do logout sai.
        $this->novaRequisicao();
        $this->getJson('/api/v1/usuario', $this->comToken($outro))->assertOk();
    }

    public function test_cookie_de_sessao_do_site_nao_autentica_na_v1(): void
    {
        $this->postJson('/api/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])->assertOk();
        $this->assertAuthenticated('web');

        $this->getJson('/api/v1/usuario')
            ->assertUnauthorized()
            ->assertExactJson(['success' => false, 'erro' => 'Você precisa entrar para fazer isso.']);
    }

    public function test_login_do_site_continua_exigindo_csrf_e_a_v1_nao(): void
    {
        // Nos testes o Laravel desliga o CSRF; aqui ele volta a valer.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $this->postJson('/api/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])
            ->assertStatus(419)
            ->assertExactJson(['erro' => 'Sua sessão expirou. Recarregue a página e tente novamente.']);
        $this->assertGuest('web');

        $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])->assertOk();
    }

    public function test_login_do_site_recusa_conta_desativada(): void
    {
        $this->beatriz()->update(['ativo' => false]);

        $this->postJson('/api/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])
            ->assertForbidden()
            ->assertExactJson(['erro' => 'Conta desativada. Procure a coordenação.']);
        $this->assertGuest('web');

        // Senha errada continua 401, mesmo com a conta desativada.
        $this->postJson('/api/login', ['email' => self::BEATRIZ, 'senha' => 'errada'])
            ->assertUnauthorized()->assertExactJson(['erro' => 'E-mail ou senha incorretos.']);
    }

    public function test_api_do_site_continua_devolvendo_turma_como_texto(): void
    {
        $this->actingAs($this->beatriz())
            ->getJson('/api/eu')
            ->assertExactJson(['id' => 2, 'nome' => 'Beatriz Lima', 'email' => self::BEATRIZ, 'papel' => 'aluno', 'turma' => '8º ano B']);

        $this->postJson('/api/reservas', ['livro_id' => Livro::firstWhere('slug', 'vidas-secas')->id])
            ->assertCreated()->assertJsonPath('turma', '8º ano B');

        $this->actingAs(Usuario::firstWhere('papel', 'admin'));
        $this->getJson('/api/admin/reservas')->assertOk()->assertJsonPath('0.turma', '8º ano B');

        $turmas = collect($this->getJson('/api/admin/usuarios')->assertOk()->json())->pluck('turma', 'email');
        $this->assertSame(['admin@fpsf.edu.br' => null, self::BEATRIZ => '8º ano B', 'rafael.souza@fpsf.edu.br' => '9º ano A'], $turmas->sortKeys()->all());
    }

    public function test_cadastro_pelo_painel_reaproveita_a_turma_existente(): void
    {
        $this->actingAs(Usuario::firstWhere('papel', 'admin'));
        $turmasAntes = DB::table('turmas')->count();

        $this->postJson('/api/admin/usuarios', ['nome' => 'Ana', 'email' => 'ana@fpsf.edu.br', 'senha' => 'segredo1', 'turma' => ' 8º ano B '])
            ->assertCreated()->assertJsonPath('turma', '8º ano B');
        $this->postJson('/api/admin/usuarios', ['nome' => 'Bruno', 'email' => 'bruno@fpsf.edu.br', 'senha' => 'segredo1'])
            ->assertCreated()->assertJsonPath('turma', null);

        $this->assertSame($turmasAntes, DB::table('turmas')->count());
        $this->assertSame($this->beatriz()->turma_id, Usuario::firstWhere('email', 'ana@fpsf.edu.br')->turma_id);
    }

    public function test_documentacao_explica_a_autenticacao(): void
    {
        $this->get('/api/documentacao')
            ->assertOk()
            ->assertSee('id="autenticacao"', false)
            ->assertSee('/api/v1/auth/login')
            ->assertSee('Authorization: Bearer SEU_TOKEN');
    }

    public function test_limite_de_10_logins_por_minuto(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ, 'senha' => 'errada'])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])
            ->assertStatus(429)
            ->assertExactJson(['success' => false, 'erro' => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);

        // O contador da v1 é separado do login do site.
        $this->postJson('/api/login', ['email' => self::BEATRIZ, 'senha' => 'aluno123'])->assertOk();
    }
}
