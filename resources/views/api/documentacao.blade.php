<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
(function(){
  var salvo = null;
  try { salvo = localStorage.getItem('tema'); } catch (e) {}
  var escuro = salvo ? salvo === 'escuro' : window.matchMedia('(prefers-color-scheme: dark)').matches;
  document.documentElement.setAttribute('data-tema', escuro ? 'escuro' : 'claro');
})();
</script>
<title>Documentação da API — Biblioteca Virtual</title>
<link rel="icon" href="/assets/icon-fpsf.png">
<link rel="stylesheet" href="/css/style.css?v=3">
<style>
  .doc{ padding: 42px 0 10px; }
  .doc section{ margin-bottom: 46px; }
  .doc h2{ font-size: 26px; margin-bottom: 14px; }
  .doc h3{ font-size: 19px; margin: 26px 0 10px; }
  .doc p, .doc li{ max-width: 78ch; }
  .doc p + p{ margin-top: 10px; }
  .doc ul{ padding-left: 20px; margin: 10px 0; }
  .doc a code{ text-decoration: underline; }
  .doc code{
    font-family: Consolas, "Cascadia Code", Menlo, monospace;
    font-size: .9em;
    background: var(--lilac-tint);
    color: var(--heading);
    padding: 1px 6px;
    border-radius: var(--radius-s);
    overflow-wrap: anywhere;
  }
  .doc pre{
    background: var(--navy-deep);
    color: #E7E7F1;
    padding: 16px 18px;
    border-radius: var(--radius-m);
    overflow-x: auto;
    font-size: 13px;
    line-height: 1.5;
    margin: 10px 0 0;
  }
  .doc pre code{ background: none; color: inherit; padding: 0; font-size: inherit; overflow-wrap: normal; }
  .doc__tabela{ width: 100%; overflow-x: auto; margin-top: 12px; }
  .doc table{
    width: 100%;
    border-collapse: collapse;
    background: var(--card);
    border: 1px solid var(--line);
    font-size: 14px;
  }
  .doc th, .doc td{ text-align: left; vertical-align: top; padding: 10px 14px; border-bottom: 1px solid var(--line); }
  .doc th{ color: var(--heading); font-weight: 700; background: var(--paper-dim); }
  .doc tr:last-child td{ border-bottom: 0; }
  .metodo{
    display: inline-block;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    color: #fff;
    background: var(--lilac-deep);
    padding: 2px 8px;
    border-radius: var(--radius-s);
  }
  .endpoint{
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius-l);
    padding: 22px 24px;
    margin-top: 18px;
  }
  .endpoint h3{ margin: 0 0 8px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; font-size: 18px; }
  .endpoint h3 code{ font-size: 15px; }
  .endpoint dl{ display: grid; grid-template-columns: 130px 1fr; gap: 6px 14px; margin: 14px 0 0; font-size: 14px; }
  .endpoint dt{ font-weight: 700; color: var(--heading); }
  .endpoint dd{ margin: 0; }
  .endpoint > p + pre, .endpoint dl + pre{ margin-top: 14px; }
  .aviso{
    border-left: 4px solid var(--gold);
    background: var(--card);
    padding: 14px 18px;
    border-radius: var(--radius-s);
    margin-top: 14px;
  }
  .site-footer{ padding: 26px 0; }
  @media (max-width: 640px){
    .container{ padding: 0 16px; }
    .endpoint{ padding: 18px 16px; }
    .endpoint dl{ grid-template-columns: 1fr; }
    .endpoint dt{ margin-top: 6px; }
  }
</style>
</head>
<body>

<header class="navbar">
  <div class="container">
    <a class="brand" href="/">
      <img src="/assets/icon-fpsf.png" alt="Biblioteca Virtual — página inicial">
    </a>
    <div class="nav-actions">
      <a href="/" class="btn btn--lilac">Voltar ao site</a>
    </div>
  </div>
</header>

<section class="page-header">
  <div class="container">
    <h1>API v1</h1>
    <p>Dados abertos do catálogo e acesso do aluno por token, em JSON, para aplicativos e outros sistemas da Biblioteca Virtual da Escola Prof. Francisco Pereira de Souza Filho.</p>
  </div>
