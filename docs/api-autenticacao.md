# API v1 — Autenticação por token (Laravel Sanctum)

Roteiro da aula "API Confeitaria DaVilla — Fluxo de autenticação", adaptado à Biblioteca
Virtual da Escola Prof. Francisco Pereira de Souza Filho (Laravel 13, PHP 8.4, SQLite, Docker).

A documentação pública dos endpoints, com exemplos, está em
[`/api/documentacao`](http://localhost:8000/api/documentacao#autenticacao).

> **Nunca** coloque um token real em documentação, README, commit, print ou relatório. Nos
> exemplos ele aparece como `SEU_TOKEN`.

## 1. Objetivo e fluxo

O aluno informa e-mail e senha, a API valida, o Sanctum gera um token, o aplicativo guarda o
token e o envia nas próximas requisições, e o Laravel identifica o aluno pelo token.

```
Aplicativo                                       API (Laravel)
    │ POST /api/v1/auth/login                         │
    │ { "email": "...", "senha": "..." }       ─────► │ 1. busca o usuário pelo e-mail
    │                                                 │ 2. Hash::check(senha, usuarios.senha)
    │                                                 │ 3. confere usuarios.ativo
    │                                                 │ 4. createToken() → grava o HASH em personal_access_tokens
    │ ◄───── { "data": { "token": "SEU_TOKEN" } }     │ 5. devolve o token em texto (só desta vez)
    │                                                 │
    │ (guarda o token)                                │
    │                                                 │
    │ GET /api/v1/usuario                             │
    │ Authorization: Bearer SEU_TOKEN          ─────► │ auth:sanctum: hash do token → linha da tabela
    │                                                 │ → tokenable_id → Usuario
    │ ◄───── { "data": { "id": 4, ... } }             │ $request->user() é o dono do token
```

| Etapa | Onde acontece |
|---|---|
| Validar e-mail e senha | `App\Http\Controllers\Api\V1\AuthController@login` |
| Gerar o token | `$usuario->createToken($dispositivo)` (trait `HasApiTokens`) |
| Guardar o token | Aplicativo (armazenamento seguro do celular, variável do Postman...) |
| Enviar o token | Cabeçalho `Authorization: Bearer SEU_TOKEN` |
| Identificar o usuário | Middleware `auth:sanctum` → `$request->user()` |
| Encerrar | `POST /api/v1/auth/logout` apaga o token |

## 2. Diferenças em relação à aula

| Aula (DaVilla) | Biblioteca FPSF |
|---|---|
| `docker compose exec --user "$(id -u):$(id -g)" php php artisan ...` | `docker compose exec -u app app php artisan ...` |
| `http://localhost:8081` | `http://localhost:8000` |
| Model novo `Cliente` (`tbl_clientes`, `senha_cliente`, `getAuthPassword()`) | Model `App\Models\Usuario` que já existia (tabela `usuarios`, coluna `senha`) |
| `status_cliente = 'ATIVO'` | coluna `usuarios.ativo` (boolean, padrão `true`) |
| `/api/v1/cliente` + `ClienteController` | `/api/v1/usuario` + `Api\V1\UsuarioController` |
| `routes/api.php` no grupo `api` | `routes/api.php` é a API do **site** (grupo `web`); a v1 foi para `routes/api_v1.php` (grupo `api`) |
| Erros `{ "success": false, "message": ... }` | Erros `{ "success": false, "erro": ... }` (padrão do projeto, em `bootstrap/app.php`) |
| PATCH com as mesmas regras `required` do PUT | PATCH realmente parcial (`sometimes`) |

## 3. Validação inicial do banco

```bash
docker compose exec -u app app php artisan db:table usuarios
docker compose exec -u app app php artisan tinker --execute 'echo App\Models\Usuario::where("ativo", true)->count(), PHP_EOL;'
```

Resultado: `usuarios` com `id, nome, email, senha, papel, criado_em, turma_id, ativo`; 3 de 3
usuários ativos (a coluna `ativo` foi criada antes, ver `docs/banco-de-dados.md`, Parte 6).

## 4. Instalação do Sanctum

```bash
docker compose exec -u app app php artisan install:api --without-migration-prompt
git diff bootstrap/app.php routes/
```

O comando instalou o `laravel/sanctum` (v4.3.3, em `composer.json`/`composer.lock`), publicou
`config/sanctum.php` e a migration `create_personal_access_tokens_table`. Ele respondeu
`API routes file already exists` e **não alterou** `bootstrap/app.php` nem `routes/` (o
`git diff` saiu vazio). Se tivesse inserido a linha `api: __DIR__.'/../routes/api.php',` no
`withRouting()`, ela teria de ser removida: o `routes/api.php` já é carregado no `then:` e, com
essa linha, as rotas do site seriam carregadas duas vezes, a segunda sem sessão.

### Por que a v1 saiu do `routes/api.php`

O `routes/api.php` roda no grupo `web` (sessão por cookie + CSRF), porque é o que o site
(`public/js/api.js`) usa. Nesse grupo, um `POST /api/v1/auth/login` vindo do curl ou do Postman
seria recusado com **419** (falta do token CSRF). Por isso:

- as rotas da v1 foram para um arquivo novo, `routes/api_v1.php`;
- no `then:` do `bootstrap/app.php` ele é registrado com o grupo `api` (sem sessão e sem CSRF):

```php
Route::middleware(['api', ExpirarReservasVencidas::class, 'throttle:60,1,v1'])
    ->prefix('api/v1')
    ->name('api.v1.')
    ->group(base_path('routes/api_v1.php'));
```

- o `routes/api.php` continua no grupo `web`, com as mesmas URLs e respostas;
- o `statefulApi()` **não** foi habilitado.

### `config/sanctum.php`

| Chave | Valor | Por quê |
|---|---|---|
| `guard` | `[]` | A v1 aceita **somente** o token. Com `['web']` (padrão), quem estivesse logado no site entraria na v1 só com o cookie de sessão |
| `expiration` | `10080` | 7 dias, o mesmo `SESSION_LIFETIME` do site |

Os tokens vencidos são apagados por `sanctum:prune-expired --hours=24`, agendado uma vez por
dia em `routes/console.php` (`php artisan schedule:list` mostra o agendamento).

### A tabela `personal_access_tokens`

Antes do `migrate`, backup do banco principal com `VACUUM INTO` em `backup/` (fora do Git).

```bash
docker compose exec -u app app php artisan migrate
docker compose exec -u app app php artisan db:table personal_access_tokens
```

| Coluna | O que guarda |
|---|---|
| `id` | Número do token. É a parte antes do `\|` no token entregue ao aplicativo |
| `tokenable_type`, `tokenable_id` | De quem é o token: `App\Models\Usuario` + id do usuário (relação polimórfica) |
| `name` | Nome do dispositivo (`device_name`: `app`, `postman-tipi05`...) |
| `token` | **Hash SHA-256** do token, nunca o texto. Índice único |
| `abilities` | Permissões do token (`["*"]` = todas) |
| `last_used_at` | Último uso |
| `expires_at` | Vencimento individual (não usado; vale o `expiration` do config) |
| `created_at`, `updated_at` | Datas do Laravel |

O aplicativo **nunca** acessa essa tabela: ele não tem acesso ao banco, só à API. E mesmo quem lê
o banco não consegue usar os tokens, porque ali só existe o hash. O token em texto aparece uma
única vez, na resposta do login; se o aplicativo perder o token, faz login de novo. Quem cria,
confere e apaga as linhas é o Sanctum (`createToken()`, `auth:sanctum`, `currentAccessToken()->delete()`).

## 5. Model `Usuario`

Nenhum model novo. Só foi adicionado `use HasApiTokens;` em `App\Models\Usuario`, que dá
`createToken()`, `tokens()` e `currentAccessToken()`.

O `getAuthPassword()` da aula não é necessário. Na aula ele existe porque a senha fica em
`senha_cliente`, e o Laravel procura `password`. Aqui o model já declara
`protected $authPasswordName = 'senha';`, que é o que o `getAuthPassword()` padrão do Laravel lê.
O cast `'senha' => 'hashed'` gera o bcrypt sozinho ao atribuir a senha em texto.

## 6. Controllers e regras

`App\Http\Controllers\Api\V1\AuthController` (gerado com
`php artisan make:controller Api/V1/AuthController`):

- **login** (`LoginTokenRequest`: `email` required\|email, `senha` required, `device_name`
  nullable\|max:100): e-mail em minúsculas; e-mail inexistente e senha errada → `401 E-mail ou
  senha incorretos.` (mesma mensagem nos dois casos); conta desativada → `403 Conta desativada.
  Procure a coordenação.`; apaga os tokens do mesmo `device_name` (padrão `app`) e cria um novo.
  Limite próprio: `throttle:10,1,v1-login`.
- **logout**: apaga só o token usado na requisição.

`App\Http\Controllers\Api\V1\UsuarioController` (não confundir com `Admin\UsuarioController`):

- **show**: `UsuarioResource` (a senha nunca aparece; `turma` é o nome da turma).
- **update** (PUT e PATCH, `PerfilRequest`): só `nome` (máx. 80) e `email` (máx. 120, único
  ignorando o próprio id, normalizado para minúsculas antes de validar). No PUT os dois são
  obrigatórios; no PATCH, só o que vier. `papel`, `turma_id`, `ativo` e `senha` ficam fora do
  `validated()` e são ignorados.
- **updateSenha** (`AlterarSenhaRequest`: `senha_atual`, `nova_senha` min:8\|confirmed\|different):
  senha atual errada → `422 Senha atual incorreta.`; grava a nova senha em texto (o cast faz o
  bcrypt) e apaga os **outros** tokens do usuário.

Login do site (`App\Http\Controllers\AuthController`): a mesma regra do `ativo` vale ali. Conta
desativada recebe `403 { "erro": "Conta desativada. Procure a coordenação." }` e não abre sessão.

## 7. Rotas

```bash
docker compose exec -u app app php artisan route:list --path=api/v1
```

```
POST      api/v1/auth/login ............ api.v1.auth.login › Api\V1\AuthController@login
POST      api/v1/auth/logout .......... api.v1.auth.logout › Api\V1\AuthController@logout
GET|HEAD  api/v1/depoimentos ......... api.v1.depoimentos › Api\V1\DepoimentoController@index
GET|HEAD  api/v1/livros ..................... api.v1.livros › Api\V1\LivroController@index
GET|HEAD  api/v1/livros/{livro:slug} ...... api.v1.livros.show › Api\V1\LivroController@show
GET|HEAD  api/v1/materias ............... api.v1.materias › Api\V1\MateriaController@index
GET|HEAD  api/v1/materias/{materia}/livros  api.v1.materias.livros › Api\V1\MateriaController@livros
GET|HEAD  api/v1/status .................... api.v1.status › Api\V1\StatusController@index
GET|HEAD  api/v1/usuario ............ api.v1.usuario.show › Api\V1\UsuarioController@show
PUT       api/v1/usuario .......... api.v1.usuario.update › Api\V1\UsuarioController@update
PATCH     api/v1/usuario ........... api.v1.usuario.patch › Api\V1\UsuarioController@update
PUT       api/v1/usuario/senha ... api.v1.usuario.senha › Api\V1\UsuarioController@updateSenha

                                                                  Showing [12] routes
```

6 públicas + 6 de autenticação, nenhuma duplicada.

| Método | Endpoint | Acesso | Objetivo |
|---|---|---|---|
| POST | /api/v1/auth/login | Público | Autenticar e gerar token |
| GET | /api/v1/usuario | Token | Consultar o próprio perfil |
| PUT | /api/v1/usuario | Token | Atualizar dados do perfil |
| PATCH | /api/v1/usuario | Token | Atualização parcial do perfil |
| PUT | /api/v1/usuario/senha | Token | Alterar a senha |
| POST | /api/v1/auth/logout | Token | Invalidar o token atual |

Sem token → `401 { "success": false, "erro": "Você precisa entrar para fazer isso." }`.

## 8. Teste via terminal

### Aluno de teste

```bash
docker compose exec -u app app php artisan tinker
```

```php
$turma = App\Models\Turma::firstWhere('nome', '9º ano A');
App\Models\Usuario::create([
    'nome' => 'Aluno Teste API', 'email' => 'aluno.teste@fpsf.edu.br',
    'senha' => '12345678', 'papel' => 'aluno', 'turma_id' => $turma->id,
]);
```

`create()` passa pelo cast `hashed` (bcrypt). `forceCreate()`/`DB::table()->insert()` com a senha
em texto gravaria a senha **sem** hash. Conferência sem exibir a senha:

```sql
SELECT id, nome, email, substr(senha, 1, 4), length(senha) FROM usuarios WHERE email = 'aluno.teste@fpsf.edu.br';
-- 4 | Aluno Teste API | aluno.teste@fpsf.edu.br | $2y$ | 60   (bcrypt)
```

### Login e rota protegida

Guarde o token numa variável do terminal em vez de copiá-lo:

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"email":"aluno.teste@fpsf.edu.br","senha":"12345678","device_name":"aula-tipi05"}' \
  | php -r 'echo json_decode(stream_get_contents(STDIN))->data->token;')

