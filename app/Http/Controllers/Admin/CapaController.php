<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Upload da capa de um livro.
 *
 * O front-end manda a imagem em base64 dentro de um JSON { nome, dados }.
 * O arquivo é salvo em public/assets/, ao lado das capas já existentes,
 * e a resposta devolve só o nome do arquivo.
 */
class CapaController extends Controller
{
    private const TAMANHO_MAXIMO = 6 * 1024 * 1024;

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'dados' => ['required', 'string'],
        ]);

        $bytes = base64_decode($dados['dados'], true);
        if ($bytes === false || $bytes === '') {
            abort(400, 'Envie uma imagem JPG, PNG ou WEBP.');
        }
        if (strlen($bytes) > self::TAMANHO_MAXIMO) {
            abort(413, 'A imagem da capa deve ter no máximo 6 MB.');
        }

        // O tipo é decidido pelos primeiros bytes do arquivo, não pelo nome.
        $extensao = $this->extensaoPelaAssinatura($bytes)
            ?? abort(400, 'Envie uma imagem JPG, PNG ou WEBP.');

        // O nome vem do arquivo original, limpo; se já existir, ganha sufixo numérico.
        $base = Str::slug(pathinfo((string) ($dados['nome'] ?? ''), PATHINFO_FILENAME)) ?: 'capa';
        $pasta = public_path('assets');
        $arquivo = "{$base}.{$extensao}";
        for ($i = 2; File::exists("{$pasta}/{$arquivo}"); $i++) {
            $arquivo = "{$base}-{$i}.{$extensao}";
        }

        File::ensureDirectoryExists($pasta);
        File::put("{$pasta}/{$arquivo}", $bytes);

        return response()->json(['arquivo' => $arquivo], 201);
    }

    private function extensaoPelaAssinatura(string $b): ?string
    {
        return match (true) {
            str_starts_with($b, "\xFF\xD8\xFF") => 'jpg',
            str_starts_with($b, "\x89PNG\r\n\x1A\n") => 'png',
            substr($b, 0, 4) === 'RIFF' && substr($b, 8, 4) === 'WEBP' => 'webp',
            default => null,
        };
    }
}
