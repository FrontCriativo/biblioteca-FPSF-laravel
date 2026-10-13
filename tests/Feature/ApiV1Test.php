<?php

namespace Tests\Feature;

use App\Models\Depoimento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_status_responde_online(): void
    {
        $this->getJson('/api/v1/status')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.aplicacao', 'Biblioteca Virtual FPSF')
            ->assertJsonPath('data.api', 'v1')
            ->assertJsonPath('data.status', 'online')
            ->assertJsonStructure(['success', 'data' => ['data_hora']]);
    }

    public function test_depoimentos_so_aprovados_e_sem_dados_privados(): void
    {
        $aluno = Usuario::where('email', 'beatriz.lima@fpsf.edu.br')->firstOrFail();
        Depoimento::create(['usuario_id' => $aluno->id, 'nome' => 'Pendente', 'texto' => 'Ainda não aprovado.', 'status' => 'pendente']);
        Depoimento::create(['nome' => 'Recusado', 'texto' => 'Não foi aprovado.', 'status' => 'recusado']);
        $recente = Depoimento::forceCreate(['nome' => 'Recente', 'texto' => 'O mais novo.', 'nota' => 4, 'status' => 'aprovado', 'criado_em' => now()->addDay()]);

        $resposta = $this->getJson('/api/v1/depoimentos')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(7, 'data') // 6 do seeder + o recente
            ->assertJsonPath('data.0.id', $recente->id)
            ->assertJsonPath('data.0.nota', 4);

        $nomes = collect($resposta->json('data'))->pluck('nome');
        $this->assertNotContains('Pendente', $nomes);
        $this->assertNotContains('Recusado', $nomes);

        foreach ($resposta->json('data') as $depoimento) {
            $this->assertSame(['id', 'nome', 'descricao_autor', 'texto', 'nota', 'criado_em'], array_keys($depoimento));
        }
        $this->assertStringNotContainsString('usuario_id', $resposta->getContent());
        $this->assertStringNotContainsString('@fpsf.edu.br', $resposta->getContent());
    }

    public function test_materias_com_total_de_livros(): void
    {
        $this->getJson('/api/v1/materias')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data')
            ->assertJsonFragment(['slug' => 'literatura', 'nome' => 'Literatura', 'icone' => 'livro.png', 'total_livros' => 6]);
    }

    public function test_livros_de_uma_materia_ou_404(): void
    {
        $this->getJson('/api/v1/materias/filosofia/livros')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'a-republica')
            ->assertJsonPath('data.0.materia_nome', 'Filosofia');

        $this->getJson('/api/v1/materias/literatura/livros')->assertOk()->assertJsonCount(6, 'data');
        $this->getJson('/api/v1/materias/artes/livros')->assertOk()->assertExactJson(['success' => true, 'data' => []]);

        $this->getJson('/api/v1/materias/nao-existe/livros')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'erro' => 'Matéria não encontrada.']);
    }

    public function test_lista_livros_com_filtros(): void
    {
        $resposta = $this->getJson('/api/v1/livros')->assertOk()->assertJsonPath('success', true)->assertJsonCount(8, 'data');

        $livro = collect($resposta->json('data'))->firstWhere('slug', 'o-pequeno-principe');
        $this->assertSame(5, $livro['total_exemplares']);
        $this->assertSame(5, $livro['disponiveis']);

        $this->getJson('/api/v1/livros?materia=filosofia')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'a-republica');
        $this->getJson('/api/v1/livros?materia=todos')->assertJsonCount(8, 'data');
        $this->getJson('/api/v1/livros?busca=quixote')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'dom-quixote');
        $this->getJson('/api/v1/livros?busca=orwell')->assertJsonCount(1, 'data')->assertJsonPath('data.0.titulo', 'A Revolução dos Bichos');
        $this->getJson('/api/v1/livros?busca=nada-a-ver')->assertOk()->assertExactJson(['success' => true, 'data' => []]);
    }

    public function test_detalhe_do_livro_pelo_slug_ou_404(): void
    {
        $this->getJson('/api/v1/livros/dom-quixote')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.autor', 'Miguel de Cervantes')
            ->assertJsonPath('data.materia_nome', 'Literatura')
            ->assertJsonPath('data.total_exemplares', 2);

        $this->getJson('/api/v1/livros/nao-existe')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'erro' => 'Livro não encontrado.']);

        $this->getJson('/api/v1/qualquer-coisa')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'erro' => 'Rota não encontrada.']);
    }

    public function test_api_antiga_continua_sem_envelope(): void
    {
        $this->getJson('/api/livros')->assertOk()->assertJsonCount(8)->assertJsonMissingPath('success');
        $this->getJson('/api/livros/dom-quixote')->assertOk()->assertJsonPath('slug', 'dom-quixote')->assertJsonMissingPath('data');
        $this->getJson('/api/livros/nao-existe')->assertNotFound()->assertExactJson(['erro' => 'Livro não encontrado.']);
        $this->getJson('/api/qualquer-coisa')->assertNotFound()->assertExactJson(['erro' => 'Rota não encontrada.']);
    }

    public function test_limite_da_v1_nao_bloqueia_o_login_do_site(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->getJson('/api/v1/status')->assertOk();
        }

        $this->postJson('/api/login', ['email' => 'beatriz.lima@fpsf.edu.br', 'senha' => 'aluno123'])->assertOk();
    }

    public function test_limite_de_60_por_minuto_na_v1(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/status')->assertOk();
        }

        $this->getJson('/api/v1/status')
            ->assertStatus(429)
            ->assertExactJson(['success' => false, 'erro' => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);
    }

    public function test_pagina_de_documentacao_e_html(): void
    {
        $this->get('/api/documentacao')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('API pública v1')
            ->assertSee('/api/v1/livros');
    }
}
