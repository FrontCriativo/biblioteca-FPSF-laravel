import { escaparHtml, montarSessaoNavbar } from './api.js';

const ESTRELA_CHEIA = 'assets/depoimentoPreenchido.png';
const ESTRELA_VAZIA = 'assets/depoimento.png';
const TOTAL_ESTRELAS = 5;

const usuario = await montarSessaoNavbar();
const form = document.querySelector('form[data-form-depoimento]');
const grade = document.querySelector('[data-depoimentos]');
const avaliacao = form.querySelector('[data-avaliacao]');
const botoes = [...avaliacao.querySelectorAll('.avaliacao__estrela')];
const campoEstrelas = form.querySelector('input[name="estrelas"]');
const textoAvaliacao = avaliacao.querySelector('.avaliacao__texto');

if (usuario?.nome) form.nome.value = usuario.nome;

function rotuloEstrelas(n) {
  if (n === 0) return 'Sem estrelas';
  return n === 1 ? '1 estrela' : `${n} estrelas`;
}

/* Pinta as `n` primeiras estrelas cheias e o restante vazias. */
function pintar(n) {
  botoes.forEach((botao, i) => {
    botao.querySelector('img').src = i < n ? ESTRELA_CHEIA : ESTRELA_VAZIA;
  });
}

function definirNota(n) {
  campoEstrelas.value = n;
  botoes.forEach((botao, i) => botao.setAttribute('aria-pressed', String(i + 1 === n)));
  textoAvaliacao.textContent = rotuloEstrelas(n);
  pintar(n);
}

botoes.forEach((botao) => {
  const valor = Number(botao.dataset.valor);
  // Clicar na estrela que já é a nota atual zera a avaliação.
  botao.addEventListener('click', () => definirNota(Number(campoEstrelas.value) === valor ? 0 : valor));
  botao.addEventListener('mouseenter', () => pintar(valor));
});
avaliacao.addEventListener('mouseleave', () => pintar(Number(campoEstrelas.value)));

function estrelasHtml(n) {
  let html = '';
  for (let i = 0; i < TOTAL_ESTRELAS; i++) {
    html += `<img src="${i < n ? ESTRELA_CHEIA : ESTRELA_VAZIA}" alt="">`;
  }
  return html;
}

/* Ainda sem back-end de depoimentos: o card novo só aparece nesta visita. */
form.addEventListener('submit', (evento) => {
  evento.preventDefault();
  const estrelas = Number(campoEstrelas.value);
  const turma = form.turma.value.trim();

  const card = document.createElement('div');
  card.className = 'quote-card';
  card.innerHTML = `
    <div class="quote-card__stars" role="img" aria-label="${estrelas} de ${TOTAL_ESTRELAS} estrelas">${estrelasHtml(estrelas)}</div>
    <p>${escaparHtml(form.texto.value.trim())}</p>
    <div class="quote-card__who">
      <img src="assets/userRoxo.png" alt="">
      <div><strong>${escaparHtml(form.nome.value.trim())}</strong>${turma ? `<span>${escaparHtml(turma)}</span>` : ''}</div>
    </div>`;
  grade.prepend(card);

  form.texto.value = '';
  form.turma.value = '';
  definirNota(0);

  const feedback = form.querySelector('.form-feedback');
  feedback.textContent = 'Obrigado! Seu depoimento foi adicionado no topo da página.';
  feedback.style.display = 'block';
  card.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
