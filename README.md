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
docker compose exec -u app app php artisan migrate:fresh --seed   # zerar o banco
docker compose exec -u app app php artisan test          # rodar os testes
docker compose down                                      # parar
```

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

Para zerar o banco e popular de novo: `php artisan migrate:fresh --seed`.

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

Por padrão usa SQLite (`database/database.sqlite`). Para MySQL, ajuste no `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=biblioteca
DB_USERNAME=root
DB_PASSWORD=
```

A regra "um exemplar só pode ter uma reserva aberta" é garantida pelo próprio
banco com um índice único parcial no SQLite/PostgreSQL. No MySQL, que não tem
esse tipo de índice, a garantia vem do `lockForUpdate()` dentro da transação.

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
