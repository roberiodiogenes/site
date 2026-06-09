/* ================================================================
 * ROBÉRIO DIÓGENES — js/livros-shared.js (v3.0)
 * Script compartilhado para todas as páginas de livros individuais.
 *
 * Funcionalidades:
 * ✓ Botão Favoritar (toggle, requer login)
 * ✓ Avaliação por estrelas (1–5, requer login)
 * ✓ Download de amostra gratuita PDF e ePub (requer login)
 * ✓ Contadores públicos (downloads, média de avaliações)
 * ✓ Estado inicial do usuário (já favoritou? já avaliou?)
 * ✓ Redirecionamento para login com mensagem contextual
 * ✓ Feedback visual (toast, animações)
 * ✓ BASE_URL dinâmico — funciona em livros/ (subpasta)
 * ================================================================ */

'use strict';

/* ── BASE URL (páginas de livro estão em livros/, um nível acima de backend/) ── */
const LIVROS_BASE = '../backend';

/* ── SLUG DO LIVRO (definido no <body data-livro="slug">) ──────── */
const LIVRO_SLUG = document.body.dataset.livro || '';

/* ── UTILITÁRIOS ────────────────────────────────────────────────── */

function mostrarToast(msg, tipo = 'info') {
  let toast = document.getElementById('livro-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'livro-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    Object.assign(toast.style, {
      position:     'fixed',
      bottom:       '2rem',
      left:         '50%',
      transform:    'translateX(-50%) translateY(20px)',
      background:   'var(--fundo-card)',
      border:       '1px solid var(--borda-media)',
      borderRadius: 'var(--raio-lg)',
      padding:      '0.8rem 1.5rem',
      fontFamily:   'var(--fonte-ui)',
      fontSize:     '0.92rem',
      color:        'var(--texto)',
      boxShadow:    'var(--sombra-md)',
      zIndex:       '9999',
      opacity:      '0',
      transition:   'all 0.35s cubic-bezier(0.25,0.46,0.45,0.94)',
      pointerEvents:'none',
      maxWidth:     '90vw',
      textAlign:    'center',
    });
    document.body.appendChild(toast);
  }

  const cores = {
    sucesso: 'var(--ouro)',
    erro:    'var(--ferrugem)',
    info:    'var(--ouro-claro)',
  };
  toast.style.borderColor = cores[tipo] || cores.info;
  toast.textContent = msg;

  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';
  });

  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(-50%) translateY(20px)';
  }, 3500);
}

async function chamarAPI(endpoint, opcoes = {}) {
  try {
    const res = await fetch(`${LIVROS_BASE}/${endpoint}`, {
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      ...opcoes,
    });
    const data = await res.json();
    return data;
  } catch (e) {
    console.error('[livros-shared] Erro de rede:', e);
    return { ok: false, erro: 'Erro de conexão. Tente novamente.' };
  }
}

function redirecionarParaLogin(motivo = '') {
  const url = '../login.html' + (motivo ? `?next=${encodeURIComponent(window.location.pathname)}&motivo=${encodeURIComponent(motivo)}` : '');
  window.location.href = url;
}

/* ── ESTADO INICIAL ─────────────────────────────────────────────── */

async function carregarEstado() {
  if (!LIVRO_SLUG) return;

  const [estado, contadores] = await Promise.all([
    chamarAPI(`livros.php?acao=estado&livro=${LIVRO_SLUG}`),
    chamarAPI(`livros.php?acao=contadores&livro=${LIVRO_SLUG}`),
  ]);

  /* Botão de favoritar */
  const btnFav = document.getElementById('btn-favoritar');
  if (btnFav && estado.ok) {
    if (estado.logado) {
      aplicarEstadoFavorito(btnFav, estado.favorito);
    }
  }

  /* Estrelas */
  if (estado.ok && estado.logado && estado.estrelas > 0) {
    aplicarEstrelasUsuario(estado.estrelas);
  }

  /* Contadores públicos */
  if (contadores.ok) {
    const elDownloads = document.getElementById('contador-downloads');
    if (elDownloads && contadores.downloads !== undefined) {
      elDownloads.textContent = contadores.downloads;
    }

    const elMedia = document.getElementById('media-estrelas');
    const elTotal = document.getElementById('total-avaliacoes');
    if (elMedia && contadores.media_estrelas !== null) {
      elMedia.textContent = contadores.media_estrelas.toFixed(1);
    }
    if (elTotal) {
      elTotal.textContent = contadores.total_aval || 0;
    }

    /* Preencher estrelas visuais da média pública */
    renderizarEstrelasMedia(contadores.media_estrelas);
  }
}