curl -s http://localhost:8000/api/v1/usuario -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

### Resultados obtidos (13/10/2026)

| Teste | Resposta |
|---|---|
| Login correto | `200` `{"success":true,"mensagem":"Login realizado com sucesso.","data":{"token":"SEU_TOKEN","usuario":{"id":4,...,"turma":"9º ano A"}}}` |
| `GET /usuario` com token | `200` `{"success":true,"data":{"id":4,"nome":"Aluno Teste API",...}}` |
| `PUT /usuario` com `papel: admin`, `turma_id`, `ativo: false`, `senha` no corpo | `200`; nome e e-mail mudaram, `papel` continuou `aluno`, turma, ativo e senha iguais |
| `PUT /usuario` sem e-mail | `422 O campo e-mail é obrigatório.` |
| `PATCH /usuario` só com nome | `200`, e-mail mantido |
| `PATCH /usuario` com e-mail de outro aluno | `422 Já existe um usuário com esse e-mail.` |
| Troca de senha com senha atual errada | `422 Senha atual incorreta.` |
| Troca de senha com confirmação diferente | `422 A confirmação do campo nova senha não confere.` |
| Troca de senha correta | `200 Senha alterada com sucesso.`; token do outro dispositivo → `401`; token atual → `200` |
| Logout | `200 Logout realizado com sucesso.`; mesmo token depois → `401` |
| Senha errada / e-mail inexistente | `401 E-mail ou senha incorretos.` (iguais) |
| Conta com `ativo = false` | `403 Conta desativada. Procure a coordenação.` (v1 e login do site) |
| Sem token / token inválido | `401 Você precisa entrar para fazer isso.` |
| 11 logins em 1 minuto | 10 × `401`, a 11ª `429` com `Retry-After`; o login do site continuou funcionando (contador separado) |
| Navegador logado no site chamando `/api/v1/usuario` sem token | `401` |
| Site (login por sessão, catálogo, reserva, painel admin com a turma) | sem mudança; `/api/login` sem CSRF continua `419` |

