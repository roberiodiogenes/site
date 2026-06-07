/* ================================================================
   ROBÉRIO DIÓGENES — js/cookies-lgpd.js
   Banner de consentimento de cookies conforme LGPD

   · Exibido apenas na primeira visita (ou se preferência expirou)
   · Preferência salva em cookie por 365 dias
   · Opções: "Aceitar todos" | "Só essenciais"
   · Não bloqueia o uso do site (não é cookie wall)
   · Link para Política de Privacidade
   ================================================================ */

(function () {
  'use strict';

  const COOKIE_NAME = 'rd_cookies_consent';
  const DIAS        = 365;

  /* ── Verificar se já há consentimento ───────────────────── */
  function _lerCookie(nome) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + nome + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  function _gravarCookie(nome, valor, dias) {
    const expira = new Date(Date.now() + dias * 864e5).toUTCString();
    document.cookie = `${nome}=${encodeURIComponent(valor)};expires=${expira};path=/;SameSite=Lax`;
  }

  /* Já respondeu — não exibe */
  if (_lerCookie(COOKIE_NAME)) return;

  /* ── Detectar prefixo de caminho para links ─────────────── */
  const _path    = window.location.pathname;
  const _prefix  = (_path.includes('/livros/') || _path.includes('/blog/') || _path.includes('/leitor/'))
    ? '../' : '';

  /* ── Injetar CSS ────────────────────────────────────────── */
  const style = document.createElement('style');
  style.textContent = `
    #rd-cookie-banner {
      position: fixed;
      bottom: 0; left: 0; right: 0;
      z-index: 8999;
      background: #1A0F00;
      border-top: 2px solid #B8860B;
      padding: 1rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: .85rem;
      font-family: 'Segoe UI', system-ui, sans-serif;
      font-size: .83rem;
      color: #C8B898;
      box-shadow: 0 -4px 24px rgba(0,0,0,.4);
      transform: translateY(100%);
      transition: transform .35s ease;
    }
    #rd-cookie-banner.rd-visivel { transform: translateY(0); }

    #rd-cookie-texto {
      flex: 1;
      min-width: 200px;
      line-height: 1.55;
    }
    #rd-cookie-texto a {
      color: #B8860B;
      text-decoration: underline;
    }
    #rd-cookie-acoes {
      display: flex;
      gap: .6rem;
      flex-shrink: 0;
      flex-wrap: wrap;
    }
    .rd-cookie-btn {
      padding: .5rem 1.1rem;
      border-radius: 5px;
      font-size: .8rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      letter-spacing: .04em;
      transition: opacity .15s;
      white-space: nowrap;
    }
    .rd-cookie-btn:hover { opacity: .85; }
    .rd-cookie-btn-aceitar {
      background: #B8860B;
      color: #1A0F00;
    }
    .rd-cookie-btn-essencial {
      background: transparent;
      border: 1px solid rgba(184,134,11,.4);
      color: #8C7D65;
    }
    .rd-cookie-btn-essencial:hover { border-color: #B8860B; color: #B8860B; }
  `;
  document.head.appendChild(style);

  /* ── Injetar HTML ───────────────────────────────────────── */
  const banner = document.createElement('div');
  banner.id = 'rd-cookie-banner';
  banner.setAttribute('role', 'region');
  banner.setAttribute('aria-label', 'Consentimento de cookies');
  banner.innerHTML = `
    <div id="rd-cookie-texto">
      <strong style="color:#E8DCC8">Este site usa cookies.</strong>
      Usamos cookies essenciais para o funcionamento do site e cookies funcionais para salvar suas preferências (tema, newsletter). Não usamos cookies de rastreamento de terceiros.
      <a href="${_prefix}privacidade.html" target="_blank" rel="noopener">Saiba mais</a>.
    </div>
    <div id="rd-cookie-acoes">
      <button class="rd-cookie-btn rd-cookie-btn-essencial" id="rdCookieEssencial">
        Só essenciais
      </button>
      <button class="rd-cookie-btn rd-cookie-btn-aceitar" id="rdCookieAceitar">
        Aceitar todos
      </button>
    </div>
  `;
  document.body.appendChild(banner);

  /* Animar entrada após renderização */
  requestAnimationFrame(() => {
    requestAnimationFrame(() => banner.classList.add('rd-visivel'));
  });

  /* ── Handlers ────────────────────────────────────────────── */
  function _fechar(valor) {
    _gravarCookie(COOKIE_NAME, valor, DIAS);
    banner.style.transform = 'translateY(100%)';
    setTimeout(() => banner.remove(), 400);

    /* Disparar evento para outros scripts saberem da decisão */
    document.dispatchEvent(new CustomEvent('rd:cookieConsent', { detail: { tipo: valor } }));
  }

  document.getElementById('rdCookieAceitar').addEventListener('click', function () {
    _fechar('all');
  });

  document.getElementById('rdCookieEssencial').addEventListener('click', function () {
    _fechar('essential');
  });

  /* ── API pública ─────────────────────────────────────────── */

  /**
   * Verifica se o usuário aceitou todos os cookies.
   * Uso: if (window.rdCookieAceito()) { /* ativa analytics *\/ }
   */
  window.rdCookieAceito = function () {
    return _lerCookie(COOKIE_NAME) === 'all';
  };

  /**
   * Abre o painel de cookies novamente (ex.: link no rodapé).
   */
  window.rdCookieAbrir = function () {
    if (!document.getElementById('rd-cookie-banner')) {
      document.body.appendChild(banner);
      requestAnimationFrame(() => {
        requestAnimationFrame(() => banner.classList.add('rd-visivel'));
      });
    }
  };

})();
