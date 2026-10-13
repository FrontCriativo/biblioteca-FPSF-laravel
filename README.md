# Biblioteca Virtual FPSF — Laravel 13

Biblioteca Virtual da Escola Prof. Francisco Pereira de Souza Filho.
O back-end, antes em Node.js puro, agora é uma aplicação **Laravel 13**.
O front-end (HTML, CSS e JavaScript) continua o mesmo e fica em `public/`.

## Requisitos

- PHP 8.3 ou mais novo, com as extensões `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `openssl`, `fileinfo`
- Composer 2

## Como rodar com Docker

Só precisa do Docker (com Docker Compose). Rode no terminal do WSL/Linux, na pasta do projeto:

```bash
docker compose up -d --build   # sobe em http://localhost:8000
```

Na primeira subida o container instala as dependências do Composer, cria o `.env`,
gera a `APP_KEY`, cria o banco SQLite e popula com os dados de exemplo (pode levar
um ou dois minutos; acompanhe com `docker compose logs -f`). Nas próximas, só roda
as migrations pendentes.

```bash
docker compose logs -f                                   # ver os logs
docker compose exec -u app app php artisan migrate:status   # ver as migrations
docker compose exec -u app app php artisan test          # rodar os testes
docker compose down                                      # parar
```

Para voltar o banco aos dados de exemplo, veja [Banco de dados](#banco-de-dados).
Não use `migrate:fresh` no banco principal.

O código fica montado dentro do container, então as alterações aparecem na hora.

## Como rodar sem Docker

```bash
composer run setup   # instala dependências, cria .env, gera APP_KEY, cria o banco e popula
composer run dev     # sobe em http://localhost:8000
```

Ou passo a passo:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Usuários de exemplo criados pelo seeder:

| Papel | E-mail | Senha |
|---|---|---|
| Coordenação (admin) | admin@fpsf.edu.br | admin123 |
| Aluna | beatriz.lima@fpsf.edu.br | aluno123 |
| Aluno | rafael.souza@fpsf.edu.br | aluno123 |

Para voltar o banco aos dados de exemplo, veja [Banco de dados](#banco-de-dados).

## Testes

```bash
php artisan test
```

## Onde está cada coisa

| Antes (Node) | Agora (Laravel) |
|---|---|
| `backend/server.js` | `bootstrap/app.php` (rotas da API, middlewares, formato dos erros) |
| `backend/rotas.js` | `routes/api.php` + `app/Http/Controllers/` |
| `backend/repositorio.js` | `app/Models/` (Eloquent) + `app/Services/ServicoDeReservas.php` |
| `backend/auth.js` (sessões, senha) | Autenticação nativa do Laravel (sessão por cookie, senha com bcrypt) |
| validação manual (`campoObrigatorio`...) | `app/Http/Requests/` + mensagens em `lang/pt_BR/validation.php` |
| `backend/db/schema.sql` | `database/migrations/` |
| `backend/db/seed.js` | `database/seeders/DatabaseSeeder.php` |
| `frontend/` | `public/` |

## API

As rotas e o formato das respostas continuam os mesmos (`/api/...`), e os erros
continuam vindo como `{ "erro": "mensagem" }`.

| Método | Rota | Acesso |
|---|---|---|
| POST | `/api/login` · `/api/logout` | público |
| GET | `/api/eu` | público (devolve `null` sem login) |
| GET | `/api/materias` · `/api/livros` · `/api/livros/{slug}` | público |
| GET/POST | `/api/reservas` | aluno logado |
| POST | `/api/reservas/{id}/cancelar` | dono da reserva ou admin |
| POST/PUT/DELETE | `/api/livros`, `/api/livros/{id}`, `/api/livros/{id}/exemplares` | admin |
| GET/POST | `/api/admin/resumo`, `/api/admin/reservas`, `/api/admin/usuarios`, `/api/admin/capas` | admin |
| POST | `/api/admin/reservas/{id}/retirar` · `/devolver` | admin |

## Banco de dados

O banco é **SQLite**, no arquivo `database/database.sqlite` (fora do Git). Toda a
estrutura vem das migrations em `database/migrations/`:

| Migration | Tabelas |
|---|---|
| `0001_01_01_000000_create_usuarios_table` | `usuarios`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_10_08_000000_create_acervo_tables` | `materias`, `livros`, `exemplares`, `reservas` |

