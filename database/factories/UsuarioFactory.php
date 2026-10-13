<?php

namespace Database\Factories;

use App\Models\Turma;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'senha' => 'aluno123',
            'papel' => 'aluno',
            'turma_id' => fn () => Turma::firstOrCreate(['nome' => '9º ano A'], ['ano_letivo' => now()->year])->id,
            'ativo' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(['papel' => 'admin', 'turma_id' => null]);
    }

    public function inativo(): static
    {
        return $this->state(['ativo' => false]);
    }
}
