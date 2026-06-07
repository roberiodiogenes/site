/* ================================================================
   ROBÉRIO DIÓGENES — tema-sazonal.js
   Aplica o tema sazonal vigente sobreescrevendo as CSS variables
   de acento no :root. Cache localStorage de 1 hora.

   Incluído via global.js — não requer inclusão manual nas páginas.
   ================================================================ */
(function () {
  'use strict';

  const CACHE_KEY = 'rd_tema_sazonal';
  const CACHE_TTL = 3_600_000; // 1 hora em ms

  /* Base URL: funciona em localhost/roberiodiogenes.com e em prod */
  const BASE = location.hostname === 'localhost'
    ? `${location.protocol}//${location.host}/roberiodiogenes.com`
    : `${location.protocol}//${location.host}`;

  /* ── Aplica as variáveis CSS no :root ─────────────────────── */
  function aplicar(tema) {
    if (!tema) return;
    const r = document.documentElement;
    const vars = [
      '--ouro', '--ouro-claro', '--ouro-escuro', '--ferrugem',
      '--ornamento-cor', '--particula-cor', '--particula-cor2'
    ];
    vars.forEach(v => { if (tema[v]) r.style.setProperty(v, tema[v]); });
  }

  /* ── Tenta usar cache primeiro ────────────────────────────── */
  try {
    const raw = localStorage.getItem(CACHE_KEY);
    if (raw) {
      const { ts, tema, dataKey } = JSON.parse(raw);
      const hoje = new Date().toDateString();
      // Cache válido se: não expirou E é do mesmo dia (temas mudam à meia-noite)
      if (Date.now() - ts < CACHE_TTL && dataKey === hoje) {
        aplicar(tema);
        return; // Não faz fetch; sai cedo
      }
    }
  } catch (e) { /* localStorage indisponível — segue para fetch */ }

  /* ── Fetch do tema ativo ──────────────────────────────────── */
  fetch(BASE + '/backend/configuracoes.php?acao=tema_ativo', {
    method: 'GET',
    credentials: 'omit',   // requisição pública, sem cookie
    cache: 'no-store'
  })
    .then(r => r.json())
    .then(d => {
      if (!d.ok) return;
      aplicar(d.tema);
      // Persiste no cache
      try {
        localStorage.setItem(CACHE_KEY, JSON.stringify({
          ts:      Date.now(),
          dataKey: new Date().toDateString(),
          tema:    d.tema
        }));
      } catch (e) { /* quota cheia */ }
    })
    .catch(() => { /* falha silenciosa — não quebra o site */ });
})();