</section>

<main class="doc">
  <div class="container">

    <section id="o-que-e">
      <h2>O que é</h2>
      <p>A API v1 tem duas partes:</p>
      <ul>
        <li><strong>API pública v1</strong> (<a href="#endpoints">endpoints públicos</a>): devolve as mesmas informações que qualquer visitante vê no site — matérias, livros com a disponibilidade de exemplares e depoimentos aprovados. É só de leitura (<code>GET</code>), não precisa de login e não expõe nenhum dado pessoal.</li>
        <li><strong>Área do aluno</strong> (<a href="#autenticacao">autenticação</a>): o aluno entra com e-mail e senha, recebe um token e, com ele, consulta e altera o próprio perfil e a própria senha.</li>
      </ul>
      <div class="aviso">
        <p><strong>E a API antiga?</strong> O site continua usando as rotas <code>/api/...</code> (login, reservas, coordenação etc.), com sessão por cookie. Elas são internas do site, podem mudar junto com ele e não fazem parte desta documentação pública. Para integrações, use sempre <code>/api/v1/...</code>.</p>
      </div>
    </section>

    <section id="url-base">
      <h2>URL base</h2>
      <pre><code>http://localhost:8000/api/v1</code></pre>
      <p style="margin-top:12px">Todos os endpoints abaixo são relativos a essa URL. O <code>v1</code> é a versão: se um dia o formato mudar de um jeito incompatível, ele vai para <code>/api/v2</code> e a v1 continua funcionando.</p>
    </section>

    <section id="formato">
      <h2>Formato das respostas</h2>
      <h3>Sucesso</h3>
      <p>Toda resposta de sucesso vem dentro de um envelope com <code>success: true</code> e os dados em <code>data</code> (um objeto ou uma lista). As ações da área do aluno (login, alterar perfil, trocar senha, logout) trazem também uma <code>mensagem</code>; quando não há dados a devolver, <code>data</code> não aparece:</p>
@verbatim
      <pre><code>{
  "success": true,
  "data": { ... }
}

{
  "success": true,
  "mensagem": "Perfil atualizado com sucesso.",
  "data": { ... }
}</code></pre>
@endverbatim
      <h3>Erro</h3>
      <p>Em caso de erro, <code>success</code> vem como <code>false</code> e a mensagem, em português, em <code>erro</code>. O código HTTP indica o tipo do erro:</p>
