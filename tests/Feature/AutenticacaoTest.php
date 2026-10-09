<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_eu_devolve_null_sem_login(): void
    {
        $resposta = $this->getJson('/api/eu')->assertOk();
        $this->assertSame('null', $resposta->getContent());
    }

    public function test_login_com_senha_correta(): void
    {
        $this->postJson('/api/login', ['email' => 'Beatriz.Lima@fpsf.edu.br', 'senha' => 'aluno123'])
            ->assertOk()
            ->assertExactJson([
                'id' => 2, 'nome' => 'Beatriz Lima', 'email' => 'beatriz.lima@fpsf.edu.br',
                'papel' => 'aluno', 'turma' => '8º ano B',
            ]);

        $this->assertAuthenticated();
        $this->getJson('/api/eu')->assertJsonPath('email', 'beatriz.lima@fpsf.edu.br');
    }

    public function test_login_com_senha_errada(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@fpsf.edu.br', 'senha' => 'errada'])
            ->assertUnauthorized()
            ->assertExactJson(['erro' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_login_sem_campos(): void
    {
        $this->postJson('/api/login', ['email' => 'admin@fpsf.edu.br'])
            ->assertUnprocessable()
            ->assertExactJson(['erro' => 'O campo senha é obrigatório.']);
    }

    public function test_logout(): void
    {
        $this->actingAs(Usuario::firstWhere('email', 'admin@fpsf.edu.br'));

        $this->postJson('/api/logout')->assertOk()->assertExactJson(['ok' => true]);
        $this->assertGuest('web');
    }
}
