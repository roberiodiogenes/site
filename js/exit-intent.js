/* ================================================================
   ROBÉRIO DIÓGENES — js/exit-intent.js
   Popup de intenção de saída (exit-intent) para captura de leads.

   Inclua no final do <body> das páginas desejadas:
     <script src="js/exit-intent.js"></script>

   Triggeres:
   1. Mouse sai pelo topo da janela (cursor próximo ao topo)
   2. Após 40 segundos na página sem interação
   3. Ao rolar para cima rapidamente (sinal de abandono)

   Só exibe UMA vez por sessão. Nunca exibe para usuários já
   inscritos (verifica cookie 'nl_confirmado').
   ================================================================ */

(function () {
  'use strict';

  /* ── Configurações ──────────────────────────────────────────── */
  const SESSAO_KEY  = 'rd_exit_shown';   // sessionStorage — não repete na sessão
  const COOKIE_CONF = 'nl_confirmado';   // cookie setado após confirmação
  const DELAY_AUTO  = 40 * 1000;        // 40 s até trigger automático
  const MOUSE_TOP   = 20;               // px do topo para trigger de mouse

  /* ── Verificar se deve exibir ──────────────────────────────── */
  function _cookieExiste(nome) {
    return document.cookie.split(';').some(c => c.trim().startsWith(nome + '='));
  }

  if (sessionStorage.getItem(SESSAO_KEY)) return;   // já mostrou nesta sessão
  if (_cookieExiste(COOKIE_CONF)) return;            // já é assinante

  /* ── Injetar CSS ────────────────────────────────────────────── */
  const style = document.createElement('style');
  style.textContent = `
    #ei-overlay {
      position: fixed; inset: 0; z-index: 9000;
      background: rgba(10, 6, 2, .75);
      display: flex; align-items: center; justify-content: center;
      padding: 1rem;
      opacity: 0; pointer-events: none;
      transition: opacity .3s ease;
    }
    #ei-overlay.ei-aberto {
      opacity: 1; pointer-events: all;
    }
    #ei-box {
      background: #FAF7F2;
      border-top: 4px solid #B8860B;
      border-radius: 10px;
      padding: 2.5rem 2rem;
      max-width: 480px; width: 100%;
      text-align: center;
      transform: translateY(20px);
      transition: transform .3s ease;
      position: relative;
      font-family: Georgia, 'Times New Roman', serif;
    }
    #ei-overlay.ei-aberto #ei-box {
      transform: translateY(0);
    }
    #ei-fechar {
      position: absolute; top: .75rem; right: 1rem;
      background: none; border: none; cursor: pointer;
      font-size: 1.4rem; color: #8C7D65; line-height: 1;
    }
    #ei-fechar:hover { color: #2C2418; }
    #ei-eyebrow {
      font-size: .62rem; letter-spacing: .25em;
      text-transform: uppercase; color: #B8860B;
      margin-bottom: .85rem;
    }
    #ei-titulo {
      font-size: 1.5rem; font-weight: 400;
      color: #2C2418; margin: 0 0 .5rem; line-height: 1.3;
    }
    #ei-subtitulo {
      font-size: .9rem; color: #5C4F3A;
      line-height: 1.65; margin-bottom: 1.5rem;
    }
    #ei-form {
      display: flex; flex-direction: column; gap: .6rem;
    }
    #ei-email {
      width: 100%; padding: .75rem 1rem;
      border: 1px solid #D4C9B0; border-radius: 6px;
      font-family: Georgia, serif; font-size: .95rem;
      color: #2C2418; background: #fff;
      box-sizing: border-box; outline: none;
      transition: border-color .2s;
    }
    #ei-email:focus { border-color: #B8860B; }
    #ei-btn {
      padding: .8rem 1rem;
      background: #B8860B; color: #1A0F00;
      border: none; border-radius: 6px;
      font-family: Georgia, serif; font-size: .95rem;
      font-weight: 700; cursor: pointer; letter-spacing: .04em;
      transition: background .2s;
    }
    #ei-btn:hover { background: #9A7009; }
    #ei-btn:disabled { opacity: .6; cursor: not-allowed; }
    #ei-msg { font-size: .82rem; min-height: 1.2rem; }
    #ei-msg.ei-ok    { color: #2E7D32; }
    #ei-msg.ei-erro  { color: #b71c1c; }
    #ei-recusar {
      margin-top: .5rem; display: block;
      font-size: .72rem; color: #8C7D65;
      cursor: pointer; background: none; border: none;
      text-decoration: underline;
    }
    #ei-recusar:hover { color: #2C2418; }
    #ei-beneficios {
      display: flex; justify-content: center; gap: 1.25rem;
      margin-bottom: 1.25rem; flex-wrap: wrap;
    }
    .ei-item {
      font-size: .75rem; color: #5C4F3A;
      display: flex; align-items: center; gap: .3rem;
    }
    .ei-item::before { content: '✓'; color: #B8860B; font-weight: 700; }
  `;
  document.head.appendChild(style);

  /* ── Injetar HTML ───────────────────────────────────────────── */
  const overlay = document.createElement('div');
  overlay.id = 'ei-overlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('aria-label', 'Inscreva-se na newsletter');
  overlay.innerHTML = `
    <div id="ei-box">
      <button id="ei-fechar" aria-label="Fechar">×</button>
      <p id="ei-eyebrow">Antes de ir embora</p>
      <h2 id="ei-titulo">Fique por dentro dos bastidores</h2>
      <p id="ei-subtitulo">
        Novos lançamentos, trechos inéditos e reflexões do autor —
        direto no seu e-mail. Sem spam.
      </p>
      <div id="ei-beneficios">
        <span class="ei-item">Novos lançamentos</span>
        <span class="ei-item">Trechos exclusivos</span>
        <span class="ei-item">Bastidores do autor</span>
      </div>
      <form id="ei-form" novalidate>
        <input
          id="ei-email" type="email"
          placeholder="Seu melhor e-mail"
          autocomplete="email"
          required
          aria-label="E-mail"
        />
        <button id="ei-btn" type="submit">
          Quero receber →
        </button>
        <p id="ei-msg" role="status"></p>
      </form>
      <button id="ei-recusar" type="button">Não, obrigado — vou sair</button>
    </div>
  `;
  document.body.appendChild(overlay);

  /* ── Mostrar / esconder ────────────────────────────────────── */
  let _mostrado = false;

  function mostrar() {
    if (_mostrado) return;
    _mostrado = true;
    sessionStorage.setItem(SESSAO_KEY, '1');
    overlay.classList.add('ei-aberto');
    setTimeout(() => document.getElementById('ei-email')?.focus(), 320);
  }

  function esconder() {
    overlay.classList.remove('ei-aberto');
  }

  /* ── Fechar ao clicar fora / botão fechar / recusar ────────── */
  document.getElementById('ei-fechar').addEventListener('click', esconder);
  document.getElementById('ei-recusar').addEventListener('click', esconder);
  overlay.addEventListener('click', e => { if (e.target === overlay) esconder(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') esconder(); });

  /* ── Envio do formulário ────────────────────────────────────── */
  document.getElementById('ei-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const emailVal = document.getElementById('ei-email').value.trim();
    const btn      = document.getElementById('ei-btn');
    const msg      = document.getElementById('ei-msg');

    if (!emailVal || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
      msg.className   = 'ei-erro';
      msg.textContent = 'Por favor, informe um e-mail válido.';
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'Inscrevendo…';
    msg.textContent = '';

    /* Detecta prefixo do backend (raiz ou subpasta) */
    const depth = window.location.pathname.split('/').filter(Boolean).length;
    const base  = window.location.pathname.includes('/livros/')
               || window.location.pathname.includes('/blog/')
               || window.location.pathname.includes('/leitor/')
      ? '../backend' : 'backend';

    try {
      const res  = await fetch(`${base}/newsletter.php`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ email: emailVal.toLowerCase(), origem: 'popup_saida' }),
      });
      const json = await res.json();

      if (json.ok) {
        msg.className   = 'ei-ok';
        msg.textContent = 'Perfeito! Verifique seu e-mail para confirmar.';
        btn.textContent = '✓ Inscrito!';
        /* Cookie de 365 dias para não mostrar novamente */
        document.cookie = `${COOKIE_CONF}=1;max-age=${365 * 86400};path=/;SameSite=Lax`;
        setTimeout(esconder, 3000);
      } else {
        throw new Error(json.erro || 'Erro ao processar.');
      }
    } catch (err) {
      msg.className   = 'ei-erro';
      msg.textContent = err.message || 'Erro. Tente novamente.';
      btn.disabled    = false;
      btn.textContent = 'Quero receber →';
    }
  });

  /* ── Triggers ───────────────────────────────────────────────── */

  // 1. Mouse saindo pelo topo
  document.addEventListener('mouseleave', function (e) {
    if (e.clientY <= MOUSE_TOP) mostrar();
  });

  // 2. Timer automático (40 s)
  const _timer = setTimeout(mostrar, DELAY_AUTO);

  // 3. Scroll rápido para cima (abandono)
  let _lastY = window.scrollY;
  let _lastT = Date.now();
  window.addEventListener('scroll', function () {
    const currentY = window.scrollY;
    const currentT = Date.now();
    const velocidade = (currentY - _lastY) / (currentT - _lastT); // px/ms
    // Velocidade negativa (rolar para cima) rápida
    if (velocidade < -2 && currentY < 300) mostrar();
    _lastY = currentY;
    _lastT = currentT;
  }, { passive: true });

  // Limpar timer se o usuário interagir com a página normalmente
  ['click', 'keydown', 'touchstart'].forEach(ev => {
    document.addEventListener(ev, () => {
      // Não cancela o timer — só indica intenção de permanência
    }, { once: true, passive: true });
  });

})();
