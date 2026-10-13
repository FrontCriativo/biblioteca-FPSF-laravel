<?php

namespace Database\Seeders;

use App\Models\Livro;
use App\Models\Materia;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Popula o banco com as matérias, o acervo inicial, turmas, usuários de
 * exemplo e os depoimentos aprovados que aparecem no site.
 * Pode ser rodado mais de uma vez: o que já existe não é duplicado.
 */
class DatabaseSeeder extends Seeder
{
    private const MATERIAS = [
        ['literatura', 'Literatura', 'livro.png'],
        ['matematica', 'Matemática', 'matematica.png'],
        ['historia', 'História', 'historia.png'],
        ['geografia', 'Geografia', 'geografia.png'],
        ['ciencias', 'Ciências', 'ciencias.png'],
        ['filosofia', 'Filosofia', 'filosofia.png'],
        ['artes', 'Artes', 'artes.png'],
        ['musica', 'Música', 'musica.png'],
        ['tecnologia', 'Tecnologia', 'tecnologia.png'],
        ['meio-ambiente', 'Meio Ambiente', 'meioAmbiente.png'],
    ];

    private const LIVROS = [
        [
            'slug' => 'a-revolucao-dos-bichos', 'titulo' => 'A Revolução dos Bichos', 'autor' => 'George Orwell',
            'materia' => 'literatura', 'capa' => 'aRevolucaoDosBichos.jpg', 'paginas' => 152, 'exemplares' => 3,
            'sinopse' => 'Os animais de uma granja expulsam o dono e passam a governar a si mesmos. A promessa de igualdade vai se desfazendo conforme os porcos assumem o comando.',
        ],
        [
            'slug' => 'diario-de-um-banana', 'titulo' => 'Diário de um Banana', 'autor' => 'Jeff Kinney',
            'materia' => 'literatura', 'capa' => 'diarioDeUmBanana.jpg', 'paginas' => 224, 'exemplares' => 4,
            'sinopse' => 'Greg Heffley registra em forma de diário as confusões do primeiro ano no ensino fundamental II, entre amizades atrapalhadas e planos que nunca saem como esperado.',
        ],
        [
            'slug' => 'dom-quixote', 'titulo' => 'Dom Quixote', 'autor' => 'Miguel de Cervantes',
            'materia' => 'literatura', 'capa' => 'domQuixote.jpg', 'paginas' => 863, 'exemplares' => 2,
            'sinopse' => 'Um fidalgo enlouquecido pelas novelas de cavalaria sai pelo campo em busca de aventuras, acompanhado do escudeiro Sancho Pança.',
        ],
        [
            'slug' => 'o-pequeno-principe', 'titulo' => 'O Pequeno Príncipe', 'autor' => 'Antoine de Saint-Exupéry',
            'materia' => 'literatura', 'capa' => 'oPequenoPrincipe.jpg', 'paginas' => 96, 'exemplares' => 5,
            'sinopse' => 'Um piloto perdido no deserto encontra um menino vindo de um pequeno planeta distante. Entre uma pergunta e outra, o pequeno viajante conta sobre a rosa que deixou para trás e os lugares estranhos que visitou até chegar à Terra.',
        ],
        [
            'slug' => 'romeu-e-julieta', 'titulo' => 'Romeu e Julieta', 'autor' => 'William Shakespeare',
            'materia' => 'literatura', 'capa' => 'RomeuAndJulieta.jpg', 'paginas' => 160, 'exemplares' => 2,
            'sinopse' => 'Duas famílias rivais de Verona e dois jovens que se apaixonam no meio dessa disputa antiga.',
        ],
        [
            'slug' => 'vidas-secas', 'titulo' => 'Vidas Secas', 'autor' => 'Graciliano Ramos',
            'materia' => 'literatura', 'capa' => 'vidasSecas.jpg', 'paginas' => 176, 'exemplares' => 3,
            'sinopse' => 'Fabiano, Sinhá Vitória e os dois filhos atravessam o sertão fugindo da seca, carregando pouco mais do que a cachorra Baleia e a esperança de um lugar melhor.',
        ],
        [
            'slug' => 'a-origem-das-especies', 'titulo' => 'A Origem das Espécies', 'autor' => 'Charles Darwin',
            'materia' => 'ciencias', 'capa' => 'aOrigemDasEspecies.jpg', 'paginas' => 504, 'exemplares' => 2,
            'sinopse' => 'A obra que apresentou a seleção natural como explicação para a diversidade da vida, reunindo observações feitas ao longo da viagem do Beagle.',
        ],
        [
            'slug' => 'a-republica', 'titulo' => 'A República', 'autor' => 'Platão',
            'materia' => 'filosofia', 'capa' => 'aRepublica.png', 'paginas' => 416, 'exemplares' => 2,
            'sinopse' => 'Em diálogos conduzidos por Sócrates, Platão investiga o que é a justiça e como seria uma cidade organizada de forma justa.',
        ],
    ];

