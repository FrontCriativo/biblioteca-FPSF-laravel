# Publicação no Plesk (servidor da escola)

Passo a passo para colocar a Biblioteca Virtual FPSF (Laravel 13, PHP 8.4, SQLite, Sanctum) no
servidor Plesk da escola e para atualizar o site nas próximas versões. Segue o roteiro da aula
"Atualização da API no Servidor e Testes", adaptado a este projeto.

> Nada de senha, token ou conteúdo do `.env` de produção neste arquivo, em commits ou em prints.

## Diferenças em relação ao guia da aula

- O Laravel fica na **raiz** do repositório (não há pasta `src/`).
- O banco é **SQLite** em `database/database.sqlite`, fora do Git (`database/.gitignore`). Não há
  phpMyAdmin: as consultas são feitas com `php artisan db:show` / `db:table` ou `php -r` com PDO.
- O site precisa ser servido pela **raiz do subdomínio**. O front (`public/js/api.js`) chama `/api/...`,
  a página `/api/documentacao` usa `/css/style.css` e o redirecionamento de visitante vai para
  `/login.html`, todos com caminho absoluto. Numa subpasta (como `/davilla/`) o site quebra.
- O SSH do Plesk é chroot e não tem `composer`: as dependências são instaladas pelo painel.
- As capas enviadas pela coordenação são salvas em `public/assets/` no servidor e aparecem como
  arquivos não rastreados no `git status`. Isso é esperado: **não apague**.

## Regras

- No servidor, **nunca**: `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`,
  `git reset --hard`, `git clean`, `git push --force`.
- `key:generate` só **uma vez**, na primeira publicação. Rodar de novo invalida sessões e dados
  criptografados.
- `db:seed` só na primeira publicação e de forma consciente (veja a Fase 4).
- Nenhum `migrate --force` com banco já em uso sem **backup** e sem **ensaio numa cópia**.

---

## Variáveis (preencha uma vez por sessão do Terminal SSH)

Os comandos abaixo usam estas variáveis. Ao abrir o Terminal SSH do Plesk, cole este bloco com o
subdomínio real (o caminho do webspace é o que aparece no `pwd` ao abrir o terminal):

```bash
SUB=seunome.tipi5.smpsistema.com.br                       # subdomínio criado no Plesk
WEB=/var/www/vhosts/smpsistema.com.br/$SUB                # pasta do subdomínio (confira com pwd)
PROJ=$WEB/biblioteca-FPSF-laravel                         # pasta do projeto
PHP=/opt/plesk/php/8.4/bin/php
```

---

## Fase 0: criar o subdomínio

No Plesk, em **Websites e Domínios**, use **Adicionar subdomínio** para criar o subdomínio (ou peça ao
professor). Anote o nome completo. A pasta e o document root são ajustados na Fase 2.

## Fase 2: acesso e Git no servidor

```bash
pwd
cd "$WEB" && ls -la
$PHP -v                                                   # precisa ser 8.4.1 ou mais nova
$PHP -m | grep -Ei "pdo_sqlite|sqlite3|mbstring|openssl|fileinfo"
```

O `composer.lock` usa pacotes do Symfony que exigem **PHP ≥ 8.4.1**. Sem `pdo_sqlite` ou com PHP
8.4.0, **pare**.

### Primeira publicação

```bash
cd "$WEB"
git clone https://github.com/FrontCriativo/biblioteca-FPSF-laravel.git
cd "$PROJ" && ls -la                                      # artisan, app/, public/, composer.json
git log --oneline -1
```

No Plesk, em **Websites e Domínios**, abra o subdomínio e depois **Configurações de hospedagem**:

- **Document root:** `<subdomínio>/biblioteca-FPSF-laravel/public` (a pasta `public` do projeto);
- **Versão do PHP:** 8.4.

### Atualização (próximas versões)

```bash
cd "$PROJ"
git status
git diff --summary
```