@verbatim
      <pre><code>{
  "success": false,
  "erro": "Livro não encontrado."
}</code></pre>
@endverbatim
      <div class="doc__tabela">
        <table>
          <thead><tr><th>Código</th><th>Quando acontece</th><th>Exemplo de <code>erro</code></th></tr></thead>
          <tbody>
            <tr><td><code>200</code></td><td>Requisição atendida.</td><td>—</td></tr>
            <tr><td><code>401</code></td><td>Login com e-mail ou senha errados; rota protegida sem token, com token inválido, vencido ou já encerrado no logout.</td><td><code>E-mail ou senha incorretos.</code> · <code>Você precisa entrar para fazer isso.</code></td></tr>
            <tr><td><code>403</code></td><td>Login de uma conta desativada pela coordenação.</td><td><code>Conta desativada. Procure a coordenação.</code></td></tr>
            <tr><td><code>404</code></td><td>Livro, matéria ou rota inexistente.</td><td><code>Livro não encontrado.</code> · <code>Matéria não encontrada.</code> · <code>Rota não encontrada.</code></td></tr>
            <tr><td><code>405</code></td><td>Método que a rota não aceita (ex.: <code>POST</code> em <code>/api/v1/livros</code>).</td><td><code>Método não permitido nesta rota.</code></td></tr>
            <tr><td><code>422</code></td><td>Dados enviados inválidos (vem a primeira mensagem de validação).</td><td><code>O campo e-mail é obrigatório.</code> · <code>Senha atual incorreta.</code></td></tr>
            <tr><td><code>429</code></td><td>Mais de 60 requisições por minuto vindas do mesmo endereço (ou do mesmo usuário, nas rotas com token), ou mais de 10 tentativas de login por minuto.</td><td><code>Muitas tentativas. Aguarde um minuto e tente de novo.</code></td></tr>
          </tbody>
        </table>
      </div>
      <p style="margin-top:12px">Os cabeçalhos <code>X-RateLimit-Limit</code> e <code>X-RateLimit-Remaining</code> mostram o limite e quantas requisições ainda restam no minuto. Os acentos vêm escapados no JSON (<code>ã</code> no lugar de <code>ã</code>); qualquer leitor de JSON os converte de volta.</p>
    </section>

    <section id="endpoints">
      <h2>Endpoints públicos</h2>

      <div class="doc__tabela">
        <table>
          <thead><tr><th>Método</th><th>URL</th><th>Parâmetros</th><th>O que devolve</th><th>Códigos</th></tr></thead>
          <tbody>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/status</code></td><td>—</td><td>Se a API está no ar</td><td>200, 429</td></tr>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/depoimentos</code></td><td>—</td><td>Depoimentos aprovados, mais recentes primeiro</td><td>200, 429</td></tr>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/materias</code></td><td>—</td><td>Matérias com o total de livros</td><td>200, 429</td></tr>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/materias/{materia}/livros</code></td><td><code>materia</code> (slug, na URL)</td><td>Livros da matéria</td><td>200, 404, 429</td></tr>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/livros</code></td><td><code>?materia=</code> e <code>?busca=</code> (opcionais)</td><td>Livros com disponibilidade</td><td>200, 429</td></tr>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/livros/{slug}</code></td><td><code>slug</code> (na URL)</td><td>Detalhe de um livro</td><td>200, 404, 429</td></tr>
          </tbody>
        </table>
      </div>

      <article class="endpoint" id="status">
        <h3><span class="metodo">GET</span> <code>/api/v1/status</code></h3>
        <p>Confirma que a API está no ar. Útil para monitoramento.</p>
        <dl>
          <dt>Parâmetros</dt><dd>Nenhum.</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>429</code></dd>
        </dl>
@verbatim
        <pre><code>{
  "success": true,
  "data": {
    "aplicacao": "Biblioteca Virtual FPSF",
    "api": "v1",
    "status": "online",
    "data_hora": "2026-10-13T12:23:48+00:00"
  }
}</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="depoimentos">
        <h3><span class="metodo">GET</span> <code>/api/v1/depoimentos</code></h3>
        <p>Depoimentos já aprovados pela coordenação, dos mais recentes para os mais antigos. <code>nota</code> vai de 1 a 5 estrelas, ou <code>null</code> quando o autor não deu nota. <code>descricao_autor</code> pode ser <code>null</code>.</p>
        <dl>
          <dt>Parâmetros</dt><dd>Nenhum.</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>429</code></dd>
        </dl>
@verbatim
        <pre><code>{
  "success": true,
  "data": [
    {
      "id": 2,
      "nome": "Prof. Marcos Andrade",
      "descricao_autor": "Professor de Ciências",
      "texto": "Consigo indicar leituras por matéria em segundos e acompanhar o que cada turma anda descobrindo. ...",
      "nota": 5,
      "criado_em": "2026-10-13T12:00:00+00:00"
    }
  ]
}</code></pre>
@endverbatim
        <p style="margin-top:10px">Sem depoimentos aprovados, a resposta é <code>{"success": true, "data": []}</code>.</p>
      </article>

      <article class="endpoint" id="materias">
        <h3><span class="metodo">GET</span> <code>/api/v1/materias</code></h3>
        <p>Todas as matérias do acervo, em ordem alfabética, com quantos livros cada uma tem. O <code>slug</code> é o identificador usado nos outros endpoints. O <code>icone</code> fica em <code>/assets/{icone}</code>.</p>
        <dl>
          <dt>Parâmetros</dt><dd>Nenhum.</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>429</code></dd>
        </dl>
