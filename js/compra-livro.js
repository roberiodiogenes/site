/* ================================================================
   ROBÉRIO DIÓGENES — js/compra-livro.js
   Gerencia o botão "Ler agora / Comprar" nas páginas de livros.

   Uso: inclua este script nas páginas de livros após o DOM.
   O script lê o slug do body[data-livro] e injeta o botão correto.

   Fluxo:
     1. Verifica sessão do usuário
     2. Verifica acesso (já comprou ou tem assinatura)
     3. Exibe botão adequado:
        - Logado + tem acesso  → "Ler agora"
        - Logado + sem acesso  → "Comprar · R$ XX"  (+ link assinar)
        - Não logado           → "Entre para ler"
   ================================================================ */

'use strict';

(async function iniciarCompraBotao() {

  /* ── Elemento(s) alvo ─────────────────────────────────────── */
  // Todos os elementos com [data-slot-compra] recebem o botão
  const slots = document.querySelectorAll('[data-slot-compra]');
  if (!slots.length) return;

  const slug = document.body.dataset.livro || '';
  if (!slug) return;

  /* ── Busca paralela: sessão + acesso + preço ──────────────── */
  const [sessaoResp, acessoResp, livroResp] = await Promise.allSettled([
    fetch('../backend/auth/sessao.php',    { credentials: 'same-origin' }).then(r => r.json()),
    fetch(`../backend/acesso.php?livro=${slug}`, { credentials: 'same-origin' }).then(r => r.json()),
    fetch(`../backend/pagamento.php?acao=livro&slug=${slug}`).then(r => r.json()),
  ]);

  const sessao = sessaoResp.status === 'fulfilled' ? sessaoResp.value : {};
  const acesso = acessoResp.status === 'fulfilled' ? acessoResp.value : {};
  const livro  = livroResp.status  === 'fulfilled' ? livroResp.value?.livro : null;

  const logado    = sessao.ok && sessao.logado;
  const temAcesso = acesso.ok && acesso.tem_acesso;
  const preco     = livro?.preco_promocao ?? livro?.preco ?? null;
  const precoFmt  = preco ? 'R$\u00a0' + preco.toFixed(2).replace('.', ',') : '';

  /* ── Gera o HTML dos botões conforme estado ───────────────── */
  const btnPresentear = logado
    ? `<a href="../presentear.html?livro=${slug}"
          class="btn btn-ghost"
          style="font-size:0.82rem;"
          aria-label="Presentear este livro para alguém">
         <i class="fa fa-gift" aria-hidden="true"></i>
         Presentear alguém
       </a>`
    : '';

  function gerarBotaoHTML(modo) {
    if (modo === 'ler') {
      return `
        <a href="../leitor/index.html?livro=${slug}"
           class="btn btn-primario btn-ler-agora"
           aria-label="Ler ${slug} agora no leitor online">
          <i class="fa fa-book-open" aria-hidden="true"></i>
          Ler agora
        </a>
        ${btnPresentear}`;
    }

    if (modo === 'comprar') {
      return `
        <button
          class="btn btn-primario btn-comprar-livro"
          data-slug="${slug}"
          aria-label="Comprar ${slug} por ${precoFmt}">
          <i class="fa fa-shopping-cart" aria-hidden="true"></i>
          Comprar${precoFmt ? ' · ' + precoFmt : ''}
          <i class="fa fa-circle-notch btn-spinner-compra" aria-hidden="true" style="display:none"></i>
        </button>
        <a href="../leitor/index.html?livro=${slug}"
           class="btn btn-ler-agora"
           style="font-size:0.82rem;"
           aria-label="Ler amostra gratuita">
          <i class="fa fa-eye" aria-hidden="true"></i>
          Ler amostra (10% grátis)
        </a>
        <a href="../pagamento/assinatura.html"
           class="btn btn-ghost btn-ver-planos"
           style="font-size:0.82rem;"
           aria-label="Ver planos de assinatura">
          <i class="fa fa-crown" aria-hidden="true"></i>
          Ou assine e leia todos
        </a>
        ${btnPresentear}`;
    }

    // modo === 'login'
    return `
      <a href="../login.html?redir=livros/${slug}.html"
         class="btn btn-primario"
         aria-label="Fazer login para ler ou comprar">
        <i class="fa fa-sign-in-alt" aria-hidden="true"></i>
        Entre para ler
      </a>
      <a href="../pagamento/assinatura.html"
         class="btn btn-ghost"
         style="font-size:0.82rem;"
         aria-label="Ver planos de assinatura">
        <i class="fa fa-crown" aria-hidden="true"></i>
        Ver planos
      </a>`;
  }

  /* ── Determina modo e injeta nos slots ─────────────────────── */
  const modo = temAcesso ? 'ler' : logado ? 'comprar' : 'login';
  const html = gerarBotaoHTML(modo);

  slots.forEach(slot => {
    slot.innerHTML = html;
    // Registra evento no botão de compra
    const btnComprar = slot.querySelector('.btn-comprar-livro');
    if (btnComprar) registrarCompraBotao(btnComprar);
  });

  /* ── Lógica de compra avulsa ───────────────────────────────── */
  function registrarCompraBotao(btn) {
    btn.addEventListener('click', async () => {
      const spinner = btn.querySelector('.btn-spinner-compra');
      const icone   = btn.querySelector('.fa-shopping-cart');

      btn.disabled = true;
      if (spinner) spinner.style.display = 'inline-block';
      if (icone)   icone.style.display   = 'none';

      try {
        const resp = await fetch('../backend/pagamento.php', {
          method:      'POST',
          credentials: 'same-origin',
          headers:     { 'Content-Type': 'application/json' },
          body:        JSON.stringify({ acao: 'iniciar_compra', livro_slug: slug }),
        });
        const data = await resp.json();

        if (data.ok && data.checkout_url) {
          window.location.href = data.checkout_url;
        } else if (data.erro?.includes('já possui')) {
          // Já comprou — redireciona direto para o leitor
          window.location.href = `../leitor/livro.html?livro=${slug}`;
        } else {
          mostrarToastCompra(data.erro || 'Erro ao iniciar pagamento. Tente novamente.', 'erro');
          btn.disabled = false;
          if (spinner) spinner.style.display = 'none';
          if (icone)   icone.style.display   = '';
        }
      } catch (e) {
        mostrarToastCompra('Erro de conexão. Tente novamente.', 'erro');
        btn.disabled = false;
        if (spinner) spinner.style.display = 'none';
        if (icone)   icone.style.display   = '';
      }
    });
  }

  /* ── Toast inline (não depende do leitor-toast) ─────────────── */
  function mostrarToastCompra(msg, tipo) {
    let t = document.getElementById('toast-compra');
    if (!t) {
      t = document.createElement('div');
      t.id = 'toast-compra';
      t.style.cssText = `
        position:fixed;bottom:2rem;left:50%;
        transform:translateX(-50%) translateY(4rem);
        background:var(--fundo-card);border:1px solid var(--ouro);
        color:var(--texto);padding:.7rem 1.4rem;
        border-radius:var(--raio-lg);font-size:.9rem;
        box-shadow:var(--sombra-md);z-index:3000;
        transition:transform .35s,opacity .35s;
        opacity:0;pointer-events:none;white-space:nowrap;
      `;
      document.body.appendChild(t);
    }
    t.textContent   = msg;
    t.style.borderColor = tipo === 'erro' ? '#c0392b' : 'var(--ouro)';
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    setTimeout(() => {
      t.style.opacity   = '0';
      t.style.transform = 'translateX(-50%) translateY(4rem)';
    }, 3500);
  }

})();