- Se só houver mudança de modo (`100644 => 100755`): `git config core.fileMode false`.
- Arquivos não rastreados em `public/assets/` são capas enviadas pela coordenação. Isso é esperado.
- Qualquer outro arquivo modificado: **pare** e descubra quem mudou antes de continuar.

```bash
git fetch origin
git log --oneline HEAD..origin/main                       # o que vai entrar
git status -sb
git pull --ff-only origin main
git log --oneline -5
```

## Fase 3: dependências, .env, permissões e cache

### Composer (pelo painel)

No Plesk, abra o subdomínio e vá em **PHP Composer**. Aponte para a pasta
`<subdomínio>/biblioteca-FPSF-laravel` e clique em **Instalar**. Instalar respeita o `composer.lock`;
nunca use **Atualizar**. Se houver opção de ambiente, escolha produção (sem dependências de dev).

```bash
ls -la vendor/laravel/sanctum
```

### .env

Primeira publicação:

```bash
cd "$PROJ"
cp .env.example .env
```

Edite o `.env` pelo **Gerenciador de arquivos** do Plesk e ajuste estas chaves (o resto fica como no
`.env.example`):

```dotenv
APP_NAME="Biblioteca FPSF"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seunome.tipi5.smpsistema.com.br

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

LOG_LEVEL=warning

DB_CONNECTION=sqlite
# Sem DB_DATABASE: o padrão é database/database.sqlite.

SESSION_SECURE_COOKIE=true
```

Gere a chave **uma única vez**:

```bash
$PHP artisan key:generate
```

Na atualização, só confira as chaves, sem exibir segredos:

```bash
grep -E "^(APP_ENV|APP_DEBUG|APP_URL|DB_CONNECTION)=" .env
```

### Banco e permissões

```bash
cd "$PROJ"
[ -f database/database.sqlite ] || touch database/database.sqlite   # só cria se não existir
chmod 775 database public/assets
chmod -R 775 storage bootstrap/cache
chmod 664 database/database.sqlite
```

O SQLite precisa de escrita na **pasta** `database/`, não só no arquivo: ele cria arquivos de
journal ao lado do banco.

### Cache

```bash
$PHP artisan optimize:clear
```

Rode isso antes de qualquer comando com `DB_DATABASE=...`: com config em cache, a variável é ignorada.

Abra `https://$SUB/api/v1/status`. Esperado:

```json
{"success":true,"data":{"aplicacao":"Biblioteca Virtual FPSF","api":"v1","status":"online", ...}}
```

## Fase 4: migrations com segurança

```bash
$PHP artisan migrate:status
```

Migrations do projeto: `0001_01_01_000000_create_usuarios_table`, `0001_01_01_000001_create_cache_table`,
`0001_01_01_000002_create_jobs_table`, `2026_10_08_000000_create_acervo_tables` e as nove de
`2026_10_13_*` (turmas, turma_id, favoritos, depoimentos, mensagens_contato, reservas para
empréstimo, remoção de `usuarios.turma`, `usuarios.ativo` e `personal_access_tokens`).

- **Primeira publicação:** todas `Pending`. Isso é normal.
- **Atualização:** as que já rodaram aparecem como `Ran` e só as novas como `Pending`.
- Se alguma tabela já existe mas a migration dela está `Pending`, **pare**. Nesse caso é preciso
  registrar a baseline na tabela `migrations` antes de migrar.

### Primeira publicação (banco vazio)

Não há dados a proteger, então não há backup nem ensaio:

```bash
$PHP artisan migrate --force
$PHP artisan migrate:status                               # todas Ran
$PHP artisan migrate --force                              # esperado: INFO  Nothing to migrate.
```

**Dados iniciais (opcional).** O `DatabaseSeeder` cria matérias, livros, exemplares, turmas e
depoimentos. Ele também cria usuários de exemplo com **senhas fracas e públicas** (estão no código):
`admin@fpsf.edu.br`, `beatriz.lima@fpsf.edu.br` e `rafael.souza@fpsf.edu.br`. Se for rodar:

```bash
$PHP artisan db:seed --force
$PHP artisan tinker
```

