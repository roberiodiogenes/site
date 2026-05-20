/* ================================================================
   ROBÉRIO DIÓGENES — js/index.js
   Scripts exclusivos da página inicial.
   As funções de newsletter são gerenciadas pelo api-client.js.
   ================================================================ */

/* ── Contador de visitas ─────────────────────────────────────── */
(function iniciarContadorVisitas() {

  document.addEventListener('DOMContentLoaded', async () => {
    const elNum  = document.getElementById('footerVisitasNum');
    const elWrap = document.getElementById('footerVisitas');
    if (!elNum || !elWrap) return;

    try {
      const total = await API.Visitas.registrar();
      if (total === null) return; // backend indisponível — mantém oculto

      animarContagem(elNum, total, 1400);
      elWrap.classList.add('visivel');
    } catch (e) {
      // Silencioso — contador não é crítico para o funcionamento da página
    }
  });

  function animarContagem(el, alvo, duracao) {
    const inicio = performance.now();

    function passo(agora) {
      const progresso = Math.min((agora - inicio) / duracao, 1);
      const fator  = 1 - Math.pow(1 - progresso, 3); // easing out cubic
      const atual  = Math.round(alvo * fator);
      el.textContent = atual.toLocaleString('pt-BR');
      if (progresso < 1) requestAnimationFrame(passo);
    }

    requestAnimationFrame(passo);
  }

})();

