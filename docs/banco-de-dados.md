# Levantamento Inicial do Banco de Dados — Biblioteca Virtual FPSF

Roteiro da aula "Levantamento Inicial do Banco de Dados" (feito para a Confeitaria DaVilla
em MySQL), adaptado à Biblioteca Virtual da Escola Prof. Francisco Pereira de Souza Filho
(Laravel 13, PHP 8.4, SQLite, Docker).

Levantamento feito em 13/10/2026.

## Perguntas iniciais

Antes de criar qualquer tabela nova, precisamos responder:

1. Quais tabelas já existem no banco?
2. Quais são do Laravel e quais são do negócio da biblioteca?
3. Como cada tabela do negócio está estruturada (colunas, tipos, índices, chaves)?
4. Como as tabelas se relacionam (o mapa do banco)?
5. O que falta no banco para as próximas funcionalidades da API?

## Diferenças em relação ao roteiro da DaVilla

| Roteiro (DaVilla) | Biblioteca FPSF |
|---|---|
| Serviços `mysql`, `php`, `nginx` | Um único serviço `app` (container `biblioteca-fpsf`, porta 8000) |
| `docker compose exec php php artisan ...` | `docker compose exec -u app app php artisan ...` |
| MySQL, banco `davilla`, `SHOW TABLES` / `DESCRIBE` | SQLite em `database/database.sqlite`; `php artisan db:show` e `php artisan db:table <tabela>` |
| Prefixo `tbl_` e colunas com sufixo (`nome_cliente`) | Tabelas sem prefixo, em português; colunas sem sufixo (`nome`, `email`) |
| `created_at` / `updated_at` | `criado_em` / `criada_em` concordando com o nome da tabela (`usuarios.criado_em`, `reservas.criada_em`) |
| Tabela `users` do Laravel | Tabela própria `usuarios` (model `App\Models\Usuario`) |

## Parte 1 — Levantamento (somente leitura)

### 1. Verificar os containers

```bash
docker compose ps
```

```
NAME              IMAGE                         COMMAND                  SERVICE   STATUS          PORTS
biblioteca-fpsf   biblioteca-fpsf-laravel-app   "entrypoint.sh apach…"   app       Up 43 minutes   0.0.0.0:8000->80/tcp
```

O serviço `app` precisa estar `Up`. Se não estiver: `docker compose up -d`.

> O `docker/entrypoint.sh` roda `php artisan migrate --force` toda vez que o container
> sobe. Por isso o backup tem que ser feito **antes** de reiniciar o container com
> migrations novas na pasta.

### 2. Descobrir quais tabelas existem

No roteiro da DaVilla o comando era `mysql -uuser -puser davilla` seguido de `SHOW TABLES`.
Aqui o equivalente é:

```bash
docker compose exec -u app app php artisan db:show --counts
```

| Parte | O que faz |
|---|---|
| `docker compose exec` | Executa um comando dentro de um container que já está rodando |
| `-u app` | Roda como o usuário `app` (mesmo UID do seu usuário no WSL), para que arquivos criados não fiquem com dono `root` |
| `app` | Nome do **serviço** no `docker-compose.yml` (no roteiro era `php` ou `mysql`) |
| `php artisan` | A ferramenta de linha de comando do Laravel |
| `db:show` | Mostra o banco configurado no `.env`: tipo, versão, arquivo e a lista de tabelas |
| `--counts` | Acrescenta a quantidade de linhas de cada tabela |

Não há usuário nem senha como no `mysql -uuser -puser`: o SQLite é um arquivo, e o Laravel
descobre qual pelo `.env` (`DB_CONNECTION=sqlite`, `DB_DATABASE` vazio = `database/database.sqlite`).

**Problema encontrado:** na imagem atual o `--counts` falha, porque a extensão PHP `intl`
não está instalada:

```
The "intl" PHP extension is required to use the [format] method.
```

Sem o `--counts` o comando funciona e lista as tabelas. As contagens foram feitas com PDO,
direto no arquivo (somente leitura):