Logo em seguida, no tinker, troque a senha do admin e desative os alunos de exemplo. Desativar é
melhor que apagar, porque os depoimentos de exemplo apontam para eles:

```php
App\Models\Usuario::where('email', 'admin@fpsf.edu.br')->first()->update(['senha' => 'SENHA-FORTE-AQUI']);
App\Models\Usuario::whereIn('email', ['beatriz.lima@fpsf.edu.br', 'rafael.souza@fpsf.edu.br'])->update(['ativo' => false]);
exit
```

O cast `hashed` do model faz o bcrypt da senha. Não digite essa senha em nenhum arquivo.

### Atualização (banco com dados): backup, simulação, ensaio e produção

**1. Backup.** A pasta `backup/` fica fora de `public/` e está no `.gitignore`:

```bash
cd "$PROJ"
mkdir -p backup
$PHP -r '(new PDO("sqlite:database/database.sqlite"))->prepare("VACUUM INTO ?")->execute(["backup/biblioteca-antes-deploy-".date("Y-m-d-His").".sqlite"]);'
ls -la backup/                                            # o arquivo novo precisa ter mais de 0 bytes
```

**2. Simulação.** Responda `Yes` à pergunta de produção:

```bash
$PHP artisan migrate --pretend
```

No SQLite, o `--pretend` **não** mostra corretamente as migrations que recriam tabelas ou movem
dados, porque ele não executa as consultas de leitura. A validação de verdade é o ensaio.

**3. Ensaio numa cópia.** Troque o nome pelo do backup recém-criado:

```bash
cp backup/biblioteca-antes-deploy-AAAA-MM-DD-HHMMSS.sqlite database/ensaio.sqlite
E="DB_DATABASE=$PROJ/database/ensaio.sqlite"
env $E $PHP artisan db:show                               # precisa mostrar ensaio.sqlite
env $E $PHP artisan migrate --force
env $E $PHP artisan migrate:status
env $E $PHP artisan db:show --counts                      # compare usuarios/livros/exemplares/reservas
$PHP -r '$p=new PDO("sqlite:database/ensaio.sqlite");
  echo "FK: "; print_r($p->query("PRAGMA foreign_key_check")->fetchAll());
  echo "indice: ", $p->query("SELECT count(*) FROM sqlite_master WHERE name=\"reservas_exemplar_aberta_unique\"")->fetchColumn(), PHP_EOL;
  echo "alunos sem turma: ", $p->query("SELECT count(*) FROM usuarios WHERE papel=\"aluno\" AND turma_id IS NULL")->fetchColumn(), PHP_EOL;'
rm database/ensaio.sqlite
```

Esperado: as contagens iguais às de antes, `FK` vazio, `indice: 1` e nenhum aluno que tinha turma
ficando sem `turma_id`.

**4. Produção:**

```bash
$PHP artisan migrate --force
$PHP artisan migrate:status                               # todas Ran
$PHP artisan migrate --force                              # INFO  Nothing to migrate.
```

Depois, repita as conferências do ensaio, agora apontando para `database/database.sqlite`.

### Validar tabelas

```bash
$PHP artisan db:show --counts
$PHP artisan db:table personal_access_tokens
$PHP artisan db:table favoritos
$PHP artisan db:table depoimentos
$PHP artisan db:table turmas
```

## Fase 5: rotas e testes da API em produção

```bash
$PHP artisan route:list --path=api/v1
```

São esperadas 12 rotas: `GET status`, `GET depoimentos`, `GET materias`, `GET materias/{materia}/livros`,
`GET livros`, `GET livros/{livro}`, `POST auth/login`, `POST auth/logout`, `GET usuario`, `PUT usuario`,
`PATCH usuario` e `PUT usuario/senha`.

### Usuário de teste

```bash
$PHP artisan tinker
```

```php
App\Models\Usuario::create(['nome' => 'Aluno Teste API', 'email' => 'aluno.teste@fpsf.edu.br', 'senha' => 'SENHA-FORTE-AQUI', 'papel' => 'aluno']);
exit
```