@verbatim
        <pre><code>{
  "success": true,
  "data": [
    { "slug": "artes", "nome": "Artes", "icone": "artes.png", "total_livros": 0 },
    { "slug": "ciencias", "nome": "Ciências", "icone": "ciencias.png", "total_livros": 1 },
    { "slug": "literatura", "nome": "Literatura", "icone": "livro.png", "total_livros": 6 },
    ...
  ]
}</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="materias-livros">
        <h3><span class="metodo">GET</span> <code>/api/v1/materias/{materia}/livros</code></h3>
        <p>Livros de uma matéria, em ordem alfabética, no mesmo formato de <code>/api/v1/livros</code>.</p>
        <dl>
          <dt>Parâmetros</dt><dd><code>materia</code>: slug da matéria, na URL (ex.: <code>literatura</code>).</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>404</code> (<code>Matéria não encontrada.</code>) · <code>429</code></dd>
        </dl>
@verbatim
        <pre><code>// GET /api/v1/materias/literatura/livros
{
  "success": true,
  "data": [
    {
      "id": 1,
      "slug": "a-revolucao-dos-bichos",
      "titulo": "A Revolução dos Bichos",
      "autor": "George Orwell",
      "materia_slug": "literatura",
      "capa": "aRevolucaoDosBichos.jpg",
      "sinopse": "Os animais de uma granja expulsam o dono e passam a governar a si mesmos. ...",
      "paginas": 152,
      "idioma": "Português",
      "materia_nome": "Literatura",
      "materia_icone": "livro.png",
      "total_exemplares": 3,
      "disponiveis": 3
    },
    ...
  ]
}

// GET /api/v1/materias/nao-existe/livros  →  404
{ "success": false, "erro": "Matéria não encontrada." }</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="livros">
        <h3><span class="metodo">GET</span> <code>/api/v1/livros</code></h3>
        <p>Todos os livros, em ordem alfabética, com a disponibilidade: <code>total_exemplares</code> é quantas cópias em bom estado a biblioteca tem e <code>disponiveis</code>, quantas estão livres para reserva agora. A <code>capa</code> fica em <code>/assets/{capa}</code>.</p>
        <dl>
          <dt>Parâmetros</dt>
          <dd>
            <code>materia</code> (opcional): slug da matéria; <code>todos</code> ou vazio não filtra.<br>
            <code>busca</code> (opcional): parte do título ou do autor.
          </dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>429</code> (filtro sem resultado devolve <code>data: []</code>)</dd>
          <dt>Exemplos</dt><dd><code>/api/v1/livros?materia=filosofia</code> · <code>/api/v1/livros?busca=quixote</code></dd>
        </dl>
@verbatim
        <pre><code>// GET /api/v1/livros?busca=quixote
{
  "success": true,
  "data": [
    {
      "id": 3,
      "slug": "dom-quixote",
      "titulo": "Dom Quixote",
      "autor": "Miguel de Cervantes",
      "materia_slug": "literatura",
      "capa": "domQuixote.jpg",
      "sinopse": "Um fidalgo enlouquecido pelas novelas de cavalaria sai pelo campo em busca de aventuras, acompanhado do escudeiro Sancho Pança.",
      "paginas": 863,
      "idioma": "Português",
      "materia_nome": "Literatura",
      "materia_icone": "livro.png",
      "total_exemplares": 2,
      "disponiveis": 2
    }
  ]
}</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="livro">
        <h3><span class="metodo">GET</span> <code>/api/v1/livros/{slug}</code></h3>
        <p>Detalhe de um livro, pelo slug. Mesmos campos da listagem.</p>
        <dl>
          <dt>Parâmetros</dt><dd><code>slug</code>: slug do livro, na URL (ex.: <code>dom-quixote</code>).</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>404</code> (<code>Livro não encontrado.</code>) · <code>429</code></dd>
        </dl>
@verbatim
        <pre><code>// GET /api/v1/livros/dom-quixote
{
  "success": true,
  "data": {
    "id": 3,
    "slug": "dom-quixote",
    "titulo": "Dom Quixote",
    "autor": "Miguel de Cervantes",
    ...
    "total_exemplares": 2,
    "disponiveis": 2
  }
}

