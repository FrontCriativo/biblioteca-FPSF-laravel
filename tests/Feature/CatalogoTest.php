<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_lista_materias_com_total_de_livros(): void
    {
        $this->getJson('/api/materias')
            ->assertOk()
            ->assertJsonCount(10)
            ->assertJsonFragment(['slug' => 'literatura', 'nome' => 'Literatura', 'icone' => 'livro.png', 'total_livros' => 6]);
    }

    public function test_lista_livros_com_disponibilidade(): void
    {
        $resposta = $this->getJson('/api/livros')->assertOk()->assertJsonCount(8);

        $livro = collect($resposta->json())->firstWhere('slug', 'o-pequeno-principe');
        $this->assertSame(5, $livro['total_exemplares']);
        $this->assertSame(5, $livro['disponiveis']);
        $this->assertSame('Literatura', $livro['materia_nome']);
        $this->assertSame('livro.png', $livro['materia_icone']);
    }

    public function test_filtra_por_materia_e_busca(): void
    {
        $this->getJson('/api/livros?materia=filosofia')->assertJsonCount(1)->assertJsonPath('0.slug', 'a-republica');
        $this->getJson('/api/livros?materia=todos')->assertJsonCount(8);
        $this->getJson('/api/livros?busca=orwell')->assertJsonCount(1)->assertJsonPath('0.titulo', 'A Revolução dos Bichos');
        $this->getJson('/api/livros?busca=nada-a-ver')->assertJsonCount(0);
    }

    public function test_mostra_livro_pelo_slug_ou_404(): void
    {
        $this->getJson('/api/livros/dom-quixote')->assertOk()->assertJsonPath('autor', 'Miguel de Cervantes');
        $this->getJson('/api/livros/nao-existe')->assertNotFound()->assertExactJson(['erro' => 'Livro não encontrado.']);
    }

    public function test_rota_inexistente_responde_json(): void
    {
        $this->getJson('/api/qualquer-coisa')->assertNotFound()->assertExactJson(['erro' => 'Rota não encontrada.']);
    }

    public function test_pagina_inicial_e_servida(): void
    {
        $this->get('/')->assertOk();
    }
}
