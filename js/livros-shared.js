/* ================================================================
   ROBÉRIO DIÓGENES — js/livros-shared.js
   Funções compartilhadas por todas as páginas de livros:
   - Newsletter real (conectada ao banco)
   - Comentários reais (conectados ao banco)
   - Verificação de sessão e menu de usuário
   Deve ser carregado APÓS global.js e api-client.js
   ================================================================ */
'use strict';

/* ── Caminho base do backend (páginas de livros ficam em livros/) ── */
const LIVROS_BASE = '../backend';

/* ── Requisição genérica ─────────────────────────────────────── */
async function livrosReq(endpoint, dados = null, metodo = 'POST') {
  try {
    const opts = { method: metodo, credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } };
    if (dados !== null) opts.body = JSON.stringify(dados);
    const r = await fetch(LIVROS_BASE + '/' + endpoint, opts);
    return await r.json();
  } catch (e) {
    return { ok: false, erro: 'Falha na conexão.' };
  }
}

/* ── Newsletter ──────────────────────────────────────────────── */
window.submeterNewsletter = async function(e) {
  e.preventDefault();
  const input = e.target.querySelector('input[type="email"]');
  const btn   = e.target.querySelector('button[type="submit"]');
  if (!input?.value) return;

  if (btn) { btn.disabled = true; btn.textContent = 'Aguarde…'; }

  const r = await livrosReq('newsletter.php', { email: input.value.trim() });

  if (btn) { btn.disabled = false; btn.textContent = 'Inscrever-se'; }

  if (r.ok) {
    if (typeof mostrarToast === 'function') mostrarToast(r.mensagem || 'Inscrição realizada! 📖', 'sucesso', 5000);
    input.value = '';
  } else {
    if (typeof mostrarToast === 'function') mostrarToast(r.erro || 'Erro na inscrição.', 'erro');
  }
};

/* ── Inscrição com email do modal (download cap. 1) ──────────── */
window.inscricaoEmailLivro = async function(email) {
  if (!email || !email.includes('@')) return; // opcional — não bloqueia o download
  await livrosReq('newsletter.php', { email: email.trim() });
};

/* ── Comentários ─────────────────────────────────────────────── */

/** Carrega e exibe comentários aprovados do livro */
async function carregarComentarios(slug) {
  const container = document.getElementById('comentariosLista');
  if (!container || !slug) return;

  const r = await livrosReq(`comentarios.php?livro=${encodeURIComponent(slug)}`, null, 'GET');
  if (!r.ok || !r.comentarios?.length) return;

  r.comentarios.forEach(c => {
    const leuTexto = { sim: 'Leu o livro completo', cap: 'Leu o capítulo gratuito', nao: 'Ainda não leu', '': '' }[c.leu] || '';
    const div = document.createElement('div');
    div.className = 'com-card r d2';
    div.innerHTML = `
      <span class="com-aspas" aria-hidden="true">"</span>
      <p class="com-texto">${esc(c.texto)}</p>
      <p class="com-autor">— ${esc(c.nome)}${c.cidade ? ' · ' + esc(c.cidade) : ''}${leuTexto ? ' · ' + leuTexto : ''} · ${c.data}</p>`;
    container.appendChild(div);
  });
}

/** Envia comentário para o backend */
window.enviarComentario = async function(e) {
  e.preventDefault();
  const form = e.target;
  const btn  = form.querySelector('button[type="submit"]');
  const slug = form.dataset.livro || document.body.dataset.livro || '';

  // Coletar campos (IDs variam por página — tenta nomes comuns)
  const get = id => (document.getElementById(id) || form.querySelector(`[name="${id}"]`))?.value?.trim() ?? '';
  const nome  = get('cn') || get('nome');
  const cidade= get('cc') || get('cidade');
  const leu   = get('cl') || get('leu');
  const texto = get('ct') || get('texto') || get('comentario');

  if (!nome)  { if (typeof mostrarToast === 'function') mostrarToast('Informe seu nome.', 'aviso'); return; }
  if (!texto) { if (typeof mostrarToast === 'function') mostrarToast('Escreva seu comentário.', 'aviso'); return; }

  if (btn) { btn.disabled = true; btn.textContent = 'Enviando…'; }

  const r = await livrosReq('comentarios.php', { livro: slug, nome, cidade, leu, texto });

  if (btn) { btn.disabled = false; btn.textContent = 'Publicar Comentário'; }

  if (r.ok) {
    if (typeof mostrarToast === 'function') mostrarToast(r.mensagem, 'sucesso', 6000);
    form.reset();
  } else {
    if (typeof mostrarToast === 'function') mostrarToast(r.erro || 'Erro ao enviar.', 'erro');
  }
};

/** Escape HTML para exibição segura */
function esc(s) {
  return (s || '').replace(/[&<>"']/g, c =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
}

/* ── Inicialização automática ────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Carregar comentários se houver container e slug
  const slug = document.body.dataset.livro;
  if (slug) carregarComentarios(slug);

  // Adicionar data-livro nos formulários de comentário
  document.querySelectorAll('form[onsubmit*="enviarComentario"]').forEach(f => {
    if (!f.dataset.livro && slug) f.dataset.livro = slug;
  });
});