/* ── FAVORITAR ──────────────────────────────────────────────────── */

function aplicarEstadoFavorito(btn, isFav) {
  btn.dataset.favoritado = isFav ? '1' : '0';
  const icone = btn.querySelector('.fav-icone');
  const texto = btn.querySelector('.fav-texto');
  if (icone) icone.textContent = isFav ? '♥' : '♡';
  if (texto) texto.textContent = isFav ? 'Favoritado' : 'Favoritar';
  btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
  btn.classList.toggle('favoritado', isFav);
}

async function alternarFavorito() {
  const btn = document.getElementById('btn-favoritar');
  if (!btn || !LIVRO_SLUG) return;

  /* Verificar login */
  const estado = await chamarAPI(`livros.php?acao=estado&livro=${LIVRO_SLUG}`);
  if (!estado.ok || !estado.logado) {
    mostrarToast('Faça login para salvar nos favoritos.', 'info');
    setTimeout(() => redirecionarParaLogin('favoritar'), 1500);
    return;
  }

  btn.disabled = true;
  const data = await chamarAPI('livros.php', {
    method: 'POST',
    body: JSON.stringify({ acao: 'favoritar', livro: LIVRO_SLUG }),
  });
  btn.disabled = false;

  if (data.ok) {
    aplicarEstadoFavorito(btn, data.favorito);
    mostrarToast(data.mensagem, 'sucesso');
  } else {
    mostrarToast(data.erro || 'Erro ao atualizar favorito.', 'erro');
  }
}

/* ── AVALIAÇÃO POR ESTRELAS ─────────────────────────────────────── */

function renderizarEstrelasMedia(media) {
  const container = document.getElementById('estrelas-media');
  if (!container || media === null || media === undefined) return;
  const cheias = Math.round(media);
  container.innerHTML = [1,2,3,4,5].map(n =>
    `<span class="estrela-media ${n <= cheias ? 'cheia' : ''}" aria-hidden="true">★</span>`
  ).join('');
}

function aplicarEstrelasUsuario(n) {
  document.querySelectorAll('.estrela-avaliacao').forEach(el => {
    const val = parseInt(el.dataset.valor);
    el.classList.toggle('selecionada', val <= n);
    el.setAttribute('aria-checked', val === n ? 'true' : 'false');
  });
}

async function avaliar(estrelas) {
  if (!LIVRO_SLUG) return;

  /* Verificar login */
  const estado = await chamarAPI(`livros.php?acao=estado&livro=${LIVRO_SLUG}`);
  if (!estado.ok || !estado.logado) {
    mostrarToast('Faça login para avaliar este livro.', 'info');
    setTimeout(() => redirecionarParaLogin('avaliar'), 1500);
    return;
  }

  const data = await chamarAPI('livros.php', {
    method: 'POST',
    body: JSON.stringify({ acao: 'avaliar', livro: LIVRO_SLUG, estrelas }),
  });

  if (data.ok) {
    aplicarEstrelasUsuario(data.estrelas);
    renderizarEstrelasMedia(data.media_estrelas);
    const elMedia = document.getElementById('media-estrelas');
    const elTotal = document.getElementById('total-avaliacoes');
    if (elMedia) elMedia.textContent = data.media_estrelas.toFixed(1);
    if (elTotal) elTotal.textContent = data.total_aval;
    mostrarToast(data.mensagem, 'sucesso');
  } else {
    mostrarToast(data.erro || 'Erro ao registrar avaliação.', 'erro');
  }
}

/* ── DOWNLOAD ───────────────────────────────────────────────────── */

async function baixarCapitulo(formato) {
  if (!LIVRO_SLUG) return;

  /* Verificar login primeiro */
  const estado = await chamarAPI(`livros.php?acao=estado&livro=${LIVRO_SLUG}`);
  if (!estado.ok || !estado.logado) {
    mostrarToast(`Faça login para baixar a amostra ${formato.toUpperCase()} gratuita.`, 'info');
    setTimeout(() => redirecionarParaLogin('download'), 1500);
    return;
  }

  mostrarToast(`Iniciando download ${formato.toUpperCase()}…`, 'info');

  const url = `${LIVROS_BASE}/downloads.php?livro=${encodeURIComponent(LIVRO_SLUG)}&formato=${encodeURIComponent(formato)}`;
  const link = document.createElement('a');
  link.href = url;
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  setTimeout(() => link.remove(), 2000);

  setTimeout(async () => {
    const contadores = await chamarAPI(`livros.php?acao=contadores&livro=${LIVRO_SLUG}`);
    if (contadores.ok) {
      const el = document.getElementById('contador-downloads');
      if (el) el.textContent = contadores.downloads;
    }
  }, 2000);
}

