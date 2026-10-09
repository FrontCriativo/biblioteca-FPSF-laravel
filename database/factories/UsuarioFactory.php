<?php

namespace Database\Factories;

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
            'turma' => '9º ano A',
        ];
    }

    public function admin(): static
    {
        return $this->state(['papel' => 'admin', 'turma' => null]);
    }
}