## 9. Teste no Postman

A collection pronta está em
[`docs/postman/biblioteca-fpsf-v1.postman_collection.json`](postman/biblioteca-fpsf-v1.postman_collection.json)
(**Import** → arquivo). Para montá-la do zero:

1. **New → Collection**, nome `Biblioteca FPSF — API v1`.
2. Na collection, aba **Variables**: `base_url` = `http://localhost:8000/api/v1` e `token` vazio.
   Salve (Ctrl+S).
3. **Login**: nova requisição `POST {{base_url}}/auth/login`.
   - **Headers**: `Content-Type: application/json` e `Accept: application/json`.
   - **Body → raw → JSON**:
     ```json
     { "email": "aluno.teste@fpsf.edu.br", "senha": "12345678", "device_name": "postman-tipi05" }
     ```
   - **Tests** (em versões novas, **Scripts → Post-response**):
     ```js
     pm.test('Login com sucesso (200)', function () {
         pm.response.to.have.status(200);
     });
     const corpo = pm.response.json();
     if (corpo.success && corpo.data && corpo.data.token) {
         pm.collectionVariables.set('token', corpo.data.token);
     }
     ```
   - **Send**. A variável `token` da collection passa a ter o valor (aba Variables, coluna
     *Current value*).
4. **Consultar perfil**: `GET {{base_url}}/usuario`, header `Accept: application/json`, aba
   **Authorization → Type: Bearer Token**, campo Token = `{{token}}`.
