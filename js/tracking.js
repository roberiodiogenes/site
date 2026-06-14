/* ================================================================
   ROBÉRIO DIÓGENES — js/tracking.js
   Módulo centralizado de rastreamento e BI.

   ╔══════════════════════════════════════════════════════════════╗
   ║  CONFIGURE AQUI — preencha com seus IDs reais               ║
   ║  (todos os serviços abaixo são gratuitos)                    ║
   ╚══════════════════════════════════════════════════════════════╝

   Como criar cada conta:
   • GTM:     https://tagmanager.google.com → Criar conta → Formato: GTM-XXXXXXX
   • GA4:     https://analytics.google.com  → Criar propriedade → Formato: G-XXXXXXXXXX
   • Meta:    https://business.facebook.com → Events Manager → Formato: 15 dígitos
   • TikTok:  https://ads.tiktok.com → Assets → Events → Formato: CXXXXXXXXXXXXXXXX
   • Clarity: https://clarity.microsoft.com → Criar projeto → Formato: 10 caracteres
   ================================================================ */
'use strict';

(function () {

  /* ══ CONFIGURAÇÃO ═══════════════════════════════════════════════
     Preencha os IDs abaixo. Deixe em branco ('') para desativar
     um serviço específico.
  ═══════════════════════════════════════════════════════════════ */
  var C = {
    GTM_ID:      'GTM-PZXC4SK8', // Google Tag Manager
    GA4_ID:      'G-D4846SQWW1', // Google Analytics 4
    META_PIXEL:  '',             // Ex: '1234567890123' — Meta Pixel (Facebook/Instagram)
    TIKTOK_PIXEL:'',             // Ex: 'CXXXXXXXXXXXXXXXXX' — TikTok Pixel
    CLARITY_ID:  'x52noc8f87',  // Microsoft Clarity
    BI_ENDPOINT: '/roberiodiogenes.com/backend/tracking.php', // URL do endpoint BI local
  };

  /* ══ DETECÇÃO DE AMBIENTE ════════════════════════════════════ */
  var isProd = location.hostname === 'www.roberiodiogenes.com'
            || location.hostname === 'roberiodiogenes.com';
  var biEndpoint = isProd
    ? '/backend/tracking.php'
    : '/roberiodiogenes.com/backend/tracking.php';

  /* ══ ESTADO GLOBAL ═══════════════════════════════════════════ */
  var RD_TRACK = {
    sessionId:   null,
    consentDado: false,
    utms:        {},
    pageStart:   Date.now(),
    paginaSlug:  '',
    paginaTipo:  '',   // 'livro' | 'post' | 'leitor' | 'home' | 'autor' | etc.
    pixelsCarregados: { meta: false, tiktok: false, clarity: false, ga4: false, gtm: false },
  };
  window.RD_TRACK = RD_TRACK;

  /* ══ HELPERS ═════════════════════════════════════════════════ */
  function log() {
    if (!isProd && window.console) console.log('[RD:track]', ...arguments);
  }
  function safe(fn) {
    try { return fn(); } catch(e) { log('erro:', e); }
  }
  function getCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  }
  function setCookie(name, val, days) {
    var exp = new Date(); exp.setTime(exp.getTime() + days * 864e5);
    document.cookie = name + '=' + encodeURIComponent(val) + ';expires=' + exp.toUTCString() + ';path=/;SameSite=Lax';
  }
  function injectScript(src, id, onload) {
    if (id && document.getElementById(id)) return;
    var s = document.createElement('script');
    s.src = src; s.async = true;
    if (id) s.id = id;
    if (onload) s.onload = onload;
    document.head.appendChild(s);
  }
  function injectInlineScript(code, id) {
    if (id && document.getElementById(id)) return;
    var s = document.createElement('script');
    s.textContent = code;
    if (id) s.id = id;
    document.head.appendChild(s);
  }

  /* ══ SESSION ID ══════════════════════════════════════════════ */
  function obterSessionId() {
    var sid = sessionStorage.getItem('rd_sid');
    if (!sid) {
      sid = 'rd_' + Date.now() + '_' + Math.random().toString(36).slice(2, 9);
      sessionStorage.setItem('rd_sid', sid);
    }
    RD_TRACK.sessionId = sid;
    return sid;
  }

  /* ══ UTM CAPTURE ═════════════════════════════════════════════ */
  function captureUTM() {
    safe(function() {
      var params = new URLSearchParams(location.search);
      var utms = {
        utm_source:   params.get('utm_source')   || '',
        utm_medium:   params.get('utm_medium')   || '',
        utm_campaign: params.get('utm_campaign') || '',
        utm_term:     params.get('utm_term')     || '',
        utm_content:  params.get('utm_content')  || '',
      };
      // Salva somente se veio algum UTM nesta navegação
      var temUTM = Object.values(utms).some(Boolean);
      if (temUTM) {
        sessionStorage.setItem('rd_utms', JSON.stringify(utms));
        RD_TRACK.utms = utms;
        log('UTMs capturados:', utms);
      } else {
        // Recupera UTMs salvos anteriormente nesta sessão
        var saved = sessionStorage.getItem('rd_utms');
        if (saved) RD_TRACK.utms = JSON.parse(saved);
      }
    });
  }

  /* ══ DETECÇÃO DE CONTEXTO DA PÁGINA ══════════════════════════ */
  function detectarContexto() {
    safe(function() {
      var path = location.pathname;
      if      (path === '/' || path.includes('index.html'))  { RD_TRACK.paginaTipo = 'home'; }
      else if (path.includes('/livros/'))                    { RD_TRACK.paginaTipo = 'livro'; }
      else if (path.includes('/blog/'))                      { RD_TRACK.paginaTipo = 'post'; }
      else if (path.includes('/leitor/'))                    { RD_TRACK.paginaTipo = 'leitor'; }
      else if (path.includes('/autor'))                      { RD_TRACK.paginaTipo = 'autor'; }
      else if (path.includes('/contato'))                    { RD_TRACK.paginaTipo = 'contato'; }
      else if (path.includes('/livros.html'))                { RD_TRACK.paginaTipo = 'biblioteca'; }
      else if (path.includes('/blog.html'))                  { RD_TRACK.paginaTipo = 'blog'; }
      else                                                   { RD_TRACK.paginaTipo = 'outro'; }

      // Slug da página atual
      RD_TRACK.paginaSlug = path.split('/').pop().replace(/\.html?$/, '') || 'home';
    });
  }

  /* ══════════════════════════════════════════════════════════════
     PILAR 1: GOOGLE TAG MANAGER / GA4
  ═══════════════════════════════════════════════════════════════ */
  function initGTM() {
    if (!C.GTM_ID) return;
    safe(function() {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
      injectScript('https://www.googletagmanager.com/gtm.js?id=' + C.GTM_ID, 'gtm-script');
      RD_TRACK.pixelsCarregados.gtm = true;
      log('GTM iniciado:', C.GTM_ID);
    });
  }

  function initGA4() {
    if (!C.GA4_ID || C.GTM_ID) return; // GTM gerencia o GA4 quando ativo
    safe(function() {
      injectScript('https://www.googletagmanager.com/gtag/js?id=' + C.GA4_ID, 'ga4-script', function() {
        window.dataLayer = window.dataLayer || [];
        window.gtag = function() { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', C.GA4_ID, { send_page_view: true });
        RD_TRACK.pixelsCarregados.ga4 = true;
        log('GA4 iniciado:', C.GA4_ID);
      });
    });
  }

  /* ══════════════════════════════════════════════════════════════
     PILAR 2: PIXELS DE MARKETING (somente com consentimento)
  ═══════════════════════════════════════════════════════════════ */

  /* ── Meta Pixel ──────────────────────────────────────────── */
  function initMetaPixel() {
    if (!C.META_PIXEL || RD_TRACK.pixelsCarregados.meta) return;
    safe(function() {
      !function(f,b,e,v,n,t,s){
        if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
        t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)
      }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
      window.fbq('init', C.META_PIXEL);
      window.fbq('track', 'PageView');
      RD_TRACK.pixelsCarregados.meta = true;
      log('Meta Pixel iniciado:', C.META_PIXEL);
    });
  }

  /* ── TikTok Pixel ────────────────────────────────────────── */
  function initTikTokPixel() {
    if (!C.TIKTOK_PIXEL || RD_TRACK.pixelsCarregados.tiktok) return;
    safe(function() {
      !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
        ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];
        ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
        for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
        ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
        ttq.load=function(e,n){var i='https://analytics.tiktok.com/i18n/pixel/events.js';
        ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};
        ttq._o[e]=n||{};var o=document.createElement('script');o.type='text/javascript';o.async=!0;
        o.src=i+'?sdkid='+e+'&lib='+t;var a=document.getElementsByTagName('script')[0];a.parentNode.insertBefore(o,a)};
        ttq.load(C.TIKTOK_PIXEL);ttq.page();
      }(window,document,'ttq');
      RD_TRACK.pixelsCarregados.tiktok = true;
      log('TikTok Pixel iniciado:', C.TIKTOK_PIXEL);
    });
  }

  /* ── Microsoft Clarity ───────────────────────────────────── */
  function initClarity() {
    if (!C.CLARITY_ID || RD_TRACK.pixelsCarregados.clarity) return;
    safe(function() {
      window.clarity = window.clarity || function() { (window.clarity.q = window.clarity.q || []).push(arguments); };
      injectScript('https://www.clarity.ms/tag/' + C.CLARITY_ID, 'clarity-script');
      RD_TRACK.pixelsCarregados.clarity = true;
      log('Clarity iniciado:', C.CLARITY_ID);
    });
  }

  /* ══ CONTROLE DE CONSENTIMENTO (LGPD) ═══════════════════════ */
  function verificarConsentimentoInicial() {
    safe(function() {
      var consent = getCookie('rdCookieConsent');
      if (consent === 'completo') {
        RD_TRACK.consentDado = true;
        carregarPixelsMarketing();
      }
      // Essencial: não carrega pixels de marketing
    });
  }

  function carregarPixelsMarketing() {
    initMetaPixel();
    initTikTokPixel();
    initClarity();
  }

  // Escuta quando o usuário aceita cookies no banner LGPD
  document.addEventListener('rd:cookieConsent', function(e) {
    safe(function() {
      if (e.detail && e.detail.tipo === 'completo') {
        RD_TRACK.consentDado = true;
        carregarPixelsMarketing();
        log('Consentimento completo → pixels carregados');
      }
    });
  });

  /* ══════════════════════════════════════════════════════════════
     PILAR 2B: EVENTOS PERSONALIZADOS (DataLayer + pixels diretos)
  ═══════════════════════════════════════════════════════════════ */

  /**
   * Envia evento para GTM DataLayer, GA4 e pixels diretos.
   * @param {string} evento   Nome do evento (ex: 'ViewContent')
   * @param {object} params   Parâmetros extras
   */
  function dispararEvento(evento, params) {
    safe(function() {
      params = params || {};
      log('Evento:', evento, params);

      // GTM DataLayer
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(Object.assign({ event: evento }, params));

      // GA4 direto (quando GTM não está ativo)
      if (window.gtag && !C.GTM_ID) {
        window.gtag('event', evento, params);
      }

      // Meta Pixel
      if (window.fbq && RD_TRACK.pixelsCarregados.meta) {
        var metaEvt = ({ ViewContent:'ViewContent', Lead:'Lead', Purchase:'Purchase' })[evento] || 'CustomEvent';
        if (metaEvt !== 'CustomEvent') window.fbq('track', metaEvt, params);
        else window.fbq('trackCustom', evento, params);
      }

      // TikTok Pixel
      if (window.ttq && RD_TRACK.pixelsCarregados.tiktok) {
        var tktEvt = ({ ViewContent:'ViewContent', Lead:'CompleteRegistration', Purchase:'PlaceAnOrder' })[evento];
        if (tktEvt) window.ttq.track(tktEvt, params);
        else window.ttq.track('CustomEvent', Object.assign({ event_name: evento }, params));
      }

      // Envia para BI local
      enviarEventoBI(evento, params);
    });
  }

  /* ── Eventos específicos do site ─────────────────────────── */

  /** ViewContent — ao visualizar livro ou post */
  window.RD_viewContent = function(slug, titulo, tipo) {
    dispararEvento('ViewContent', {
      content_name: titulo || slug,
      content_ids:  [slug],
      content_type: tipo || 'product',
    });
  };

  /** Lead — ao se inscrever na newsletter ou enviar contato */
  window.RD_lead = function(origem) {
    dispararEvento('Lead', { origem: origem || 'newsletter' });
  };

  /** Leitura_Progresso — avançar capítulo no leitor */
  window.RD_progressoLeitura = function(livroTitulo, capitulo, percentual) {
    dispararEvento('Leitura_Progresso', {
      livro_titulo:  livroTitulo,
      capitulo:      capitulo,
      percentual:    percentual,
      content_ids:   [RD_TRACK.paginaSlug],
    });
  };

  /** Download_Amostra — baixar epub/pdf */
  window.RD_downloadAmostra = function(itemNome, formato) {
    dispararEvento('Download_Amostra', {
      item_nome: itemNome,
      formato:   formato || 'EPUB',
      content_ids: [RD_TRACK.paginaSlug],
    });
  };

  /* ══════════════════════════════════════════════════════════════
     PILAR 3: BI — ENVIO DE DADOS PARA O BANCO LOCAL
  ═══════════════════════════════════════════════════════════════ */

  function detectarDispositivo() {
    var ua = navigator.userAgent;
    if (/Mobi|Android|iPhone/i.test(ua)) return 'mobile';
    if (/iPad|Tablet/i.test(ua)) return 'tablet';
    return 'desktop';
  }

  /** Registra a sessão no banco local (chamada na primeira visita da sessão) */
  function registrarSessao() {
    safe(function() {
      if (sessionStorage.getItem('rd_sessao_enviada')) return;
      sessionStorage.setItem('rd_sessao_enviada', '1');

      var dados = {
        acao:         'sessao',
        session_id:   RD_TRACK.sessionId,
        utm_source:   RD_TRACK.utms.utm_source   || '',
        utm_medium:   RD_TRACK.utms.utm_medium   || '',
        utm_campaign: RD_TRACK.utms.utm_campaign || '',
        utm_term:     RD_TRACK.utms.utm_term     || '',
        utm_content:  RD_TRACK.utms.utm_content  || '',
        dispositivo:  detectarDispositivo(),
        idioma:       navigator.language || '',
        referrer:     document.referrer || '',
        landing_page: location.href,
        pagina_tipo:  RD_TRACK.paginaTipo,
      };

      if (navigator.sendBeacon) {
        navigator.sendBeacon(biEndpoint, new Blob([JSON.stringify(dados)], { type: 'application/json' }));
      } else {
        fetch(biEndpoint, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(dados) }).catch(function(){});
      }
      log('Sessão registrada no BI');
    });
  }

  /** Envia evento personalizado para o banco local */
  function enviarEventoBI(tipo, params) {
    safe(function() {
      var dados = {
        acao:           'evento',
        session_id:     RD_TRACK.sessionId,
        tipo_evento:    tipo,
        conteudo_slug:  RD_TRACK.paginaSlug,
        conteudo_titulo:document.title,
        params:         params,
      };
      fetch(biEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados),
        keepalive: true,
      }).catch(function(){});
    });
  }

  /* ── Tempo de permanência na página ─────────────────────── */
  function initTempoNaPagina() {
    safe(function() {
      var enviado = false;

      function enviarTempo() {
        if (enviado) return;
        enviado = true;
        var segundos = Math.round((Date.now() - RD_TRACK.pageStart) / 1000);
        if (segundos < 2) return; // ignora bounces instantâneos

        var dados = {
          acao:           'tempo_pagina',
          session_id:     RD_TRACK.sessionId,
          conteudo_slug:  RD_TRACK.paginaSlug,
          pagina_tipo:    RD_TRACK.paginaTipo,
          tempo_segundos: segundos,
          url:            location.pathname,
        };

        // sendBeacon garante envio mesmo ao fechar a aba
        if (navigator.sendBeacon) {
          navigator.sendBeacon(biEndpoint, new Blob([JSON.stringify(dados)], { type: 'application/json' }));
        } else {
          fetch(biEndpoint, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(dados), keepalive: true }).catch(function(){});
        }
        log('Tempo na página:', segundos + 's');
      }

      window.addEventListener('beforeunload', enviarTempo);
      // Fallback: envia também no pagehide (iOS Safari)
      window.addEventListener('pagehide', enviarTempo);
    });
  }

  /* ── Disparo automático de ViewContent (livros e posts) ──── */
  function autoViewContent() {
    safe(function() {
      var tipo = RD_TRACK.paginaTipo;
      if (tipo !== 'livro' && tipo !== 'post') return;

      var titulo = document.querySelector('h1')?.textContent?.trim()
                 || document.title;

      // Pequeno delay para garantir que o título já foi renderizado
      setTimeout(function() {
        window.RD_viewContent(RD_TRACK.paginaSlug, titulo, tipo);
      }, 1500);
    });
  }

  /* ── Monitorar downloads de amostras ─────────────────────── */
  function monitorarDownloads() {
    safe(function() {
      document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href*="/download/"]');
        if (!link) return;
        var href = link.getAttribute('href') || '';
        var nome  = link.textContent.trim() || href.split('/').pop();
        var fmt   = href.match(/\.(epub|pdf|mobi)/i)?.[1]?.toUpperCase() || 'EPUB';
        window.RD_downloadAmostra(nome, fmt);
      });
    });
  }

  /* ── Monitorar inscrições na newsletter ──────────────────── */
  function monitorarNewsletter() {
    safe(function() {
      // Escuta o evento customizado que newsletter.js dispara ao inscrever
      document.addEventListener('rd:newsletterInscrito', function(e) {
        window.RD_lead('newsletter');
      });
      // Escuta submit dos formulários de contato
      document.querySelectorAll('form#fContato, form[data-form="contato"]').forEach(function(f) {
        f.addEventListener('submit', function() {
          window.RD_lead('contato');
        });
      });
    });
  }

  /* ══ INICIALIZAÇÃO ═══════════════════════════════════════════ */
  function init() {
    obterSessionId();
    captureUTM();
    detectarContexto();

    // Analytics (sem necessidade de consentimento — dados agregados anônimos)
    initGTM();
    initGA4();

    // Pixels de marketing (somente com consentimento LGPD)
    verificarConsentimentoInicial();

    // BI local
    registrarSessao();
    initTempoNaPagina();

    // Eventos automáticos
    autoViewContent();
    monitorarDownloads();
    monitorarNewsletter();

    log('Tracking iniciado | sessão:', RD_TRACK.sessionId, '| tipo:', RD_TRACK.paginaTipo);
  }

  // Aguarda o DOM estar pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
