<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'materias', key: 'slug', keyType: 'string', incrementing: false, timestamps: false)]
#[Fillable(['slug', 'nome', 'icone'])]
class Materia extends Model
{
    public function livros(): HasMany
    {
        return $this->hasMany(Livro::class, 'materia_slug', 'slug');
    }
}