Para conferir sem expor a senha:

```bash
$PHP -r '$p=new PDO("sqlite:database/database.sqlite"); print_r($p->query("SELECT id, substr(senha,1,4) AS prefixo, length(senha) AS tamanho, ativo FROM usuarios WHERE email=\"aluno.teste@fpsf.edu.br\"")->fetch(PDO::FETCH_ASSOC));'
```

Esperado: `prefixo = $2y$`, `tamanho = 60` e `ativo = 1`.

### Postman

Importe `docs/postman/biblioteca-fpsf-v1.postman_collection.json` e crie o Environment **Produção**
com `base_url = https://<subdomínio>/api/v1` e `token` vazio.

| # | Requisição | Esperado |
|---|---|---|
| 1 | `POST {{base_url}}/auth/login`, com `Content-Type` e `Accept: application/json`, body `{"email": "...", "senha": "...", "device_name": "postman-producao"}` | 200, `success: true`, `mensagem`, `data.token` |
| 2 | `GET {{base_url}}/usuario` com Bearer Token | 200, dados do usuário sem senha |
| 3 | `POST {{base_url}}/auth/logout` com Bearer Token | 200; o token some de `personal_access_tokens` |
| 4 | `GET {{base_url}}/usuario` com o mesmo token | 401 em JSON: `{"success":false,"erro":...}` |

Se o teste 4 vier em HTML ou com 200, desative **Automatically follow redirects** no Postman e confira
se o `public/.htaccess` está sendo aplicado. O cabeçalho `Authorization` depende da regra
`HTTP_AUTHORIZATION` dele.

Para contar tokens antes e depois do logout:

```bash
$PHP -r 'echo (new PDO("sqlite:database/database.sqlite"))->query("SELECT count(*) FROM personal_access_tokens")->fetchColumn(), PHP_EOL;'
```

### Site

Teste no navegador: `login.html`, catálogo, reserva, painel da coordenação (`admin.html`) e
`/api/documentacao`.

Ao final, desative o usuário de teste (`ativo = false`) ou apague-o, se ele não tiver reservas.

## Fase 6: agendador

No Plesk, em **Tarefas agendadas**, adicione uma tarefa que roda **a cada minuto** (troque pelo
caminho real do projeto):

```bash
/opt/plesk/php/8.4/bin/php /var/www/vhosts/smpsistema.com.br/<subdomínio>/biblioteca-FPSF-laravel/artisan schedule:run
```

Ela executa:

- `reservas:expirar` a cada 15 minutos, cancelando as pré-reservas que passaram do prazo;
- `sanctum:prune-expired --hours=24` uma vez por dia, apagando os tokens vencidos.

Para conferir:

```bash
$PHP artisan schedule:list
```

### Cache de produção (opcional, depois de tudo validado)

```bash
$PHP artisan config:cache && $PHP artisan route:cache
```

Com cache ligado, uma mudança no `.env` só vale depois de `$PHP artisan optimize:clear`.

---

## Rotina de atualização (próximas versões)

1. Cole o bloco de **Variáveis** e rode `cd "$PROJ"`.
2. `git status`: só `public/assets/` pode aparecer como não rastreado.
3. `git fetch origin` e depois `git log --oneline HEAD..origin/main`.
4. `git pull --ff-only origin main`.
5. Se `composer.json` ou `composer.lock` mudaram: **PHP Composer** do Plesk e depois **Instalar**.
6. `$PHP artisan optimize:clear`.
7. `$PHP artisan migrate:status`.
8. Se houver migration `Pending`: **backup**, `migrate --pretend`, **ensaio na cópia** (obrigatório se
   alguma migration altera ou recria tabela) e só então `migrate --force`.
9. `$PHP artisan optimize:clear` (e `config:cache` / `route:cache`, se usar).
10. Permissões: `chmod 775 database public/assets`, `chmod -R 775 storage bootstrap/cache` e
    `chmod 664 database/database.sqlite`.
11. Testes rápidos: `/api/v1/status`, login pelo site e login pela API (Postman).