Ao todo são 12 tabelas (contando `migrations`), 70 colunas e 4 chaves estrangeiras:
`livros.materia_slug → materias.slug`, `exemplares.livro_id → livros.id`,
`reservas.exemplar_id → exemplares.id` e `reservas.usuario_id → usuarios.id`.

A regra "um exemplar só pode ter uma reserva aberta" é garantida pelo próprio
banco com um índice único parcial (`reservas_exemplar_aberta_unique`).

> **Atenção:** não rode `migrate:fresh`, `migrate:refresh`, `migrate:reset` nem
> `db:wipe` no banco principal. Esses comandos apagam todas as tabelas, incluindo
> os livros e capas cadastrados pela coordenação e as reservas dos alunos.

### Backup

A pasta `backup/` está no `.gitignore`, porque os backups têm e-mails e hashes de
senha dos alunos. O `VACUUM INTO` gera uma cópia consistente mesmo com o site no ar
e não sobrescreve um arquivo que já exista:

```bash
mkdir -p backup
docker compose exec -u app app php -r '(new PDO("sqlite:database/database.sqlite"))->prepare("VACUUM INTO ?")->execute(["backup/biblioteca-".date("Y-m-d").".sqlite"]);'
```

Para restaurar, pare o container (`docker compose stop app`), copie o backup por
cima de `database/database.sqlite` e suba de novo (`docker compose start app`).

### Voltar aos dados de exemplo

Em vez de `migrate:fresh`, tire o banco atual do caminho e reinicie o container.
O `docker/entrypoint.sh` cria um banco novo e roda as migrations com o seeder:

```bash
mkdir -p backup
mv database/database.sqlite backup/biblioteca-$(date +%F-%H%M).sqlite
docker compose restart app
```

### Validar as migrations num banco vazio

Para conferir se as migrations recriam o banco inteiro, use um segundo arquivo SQLite.
A opção `-e DB_DATABASE=...` é o que protege o banco principal, então nunca a tire
desses comandos:

```bash
touch database/biblioteca_migrations_teste.sqlite
docker compose exec -u app -e DB_DATABASE=/var/www/html/database/biblioteca_migrations_teste.sqlite app php artisan db:show   # confirme o arquivo ANTES
docker compose exec -u app -e DB_DATABASE=/var/www/html/database/biblioteca_migrations_teste.sqlite app php artisan migrate
docker compose exec -u app -e DB_DATABASE=/var/www/html/database/biblioteca_migrations_teste.sqlite app php artisan migrate:status
rm database/biblioteca_migrations_teste.sqlite   # depois de comparar
```

Os testes automatizados não usam nenhum desses arquivos. Eles rodam num SQLite em
memória (ver `phpunit.xml`).

### Fluxo correto a partir de agora

Mudanças de estrutura (tabela, coluna, índice, chave estrangeira) entram **só por
migration nova**. Nunca altere o banco direto num editor de SQLite (DB Browser,
extensão do VS Code etc.) e não edite migrations que já rodaram:

```bash
docker compose exec -u app app php artisan make:migration add_isbn_to_livros_table
# edite o arquivo criado em database/migrations/ (up() e down())
docker compose exec -u app app php artisan migrate
docker compose exec -u app app php artisan migrate:status
```

Faça um backup antes de rodar migrations que alterem tabelas com dados. O container
já roda `php artisan migrate --force` toda vez que sobe.

### MySQL (opcional)

O projeto também roda em MySQL (o `pdo_mysql` já vem na imagem): basta ajustar
`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`
no `.env`. O MySQL não tem índice parcial, então lá a regra de uma reserva aberta
por exemplar fica a cargo do `lockForUpdate()` em `App\Services\ServicoDeReservas`.

## Reservas vencidas

A pré-reserva vale 3 dias. As vencidas são canceladas automaticamente a cada
chamada à API (middleware `ExpirarReservasVencidas`). Em produção, dá para
também deixar o agendador do Laravel cuidando disso, adicionando ao cron:

```cron
* * * * * cd /caminho/do/projeto && php artisan schedule:run >> /dev/null 2>&1
```

Ou rodar na mão: `php artisan reservas:expirar`.

## Publicação (produção)

- Aponte o servidor web (Apache/Nginx) para a pasta `public/`.
- No `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` com o endereço real.
- A pasta `public/assets/` precisa ter permissão de escrita (é onde as capas enviadas pela coordenação são salvas), assim como `storage/` e `bootstrap/cache/`.
- `php artisan config:cache && php artisan route:cache` para acelerar.
