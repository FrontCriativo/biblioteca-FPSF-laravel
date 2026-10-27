import { api, escaparHtml, montarSessaoNavbar } from './api.js';

const grade = document.querySelector('[data-catalogo]');
const filtros = document.querySelector('[data-filtros]');
const campoBusca = document.querySelector('[data-catalog-search]');

let materiaAtiva = location.hash.slice(1) || 'todos';
let termoBusca = '';

function cartaoLivro(livro) {
  const esgotado = livro.disponiveis === 0;
  const rotulo = esgotado
    ? 'Sem exemplar livre'
    : `${livro.disponiveis} de ${livro.total_exemplares} ${livro.disponiveis === 1 ? 'disponível' : 'disponíveis'}`;

  return `
    <a class="book" href="livro.html?titulo=${encodeURIComponent(livro.slug)}">
      <div class="book__cover">
        <img src="assets/${escaparHtml(livro.capa)}" alt="Capa de ${escaparHtml(livro.titulo)}">
      </div>
      <span class="book__title">${escaparHtml(livro.titulo)}</span>
      <span class="book__author">${escaparHtml(livro.autor)}</span>
      <span class="disp-tag ${esgotado ? 'disp-tag--fora' : 'disp-tag--ok'}">${rotulo}</span>
    </a>`;
}

function blocoMateria(materia, livros) {
  const plural = livros.length === 1 ? 'livro' : 'livros';
  return `
    <div class="category-block" id="${escaparHtml(materia.slug)}">
      <div class="category-block__head">
        <img src="assets/${escaparHtml(materia.icone)}" alt="">
        <h3>${escaparHtml(materia.nome)}</h3>
        <span>${livros.length} ${plural}</span>
      </div>
      <div class="book-grid">${livros.map(cartaoLivro).join('')}</div>
    </div>`;
}

function blocoVazio(materia) {
  return `
    <div class="category-block" id="${escaparHtml(materia.slug)}">
      <div class="category-block__head">
        <img src="assets/${escaparHtml(materia.icone)}" alt="">
        <h3>${escaparHtml(materia.nome)}</h3>
        <span>em breve</span>
      </div>
      <div class="empty-state">
        Ainda não adicionamos livros de ${escaparHtml(materia.nome)} à estante virtual.
        Assim que chegarem, eles aparecem aqui.
      </div>
    </div>`;
}

async function desenhar() {
  const [materias, livros] = await Promise.all([
    api.materias(),
    api.livros({ materia: materiaAtiva, busca: termoBusca }),
  ]);

  materias.sort((a, b) => posicaoMateria(a.slug) - posicaoMateria(b.slug));

  const porMateria = new Map();
  for (const livro of livros) {
    if (!porMateria.has(livro.materia_slug)) porMateria.set(livro.materia_slug, []);
    porMateria.get(livro.materia_slug).push(livro);
  }

  const visiveis = materiaAtiva === 'todos'
    ? materias
    : materias.filter((m) => m.slug === materiaAtiva);

  if (termoBusca && livros.length === 0) {
    grade.innerHTML = `<div class="empty-state">Nenhum título encontrado para “${escaparHtml(termoBusca)}”.</div>`;
    return;
  }

  grade.innerHTML = visiveis.map((materia) => {
    const doGrupo = porMateria.get(materia.slug) ?? [];
    if (doGrupo.length) return blocoMateria(materia, doGrupo);
    return termoBusca ? '' : blocoVazio(materia);
  }).join('');
}

// mesma ordem das caixas de "Explore por matéria" na página inicial
const ORDEM_MATERIAS = [
  'literatura', 'matematica', 'historia', 'geografia', 'ciencias',
  'filosofia', 'artes', 'musica', 'tecnologia', 'meio-ambiente',
];

function posicaoMateria(slug) {
  const indice = ORDEM_MATERIAS.indexOf(slug);
  return indice === -1 ? ORDEM_MATERIAS.length : indice;
}

async function montarFiltros() {
  const materias = await api.materias();
  materias.sort((a, b) => posicaoMateria(a.slug) - posicaoMateria(b.slug));

  filtros.innerHTML = materias.map((m) => `
    <button class="subject-chip${materiaAtiva === m.slug ? ' is-active' : ''}" type="button" data-subject="${escaparHtml(m.slug)}">
      <img src="assets/${escaparHtml(m.icone)}" alt="">${escaparHtml(m.nome)}
    </button>`).join('');

  filtros.addEventListener('click', (evento) => {
    const chip = evento.target.closest('.subject-chip');
    if (!chip) return;
    // clicar de novo na matéria selecionada volta a mostrar todas
    materiaAtiva = materiaAtiva === chip.dataset.subject ? 'todos' : chip.dataset.subject;
    filtros.querySelectorAll('.subject-chip').forEach((c) => c.classList.toggle('is-active', c.dataset.subject === materiaAtiva));
    desenhar();
  });
}

let idDebounce;
campoBusca?.addEventListener('input', () => {
  clearTimeout(idDebounce);
  idDebounce = setTimeout(() => {
    termoBusca = campoBusca.value.trim();
    desenhar();
  }, 220);
});

const buscaInicial = new URLSearchParams(location.search).get('busca');
if (buscaInicial && campoBusca) {
  termoBusca = buscaInicial;
  campoBusca.value = buscaInicial;
}

montarSessaoNavbar();
await montarFiltros();
await desenhar();
