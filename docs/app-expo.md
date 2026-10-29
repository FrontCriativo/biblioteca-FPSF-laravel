# Conectar o aplicativo Expo

O app móvel usa as rotas abaixo, todas em `/api/v1`. Elas ficam no fim de
`routes/api_v1.php` e devolvem os mesmos dados do resto da v1, só com os nomes
de rota e de campo do app. Os testes estão em `tests/Feature/ApiV1AppTest.php`.

| Método | Rota | Acesso | Equivale a |
|---|---|---|---|
| POST | /api/v1/login | Público | /api/v1/auth/login |
| GET | /api/v1/produto | Público | /api/v1/livros |
| GET | /api/v1/produto/{id} | Público | /api/v1/livros/{slug} |
| GET | /api/v1/categoria | Público | /api/v1/materias |
| PUT | /api/v1/cliente/senha | Token | /api/v1/usuario/senha |

## Formato

**POST /api/v1/login** recebe `{ email, password, device_name? }`:

```json
{ "success": true, "mensagem": "Login realizado com sucesso.", "token": "1|abc...", "usuario": { "id": 2, "nome": "Beatriz Lima", ... } }
```

**GET /api/v1/produto** (e `/produto/{id}`, com um livro só em `data`):

```json
{ "success": true, "data": [ {
    "id": 3,
    "slug": "dom-quixote",
    "titulo": "Dom Quixote",
    "autor": "Miguel de Cervantes",
    "categoria_produto": { "id_categoria": "literatura", "nome_categoria": "Literatura" },
    "genero": "Literatura",
    "imagem": "domQuixote.jpg",
    "status": "disponivel",
    "status_produto": "ATIVO",
    "livrosDestaque": "NAO",
    "descricao": "...",
    "total_exemplares": 2,
    "disponiveis": 2
} ] }
```

- `status`: `disponivel` se há ao menos um exemplar em bom estado sem reserva aberta; senão `emprestado`.
- `status_produto`: sempre `ATIVO`. O acervo não tem livro inativo; livro retirado do catálogo é excluído.
- `livrosDestaque`: `SIM` nos 6 livros cadastrados por último (`ProdutoController::TOTAL_DESTAQUES`).
- `descricao`: a sinopse. Não há `nota`, porque os livros não têm avaliação.
- `id_categoria`: o **slug** da matéria (texto, não número), porque as matérias não têm id numérico.

**GET /api/v1/categoria**: em ordem alfabética, `ordem_categoria` é a posição na lista.

```json
{ "success": true, "data": [ { "id_categoria": "artes", "nome_categoria": "Artes", "status_categoria": "ATIVO", "ordem_categoria": 1, "icone": "artes.png" } ] }
```

**PUT /api/v1/cliente/senha** recebe `{ senha_atual, nova_senha, nova_senha_confirmation }`
(mínimo de 8 caracteres) com `Authorization: Bearer <token>`. Os tokens dos outros
aparelhos são apagados; o atual continua valendo.

```json
{ "success": true, "mensagem": "Senha alterada com sucesso." }
```

**Erros** de qualquer rota saem como `{ "success": false, "erro": "mensagem" }`
(401, 403, 404, 422, 429). A mensagem para mostrar ao usuário está em `erro`.

## Capas

As capas ficam em `public/assets/`, não em `public/tcc/img/`. O campo `imagem`
traz só o nome do arquivo; a URL é `http://<servidor>/assets/<imagem>`.

## CORS

Não é preciso publicar `config/cors.php`: o padrão do Laravel já libera `api/*`
para qualquer origem, o que basta para rodar o app no navegador.

## Subir o servidor para o celular

O projeto exige **PHP 8.4** (as dependências do `composer.lock`).

```bash
php artisan serve --host=0.0.0.0 --port=8080
```

Sem `--host=0.0.0.0` o celular não alcança o servidor. Celular e computador
precisam estar na mesma rede.

**Rodando dentro do WSL 2**: por padrão o WSL fica atrás de uma rede própria
(NAT) e o celular não alcança o `php artisan serve` pelo IP do Windows. Ative o
modo espelhado em `C:\Users\<você>\.wslconfig`:

```ini
[wsl2]
networkingMode=mirrored
```

Depois rode `wsl --shutdown` no PowerShell, abra o Ubuntu de novo e libere a
porta 8080 no Firewall do Windows se ele perguntar.

Para testar, abra `http://<IP do computador>:8080/api/v1/produto` no navegador do celular.