/* ── BOTÕES DE ACESSO — determina o CTA correto por status ────────
   Substitui os botões de "Amostra" em TODAS as páginas de livro por:
   • Comprador     → Baixar ePub completo (download)
   • Assinante/Admin → Ler agora (leitor online)
   • Visitante/sem acesso → Ler no leitor online (auth lá dentro)
   ─────────────────────────────────────────────────────────────── */

async function carregarBotoesAcesso() {
  if (!LIVRO_SLUG) return;

  /* Placeholder enquanto carrega */
  const heroWrap    = document.querySelector('.download-wrap');
  const sidebarCard = document.querySelector('.download-box');
  const spinner     = '<span style="font-family:var(--fonte-ui);font-size:0.78rem;color:var(--texto-3);opacity:0.6">…</span>';
  if (heroWrap)    heroWrap.innerHTML    = spinner;
  if (sidebarCard) sidebarCard.innerHTML = spinner;

  /* Uma única chamada ao acesso.php resolve tudo:
     motivo = 'nao_logado' | 'compra_aprovada' | 'assinatura_ativa' |
              'gratuito'    | 'sem_acesso'                             */
  let motivo = 'nao_logado';
  try {
    const r = await chamarAPI(`acesso.php?livro=${encodeURIComponent(LIVRO_SLUG)}`);
    if (r) motivo = r.motivo || (r.tem_acesso ? 'tem_acesso' : 'sem_acesso');
  } catch (e) { /* falha silenciosa — trata como visitante */ }

  const logado      = motivo !== 'nao_logado';
  const jaComprou   = motivo === 'compra_aprovada';
  const ehAssinante = motivo === 'assinatura_ativa' || motivo === 'gratuito';

  _renderizarBotoesAcesso(logado, ehAssinante, jaComprou);
}

