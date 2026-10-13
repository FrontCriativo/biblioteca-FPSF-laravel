<?php

namespace App\Http\Resources;

use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Datas saem como "AAAA-MM-DD HH:MM:SS" em UTC, o formato que o front-end
 * (formatarData em public/js/api.js) espera.
 *
 * @mixin Reserva
 */
class ReservaResource extends JsonResource
{
    private const FORMATO_DATA = 'Y-m-d H:i:s';

    public function toArray(Request $request): array
    {
        $livro = $this->exemplar->livro;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'criada_em' => $this->criada_em?->utc()->format(self::FORMATO_DATA),
            'expira_em' => $this->expira_em?->utc()->format(self::FORMATO_DATA),
            'encerrada_em' => $this->encerrada_em?->utc()->format(self::FORMATO_DATA),
            'exemplar_codigo' => $this->exemplar->codigo,
            'livro_slug' => $livro->slug,
            'titulo' => $livro->titulo,
            'autor' => $livro->autor,
            'capa' => $livro->capa,
            'usuario_nome' => $this->usuario->nome,
            'usuario_email' => $this->usuario->email,
            'turma' => $this->usuario->turma?->nome,
        ];
    }
}
