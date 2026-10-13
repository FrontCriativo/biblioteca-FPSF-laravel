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
<link rel="stylesheet" href="/css/style.css">
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
    <h1>API pública v1</h1>
    <p>Dados abertos do catálogo da Biblioteca Virtual da Escola Prof. Francisco Pereira de Souza Filho, em JSON, para aplicativos e outros sistemas.</p>
  </div>
</section>

<main class="doc">
  <div class="container">

    <section id="o-que-e">
      <h2>O que é</h2>
      <p>A API pública devolve, em JSON, as mesmas informações que qualquer visitante vê no site: matérias, livros com a disponibilidade de exemplares e depoimentos aprovados. Ela é só de leitura (requisições <code>GET</code>) e não precisa de login.</p>
      <p>Nenhum dado pessoal é exposto: nada de e-mails, reservas ou identificação de alunos.</p>
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
      <p>Toda resposta de sucesso vem dentro de um envelope com <code>success: true</code> e os dados em <code>data</code> (um objeto ou uma lista):</p>
@verbatim
      <pre><code>{
  "success": true,
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
            <tr><td><code>404</code></td><td>Livro, matéria ou rota inexistente.</td><td><code>Livro não encontrado.</code> · <code>Matéria não encontrada.</code> · <code>Rota não encontrada.</code></td></tr>
            <tr><td><code>405</code></td><td>Método diferente de <code>GET</code>.</td><td><code>Método não permitido nesta rota.</code></td></tr>
            <tr><td><code>429</code></td><td>Mais de 60 requisições por minuto vindas do mesmo endereço.</td><td><code>Muitas tentativas. Aguarde um minuto e tente de novo.</code></td></tr>
          </tbody>
        </table>
      </div>
      <p style="margin-top:12px">Os cabeçalhos <code>X-RateLimit-Limit</code> e <code>X-RateLimit-Remaining</code> mostram o limite e quantas requisições ainda restam no minuto. Os acentos vêm escapados no JSON (<code>ã</code> no lugar de <code>ã</code>); qualquer leitor de JSON os converte de volta.</p>
    </section>

    <section id="endpoints">
      <h2>Endpoints</h2>

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
      <p>Neste projeto o <code>routes/api.php</code> já existia e já era carregado no <code>then:</code> do <code>withRouting()</code>, com o grupo <code>web</code> (sessão por cookie e proteção CSRF), o prefixo <code>api</code> e o middleware <code>ExpirarReservasVencidas</code>. Por isso a v1 não usa a chave <code>api:</code>:</p>
      <ul>
        <li>o mesmo arquivo seria carregado duas vezes, e todas as rotas ficariam duplicadas;</li>
        <li>as rotas atuais passariam pelo grupo <code>api</code>, que não tem sessão, e o login do site deixaria de funcionar.</li>
      </ul>
      <p>Em vez disso, a v1 entra no próprio <code>routes/api.php</code>, dentro de <code>Route::prefix('v1')</code>, com limite de 60 requisições por minuto. Como o arquivo já recebe o prefixo <code>api</code>, os endpoints ficam em <code>/api/v1/...</code>. Os controllers estão em <code>app/Http/Controllers/Api/V1/</code>.</p>
    </section>

  </div>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-bottom">
      <span>Biblioteca Virtual — Escola Prof. Francisco Pereira de Souza Filho</span>
      <span>API pública v1</span>
    </div>
  </div>
</footer>

</body>
</html>