```bash
docker compose exec -u app app php artisan db:show
docker compose exec -u app app php -r '$p = new PDO("sqlite:database/database.sqlite");
  foreach ($p->query("SELECT name FROM sqlite_master WHERE type = \"table\" ORDER BY name") as $r)
    echo str_pad($r[0], 22), $p->query("SELECT COUNT(*) FROM \"$r[0]\"")->fetchColumn(), PHP_EOL;'
```

(Instalar `intl` no `Dockerfile` resolve o `--counts`; ficou para outra etapa.)

### 3. Resultado encontrado

```
SQLite ............ 3.46.1
Database .......... database/database.sqlite
Tables ............ 12
```

**Tabelas do Laravel** (infraestrutura do framework):

| Tabela | Linhas | Para que serve |
|---|---:|---|
| `cache` | 0 | Cache em banco |
| `cache_locks` | 0 | Travas do cache |
| `failed_jobs` | 0 | Tarefas de fila que falharam |
| `job_batches` | 0 | Lotes de tarefas de fila |
| `jobs` | 0 | Fila de tarefas |
| `migrations` | 4 | Quais migrations já rodaram e em que lote |
| `sessions` | 13 | Sessões de login |

**Tabelas do negócio da biblioteca:**

| Tabela | Linhas |
|---|---:|
| `usuarios` | 3 |
| `materias` | 10 |
| `livros` | 8 |
| `exemplares` | 23 |
| `reservas` | 0 |

A `sqlite_sequence` também aparece no `sqlite_master`, mas é interna do SQLite (guarda o
último `id` de cada tabela com autoincremento) e não entra em nenhum grupo.

Os números conferem com a referência: 12 tabelas; usuarios 3, materias 10, livros 8,
exemplares 23, reservas 0.

### 4. Investigar a estrutura

No MySQL seria `DESCRIBE tbl_clientes;`. Aqui:

```bash
docker compose exec -u app app php artisan db:table usuarios
```

Como ler a saída, em paralelo com as colunas do `DESCRIBE`:

| `db:table` | `DESCRIBE` (MySQL) | Significado |
|---|---|---|
| Nome da coluna (início da linha) | `Field` | Nome da coluna |
| Tipo (fim da linha) | `Type` | `integer`, `varchar`, `text`, `datetime`... |
| `nullable` | `Null` = YES | Aceita ficar vazia (NULL). Sem `nullable` = obrigatória |
| Valor no meio da linha (`'aluno'`, `CURRENT_TIMESTAMP`) | `Default` | Valor usado quando o INSERT não informa a coluna |
| `autoincrement` | `Extra` = auto_increment | O banco gera o número sozinho |
| Bloco **Index** (`primary`, `unique`, comum) | `Key` = PRI / UNI / MUL | Chave primária, valor único ou índice de busca |
| Bloco **Foreign Key** | (não aparece no DESCRIBE; seria `SHOW CREATE TABLE`) | Coluna que aponta para outra tabela e o que acontece ao apagar/alterar o pai |

> O SQLite não guarda o tamanho do `varchar` (`string('papel', 10)` aparece só como
> `varchar`) e não recusa texto maior. O limite de tamanho tem que ser validado na API
> (Form Requests).

#### usuarios

| Coluna | Tipo | Nulo | Padrão | Observação |
|---|---|---|---|---|
| id | integer | não | | PK, autoincremento |
| nome | varchar | não | | |
| email | varchar | não | | único, sempre em minúsculas |
| senha | varchar | não | | hash bcrypt |
| papel | varchar(10) | não | `'aluno'` | `aluno` \| `admin` |
| turma | varchar(40) | sim | | texto livre ("8º ano B") — **removida na Parte 6** |
| criado_em | datetime | não | CURRENT_TIMESTAMP | |

Índices: `primary (id)`, `usuarios_email_unique (email)`.

