<?php

namespace App\Http\Controllers;

use App\Http\Resources\MateriaResource;
use App\Models\Materia;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MateriaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $materias = Materia::query()
            ->withCount('livros as total_livros')
            ->orderBy('nome')
            ->get();

        return MateriaResource::collection($materias);
    }
}
