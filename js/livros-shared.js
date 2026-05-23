/* ================================================================
   ROBÉRIO DIÓGENES — js/livros-shared.js  (v2)
   Funções compartilhadas por todas as páginas de livros:
   - Newsletter real
   - Comentários reais
   - Favoritar (toggle, só logado)
   - Avaliar com estrelas (só logado)
   - Download protegido PDF/ePub (só logado)
   - Contadores públicos de downloads
   ================================================================ */
'use strict';

const BASE_LIVROS = '../backend';

/* ── Requisição base ─────────────────────────────────────────── */
async function req(endpoint, dados = null, metodo = 'POST') {
  try {
    const opts = {
      method: metodo,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
    };
    if (dados !== null && metodo !== 'GET') opts.body = JSON.stringify(dados);
    const r = await fetch(BASE_LIVROS + '/' + endpoint, opts);
    return await r.json();
  } catch (e) {
    return { ok: false, erro: 'Falha na conexão.' };
  }
}

function toast(msg, tipo = 'sucesso', dur = 4000) {
  if (typeof mostrarToast === 'function') mostrarToast(msg, tipo, dur);
  else console.log('[' + tipo + '] ' + msg);
}

function esc(s) {
  return (s || '').replace(/[&<>"']/g,
    c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
}

/* ── Slug do livro atual ─────────────────────────────────────── */
function getSlug() {
  return document.body.dataset.livro || '';
}

/* ════════════════════════════════════════════════════════════════
   NEWSLETTER
   ════════════════════════════════════════════════════════════════ */
window.submeterNewsletter = async function(e) {
  e.preventDefault();
  const input = e.target.querySelector('input[type="email"]');
  const btn   = e.target.querySelector('button[type="submit"]');
  if (!input?.value) return;
  if (btn) { btn.disabled = true; btn.textContent = 'Aguarde…'; }
  const r = await req('newsletter.php', { email: input.value.trim() });
  if (btn) { btn.disabled = false; btn.textContent = 'Inscrever-se'; }
  r.ok ? toast(r.mensagem || 'Inscrição realizada! 📖', 'sucesso', 5000)
       : toast(r.erro || 'Erro na inscrição.', 'erro');
  if (r.ok) input.value = '';
};

window.inscricaoEmailLivro = async function(email) {
  if (!email || !email.includes('@')) return;
  await req('newsletter.php', { email: email.trim() });
};

/* ════════════════════════════════════════════════════════════════
   COMENTÁRIOS
   ════════════════════════════════════════════════════════════════ */
async function carregarComentarios(slug) {
  const container = document.getElementById('comentariosLista');
  if (!container || !slug) return;
  const r = await req(`comentarios.php?livro=${encodeURIComponent(slug)}`, null, 'GET');
  if (!r.ok || !r.comentarios?.length) return;
  r.comentarios.forEach(c => {
    const leuTexto = { sim:'Leu o livro completo', cap:'Leu o capítulo gratuito', nao:'Ainda não leu', '':{} }[c.leu] || '';
    const div = document.createElement('div');
    div.className = 'com-card r d2';
    div.innerHTML = `
      <span class="com-aspas" aria-hidden="true">"</span>
      <p class="com-texto">${esc(c.texto)}</p>
      <p class="com-autor">— ${esc(c.nome)}${c.cidade ? ' · '+esc(c.cidade) : ''}${leuTexto ? ' · '+leuTexto : ''} · ${c.data}</p>`;
    container.appendChild(div);
  });
}

window.enviarComentario = async function(e) {
  e.preventDefault();
  const form = e.target;
  const btn  = form.querySelector('button[type="submit"]');
  const slug = form.dataset.livro || getSlug();
  const get  = id => (document.getElementById(id) || form.querySelector(`[name="${id}"]`))?.value?.trim() ?? '';
  const nome  = get('cn') || get('nome');
  const cidade= get('cc') || get('cidade');
  const leu   = get('cl') || get('leu');
  const texto = get('ct') || get('texto') || get('comentario');
  if (!nome)  { toast('Informe seu nome.', 'aviso'); return; }
  if (!texto) { toast('Escreva seu comentário.', 'aviso'); return; }
  if (btn) { btn.disabled = true; btn.textContent = 'Enviando…'; }
  const r = await req('comentarios.php', { livro: slug, nome, cidade, leu, texto });
  if (btn) { btn.disabled = false; btn.textContent = 'Publicar Comentário'; }
  if (r.ok) {
    toast(r.mensagem, 'sucesso', 6000);
    form.reset();
    // Mostrar comentário otimisticamente com badge de "aguardando moderação"
    const lista = document.getElementById('comentariosLista');
    if (lista && nome && texto) {
      const div = document.createElement('div');
      div.className = 'com-card';
      div.style.cssText = 'opacity:.7;border-style:dashed;';
      div.innerHTML = `
        <span class="com-aspas" aria-hidden="true">"</span>
        <p class="com-texto">${esc(texto)}</p>
        <p class="com-autor">— ${esc(nome)}${cidade ? ' · '+esc(cidade) : ''}
          <span style="font-size:.75em;color:#f6ad55;margin-left:.5rem;">⏳ aguardando moderação</span>
        </p>`;
      lista.prepend(div);
    }
  } else {
    toast(r.erro || 'Erro ao enviar.', 'erro');
  }
};

/* ════════════════════════════════════════════════════════════════
   FAVORITAR
   ════════════════════════════════════════════════════════════════ */
async function toggleFavorito() {
  const slug = getSlug();
  const btn  = document.getElementById('btnFavoritar');
  if (!btn) return;

  btn.disabled = true;
  const r = await req('livros.php', { acao: 'favoritar', livro: slug });
  btn.disabled = false;

  if (!r.ok) {
    if (r.erro === 'Você precisa estar logado.') {
      toast('Faça login para favoritar este livro.', 'aviso', 4000);
    } else {
      toast(r.erro || 'Erro ao favoritar.', 'erro');
    }
    return;
  }

  atualizarBtnFavorito(r.favorito);
  toast(r.mensagem, r.favorito ? 'sucesso' : 'info', 3000);
}

function atualizarBtnFavorito(ativo) {
  const btn = document.getElementById('btnFavoritar');
  if (!btn) return;
  btn.dataset.favoritado = ativo ? '1' : '0';
  btn.classList.toggle('favoritado', ativo);
  btn.setAttribute('aria-pressed', String(ativo));
  const icone = btn.querySelector('.fav-icone');
  if (icone) icone.className = 'fav-icone fa ' + (ativo ? 'fa-heart' : 'fa-heart-o');
  const texto = btn.querySelector('.fav-texto');
  if (texto) texto.textContent = ativo ? 'Favoritado' : 'Favoritar';
}

/* ════════════════════════════════════════════════════════════════
   AVALIAÇÃO COM ESTRELAS
   ════════════════════════════════════════════════════════════════ */
function iniciarEstrelas() {
  const wrap = document.getElementById('avaliacaoEstrelas');
  if (!wrap) return;

  const estrelas = wrap.querySelectorAll('.estrela-btn');
  if (!estrelas.length) return;

  // Hover
  estrelas.forEach((btn, idx) => {
    btn.addEventListener('mouseenter', () => iluminar(idx + 1));
    btn.addEventListener('mouseleave', () => iluminar(Number(wrap.dataset.selecionada || 0)));
    btn.addEventListener('click',      () => avaliar(idx + 1));
  });
}

function iluminar(ate) {
  const wrap = document.getElementById('avaliacaoEstrelas');
  if (!wrap) return;
  wrap.querySelectorAll('.estrela-btn').forEach((btn, idx) => {
    btn.classList.toggle('iluminada', idx < ate);
  });
}

async function avaliar(estrelas) {
  const wrap = document.getElementById('avaliacaoEstrelas');
  const slug = getSlug();

  const r = await req('livros.php', { acao: 'avaliar', livro: slug, estrelas });

  if (!r.ok) {
    if (r.erro?.includes('logado')) {
      toast('Faça login para avaliar este livro.', 'aviso');
    } else {
      toast(r.erro || 'Erro ao avaliar.', 'erro');
    }
    return;
  }

  if (wrap) wrap.dataset.selecionada = estrelas;
  iluminar(estrelas);
  toast(r.mensagem, 'sucesso', 3000);

  // Atualizar média exibida
  const elMedia = document.getElementById('mediaEstrelas');
  const elTotal = document.getElementById('totalAval');
  if (elMedia && r.media_estrelas) elMedia.textContent = r.media_estrelas.toFixed(1);
  if (elTotal && r.total_aval)  elTotal.textContent = r.total_aval;
}

/* ════════════════════════════════════════════════════════════════
   DOWNLOAD PROTEGIDO
   ════════════════════════════════════════════════════════════════ */
window.baixarCapitulo = async function(formato) {
  const slug = getSlug();
  if (!slug) return;

  const btn = document.querySelector(`[data-formato="${formato}"]`);
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Preparando…'; }

  // Verificar sessão antes
  let sessao;
  try {
    const r = await fetch('../backend/auth/sessao.php', { credentials: 'same-origin' });
    sessao = await r.json();
  } catch { sessao = { ok: false }; }

  if (!sessao?.ok || !sessao.logado) {
    if (btn) { btn.disabled = false; btn.innerHTML = labelBtn(formato); }
    toast('Faça login para baixar este capítulo gratuitamente.', 'aviso', 5000);
    setTimeout(() => { window.location.href = '../login.html'; }, 2000);
    return;
  }

  // Disparar download via link temporário
  const url = `../backend/downloads.php?livro=${encodeURIComponent(slug)}&formato=${formato}`;
  const a = document.createElement('a');
  a.href = url;
  a.click();

  if (btn) {
    setTimeout(() => {
      btn.disabled = false;
      btn.innerHTML = labelBtn(formato);
    }, 2500);
  }

  toast(`Download em ${formato.toUpperCase()} iniciado! Boa leitura. 📖`, 'sucesso', 4000);
};

function labelBtn(formato) {
  return formato === 'pdf'
    ? '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Baixar PDF'
    : '<i class="fa fa-book" aria-hidden="true"></i> Baixar ePub';
}

/* ════════════════════════════════════════════════════════════════
   CONTADORES PÚBLICOS (total de downloads do livro)
   ════════════════════════════════════════════════════════════════ */
async function carregarContadores(slug) {
  const r = await req(`livros.php?acao=contadores&livro=${encodeURIComponent(slug)}`, null, 'GET');
  if (!r.ok) return;

  const elDl    = document.getElementById('totalDownloads');
  const elMedia = document.getElementById('mediaEstrelas');
  const elTotal = document.getElementById('totalAval');

  if (elDl    && r.downloads     !== undefined) elDl.textContent    = r.downloads.toLocaleString('pt-BR');
  if (elMedia && r.media_estrelas !== null)      elMedia.textContent = r.media_estrelas.toFixed(1);
  if (elTotal && r.total_aval    !== undefined)  elTotal.textContent = r.total_aval;
}

/* ════════════════════════════════════════════════════════════════
   ESTADO DO USUÁRIO (carregar fav e estrelas ao abrir página)
   ════════════════════════════════════════════════════════════════ */
async function carregarEstadoUsuario(slug) {
  const r = await req(`livros.php?acao=estado&livro=${encodeURIComponent(slug)}`, null, 'GET');
  if (!r.ok || !r.logado) return;

  atualizarBtnFavorito(r.favorito);

  if (r.estrelas > 0) {
    const wrap = document.getElementById('avaliacaoEstrelas');
    if (wrap) wrap.dataset.selecionada = r.estrelas;
    iluminar(r.estrelas);
  }
}

/* ════════════════════════════════════════════════════════════════
   INICIALIZAÇÃO
   ════════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', async () => {
  const slug = getSlug();
  if (!slug) return;

  // Carregar dados em paralelo
  await Promise.all([
    carregarComentarios(slug),
    carregarContadores(slug),
    carregarEstadoUsuario(slug),
  ]);

  // Iniciar sistema de estrelas
  iniciarEstrelas();

  // Event: botão favoritar
  const btnFav = document.getElementById('btnFavoritar');
  if (btnFav) btnFav.addEventListener('click', toggleFavorito);

  // Formulários de comentário — associar slug
  document.querySelectorAll('form[onsubmit*="enviarComentario"]').forEach(f => {
    if (!f.dataset.livro) f.dataset.livro = slug;
  });
});