// GET /api/v1/livros/nao-existe  →  404
{ "success": false, "erro": "Livro não encontrado." }</code></pre>
@endverbatim
      </article>
    </section>

    <section id="autenticacao">
      <h2>Autenticação</h2>
      <p>As rotas da área do aluno exigem um <strong>token</strong>, gerado pelo Laravel Sanctum. O fluxo é este:</p>
      <ol style="padding-left:20px; margin:10px 0">
        <li>O aplicativo envia o e-mail e a senha do aluno para <code>POST /api/v1/auth/login</code>.</li>
        <li>A API confere a senha com o hash guardado no banco. Se estiver certa (e a conta estiver ativa), gera um token e o devolve na resposta.</li>
        <li>O aplicativo <strong>guarda o token</strong> (ex.: no armazenamento seguro do celular). A senha não precisa mais ser guardada.</li>
        <li>Em toda requisição protegida, o aplicativo envia o token no cabeçalho <code>Authorization: Bearer SEU_TOKEN</code>.</li>
        <li>O Laravel procura o token, descobre de qual aluno ele é e responde com os dados <strong>daquele aluno</strong>. Sem token, ou com token inválido, a resposta é <code>401</code>.</li>
        <li>No logout, o token é apagado e deixa de funcionar.</li>
      </ol>
@verbatim
      <pre><code>Aplicativo                                   API (Laravel)
    │  POST /api/v1/auth/login                    │
    │  { "email": "...", "senha": "..." }  ─────► │ confere a senha, gera o token
    │ ◄─────  { "data": { "token": "4|Xyz..." } } │
    │                                             │
    │  GET /api/v1/usuario                        │
    │  Authorization: Bearer 4|Xyz...      ─────► │ token → aluno 4
    │ ◄─────  { "data": { "id": 4, ... } }        │</code></pre>
@endverbatim

      <h3>Regras do token</h3>
      <ul>
        <li><strong>Validade:</strong> 7 dias. Depois disso o aluno precisa entrar de novo.</li>
        <li><strong>Um token por dispositivo:</strong> o campo opcional <code>device_name</code> (padrão <code>app</code>) identifica o aparelho. Entrar de novo no mesmo aparelho substitui o token anterior; aparelhos diferentes têm tokens diferentes.</li>
        <li><strong>Trocar a senha</strong> encerra os tokens dos outros aparelhos; o token usado na troca continua valendo.</li>
        <li><strong>Só o token vale:</strong> o cookie de sessão de quem está logado no site não dá acesso a estas rotas.</li>
        <li>O banco guarda só um <em>hash</em> do token. Quem lê o banco não consegue usá-lo, e um token perdido não pode ser recuperado: basta fazer login de novo.</li>
        <li>Conta desativada pela coordenação não recebe token (<code>403</code>).</li>
      </ul>
      <div class="aviso">
        <p><strong>Trate o token como uma senha.</strong> Não coloque o token em URL, em print, em código versionado nem em documentação. Nos exemplos abaixo ele aparece como <code>SEU_TOKEN</code>.</p>
      </div>

      <div class="doc__tabela">
        <table>
          <thead><tr><th>Método</th><th>URL</th><th>Acesso</th><th>Objetivo</th><th>Códigos</th></tr></thead>
          <tbody>
            <tr><td><span class="metodo">POST</span></td><td><code>/api/v1/auth/login</code></td><td>Público</td><td>Autenticar e gerar o token</td><td>200, 401, 403, 422, 429</td></tr>
            <tr><td><span class="metodo">GET</span></td><td><code>/api/v1/usuario</code></td><td>Token</td><td>Consultar o próprio perfil</td><td>200, 401</td></tr>
            <tr><td><span class="metodo">PUT</span></td><td><code>/api/v1/usuario</code></td><td>Token</td><td>Atualizar nome e e-mail (os dois obrigatórios)</td><td>200, 401, 422</td></tr>
            <tr><td><span class="metodo">PATCH</span></td><td><code>/api/v1/usuario</code></td><td>Token</td><td>Atualização parcial (só o que for enviado)</td><td>200, 401, 422</td></tr>
            <tr><td><span class="metodo">PUT</span></td><td><code>/api/v1/usuario/senha</code></td><td>Token</td><td>Alterar a senha</td><td>200, 401, 422</td></tr>
            <tr><td><span class="metodo">POST</span></td><td><code>/api/v1/auth/logout</code></td><td>Token</td><td>Invalidar o token atual</td><td>200, 401</td></tr>
          </tbody>
        </table>
      </div>
      <p style="margin-top:12px">Envie sempre os cabeçalhos <code>Accept: application/json</code> e, quando houver corpo, <code>Content-Type: application/json</code>.</p>

      <article class="endpoint" id="auth-login">
        <h3><span class="metodo">POST</span> <code>/api/v1/auth/login</code></h3>
        <p>Troca e-mail e senha por um token. E-mail inexistente e senha errada recebem a <strong>mesma</strong> resposta, para não revelar quais e-mails estão cadastrados. Limite de 10 tentativas por minuto.</p>
        <dl>
          <dt>Corpo</dt><dd><code>email</code> (obrigatório) · <code>senha</code> (obrigatório) · <code>device_name</code> (opcional, até 100 caracteres)</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>401</code> (<code>E-mail ou senha incorretos.</code>) · <code>403</code> (<code>Conta desativada. Procure a coordenação.</code>) · <code>422</code> · <code>429</code></dd>
        </dl>
