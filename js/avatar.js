/* ================================================================
   ROBÉRIO DIÓGENES — js/avatar.js
   Upload de foto de perfil — integra ao perfil.js existente.

   Dependência: perfil.js deve ter gerado o #avatarWrap antes
   deste script rodar. Inclua APÓS perfil.js no perfil.html.
   ================================================================ */
'use strict';

(function iniciarAvatar() {

  // O perfil.js gera o HTML de forma assíncrona (após fetch da sessão).
  // Usamos MutationObserver para aguardar o #avatarWrap aparecer no DOM.
  const observer = new MutationObserver(() => {
    const wrap = document.getElementById('avatarWrap');
    if (wrap) {
      observer.disconnect();
      configurarUpload(wrap);
    }
  });
  observer.observe(document.body, { childList: true, subtree: true });

  // Também tenta direto caso o elemento já exista
  const wrapImediato = document.getElementById('avatarWrap');
  if (wrapImediato) { observer.disconnect(); configurarUpload(wrapImediato); }

  /* ── Configura todo o comportamento de upload ─────────────── */
  function configurarUpload(wrap) {

    /* Input file oculto */
    const input = document.createElement('input');
    input.type    = 'file';
    input.accept  = 'image/jpeg,image/png,image/webp,image/gif';
    input.style.display = 'none';
    input.id      = 'avatarInputFile';
    document.body.appendChild(input);

    /* Clique no avatar → abre seletor */
    wrap.addEventListener('click', () => input.click());
    wrap.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });

    /* Botão remover foto */
    const btnRemover = document.getElementById('btnRemoverFoto');
    atualizarBtnRemover();

    if (btnRemover) {
      btnRemover.addEventListener('click', removerFoto);
    }

    /* ── Arquivo selecionado ── */
    input.addEventListener('change', async () => {
      const file = input.files[0];
      if (!file) return;
      input.value = '';

      if (file.size > 2 * 1024 * 1024) {
        mostrarToast('Imagem muito grande. Máximo: 2 MB.', 'erro'); return;
      }
      if (!file.type.startsWith('image/')) {
        mostrarToast('Selecione uma imagem (JPG, PNG, WebP ou GIF).', 'erro'); return;
      }

      spinner(true);
      try {
        const form = new FormData();
        form.append('foto', file);

        const resp = await fetch('../backend/auth/avatar.php', {
          method: 'POST', credentials: 'same-origin', body: form,
        });
        const data = await resp.json();

        if (data.ok) {
          // Atualiza imagem no avatar
          let img = document.getElementById('avatarImg');
          if (!img) {
            document.getElementById('avatarIcone')?.remove();
            img = document.createElement('img');
            img.id  = 'avatarImg';
            img.alt = 'Foto de perfil';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%';
            wrap.insertBefore(img, wrap.querySelector('.perfil-avatar-upload'));
          }
          img.src = data.foto_url + '?v=' + Date.now();

          // Atualiza avatar no nav (global.js)
          document.querySelectorAll('.nav-usuario-avatar,.usuario-foto,[data-usuario-foto]')
            .forEach(el => { if (el.tagName === 'IMG') el.src = data.foto_url + '?v=' + Date.now(); });

          atualizarBtnRemover(true);
          mostrarToast('Foto atualizada! ✓');
        } else {
          mostrarToast(data.erro || 'Erro ao fazer upload.', 'erro');
        }
      } catch (e) {
        mostrarToast('Erro de conexão. Tente novamente.', 'erro');
      } finally {
        spinner(false);
      }
    });

    /* ── Remover foto ── */
    async function removerFoto() {
      if (!confirm('Remover sua foto de perfil?')) return;
      spinner(true);
      try {
        const resp = await fetch('../backend/auth/avatar.php', {
          method: 'DELETE', credentials: 'same-origin',
        });
        const data = await resp.json();
        if (data.ok) {
          document.getElementById('avatarImg')?.remove();
          if (!document.getElementById('avatarIcone')) {
            const ico = document.createElement('i');
            ico.className = 'fa fa-user-circle';
            ico.id = 'avatarIcone';
            ico.setAttribute('aria-hidden', 'true');
            wrap.insertBefore(ico, wrap.querySelector('.perfil-avatar-upload'));
          }
          atualizarBtnRemover(false);
          mostrarToast('Foto removida.');
        } else {
          mostrarToast(data.erro || 'Erro ao remover.', 'erro');
        }
      } catch (e) {
        mostrarToast('Erro de conexão.', 'erro');
      } finally {
        spinner(false);
      }
    }

    /* ── Helpers ── */
    function atualizarBtnRemover(temFoto) {
      if (!btnRemover) return;
      const tem = temFoto !== undefined ? temFoto : !!document.getElementById('avatarImg');
      btnRemover.style.display = tem ? 'block' : 'none';
    }

    function spinner(sim) {
      let sp = document.getElementById('avatarSpinner');
      const overlay = wrap.querySelector('.perfil-avatar-upload');
      if (sim) {
        if (!sp) {
          sp = document.createElement('div');
          sp.id = 'avatarSpinner';
          sp.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);border-radius:50%;font-size:1.5rem;color:var(--ouro)';
          sp.innerHTML = '<i class="fa fa-circle-notch" style="animation:spin .8s linear infinite"></i>';
          if (!document.getElementById('avatarSpinStyle')) {
            const s = document.createElement('style');
            s.id = 'avatarSpinStyle';
            s.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
            document.head.appendChild(s);
          }
          wrap.appendChild(sp);
        }
        if (overlay) overlay.style.display = 'none';
      } else {
        sp?.remove();
        if (overlay) overlay.style.display = '';
      }
    }
  }

  /* ── Toast ── */
  function mostrarToast(msg, tipo = 'ok') {
    let t = document.getElementById('avatarToast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'avatarToast';
      t.style.cssText = 'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%) translateY(5rem);background:var(--fundo-card);border:1px solid var(--ouro);padding:.7rem 1.5rem;border-radius:var(--raio-lg,8px);font-size:.9rem;box-shadow:0 6px 30px rgba(0,0,0,.2);z-index:3000;opacity:0;transition:transform .3s,opacity .3s;pointer-events:none;white-space:nowrap';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.borderColor = tipo === 'erro' ? '#c0392b' : 'var(--ouro)';
    t.style.opacity = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => {
      t.style.opacity = '0';
      t.style.transform = 'translateX(-50%) translateY(5rem)';
    }, 3500);
  }

})();
