/* ================================================================
   ROBÉRIO DIÓGENES — js/push-notifications.js
   Integração OneSignal Web Push com opt-in inteligente por contexto.

   ┌─ CONFIGURAÇÃO OBRIGATÓRIA ──────────────────────────────────┐
   │ Preencha ONESIGNAL_APP_ID com o App ID do painel OneSignal. │
   │ Painel → Settings → Keys & IDs                              │
   └─────────────────────────────────────────────────────────────┘

   Triggeres de opt-in por contexto:
   · Home / Livros  → após 90 segundos na página
   · Blog (lista)   → após rolar 50% da página
   · Post (artigo)  → após rolar 60% ou ler por 60 s
   · Leitor online  → após 10% de progresso de leitura
   · Pré-lançamento → imediatamente após inscrição na lista

   Nunca exibe o prompt se:
   · Notificações já foram permitidas/negadas
   · Usuário já descartou o prompt nesta sessão
   ================================================================ */

'use strict';

/* ── CONFIGURAÇÃO ────────────────────────────────────────────── */
const RD_ONESIGNAL_APP_ID = 'SEU_ONESIGNAL_APP_ID'; // ← preencher após criar conta

/* Contexto da página atual */
const _rdPath = window.location.pathname;
const _rdCtx  = (() => {
  if (_rdPath.includes('/leitor/'))           return 'leitor';
  if (_rdPath.includes('/blog/'))             return 'post';
  if (_rdPath.includes('blog.html'))          return 'blog';
  if (_rdPath.includes('pre-lancamento'))     return 'pre_lancamento';
  if (_rdPath.includes('/livros/'))           return 'livro';
  return 'home';
})();

/* ── Verificações iniciais ───────────────────────────────────── */
if (!('Notification' in window)) {
  // Browser não suporta notificações — sai silenciosamente
  throw new Error('RD Push: browser sem suporte a notificações.');
}

/* ── Carregar SDK OneSignal de forma assíncrona ─────────────── */
window.OneSignalDeferred = window.OneSignalDeferred || [];

(function () {
  const script = document.createElement('script');
  script.src   = 'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js';
  script.defer = true;
  document.head.appendChild(script);
})();

/* ── Inicializar e configurar ────────────────────────────────── */
OneSignalDeferred.push(async function (OneSignal) {

  await OneSignal.init({
    appId:                        RD_ONESIGNAL_APP_ID,
    safari_web_id:                '', // opcional — preencher se quiser suporte Safari macOS
    notifyButton:                 { enable: false }, // usamos prompt personalizado
    allowLocalhostAsSecureOrigin: true,              // facilita testes no XAMPP via ngrok
    promptOptions: {
      slidedown: {
        prompts: [{
          type:                   'push',
          autoPrompt:             false,            // controlamos o timing manualmente
          text: {
            actionMessage:        'Receba avisos de novos capítulos e lançamentos de Robério Diógenes.',
            acceptButton:         'Sim, quero!',
            cancelButton:         'Agora não',
          },
        }],
      },
    },
  });

  /* ── Tags de segmentação ────────────────────────────────────
     Espelha o interesse literário já rastreado no backend.
     Tags ficam gravadas no OneSignal — permitem filtrar disparo.  */
  async function _rdTagContexto() {
    const tags = { contexto: _rdCtx };

    /* Se estiver em uma página de livro, adiciona o slug */
    if (_rdCtx === 'livro' || _rdCtx === 'leitor') {
      const slug = document.body.dataset.livro
               || _rdPath.split('/').pop().replace('.html', '');
      if (slug) tags['ultimo_livro'] = slug;
    }

    /* Se estiver em um post, adiciona a categoria */
    const catEl = document.querySelector('[data-categoria]');
    if (catEl?.dataset.categoria) {
      tags['categoria'] = catEl.dataset.categoria;
    }

    try { await OneSignal.User.addTags(tags); } catch (e) {}
  }

  /* ── Mostrar prompt ─────────────────────────────────────────
     Verifica se já respondeu antes de exibir.                   */
  async function _rdMostrarPrompt() {
    const permissao = await OneSignal.Notifications.permission;
    if (permissao) return; // já permitiu

    const descartou = sessionStorage.getItem('rd_push_descartou');
    if (descartou) return;

    try {
      await OneSignal.Slidedown.promptPush();
      await _rdTagContexto();
    } catch (e) {}
  }

  /* ── Timing por contexto ────────────────────────────────────── */

  if (_rdCtx === 'home' || _rdCtx === 'livro') {
    /* 90 segundos na página */
    setTimeout(_rdMostrarPrompt, 90 * 1000);

  } else if (_rdCtx === 'blog') {
    /* 50% de scroll na lista do blog */
    let _blogTriggered = false;
    window.addEventListener('scroll', function () {
      if (_blogTriggered) return;
      const scrollPct = (window.scrollY + window.innerHeight) / document.body.scrollHeight;
      if (scrollPct >= 0.5) {
        _blogTriggered = true;
        _rdMostrarPrompt();
      }
    }, { passive: true });

  } else if (_rdCtx === 'post') {
    /* 60% de scroll no artigo OU 60 segundos lendo */
    let _postTriggered = false;
    const _triggerPost = () => { if (!_postTriggered) { _postTriggered = true; _rdMostrarPrompt(); } };

    window.addEventListener('scroll', function () {
      const scrollPct = (window.scrollY + window.innerHeight) / document.body.scrollHeight;
      if (scrollPct >= 0.6) _triggerPost();
    }, { passive: true });

    setTimeout(_triggerPost, 60 * 1000);

  } else if (_rdCtx === 'leitor') {
    /* Disparado externamente via rdPushLeitorProgresso(pct) */
    /* (chamado pelo leitor.js quando progresso atinge 10%) */

  } else if (_rdCtx === 'pre_lancamento') {
    /* Disparado após inscrição bem-sucedida na lista de espera */
    /* (chamado externamente via rdPushPrompt()) */
  }

  /* ── API pública ─────────────────────────────────────────────
     Funções chamáveis de outros scripts da página.              */

  /** Chamar manualmente o prompt (usado pelo leitor.js e pre-lancamento.html) */
  window.rdPushPrompt = _rdMostrarPrompt;

  /** Informar progresso de leitura — dispara prompt em 10% */
  window.rdPushLeitorProgresso = function (percentual) {
    if (percentual >= 10) _rdMostrarPrompt();
  };

  /** Adicionar tag de interesse (chamado quando usuário lê post de uma categoria) */
  window.rdPushAddTag = async function (chave, valor) {
    try { await OneSignal.User.addTags({ [chave]: valor }); } catch (e) {}
  };

  /** Registrar disparo — usado pelo backend para marcar sessão como notificada */
  window.rdPushId = async function () {
    try { return await OneSignal.User.PushSubscription.id; } catch (e) { return null; }
  };

});