@verbatim
        <pre><code>curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"aluno@fpsf.edu.br","senha":"SUA_SENHA","device_name":"meu-celular"}'

// 200
{
  "success": true,
  "mensagem": "Login realizado com sucesso.",
  "data": {
    "token": "SEU_TOKEN",
    "usuario": { "id": 4, "nome": "Aluno Teste API", "email": "aluno@fpsf.edu.br", "papel": "aluno", "turma": "9º ano A" }
  }
}

// 401
{ "success": false, "erro": "E-mail ou senha incorretos." }</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="usuario-show">
        <h3><span class="metodo">GET</span> <code>/api/v1/usuario</code></h3>
        <p>Dados do aluno dono do token. A senha nunca aparece.</p>
@verbatim
        <pre><code>curl http://localhost:8000/api/v1/usuario \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"

// 200
{
  "success": true,
  "data": { "id": 4, "nome": "Aluno Teste API", "email": "aluno@fpsf.edu.br", "papel": "aluno", "turma": "9º ano A" }
}

// 401 (sem token, token inválido, vencido ou encerrado)
{ "success": false, "erro": "Você precisa entrar para fazer isso." }</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="usuario-update">
        <h3><span class="metodo">PUT</span> / <span class="metodo">PATCH</span> <code>/api/v1/usuario</code></h3>
        <p>Só <code>nome</code> e <code>email</code> podem ser alterados por aqui. Qualquer outro campo enviado (<code>papel</code>, <code>turma_id</code>, <code>ativo</code>, <code>senha</code>...) é ignorado. O e-mail é gravado em minúsculas e não pode ser o de outro usuário.</p>
        <dl>
          <dt>PUT</dt><dd>Substitui o perfil: <code>nome</code> (até 80) e <code>email</code> (até 120) são obrigatórios.</dd>
          <dt>PATCH</dt><dd>Parcial: envie só o que quer mudar. Campo enviado não pode ir vazio.</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>401</code> · <code>422</code> (<code>Já existe um usuário com esse e-mail.</code>, <code>O campo e-mail é obrigatório.</code>...)</dd>
        </dl>
@verbatim
        <pre><code>curl -X PATCH http://localhost:8000/api/v1/usuario \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"nome":"Aluno Teste"}'

// 200
{
  "success": true,
  "mensagem": "Perfil atualizado com sucesso.",
  "data": { "id": 4, "nome": "Aluno Teste", "email": "aluno@fpsf.edu.br", "papel": "aluno", "turma": "9º ano A" }
}</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="usuario-senha">
        <h3><span class="metodo">PUT</span> <code>/api/v1/usuario/senha</code></h3>
        <p>Troca a senha. Os tokens dos outros aparelhos são encerrados; o token usado aqui continua valendo.</p>
        <dl>
          <dt>Corpo</dt><dd><code>senha_atual</code> · <code>nova_senha</code> (mínimo 8 caracteres, diferente da atual) · <code>nova_senha_confirmation</code> (igual à nova)</dd>
          <dt>Códigos</dt><dd><code>200</code> · <code>401</code> · <code>422</code> (<code>Senha atual incorreta.</code>, <code>A confirmação do campo nova senha não confere.</code>...)</dd>
        </dl>