> Estrutura no dia do levantamento. A estrutura atual (com `turma_id` e `ativo`, sem
> `turma`) está na [Parte 6](#parte-6--finalização-da-turma-e-status-do-usuário).

#### materias

| Coluna | Tipo | Nulo | Observação |
|---|---|---|---|
| slug | varchar(60) | não | PK (texto, não número) |
| nome | varchar | não | |
| icone | varchar | não | arquivo em `public/assets/` |

#### livros

| Coluna | Tipo | Nulo | Padrão | Observação |
|---|---|---|---|---|
| id | integer | não | | PK, autoincremento |
| slug | varchar | não | | único, usado na URL |
| titulo | varchar | não | | |
| autor | varchar | não | | |
| materia_slug | varchar(60) | não | | FK → materias.slug, indexada |
| capa | varchar | sim | | |
| sinopse | text | sim | | |
| paginas | integer | sim | | |
| idioma | varchar(60) | não | `'Português'` | |
| criado_em | datetime | não | CURRENT_TIMESTAMP | |

#### exemplares

Um livro (a obra) tem vários exemplares (cópias físicas na prateleira).

| Coluna | Tipo | Nulo | Padrão | Observação |
|---|---|---|---|---|
| id | integer | não | | PK, autoincremento |
| livro_id | integer | não | | FK → livros.id (cascade) |
| codigo | varchar | não | | único (etiqueta do exemplar) |
| estado | varchar(20) | não | `'ok'` | `ok` \| `manutencao` |

#### reservas

| Coluna | Tipo | Nulo | Padrão | Observação |
|---|---|---|---|---|
| id | integer | não | | PK, autoincremento |
| exemplar_id | integer | não | | FK → exemplares.id (cascade) |
| usuario_id | integer | não | | FK → usuarios.id (cascade) |
| status | varchar(20) | não | `'ativa'` | `ativa` \| `retirada` \| `devolvida` \| `cancelada` |
| criada_em | datetime | não | CURRENT_TIMESTAMP | |
| expira_em | datetime | não | | prazo para retirar |
| encerrada_em | datetime | sim | | devolução ou cancelamento |

Índices: `primary (id)`, `reservas_status_expira_em_index (status, expira_em)` e o índice
único parcial `reservas_exemplar_aberta_unique`.

#### As 4 chaves estrangeiras

| De | Para | Ao apagar o pai |
|---|---|---|
| `livros.materia_slug` | `materias.slug` | bloqueia (no action) |
| `exemplares.livro_id` | `livros.id` | apaga os exemplares (cascade) |
| `reservas.exemplar_id` | `exemplares.id` | apaga as reservas (cascade) |
| `reservas.usuario_id` | `usuarios.id` | apaga as reservas (cascade) |

#### O índice único parcial

Um exemplar só pode ter **uma** reserva aberta (`ativa` ou `retirada`). Quem garante isso é
o próprio banco, mesmo com duas requisições chegando ao mesmo tempo:

```bash
docker compose exec -u app app php -r '$s = (new PDO("sqlite:database/database.sqlite"))->prepare("SELECT sql FROM sqlite_master WHERE name = ?"); $s->execute(["reservas_exemplar_aberta_unique"]); echo $s->fetchColumn(), PHP_EOL;'
```

```sql
CREATE UNIQUE INDEX reservas_exemplar_aberta_unique
                     ON reservas (exemplar_id)
                  WHERE status IN ('ativa', 'retirada')
```

O `WHERE` faz o índice valer só para as reservas abertas: o mesmo exemplar pode ter várias
reservas `devolvida` ou `cancelada` no histórico. O MySQL não tem índice parcial; lá a regra
fica com o `lockForUpdate()` em `App\Services\ServicoDeReservas`.

#### Ciclo de vida da reserva

```
            retirar            devolver
  ativa ───────────► retirada ───────────► devolvida
    │                   │
    │ cancelar /        │ cancelar
    │ prazo vencido     │
    ▼                   ▼
  cancelada          cancelada
```

- A reserva nasce `ativa`, com `expira_em = agora + 3 dias`
  (`App\Services\ServicoDeReservas::DIAS_PARA_RETIRAR = 3`).
- Se o aluno não retira no prazo, ela vira `cancelada` automaticamente
  (middleware `ExpirarReservasVencidas` ou `php artisan reservas:expirar`).
- `encerrada_em` é preenchido ao devolver ou cancelar.

### 5. Mapa do banco

```
Matéria ── possui vários livros ── cada livro possui vários exemplares ── cada exemplar
recebe reservas ── cada reserva pertence a um usuário (aluno)

materias (slug) 1───N livros (materia_slug)
livros (id)     1───N exemplares (livro_id)
exemplares (id) 1───N reservas (exemplar_id)   ← no máximo 1 aberta por exemplar
usuarios (id)   1───N reservas (usuario_id)
```

## Parte 2 — Novas necessidades identificadas

Essas alterações não fazem parte da estrutura original. Elas representam a evolução do banco
para as próximas funcionalidades da API. Todas seguem as convenções do projeto: `id()`,
`foreignId()->constrained()`, `string` com tamanho e datas `criado_em`/`atualizado_em` com
`useCurrent()`.

### 1. Favoritos ("Quero ler")

O aluno marca livros para encontrar depois.

| Coluna | Tipo | Observação |
|---|---|---|
| id | integer | PK |
| usuario_id | integer | FK → usuarios.id, cascade |
| livro_id | integer | FK → livros.id, cascade |
| criado_em | datetime | CURRENT_TIMESTAMP |

Índice único `(usuario_id, livro_id)`: o mesmo livro não pode ser favoritado duas vezes.

### 2. Depoimentos

A página `public/depoimentos.html` já tem o formulário (nome, turma ou função, estrelas,
texto), mas hoje nada é gravado.

| Coluna | Tipo | Observação |
|---|---|---|
| id | integer | PK |
| usuario_id | integer, nulo | FK → usuarios.id, **set null** (professores e responsáveis não têm login) |
| nome | varchar(80) | |
| descricao_autor | varchar(60), nulo | "Aluna do 8º ano", "Professor de Ciências". O campo é opcional no formulário |
| texto | text | |
| nota | tinyint, nulo | 1 a 5 estrelas; **NULL = sem estrelas** (o formulário permite enviar sem avaliar) |
| status | varchar(10) | padrão `pendente`; `pendente` \| `aprovado` \| `recusado` (aprovação da coordenação) |
| criado_em, atualizado_em | datetime | CURRENT_TIMESTAMP |

Índice em `status` (o site só lista os aprovados).

### 3. Turmas

Equivalente à tabela de endereços da DaVilla. Hoje a turma é texto livre em `usuarios.turma`
("8º ano B"), o que permite grafias diferentes para a mesma turma e impede listar alunos por turma.

`turmas`:

| Coluna | Tipo | Observação |
|---|---|---|
| id | integer | PK |
| nome | varchar(40) | único |
| ano_letivo | integer (smallint) | |
| ativa | boolean | padrão `true` |
| criada_em, atualizada_em | datetime | CURRENT_TIMESTAMP |

Em migration separada, `usuarios.turma_id` (FK → turmas.id, nulo, **set null**) e a migração
dos dados: uma turma para cada valor distinto de `usuarios.turma` (sem espaços nas pontas),
com o ano letivo corrente, e o `turma_id` preenchido. O `down()` remove a coluna e as turmas
que vieram do texto.

A coluna `usuarios.turma` **continua existindo** por enquanto, porque `UsuarioResource`,
`ReservaResource` e `NovoUsuarioRequest` ainda a usam. A remoção fica para uma migration
futura, depois de adaptar o código.

> **Cuidado no SQLite:** adicionar uma chave estrangeira faz o Laravel recriar a tabela
> `usuarios` (cria `__temp__usuarios`, copia os dados, apaga a antiga e renomeia). Com as
> chaves estrangeiras ligadas, o `DROP` da antiga apagaria as reservas em cascata. Isso não
> acontece porque o Laravel desliga as chaves (`PRAGMA foreign_keys = off`) durante a
> recriação e, no SQLite, não envolve a migration numa transação (onde esse PRAGMA seria
> ignorado). Foi testado numa cópia com reservas: nenhuma se perdeu.

### 4. Mensagens de contato

O formulário de `public/contato.html` é só um protótipo.

| Coluna | Tipo | Observação |
|---|---|---|
| id | integer | PK |
| usuario_id | integer, nulo | FK → usuarios.id, set null (preenchido se estiver logado) |
| nome | varchar(80) | |
| turma | varchar(40), nulo | "Turma ou função" do formulário |
| email | varchar(120) | |
| assunto | varchar(30) | Sugestão de livro \| Dúvida sobre empréstimo \| Problema de acesso \| Outro assunto |
| mensagem | text | |
| status | varchar(12) | padrão `nova`; `nova` \| `lida` \| `respondida` |
| criada_em, atualizada_em | datetime | CURRENT_TIMESTAMP |

Índice em `status`.

### 5. Alterações em `reservas` (empréstimo)

Equivalente ao `alter_tbl_vendas_for_app`. Hoje, quando o livro é retirado, não fica
registrado quando foi retirado nem até quando deve voltar.

| Coluna nova | Tipo | Observação |
|---|---|---|
| retirada_em | datetime, nulo | quando o aluno pegou o livro |
| devolver_ate | date, nulo | prazo de devolução |
| renovacoes | tinyint | padrão 0 |
| observacao | text, nulo | uso da coordenação |

Índice `(status, devolver_ate)` para listar atrasos (`status = 'retirada' AND devolver_ate < hoje`).

**Regra de negócio** (o código vem depois): quando `status = retirada`, a reserva precisa ter
`devolver_ate`. O `encerrada_em` continua marcando devolução e cancelamento.

No SQLite essa migration usa só `ADD COLUMN`, `CREATE INDEX` e `DROP COLUMN`, então a tabela
**não** é recriada: o índice parcial e as chaves com cascade continuaram iguais (conferido com
`PRAGMA index_list(reservas)` e `PRAGMA foreign_key_list(reservas)`).

### Mapa depois da evolução

```
Matéria ── possui vários livros ── cada livro possui vários exemplares

Usuário (aluno)
├── pertence a uma turma
├── favorita vários livros
├── pode enviar depoimentos
├── pode enviar mensagens de contato
└── faz reservas
      ├── de um exemplar de um livro
      ├── tem prazo para retirar (expira_em)
      ├── registra retirada (retirada_em) e prazo de devolução (devolver_ate)
      ├── pode ser renovada (renovacoes)
      └── pode ter observação da coordenação

turmas (id)     1───N usuarios (turma_id)
usuarios (id)   1───N favoritos (usuario_id)    N───1 livros (livro_id)
usuarios (id)   1───N depoimentos (usuario_id, opcional)
usuarios (id)   1───N mensagens_contato (usuario_id, opcional)
usuarios (id)   1───N reservas (usuario_id)     N───1 exemplares (exemplar_id)
```

### Migrations criadas

Criadas uma por comando, nesta ordem:

```bash
docker compose exec -u app app php artisan make:migration create_turmas_table
docker compose exec -u app app php artisan make:migration add_turma_id_to_usuarios_table
docker compose exec -u app app php artisan make:migration create_favoritos_table
docker compose exec -u app app php artisan make:migration create_depoimentos_table
docker compose exec -u app app php artisan make:migration create_mensagens_contato_table
docker compose exec -u app app php artisan make:migration alter_reservas_for_emprestimo
```

O relógio do container deu timestamps fora de ordem (a de turmas saiu depois da de
favoritos), então os arquivos foram renomeados, antes de rodarem em qualquer banco, para
`2026_10_13_000001` … `000006`, na ordem acima.

## Parte 3 — Validação antes de tocar no banco principal

Sempre com `-e DB_DATABASE=...` apontando para um arquivo de teste, conferindo com `db:show`
antes de cada `migrate`.

1. **Banco vazio** (`database/biblioteca_migrations_teste.sqlite`): as 10 migrations rodaram
   no lote 1.
2. **Cópia com dados** (`VACUUM INTO database/biblioteca_copia_teste.sqlite`, mais 2 reservas
   fictícias só na cópia, uma `ativa` e uma `retirada`, para testar a recriação de `usuarios`):
   - contagens das tabelas antigas iguais (reservas continuaram 2);
   - turmas `8º ano B` e `9º ano A` criadas, `turma_id` preenchido (admin ficou NULL);
   - `PRAGMA foreign_key_check` vazio e `integrity_check` ok;
   - índice parcial presente com o `WHERE`, e ainda recusando reserva dupla;
   - apagar um usuário ainda apaga as reservas dele (cascade); apagar uma turma deixa o
     `turma_id` NULL (set null). Ambos dentro de transação desfeita.
3. **Reversão**: `migrate:rollback --step=6` voltou às 12 tabelas e 4 FKs, sem `turma_id`,
   com o índice parcial intacto; `migrate` de novo voltou às 16 tabelas e 9 FKs.
4. Os arquivos de teste foram apagados.

## Parte 4 — Aplicação no banco principal

1. Backup: `backup/biblioteca-antes-novas-tabelas-2026-10-13-121030.sqlite`
   (139 264 bytes, `integrity_check` ok).
2. `php artisan migrate`: as 6 migrations no **lote 2**.
3. Conferências repetidas no banco principal:

| Tabela | Antes | Depois |
|---|---:|---:|
| usuarios | 3 | 3 |
| materias | 10 | 10 |
| livros | 8 | 8 |
| exemplares | 23 | 23 |
| reservas | 0 | 0 |
| turmas | — | 2 |
| favoritos | — | 0 |
| depoimentos | — | 0 |
| mensagens_contato | — | 0 |
| **Tabelas** | **12** | **16** |
| **Chaves estrangeiras** | **4** | **9** |
| **Colunas** | **70** | **104** |

As 9 chaves estrangeiras:

```
depoimentos.usuario_id       -> usuarios.id    (set null)
exemplares.livro_id          -> livros.id      (cascade)
favoritos.livro_id           -> livros.id      (cascade)
favoritos.usuario_id         -> usuarios.id    (cascade)
livros.materia_slug          -> materias.slug  (no action)
mensagens_contato.usuario_id -> usuarios.id    (set null)
reservas.usuario_id          -> usuarios.id    (cascade)
reservas.exemplar_id         -> exemplares.id  (cascade)
usuarios.turma_id            -> turmas.id      (set null)
```

## Parte 5 — Seeder e testes

- O `DatabaseSeeder` agora cria as turmas `8º ano B` e `9º ano A`, liga os alunos de exemplo a
  elas (`turma_id`) e grava os 6 depoimentos que estavam fixos em `depoimentos.html`, com
  status `aprovado` e nota 5 (o da Beatriz Lima ligado ao usuário dela). Continua podendo rodar
  mais de uma vez sem duplicar. Foi testado num SQLite temporário, não no banco principal.
- `tests/Feature/EstruturaDoBancoTest.php` confere as novas tabelas e colunas, o índice parcial
  depois da alteração de `reservas`, o seeder e o índice único de favoritos.

## Parte 6 — Finalização da turma e status do usuário

Equivalente ao par `migrate_client_addresses_to_tbl_enderecos_cliente` +
`remove_address_columns_from_tbl_clientes` da aula da DaVilla: os dados já tinham sido copiados
para `turmas` (Parte 2, item 3); falta tirar a coluna antiga da tabela principal. Antes disso o
código precisou parar de usar `usuarios.turma`.

### 1. Conferência antes de remover

Todo usuário com `turma` preenchida precisa ter `turma_id` apontando para uma turma com o mesmo
nome:

```sql
SELECT u.id, u.turma, u.turma_id, t.nome
  FROM usuarios u LEFT JOIN turmas t ON t.id = u.turma_id
 WHERE trim(coalesce(u.turma, '')) <> ''
   AND (u.turma_id IS NULL OR t.nome IS NULL OR t.nome <> trim(u.turma));
```

Resultado: nenhuma linha (Beatriz → `8º ano B`, Rafael → `9º ano A`, admin sem turma).

### 2. O código passa a usar `turma_id`

| Arquivo | Mudança |
|---|---|
| `App\Models\Turma` (novo) | tabela `turmas`, `criada_em`/`atualizada_em`, cast `ativa` boolean, relação `usuarios()` |
| `App\Models\Usuario` | relação `turma()` (belongsTo), `$with = ['turma']`, `turma_id` e `ativo` no `$fillable` (no lugar de `turma`), cast `ativo` boolean |
| `UsuarioResource`, `ReservaResource` | a chave `turma` continua sendo o **nome** (`$this->turma?->nome`), então o front-end (`admin.js`) não muda |
| `NovoUsuarioRequest` + `Admin\UsuarioController@store` | continuam recebendo `turma` como texto; o controller faz `Turma::firstOrCreate(['nome' => ...], ['ano_letivo' => ano atual])` e grava `turma_id` |
| `DatabaseSeeder`, `UsuarioFactory` | criam a turma antes e usam `turma_id` |

O `$with = ['turma']` carrega a turma junto com o usuário em toda consulta. Sem ele, o
`Model::preventLazyLoading()` (ligado em desenvolvimento) lançaria exceção ao montar o JSON do
usuário na listagem do painel e nas reservas.

### 3. Migrations

```bash
docker compose exec -u app app php artisan make:migration remove_turma_from_usuarios_table --table=usuarios
docker compose exec -u app app php artisan make:migration add_ativo_to_usuarios_table --table=usuarios
```

| Migration | `up()` | `down()` |
|---|---|---|
| `2026_10_13_123859_remove_turma_from_usuarios_table` | remove `usuarios.turma` | recria `turma` (varchar 40, nula) e a preenche com `turmas.nome` via `turma_id` |
| `2026_10_13_123900_add_ativo_to_usuarios_table` | `boolean('ativo')->default(true)` (todos os usuários existentes ficam ativos) | remove `ativo` |

`ativo = false` é a conta desativada pela coordenação. A regra (não entrar no site nem gerar
token na API v1, erro 403) entra junto com a autenticação por token, na
[Parte 7](#parte-7--tokens-da-api-v1-personal_access_tokens).

O SQLite recriou a tabela `usuarios` ao remover a coluna (a definição da FK `turma_id` passou para
dentro do `CREATE TABLE`). Como na Parte 2, o Laravel desliga as chaves estrangeiras durante a
recriação, então nada foi apagado em cascata.

### 4. Validação

1. **Banco vazio** (`database/biblioteca_migrations_teste.sqlite`): `db:show` com 0 tabelas, as 12
   migrations rodaram no lote 1.
2. **Cópia do principal** (`VACUUM INTO database/biblioteca_copia_teste.sqlite`, mais linhas
   fictícias só na cópia: 2 reservas, 2 favoritos, 1 depoimento e 1 mensagem de contato, todos
   ligados a usuários):
   - `migrate` → `migrate:rollback --step=2` → `migrate`;
   - em cada etapa: contagens iguais (`reservas=2 depoimentos=1 favoritos=2 mensagens_contato=1`),
     `PRAGMA foreign_key_check` vazio, as 4 FKs que apontam para `usuarios` e a FK `turma_id`
     intactas, índice único `usuarios_email_unique` presente;
   - depois do rollback, `usuarios.turma` voltou com `8º ano B` / `9º ano A` / NULL.
3. **Banco principal**: backup em `backup/biblioteca-antes-remover-turma-2026-10-13-094020.sqlite`
   (`integrity_check` ok), depois `migrate` (lote 3) e as mesmas conferências.
4. Arquivos de teste apagados.

| | Antes | Depois |
|---|---|---|
| Colunas de `usuarios` | id, nome, email, senha, papel, **turma**, criado_em, turma_id | id, nome, email, senha, papel, criado_em, turma_id, **ativo** |
| usuarios / turmas | 3 / 2 | 3 / 2 |
| reservas / depoimentos / favoritos / mensagens_contato | 0 / 0 / 0 / 0 | 0 / 0 / 0 / 0 |
| `PRAGMA foreign_key_check` | vazio | vazio |
| FKs para `usuarios` | reservas (cascade), favoritos (cascade), depoimentos (set null), mensagens_contato (set null) | iguais |
| Índice único de e-mail | sim | sim |

#### usuarios (estrutura atual)

| Coluna | Tipo | Nulo | Padrão | Observação |
|---|---|---|---|---|
| id | integer | não | | PK, autoincremento |
| nome | varchar | não | | |
| email | varchar | não | | único, sempre em minúsculas |
| senha | varchar | não | | hash bcrypt |
| papel | varchar(10) | não | `'aluno'` | `aluno` \| `admin` |
| criado_em | datetime | não | CURRENT_TIMESTAMP | |
| turma_id | integer | sim | | FK → turmas.id (set null) |
| ativo | boolean | não | `1` | conta desativada não entra |

`tests/Feature/EstruturaDoBancoTest.php` agora confere que `usuarios.turma` não existe mais e que
`ativo` existe.

## Parte 7 — Tokens da API v1 (`personal_access_tokens`)

Criada pela migration `2026_10_13_124229_create_personal_access_tokens_table`, publicada pelo
`php artisan install:api` (Laravel Sanctum). Antes do `migrate`, backup em
`backup/biblioteca-antes-sanctum-2026-10-13-094555.sqlite` (`integrity_check` ok); depois, as
contagens das outras tabelas continuaram iguais e o `PRAGMA foreign_key_check` vazio.

| Coluna | Tipo | Observação |
|---|---|---|
| id | integer | PK. É o número antes do `\|` no token entregue ao aplicativo |
| tokenable_type, tokenable_id | varchar, integer | dono do token (`App\Models\Usuario` + id). Índice composto |
| name | text | nome do dispositivo (`device_name`) |
| token | varchar(64) | **hash SHA-256** do token, único. O texto do token não é guardado |
| abilities | text, nulo | permissões (`["*"]`) |
| last_used_at | datetime, nulo | último uso |
| expires_at | datetime, nulo | indexado; a validade usada é o `expiration` de `config/sanctum.php` (7 dias) |
| created_at, updated_at | datetime, nulo | padrão do Laravel (tabela do pacote, por isso em inglês) |

Não há chave estrangeira para `usuarios` (a relação é polimórfica). O aplicativo nunca acessa essa
tabela: só o Sanctum cria, confere e apaga as linhas. Os tokens vencidos são apagados pelo
`sanctum:prune-expired --hours=24`, agendado em `routes/console.php`. O fluxo completo está em
[`docs/api-autenticacao.md`](api-autenticacao.md).

Com ela, `usuarios.ativo` passou a ser usado: conta desativada não entra no site nem recebe token
(erro 403).

## Próxima etapa

- Models `Favorito`, `MensagemContato` e os novos campos em `Reserva`.
- Rotas da API com token (favoritos, minhas reservas, envio de depoimento e de mensagem de
  contato), Form Requests (tamanhos dos campos, nota 1–5, assuntos e status permitidos) e
  Resources.
- Retirada preenchendo `retirada_em` e `devolver_ate`; renovação; lista de atrasos.
- Integração das páginas `depoimentos.html` e `contato.html`.
- Tela da coordenação para desativar/reativar contas (`usuarios.ativo`).
- Instalar a extensão `intl` na imagem para o `db:show --counts` funcionar.