function _renderizarBotoesAcesso(logado, ehAssinante, jaComprou) {
  const leitorUrl  = `../leitor/?livro=${encodeURIComponent(LIVRO_SLUG)}`;
  const downloadUrl= `${LIVROS_BASE}/downloads.php?livro=${encodeURIComponent(LIVRO_SLUG)}&formato=epub&completo=1`;

  /* Estilos inline compartilhados */
  const styleVerde  = 'display:inline-flex;align-items:center;gap:0.45rem;padding:0.55rem 1.2rem;border-radius:var(--raio);font-family:var(--fonte-display,system-ui);font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;text-decoration:none;background:rgba(39,174,96,0.12);color:#27ae60;border:1px solid rgba(39,174,96,0.5);transition:all 0.2s';
  const styleGhost  = 'display:inline-flex;align-items:center;gap:0.45rem;padding:0.55rem 1.1rem;border-radius:var(--raio);font-family:var(--fonte-display,system-ui);font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;text-decoration:none;background:transparent;color:var(--texto-2);border:1px solid var(--borda-media);transition:all 0.2s';
  const styleEpub   = 'display:inline-flex;align-items:center;gap:0.45rem;padding:0.55rem 1.25rem;border-radius:var(--raio);font-family:var(--fonte-display,system-ui);font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;text-decoration:none;background:var(--ouro);color:#1A0F00;border:1px solid var(--ouro);transition:all 0.2s';

  let heroHtml, sidebarTitulo, sidebarDesc, sidebarBtns;

  if (jaComprou) {
    /* ── Comprador: download epub completo ── */
    heroHtml = `<a href="${downloadUrl}" style="${styleEpub}" aria-label="Baixar ePub completo">
      <i class="fa fa-download" aria-hidden="true"></i> Baixar ePub completo
    </a>`;
    sidebarTitulo = '<i class="fa fa-download" aria-hidden="true"></i> Seu livro';
    sidebarDesc   = 'Você adquiriu este livro. Baixe o ePub completo abaixo.';
    sidebarBtns   = `
      <a href="${downloadUrl}" class="btn btn-primario" style="width:100%;justify-content:center;margin-bottom:0.45rem" aria-label="Baixar ePub completo">
        <i class="fa fa-download" aria-hidden="true"></i> Baixar ePub completo
      </a>
      <a href="${leitorUrl}" class="btn btn-ghost" style="width:100%;justify-content:center;" aria-label="Ler online">
        <i class="fa fa-book-open" aria-hidden="true"></i> Ler online
      </a>`;

  } else if (ehAssinante) {
    /* ── Assinante: ler online ── */
    heroHtml = `<a href="${leitorUrl}" style="${styleVerde}" aria-label="Ler agora no leitor online">
      <i class="fa fa-book-open" aria-hidden="true"></i> Ler agora
    </a>`;
    sidebarTitulo = '<i class="fa fa-crown" aria-hidden="true"></i> Acesso de assinante';
    sidebarDesc   = 'Você tem acesso completo a este livro como assinante.';
    sidebarBtns   = `
      <a href="${leitorUrl}" class="btn btn-primario" style="width:100%;justify-content:center;" aria-label="Ler online agora">
        <i class="fa fa-book-open" aria-hidden="true"></i> Ler online agora
      </a>`;

  } else {
    /* ── Visitante ou logado sem acesso: leitor online ── */
    heroHtml = `<a href="${leitorUrl}" style="${styleGhost}" aria-label="Ler no leitor online">
      <i class="fa fa-book-open" aria-hidden="true"></i> Ler no leitor online
    </a>`;
    sidebarTitulo = '<i class="fa fa-book-open" aria-hidden="true"></i> Ler este livro';
    sidebarDesc   = logado
      ? 'Adquira este livro para baixar o ePub completo ou leia gratuitamente no leitor online.'
      : 'Faça login e adquira este livro para acessar o ePub completo, ou leia no leitor online.';
    sidebarBtns   = `
      <a href="${leitorUrl}" class="btn btn-secundario" style="width:100%;justify-content:center;" aria-label="Ler no leitor online">
        <i class="fa fa-book-open" aria-hidden="true"></i> Ler no leitor online
      </a>`;
  }

  /* ── Aplicar na área hero ── */
  const heroWrap = document.querySelector('.download-wrap');
  if (heroWrap) {
    heroWrap.setAttribute('aria-label', 'Acesso ao livro');
    heroWrap.innerHTML = heroHtml;
  }

  /* ── Aplicar no card sidebar ── */
  const sidebarCard = document.querySelector('.download-box');
  if (sidebarCard) {
    sidebarCard.innerHTML = `
      <p class="sidebar-titulo">${sidebarTitulo}</p>
      <p style="font-family:var(--fonte-corpo);font-size:0.87rem;color:var(--texto-2);line-height:1.7;margin-bottom:0.85rem">${sidebarDesc}</p>
      <div style="display:flex;flex-direction:column;gap:0.5rem">${sidebarBtns}</div>`;
  }
}

/* ── INICIALIZAÇÃO ──────────────────────────────────────────────── */

document.addEventListener('DOMContentLoaded', () => {
  if (!LIVRO_SLUG) {
    console.warn('[livros-shared] Nenhum data-livro encontrado no <body>. Defina data-livro="slug".');
    return;
  }

  /* Bind: botão favoritar */
  const btnFav = document.getElementById('btn-favoritar');
  if (btnFav) {
    btnFav.addEventListener('click', alternarFavorito);
  }

  /* Bind: estrelas de avaliação */
  document.querySelectorAll('.estrela-avaliacao').forEach(el => {
    el.addEventListener('click', () => avaliar(parseInt(el.dataset.valor)));
    el.addEventListener('mouseenter', () => {
      const val = parseInt(el.dataset.valor);
      document.querySelectorAll('.estrela-avaliacao').forEach(s => {
        s.classList.toggle('hover', parseInt(s.dataset.valor) <= val);
      });
    });
    el.addEventListener('mouseleave', () => {
      document.querySelectorAll('.estrela-avaliacao').forEach(s => s.classList.remove('hover'));
    });
    el.setAttribute('role', 'radio');
    el.setAttribute('tabindex', '0');
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        avaliar(parseInt(el.dataset.valor));
      }
    });
  });

  /* Botões de acesso dinâmicos (substitui os botões de amostra estáticos) */
  carregarBotoesAcesso();

  /* Bind: botões de amostra legados (caso existam em páginas não atualizadas) */
  document.querySelectorAll('[data-download]').forEach(btn => {
    btn.addEventListener('click', () => baixarCapitulo(btn.dataset.download));
  });

  /* Carregar estado do livro */
  carregarEstado();
});