@verbatim
        <pre><code>curl -X PUT http://localhost:8000/api/v1/usuario/senha \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -d '{"senha_atual":"SENHA_ATUAL","nova_senha":"NOVA_SENHA","nova_senha_confirmation":"NOVA_SENHA"}'

// 200
{ "success": true, "mensagem": "Senha alterada com sucesso." }

// 422
{ "success": false, "erro": "Senha atual incorreta." }</code></pre>
@endverbatim
      </article>

      <article class="endpoint" id="auth-logout">
        <h3><span class="metodo">POST</span> <code>/api/v1/auth/logout</code></h3>
        <p>Encerra o token enviado. Os tokens de outros aparelhos continuam valendo.</p>
@verbatim
        <pre><code>curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer SEU_TOKEN"

// 200
{ "success": true, "mensagem": "Logout realizado com sucesso." }

// o mesmo token depois do logout → 401
{ "success": false, "erro": "Você precisa entrar para fazer isso." }</code></pre>
@endverbatim
      </article>
    </section>

    <section id="api-e-view">
      <h2>Diferença entre API e View</h2>
      <p>A mesma aplicação Laravel responde de dois jeitos: a API devolve dados para outro programa usar, e a view (Blade) devolve uma página pronta para uma pessoa ler. Esta página é uma view; os endpoints que ela descreve são a API.</p>
      <div class="doc__tabela">
        <table>
          <thead><tr><th>Tipo</th><th>Exemplo</th><th>Resposta</th></tr></thead>
          <tbody>
            <tr><td>API</td><td><a href="/api/v1/livros"><code>/api/v1/livros</code></a></td><td>JSON para aplicativo ou outro sistema</td></tr>
            <tr><td>WEB</td><td><a href="/api/documentacao"><code>/api/documentacao</code></a></td><td>HTML gerado por Blade para leitura humana</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section id="registro-das-rotas">
      <h2>Como as rotas da v1 foram registradas</h2>
      <p>No roteiro da aula (Confeitaria DaVilla, seção 3), o arquivo <code>routes/api.php</code> ainda não existia e foi registrado com a chave <code>api:</code> no <code>withRouting()</code> do <code>bootstrap/app.php</code>. Isso dá ao arquivo o prefixo <code>/api</code> e o grupo de middlewares <code>api</code>, que não tem sessão.</p>
      <p>Neste projeto o <code>routes/api.php</code> já existia e já era carregado no <code>then:</code> do <code>withRouting()</code>, com o grupo <code>web</code> (sessão por cookie e proteção CSRF), o prefixo <code>api</code> e o middleware <code>ExpirarReservasVencidas</code>. Por isso a chave <code>api:</code> não é usada (o <code>php artisan install:api</code> costuma inseri-la; aqui ela ficou de fora):</p>
      <ul>
        <li>o mesmo arquivo seria carregado duas vezes, e todas as rotas ficariam duplicadas;</li>
        <li>as rotas do site passariam pelo grupo <code>api</code>, que não tem sessão, e o login do site deixaria de funcionar.</li>
      </ul>
      <p>A v1 fica num arquivo separado, <code>routes/api_v1.php</code>, registrado também no <code>then:</code>, mas com o grupo <code>api</code> (sem sessão e sem CSRF), o prefixo <code>api/v1</code> e o limite de 60 requisições por minuto. É esse grupo que permite chamar <code>POST /api/v1/auth/login</code> de um aplicativo, do curl ou do Postman: no grupo <code>web</code>, a mesma requisição seria recusada com <code>419</code> por falta do token CSRF. Os controllers estão em <code>app/Http/Controllers/Api/V1/</code>.</p>
    </section>

  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-bottom">
      <span>Biblioteca Virtual — Escola Prof. Francisco Pereira de Souza Filho</span>
      <span>API v1</span>
    </div>
  </div>
</footer>

</body>
</html>
