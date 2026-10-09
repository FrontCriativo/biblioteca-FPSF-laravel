<?php

namespace Tests\Feature;

use App\Models\Livro;
use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservasTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function aluno(string $email = 'beatriz.lima@fpsf.edu.br'): Usuario
    {
        return Usuario::firstWhere('email', $email);
    }

    private function admin(): Usuario
    {
        return Usuario::firstWhere('email', 'admin@fpsf.edu.br');
    }

    private function livro(string $slug): Livro
    {
        return Livro::firstWhere('slug', $slug);
    }

    public function test_reservar_exige_login(): void
    {
        $this->postJson('/api/reservas', ['livro_id' => 1])
            ->assertUnauthorized()
            ->assertExactJson(['erro' => 'Você precisa entrar para fazer isso.']);
    }

    public function test_aluno_reserva_e_disponibilidade_cai(): void
    {
        $livro = $this->livro('dom-quixote');

        $this->actingAs($this->aluno())
            ->postJson('/api/reservas', ['livro_id' => $livro->id])
            ->assertCreated()
            ->assertJsonPath('status', 'ativa')
            ->assertJsonPath('livro_slug', 'dom-quixote')
            ->assertJsonPath('usuario_nome', 'Beatriz Lima');

        $this->getJson('/api/livros/dom-quixote')->assertJsonPath('disponiveis', 1);

        $minhas = $this->getJson('/api/reservas')->assertOk()->assertJsonCount(1)->json();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $minhas[0]['criada_em']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $minhas[0]['expira_em']);
    }

    public function test_nao_reserva_o_mesmo_livro_duas_vezes(): void
    {
        $livro = $this->livro('dom-quixote');
        $this->actingAs($this->aluno());

        $this->postJson('/api/reservas', ['livro_id' => $livro->id])->assertCreated();
        $this->postJson('/api/reservas', ['livro_id' => $livro->id])
            ->assertConflict()
            ->assertExactJson(['erro' => 'Você já tem uma reserva ativa deste livro.']);
    }

    public function test_esgota_exemplares(): void
    {
        $livro = $this->livro('dom-quixote'); // 2 exemplares
        $outro = Usuario::factory()->create();

        $this->actingAs($this->aluno())->postJson('/api/reservas', ['livro_id' => $livro->id])->assertCreated();
        $this->actingAs($this->aluno('rafael.souza@fpsf.edu.br'))->postJson('/api/reservas', ['livro_id' => $livro->id])->assertCreated();
        $this->actingAs($outro)->postJson('/api/reservas', ['livro_id' => $livro->id])
            ->assertConflict()
            ->assertExactJson(['erro' => 'Não há exemplares disponíveis no momento.']);

        $this->getJson('/api/livros/dom-quixote')->assertJsonPath('disponiveis', 0);
    }

    public function test_banco_recusa_duas_reservas_abertas_no_mesmo_exemplar(): void
    {
        $exemplar = $this->livro('dom-quixote')->exemplares()->first();
        $dados = ['exemplar_id' => $exemplar->id, 'status' => 'ativa', 'expira_em' => now()->addDay()];

        Reserva::create($dados + ['usuario_id' => $this->aluno()->id]);

        $this->expectException(UniqueConstraintViolationException::class);
        Reserva::create($dados + ['usuario_id' => $this->admin()->id]);
    }

    public function test_reservar_livro_inexistente(): void
    {
        $this->actingAs($this->aluno())
            ->postJson('/api/reservas', ['livro_id' => 999])
            ->assertNotFound()
            ->assertExactJson(['erro' => 'Livro não encontrado.']);
    }

    public function test_aluno_cancela_a_propria_mas_nao_a_dos_outros(): void
    {
        $livro = $this->livro('vidas-secas');
        $id = $this->actingAs($this->aluno())->postJson('/api/reservas', ['livro_id' => $livro->id])->json('id');

        $this->actingAs($this->aluno('rafael.souza@fpsf.edu.br'))
            ->postJson("/api/reservas/{$id}/cancelar")
            ->assertForbidden()
            ->assertExactJson(['erro' => 'Esta reserva não é sua.']);

        $this->actingAs($this->aluno())
            ->postJson("/api/reservas/{$id}/cancelar")
            ->assertOk()
            ->assertJsonPath('status', 'cancelada');

        $this->assertNotNull(Reserva::find($id)->encerrada_em);
    }

    public function test_fluxo_do_balcao_retirada_e_devolucao(): void
    {
        $livro = $this->livro('a-republica');
        $id = $this->actingAs($this->aluno())->postJson('/api/reservas', ['livro_id' => $livro->id])->json('id');

        $this->actingAs($this->admin());

        $this->postJson("/api/admin/reservas/{$id}/devolver")
            ->assertConflict()
            ->assertExactJson(['erro' => 'Não é possível mudar de "ativa" para "devolvida".']);

        $this->postJson("/api/admin/reservas/{$id}/retirar")->assertOk()->assertJsonPath('status', 'retirada');
        $this->getJson('/api/admin/resumo')->assertJsonPath('retiradas', 1);

        $this->postJson("/api/admin/reservas/{$id}/devolver")->assertOk()->assertJsonPath('status', 'devolvida');
        $this->getJson('/api/livros/a-republica')->assertJsonPath('disponiveis', 2);

        $this->getJson('/api/admin/reservas?status=devolvida')->assertJsonCount(1);
        $this->getJson('/api/admin/reservas?status=ativa')->assertJsonCount(0);
        $this->getJson('/api/admin/reservas')->assertJsonCount(1);
    }

    public function test_reserva_vencida_e_cancelada_automaticamente(): void
    {
        $livro = $this->livro('dom-quixote');
        $id = $this->actingAs($this->aluno())->postJson('/api/reservas', ['livro_id' => $livro->id])->json('id');

        $this->travel(4)->days();

        $this->getJson('/api/reservas')->assertJsonPath('0.status', 'cancelada');
        $this->getJson('/api/livros/dom-quixote')->assertJsonPath('disponiveis', 2);
        $this->assertSame('cancelada', Reserva::find($id)->status);
    }

    public function test_comando_de_expirar(): void
    {
        $livro = $this->livro('dom-quixote');
        $this->actingAs($this->aluno())->postJson('/api/reservas', ['livro_id' => $livro->id]);

        $this->travel(4)->days();

        $this->artisan('reservas:expirar')->expectsOutput('1 reserva(s) vencida(s) cancelada(s).')->assertSuccessful();
    }
}
