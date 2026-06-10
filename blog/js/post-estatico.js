/* ================================================================
   ROBÉRIO DIÓGENES — blog/js/post-estatico.js
   JS compartilhado para todos os posts estáticos do blog.
   Deve ser carregado APÓS window.POST_CONFIG estar definido.
   ================================================================ */
'use strict';

(async function () {
  // window.POST_CONFIG é definido num <script inline> logo APÓS este arquivo.
  // Aguardamos um macrotask (setTimeout 0) para garantir que o inline já rodou.
  await new Promise(r => setTimeout(r, 0));

  const cfg      = window.POST_CONFIG || {};
  const SLUG     = cfg.slug   || '';
  const TITULO   = cfg.titulo || '';
  const URL      = cfg.url    || location.href;
  const API      = '../backend/blog_api.php';
  const API_COM  = '../backend/comentarios.php';

  /* ── Helpers ────────────────────────────────────────────────── */
  function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }
  function dataFmt(iso) {
    try { return new Date(iso).toLocaleDateString('pt-BR',{day:'numeric',month:'long',year:'numeric'}); }
    catch { return iso; }
  }
  function fmtTempo(seg) {
    seg = Math.floor(seg);
    return `${Math.floor(seg/60)}:${(seg%60).toString().padStart(2,'0')}`;
  }
  function toast(msg, tipo = 'ok') {
    const t = document.getElementById('toastRD');
    if (!t) return;
    t.textContent = msg;
    t.style.borderColor = tipo === 'erro' ? '#c0392b' : 'var(--ouro,#B8860B)';
    t.classList.add('visivel');
    clearTimeout(t._t);
    t._t = setTimeout(() => t.classList.remove('visivel'), 3500);
  }
  async function api(path, opts = {}) {
    try {
      const r = await fetch(path, { credentials: 'same-origin', ...opts });
      return await r.json();
    } catch { return { ok: false }; }
  }

  /* ── Sessão ─────────────────────────────────────────────────── */
  let sessao = null;
  const sd = await api('../backend/auth/sessao.php');
  if (sd.ok && sd.logado) {
    sessao = sd.usuario;
    const fi = document.getElementById('comentFormInfo');
    if (fi) fi.innerHTML = `Comentando como <strong>${esc(sessao.nome)}</strong>`;
    const bc = document.getElementById('btnComentar');
    if (bc) bc.disabled = false;
  }

  /* ── Curtidas / likes ──────────────────────────────────────── */
  let likesAtual = 0;
  let jaLikei    = false;
  const btnLike  = document.getElementById('btnLike');
  const contLikes = document.getElementById('contLikes');

  const estadoD = await api(`${API}?acao=post&slug=${encodeURIComponent(SLUG)}`);
  if (estadoD.ok) {
    likesAtual = estadoD.curtidas  || 0;
    jaLikei    = !!estadoD.ja_curtiu;
  }
  if (contLikes) contLikes.textContent = likesAtual;
  if (btnLike && jaLikei) {
    btnLike.classList.add('curtido');
    btnLike.setAttribute('aria-pressed','true');
  }

  if (btnLike) {
    btnLike.addEventListener('click', async () => {
      const d = await api(API, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ acao:'curtir', slug: SLUG }),
      });
      if (d.ok) {
        likesAtual = d.total;
        jaLikei    = !!d.curtiu;
        if (contLikes) contLikes.textContent = likesAtual;
        btnLike.classList.toggle('curtido', jaLikei);
        btnLike.setAttribute('aria-pressed', jaLikei ? 'true' : 'false');
        if (jaLikei) toast('Obrigado pela curtida! 👍');
      } else {
        // Fallback localStorage
        const key = 'like_' + SLUG;
        if (localStorage.getItem(key)) {
          localStorage.removeItem(key); likesAtual = Math.max(0, likesAtual - 1);
          jaLikei = false; btnLike.classList.remove('curtido');
        } else {
          localStorage.setItem(key,'1'); likesAtual++; jaLikei = true;
          btnLike.classList.add('curtido'); toast('Obrigado pela curtida! 👍');
        }
        if (contLikes) contLikes.textContent = likesAtual;
      }
    });
  }

  /* ── Player de áudio (se POST_CONFIG.audioSrc existir) ──────── */
  if (cfg.audioSrc) {
    const wrap   = document.getElementById('audio-player-wrap');
    const audioEl= document.getElementById('audioEl');
    if (wrap && audioEl) {
      audioEl.src = cfg.audioSrc;
      wrap.style.display = 'block';
      const playBtn   = document.getElementById('audioPlayBtn');
      const playIcon  = document.getElementById('audioPlayIcon');
      const barraFill = document.getElementById('audioBarraFill');
      const barra     = document.getElementById('audioBarra');
      const tempoEl   = document.getElementById('audioTempo');
      const muteBtn   = document.getElementById('audioMuteBtn');
      if (playBtn) {
        playBtn.addEventListener('click', () => audioEl.paused ? audioEl.play() : audioEl.pause());
        audioEl.addEventListener('play',  () => { if(playIcon) playIcon.className='fa fa-pause'; });
        audioEl.addEventListener('pause', () => { if(playIcon) playIcon.className='fa fa-play'; });
        audioEl.addEventListener('ended', () => { if(playIcon) playIcon.className='fa fa-play'; if(barraFill) barraFill.style.width='0%'; });
        audioEl.addEventListener('timeupdate', () => {
          if (!audioEl.duration) return;
          const pct = (audioEl.currentTime / audioEl.duration) * 100;
          if (barraFill) barraFill.style.width = pct + '%';
          if (tempoEl) tempoEl.textContent = `${fmtTempo(audioEl.currentTime)} / ${fmtTempo(audioEl.duration)}`;
        });
        if (barra) barra.addEventListener('click', e => {
          if (!audioEl.duration) return;
          const rect = barra.getBoundingClientRect();
          audioEl.currentTime = ((e.clientX - rect.left) / rect.width) * audioEl.duration;
        });
        if (muteBtn) muteBtn.addEventListener('click', () => {
          audioEl.muted = !audioEl.muted;
          const i = muteBtn.querySelector('i');
          if (i) i.className = audioEl.muted ? 'fa fa-volume-xmark' : 'fa fa-volume-high';
        });
      }
    }
  }

  /* ── Compartilhamento ─────────────────────────────────────── */
  const togBtn  = document.getElementById('compartilharToggle');
  const painel  = document.getElementById('compartilharPainel');
  const urlEnc  = encodeURIComponent(URL);
  const titEnc  = encodeURIComponent(TITULO);

  const face = document.getElementById('cpFace');
  const wa   = document.getElementById('cpWa');
  const xBtn = document.getElementById('cpX');
  const tk   = document.getElementById('cpTk');
  const lnk  = document.getElementById('cpLink');

  if (face) face.href = `https://www.facebook.com/sharer/sharer.php?u=${urlEnc}`;
  if (wa)   wa.href   = `https://wa.me/?text=${titEnc}%20${urlEnc}`;
  if (xBtn) xBtn.href = `https://twitter.com/intent/tweet?url=${urlEnc}&text=${titEnc}`;
  if (tk)   tk.href   = `https://www.tiktok.com/`;
  if (lnk)  lnk.addEventListener('click', () => {
    navigator.clipboard?.writeText(URL).then(() => toast('Link copiado!')).catch(() => toast('Copie: ' + URL));
    painel?.classList.remove('aberto');
    if (togBtn) togBtn.setAttribute('aria-expanded','false');
  });

  if (togBtn && painel) {
    togBtn.addEventListener('click', () => {
      const open = painel.classList.toggle('aberto');
      togBtn.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', e => {
      if (!togBtn.contains(e.target) && !painel.contains(e.target)) {
        painel.classList.remove('aberto');
        togBtn.setAttribute('aria-expanded','false');
      }
    });
  }

  /* ── Comentários ─────────────────────────────────────────── */
  async function carregarComentarios() {
    const lista = document.getElementById('listaComentarios');
    if (!lista) return;
    // backend/comentarios.php usa ?acao=listar&slug=...
    const d = await api(`${API_COM}?acao=listar&slug=${encodeURIComponent(SLUG)}`);
    if (!d.ok || !d.comentarios?.length) {
      lista.innerHTML = `<div class="coment-vazio"><i class="fa fa-comments"></i>Seja o primeiro a comentar.</div>`;
      return;
    }
    lista.innerHTML = d.comentarios.map(c => `
      <div class="coment-card" id="c-${c.id}">
        <div class="coment-header">
          <div class="coment-avatar">
            ${c.avatar ? `<img src="${esc(c.avatar)}" alt="">` : `<i class="fa fa-user"></i>`}
          </div>
          <div>
            <div class="coment-autor">${esc(c.nome || c.nome_exibicao || 'Leitor')}</div>
            <div class="coment-data">${dataFmt(c.criado_em)}</div>
          </div>
        </div>
        <div class="coment-texto">${esc(c.texto)}</div>
        <div class="coment-footer">
          <button class="coment-curtir ${c.ja_curtiu?'curtido':''}" onclick="curtirComentario(${c.id},this)"
                  aria-label="Curtir comentário">
            <i class="fa fa-heart"></i>
            <span class="coment-curtir-n">${c.curtidas || 0}</span>
          </button>
        </div>
      </div>`).join('');
  }

  window.curtirComentario = async function(id, btn) {
    // backend/comentarios.php usa acao:'curtir'
    const d = await api(API_COM, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ acao:'curtir', id }),
    });
    if (d.ok) {
      btn.classList.toggle('curtido', !!d.curtiu);
      const n = btn.querySelector('.coment-curtir-n');
      if (n) n.textContent = d.total ?? d.curtidas ?? 0;
    }
  };

  const btnComentar = document.getElementById('btnComentar');
  if (btnComentar && sessao) {
    btnComentar.addEventListener('click', async () => {
      const ta = document.getElementById('comentTexto');
      if (!ta || !ta.value.trim()) { toast('Escreva algo antes de enviar.','erro'); return; }
      btnComentar.disabled = true;
      // backend/comentarios.php usa acao:'criar'
      const d = await api(API_COM, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ acao:'criar', slug: SLUG, texto: ta.value.trim() }),
      });
      btnComentar.disabled = false;
      if (d.ok) {
        ta.value = '';
        toast('Comentário enviado! 🎉');
        carregarComentarios();
      } else {
        toast(d.erro || 'Erro ao enviar comentário.','erro');
      }
    });
  }

  carregarComentarios();

  /* ── Barra de progresso de leitura ──────────────────────── */
  const progEl = document.getElementById('leitura-progresso');
  let prog70 = false;

  function atualizarProgresso() {
    const corpo = document.getElementById('postCorpo');
    if (!progEl || !corpo) return;
    const rect  = corpo.getBoundingClientRect();
    const total = corpo.offsetHeight;
    const lido  = Math.max(0, -rect.top + window.innerHeight * 0.5);
    const pct   = Math.min(100, Math.round((lido / total) * 100));
    progEl.style.width = pct + '%';
    if (pct >= 70 && !prog70 && sessao) {
      prog70 = true;
      api(API, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ acao:'marcar_lido', slug: SLUG, progresso: pct }),
      });
    }
  }

  let ticking = false;
  window.addEventListener('scroll', () => {
    if (!ticking) {
      requestAnimationFrame(() => { atualizarProgresso(); ticking = false; });
      ticking = true;
    }
  }, { passive: true });
  atualizarProgresso();

})();