    private const TURMAS = ['8º ano B', '9º ano A'];

    private const USUARIOS = [
        ['Coordenação da Biblioteca', 'admin@fpsf.edu.br', 'admin123', 'admin', null],
        ['Beatriz Lima', 'beatriz.lima@fpsf.edu.br', 'aluno123', 'aluno', '8º ano B'],
        ['Rafael Souza', 'rafael.souza@fpsf.edu.br', 'aluno123', 'aluno', '9º ano A'],
    ];

    /** Os depoimentos que estavam fixos em public/depoimentos.html (e-mail quando o autor tem login). */
    private const DEPOIMENTOS = [
        ['Beatriz Lima', 'Aluna do 8º ano', 'beatriz.lima@fpsf.edu.br', 'Antes eu só lia o que a professora passava. Agora entro no site pra escolher o próximo livro antes mesmo da aula acabar.'],
        ['Prof. Marcos Andrade', 'Professor de Ciências', null, 'Consigo indicar leituras por matéria em segundos e acompanhar o que cada turma anda descobrindo. Facilitou muito o meu planejamento.'],
        ['Sandra Oliveira', 'Mãe de aluno do 6º ano', null, 'Meu filho pede pra usar o computador pra "ver os livros da escola". Nunca pediu isso pra nenhum joguinho.'],
        ['Enzo Ferreira', 'Aluno do 9º ano', null, 'Gosto de poder reler o resumo de "A República" antes da prova de Filosofia sem precisar levar o livro pra casa todo dia.'],
        ['Profa. Renata Costa', 'Professora de Português', null, 'Uso a biblioteca virtual pra montar a lista de leitura do trimestre. É rápido mostrar as capas pra turma escolher junto comigo.'],
        ['Gabriel Souza', 'Aluno do 9º ano', null, 'Achei "Vidas Secas" pela busca em menos de um minuto. Antes eu precisava esperar a bibliotecária ter um tempo livre pra me ajudar.'],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::MATERIAS as [$slug, $nome, $icone]) {
                Materia::firstOrCreate(['slug' => $slug], ['nome' => $nome, 'icone' => $icone]);
            }

            foreach (self::LIVROS as $dados) {
                $livro = Livro::firstOrCreate(['slug' => $dados['slug']], [
                    'titulo' => $dados['titulo'],
                    'autor' => $dados['autor'],
                    'materia_slug' => $dados['materia'],
                    'capa' => $dados['capa'],
                    'sinopse' => $dados['sinopse'],
                    'paginas' => $dados['paginas'],
                ]);

                $prefixo = Str::upper(Str::substr($dados['slug'], 0, 8));
                for ($i = 1; $i <= $dados['exemplares']; $i++) {
                    $livro->exemplares()->firstOrCreate([
                        'codigo' => sprintf('%s-%03d', $prefixo, $i),
                    ]);
                }
            }

            foreach (self::TURMAS as $nome) {
                DB::table('turmas')->insertOrIgnore(['nome' => $nome, 'ano_letivo' => now()->year]);
            }
            $turmas = DB::table('turmas')->pluck('id', 'nome');

            foreach (self::USUARIOS as [$nome, $email, $senha, $papel, $turma]) {
                $usuario = Usuario::firstOrCreate(['email' => $email], [
                    'nome' => $nome,
                    'senha' => $senha,
                    'papel' => $papel,
                    'turma' => $turma,
                ]);

                // turma_id ainda não está no $fillable do model; o texto em
                // usuarios.turma continua sendo o que a API usa.
                if ($turma !== null && $usuario->turma_id === null) {
                    $usuario->forceFill(['turma_id' => $turmas[$turma]])->save();
                }
            }

            foreach (self::DEPOIMENTOS as [$nome, $descricao, $email, $texto]) {
                $jaExiste = DB::table('depoimentos')->where('nome', $nome)->where('texto', $texto)->exists();

                if (! $jaExiste) {
                    DB::table('depoimentos')->insert([
                        'usuario_id' => $email ? Usuario::where('email', $email)->value('id') : null,
                        'nome' => $nome,
                        'descricao_autor' => $descricao,
                        'texto' => $texto,
                        'nota' => 5,
                        'status' => 'aprovado',
                    ]);
                }
            }
        });

        $this->command?->info(sprintf(
            '%d matérias, %d livros, %d turmas, %d usuários, %d depoimentos.',
            count(self::MATERIAS), count(self::LIVROS), count(self::TURMAS), count(self::USUARIOS), count(self::DEPOIMENTOS),
        ));
        $this->command?->line('  Admin:  admin@fpsf.edu.br / admin123');
        $this->command?->line('  Aluno:  beatriz.lima@fpsf.edu.br / aluno123');
    }
}