5. Repita o passo 4 para `PUT` e `PATCH {{base_url}}/usuario` e `PUT {{base_url}}/usuario/senha`
   (com Body raw JSON) e `POST {{base_url}}/auth/logout`. O script de Tests do logout limpa a
   variável `token`.
6. Para conferir o `401`: rode **Logout** e depois **Consultar perfil** de novo.
7. **Exportar**: menu `...` da collection → **Export** → Collection v2.1. Antes, apague o
   *Current value* da variável `token` (o arquivo não pode levar um token real).

## 10. Testes automatizados

`tests/Feature/ApiV1AutenticacaoTest.php` (SQLite em memória) cobre: login com token e hash
gravado no banco; um token por dispositivo; senha errada e e-mail inexistente com a mesma
resposta; validação; conta desativada (v1 e site); token ausente, inválido e vencido (7 dias);
PUT ignorando `papel`/`turma_id`/`ativo`/`senha`; PUT exigindo os dois campos; PATCH parcial;
e-mail único; troca de senha revogando os outros tokens; logout; cookie de sessão sem efeito na
v1; `/api/login` do site ainda exigindo CSRF (e a v1 não); `turma` como texto em `/api/eu`, nas
reservas e no painel; limite de 10 logins por minuto.

```bash
docker compose exec -u app app php artisan test
```

## 11. Próximas rotas com token

Todas dentro do grupo `auth:sanctum` de `routes/api_v1.php`, usando `$request->user()` para saber
quem é o aluno (nunca um `usuario_id` vindo do corpo):

| Método | Endpoint | Tabela |
|---|---|---|
| GET / POST / DELETE | `/api/v1/favoritos`, `/api/v1/favoritos/{livro}` | `favoritos` |
| GET | `/api/v1/reservas` (minhas reservas) | `reservas` |
| POST | `/api/v1/reservas`, `/api/v1/reservas/{id}/cancelar` | `reservas` |
| POST | `/api/v1/depoimentos` (fica `pendente` até a coordenação aprovar) | `depoimentos` |
| POST | `/api/v1/contato` | `mensagens_contato` |
