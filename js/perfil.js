/* ================================================================
   ROBÉRIO DIÓGENES — js/perfil.js
   Dashboard do Leitor
   Correções: caminhos de API, sintaxe, campos de formulário
   ================================================================ */
'use strict';

// ── Caminhos (perfil está em leitor/, backend está um nível acima) ──
const ENDPOINTS = {
  GET_PERFIL:      '../backend/auth/perfil.php',
  UPDATE_PERFIL:   '../backend/auth/perfil.php',
  CHANGE_PASSWORD: '../backend/auth/mudar-senha.php',
};

// ── Logger ────────────────────────────────────────────────────
const Log = {
  info:  (m, d) => console.log(`[PERFIL] ${m}`, d ?? ''),
  warn:  (m, d) => console.warn(`[PERFIL] ${m}`, d ?? ''),
  erro:  (m, d) => console.error(`[PERFIL] ${m}`, d ?? ''),
};

// ── Validadores ───────────────────────────────────────────────
const Val = {
  nome: v => {
    if (!v || v.trim().length < 2) return 'Nome deve ter no mínimo 2 caracteres.';
    if (v.length > 120) return 'Nome muito longo.';
    return null;
  },
  estado: v => {
    if (!v) return null;
    if (!/^[A-Za-z]{2}$/.test(v)) return 'Estado deve ter 2 letras (ex: CE, SP).';
    return null;
  },
  dataNasc: v => {
    if (!v) return null;
    const d = new Date(v + 'T00:00:00');
    if (isNaN(d)) return 'Data inválida.';
    if (d > new Date()) return 'Data não pode ser no futuro.';
    return null;
  },
  whatsapp: v => {
    if (!v) return null;
    const s = v.replace(/[\s\-\(\)]/g, '');
    if (!/^\+?55\d{2}9?\d{8,9}$/.test(s)) return 'WhatsApp inválido. Formato: +55 85 99999-9999';
    return null;
  },
  senha: v => {
    if (!v || v.length < 8) return 'Mínimo de 8 caracteres.';
    if (!/[A-Za-z]/.test(v)) return 'Deve conter letras.';
    if (!/[0-9]/.test(v)) return 'Deve conter números.';
    return null;
  },
};

// ── Alertas ───────────────────────────────────────────────────
function alerta(msg, tipo, idEl = 'perfilAlerta') {
  const el = document.getElementById(idEl);
  if (!el) return;
  el.innerHTML = '';
  const div = document.createElement('div');
  div.className = `perfil-alerta ${tipo}`;
  div.setAttribute('role', 'alert');
  div.textContent = msg;
  el.appendChild(div);
  setTimeout(() => {
    div.style.transition = 'opacity 0.4s';
    div.style.opacity = '0';
    setTimeout(() => div.remove(), 400);
  }, 5000);
}

// ── Escape HTML ───────────────────────────────────────────────
function esc(s) {
  return (s || '').replace(/[&<>"']/g, c =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[c]));
}

// ── Requisição fetch com tratamento de erro ───────────────────
async function apiFetch(url, opcoes = {}) {
  try {
    const r = await fetch(url, { credentials: 'same-origin', ...opcoes });
    return await r.json();
  } catch (e) {
    Log.erro('apiFetch', e);
    return { ok: false, erro: 'Falha na conexão com o servidor.' };
  }
}

// ── Carregar perfil do backend ────────────────────────────────
async function carregarPerfil() {
  const json = await apiFetch(ENDPOINTS.GET_PERFIL, { method: 'GET' });
  if (!json.ok) throw new Error(json.erro || 'Erro ao carregar perfil.');
  return json.usuario;
}

// ── Preencher formulário e sidebar com dados do usuário ───────
function exibirPerfil(u) {
  if (!u) return;

  // ── Sidebar ──────────────────────────────────────────────
  const card = document.getElementById('perfilCard');
  if (card) {
    card.innerHTML = `
      <div class="perfil-avatar" id="avatarWrap" role="button" tabindex="0"
           title="Clique para alterar sua foto de perfil"
           aria-label="Alterar foto de perfil">
        ${u.foto_url
          ? `<img src="${esc(u.foto_url)}" alt="Avatar de ${esc(u.nome)}" id="avatarImg" />`
          : `<i class="fa fa-user-circle" aria-hidden="true" id="avatarIcone"></i>`}
        <div class="perfil-avatar-upload" aria-hidden="true">
          <i class="fa fa-camera"></i>
        </div>
      </div>
      <button type="button" id="btnRemoverFoto"
              style="display:none;background:none;border:none;color:var(--ferrugem,#c0392b);
                     font-size:0.72rem;font-family:var(--fonte-display);letter-spacing:0.06em;
                     cursor:pointer;opacity:0.7;margin-top:0.25rem;transition:opacity 0.2s"
              onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=0.7">
        Remover foto
      </button>
      <div class="perfil-nome">${esc(u.nome)}</div>
      <div class="perfil-email">${esc(u.email)}</div>
      <div class="perfil-badges">
        <span class="perfil-badge">
          <i class="fa ${u.verificado ? 'fa-check-circle' : 'fa-clock'}" aria-hidden="true"></i>
          ${u.verificado ? 'Verificado' : 'Pendente'}
        </span>
        <span class="perfil-badge"><i class="fa fa-user" aria-hidden="true"></i> Leitor</span>
      </div>
      <div class="perfil-stats">
        <div class="perfil-stat">
          <div class="perfil-stat-numero">${u.total_favoritos ?? 0}</div>
          <div class="perfil-stat-label">Favoritos</div>
        </div>
        <div class="perfil-stat">
          <div class="perfil-stat-numero">${u.total_downloads ?? 0}</div>
          <div class="perfil-stat-label">Downloads</div>
        </div>
      </div>`;
  }

  // ── Formulário de dados ───────────────────────────────────
  const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
  set('perfilNome',     u.nome);
  set('perfilEmail',    u.email);
  set('perfilSexo',     u.sexo ?? 'nao_informado');
  set('perfilDataNasc', u.data_nascimento);
  set('perfilCidade',   u.cidade);
  set('perfilEstado',   u.estado);
  set('perfilPais',     u.pais ?? 'Brasil');
  set('perfilWhatsapp', u.whatsapp);

  // ── Estatísticas ──────────────────────────────────────────
  const setTxt = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
  setTxt('statFavoritos', u.total_favoritos ?? 0);
  setTxt('statDownloads', u.total_downloads ?? 0);
  if (u.idade) setTxt('statIdade', u.idade);
  if (u.membro_desde) setTxt('statMembro', new Date(u.membro_desde).getFullYear());
}

// ── Submeter formulário de dados pessoais ─────────────────────
async function submeterPerfil(e) {
  e.preventDefault();

  // Validação client-side
  const get = id => document.getElementById(id)?.value ?? '';
  const nome  = get('perfilNome').trim();
  const estado = get('perfilEstado').trim().toUpperCase();
  const dataNasc = get('perfilDataNasc');
  const whatsapp = get('perfilWhatsapp').trim();

  const erros = [
    Val.nome(nome),
    Val.estado(estado),
    Val.dataNasc(dataNasc),
    Val.whatsapp(whatsapp),
  ].filter(Boolean);

  if (erros.length) {
    alerta(erros[0], 'erro', 'perfilAlerta');
    return;
  }

  const btn = e.target.querySelector('[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Salvando…';

  const json = await apiFetch(ENDPOINTS.UPDATE_PERFIL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      nome,
      sexo:             get('perfilSexo'),
      data_nascimento:  dataNasc || null,
      cidade:           get('perfilCidade').trim() || null,
      estado:           estado || null,
      pais:             get('perfilPais').trim() || 'Brasil',
      whatsapp:         whatsapp || null,
    }),
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="fa fa-save"></i> Salvar Alterações';

  if (json.ok) {
    alerta('Perfil atualizado com sucesso!', 'sucesso', 'perfilAlerta');
    // Atualizar nome na sidebar
    const elNome = document.querySelector('.perfil-nome');
    if (elNome) elNome.textContent = nome;
  } else {
    alerta(json.erro || 'Erro ao salvar.', 'erro', 'perfilAlerta');
  }
}

// ── Submeter formulário de senha ──────────────────────────────
async function submeterSenha(e) {
  e.preventDefault();
  const senhaAtual = document.getElementById('senhaAtual')?.value ?? '';
  const novaSenha  = document.getElementById('novaSenha')?.value ?? '';
  const confirmar  = document.getElementById('confirmarSenha')?.value ?? '';

  const erroSenha = Val.senha(novaSenha);
  if (erroSenha) { alerta(erroSenha, 'erro', 'segurancaAlerta'); return; }
  if (novaSenha !== confirmar) { alerta('As senhas não conferem.', 'erro', 'segurancaAlerta'); return; }

  const btn = e.target.querySelector('[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Alterando…';

  const json = await apiFetch(ENDPOINTS.CHANGE_PASSWORD, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ senha_atual: senhaAtual, nova_senha: novaSenha }),
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="fa fa-refresh"></i> Atualizar Senha';

  if (json.ok) {
    alerta('Senha alterada com sucesso!', 'sucesso', 'segurancaAlerta');
    e.target.reset();
  } else {
    alerta(json.erro || 'Erro ao alterar senha.', 'erro', 'segurancaAlerta');
  }
}

// ── Sistema de abas ───────────────────────────────────────────
function iniciarAbas() {
  const abas    = document.querySelectorAll('[role="tab"]');
  const paineis = document.querySelectorAll('[role="tabpanel"]');

  function ativar(aba) {
    const id = aba.dataset.aba;
    abas.forEach(a => { a.classList.remove('ativo'); a.setAttribute('aria-selected', 'false'); });
    paineis.forEach(p => { p.classList.remove('ativo'); });
    aba.classList.add('ativo');
    aba.setAttribute('aria-selected', 'true');
    document.querySelector(`[data-aba="${id}"][role="tabpanel"]`)?.classList.add('ativo');
    document.querySelectorAll('.perfil-menu-item').forEach(m => {
      m.classList.toggle('ativo', m.dataset.aba === id);
    });
  }

  abas.forEach((aba, i) => {
    aba.addEventListener('click', () => ativar(aba));
    aba.addEventListener('keydown', e => {
      if (e.key === 'ArrowRight') { e.preventDefault(); ativar(abas[(i+1) % abas.length]); abas[(i+1) % abas.length].focus(); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); ativar(abas[(i-1+abas.length) % abas.length]); abas[(i-1+abas.length) % abas.length].focus(); }
    });
  });

  // Sincronizar menu lateral
  document.querySelectorAll('.perfil-menu-item').forEach(item => {
    item.addEventListener('click', () => {
      const aba = [...abas].find(a => a.dataset.aba === item.dataset.aba);
      if (aba) ativar(aba);
    });
  });

  // Ativar primeira aba
  if (abas.length) ativar(abas[0]);
}

// ── Modal de confirmação ──────────────────────────────────────
function iniciarModal() {
  const modal = document.getElementById('modalConfirm');
  if (!modal) return;
  [document.getElementById('modalFechar'), document.getElementById('btnModalCancelar')]
    .filter(Boolean)
    .forEach(b => b.addEventListener('click', () => modal.classList.remove('aberto')));
  modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('aberto'); });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && modal.classList.contains('aberto')) modal.classList.remove('aberto');
  });
}

window.mostrarModalConfirmacao = function(titulo, mensagem, onConfirm) {
  const modal = document.getElementById('modalConfirm');
  document.getElementById('modalTitulo').textContent = titulo;
  document.getElementById('modalMsg').textContent = mensagem;
  const btnAnt = document.getElementById('btnModalConfirmar');
  const btnNovo = btnAnt.cloneNode(true);
  btnAnt.parentElement.replaceChild(btnNovo, btnAnt);
  document.getElementById('btnModalConfirmar').addEventListener('click', () => {
    if (onConfirm) onConfirm();
    modal.classList.remove('aberto');
  });
  modal.classList.add('aberto');
};

// ── Inicialização ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  try {
    // Verificar sessão (api-client.js faz chamada para ../backend/auth/sessao.php)
    const sessao = await API.Auth.sessao();
    if (!sessao.ok || !sessao.logado) {
      window.location.href = '../login.html';
      return;
    }

    iniciarAbas();
    iniciarModal();

    const usuario = await carregarPerfil();
    exibirPerfil(usuario);

    document.getElementById('formPerfil')?.addEventListener('submit', submeterPerfil);
    document.getElementById('formSenha')?.addEventListener('submit', submeterSenha);

    Log.info('Dashboard carregado.');
  } catch (err) {
    Log.erro('Inicialização', err);
    alerta('Erro ao carregar perfil. Tente recarregar a página.', 'erro', 'perfilAlerta');
  }
});

/* ════════════════════════════════════════════════════════════════
   ABA: FAVORITOS
   ════════════════════════════════════════════════════════════════ */
async function carregarFavoritos() {
  const grid = document.getElementById('favoritosGrid');
  if (!grid) return;

  try {
    const r = await apiFetch('../backend/livros.php?acao=meus_favoritos', { method: 'GET' });
    if (!r.ok || !r.favoritos?.length) return;

    grid.innerHTML = '';
    r.favoritos.forEach(fav => {
      const card = document.createElement('div');
      card.className = 'perfil-livro-card';
      card.innerHTML = `
        <a href="../livros/${esc(fav.slug)}.html" class="perfil-livro-capa-link">
          <img src="../img/${esc(fav.slug)}.jpg"
               alt="Capa de ${esc(fav.titulo)}"
               class="perfil-livro-capa"
               onerror="this.src='../img/placeholder.jpg'" />
        </a>
        <div class="perfil-livro-info">
          <p class="perfil-livro-titulo">${esc(fav.titulo)}</p>
          <div class="perfil-livro-estrelas">
            ${'★'.repeat(fav.estrelas || 0)}${'☆'.repeat(5 - (fav.estrelas || 0))}
          </div>
          <button class="perfil-btn-remover-fav" data-slug="${esc(fav.slug)}" aria-label="Remover dos favoritos">
            <i class="fa fa-heart" aria-hidden="true"></i> Remover
          </button>
        </div>`;
      grid.appendChild(card);
    });

    // Botões de remover
    grid.querySelectorAll('.perfil-btn-remover-fav').forEach(btn => {
      btn.addEventListener('click', async () => {
        const slug = btn.dataset.slug;
        const r = await apiFetch('../backend/livros.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ acao: 'favoritar', livro: slug }),
        });
        if (r.ok) {
          btn.closest('.perfil-livro-card').remove();
          if (!grid.children.length) {
            grid.innerHTML = '<div class="perfil-vazio"><i class="fa fa-heart-o"></i><p>Sem favoritos.</p></div>';
          }
        }
      });
    });
  } catch (e) { Log.erro('carregarFavoritos', e); }
}

/* ════════════════════════════════════════════════════════════════
   ABA: DOWNLOADS
   ════════════════════════════════════════════════════════════════ */
async function carregarDownloads() {
  const container = document.getElementById('downloadsTabela');
  if (!container) return;

  try {
    const r = await apiFetch('../backend/downloads.php?acao=meus_downloads', { method: 'GET' });
    if (!r.ok || !r.downloads?.length) return;

    container.innerHTML = `
      <p style="margin-bottom:1rem;color:var(--texto-3);font-size:.88rem;">
        Total: <strong>${r.downloads.length}</strong> download(s) realizado(s).
      </p>
      <table class="perfil-tabela">
        <thead>
          <tr>
            <th>Livro</th>
            <th>Formato</th>
            <th>Data</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
          ${r.downloads.map(d => `
            <tr>
              <td><a href="../livros/${esc(d.slug)}.html" style="color:var(--ouro);">${esc(d.titulo)}</a></td>
              <td><span style="text-transform:uppercase;font-size:.8rem;">${esc(d.formato)}</span></td>
              <td style="color:var(--texto-3);font-size:.85rem;">${esc(d.data)}</td>
              <td>
                <a href="../backend/downloads.php?livro=${esc(d.slug)}&formato=${esc(d.formato)}"
                   class="perfil-btn" style="padding:.3rem .7rem;font-size:.75rem;"
                   title="Baixar novamente">
                  <i class="fa fa-download" aria-hidden="true"></i>
                </a>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>`;
  } catch (e) { Log.erro('carregarDownloads', e); }
}

/* ════════════════════════════════════════════════════════════════
   DELETAR CONTA
   ════════════════════════════════════════════════════════════════ */
function iniciarDeletarConta() {
  const btn = document.getElementById('btnDeletarConta');
  if (!btn) return;

  btn.addEventListener('click', () => {
    mostrarModalConfirmacao(
      '⚠ Deletar Conta Permanentemente',
      'Esta ação é irreversível. Todos os seus dados (favoritos, downloads, avaliações) serão apagados. Confirma?',
      async () => {
        // Pedir senha de confirmação
        const temSenha = document.getElementById('senhaAtual') || false;

        let confirmInput = '';
        // Criar mini-formulário inline
        const painel = document.getElementById('segurancaAlerta');
        if (painel) {
          painel.innerHTML = `
            <div class="perfil-alerta aviso" style="margin-bottom:1rem;">
              <strong>Confirmação final:</strong> Digite sua senha abaixo para deletar a conta.
              <div style="display:flex;gap:.5rem;margin-top:.75rem;">
                <input type="password" id="senhaConfirmDelete" placeholder="Sua senha"
                  style="flex:1;padding:.5rem;background:var(--fundo-2);border:1px solid var(--ferrugem);
                  border-radius:var(--raio);color:var(--texto);font-family:var(--fonte-ui);" />
                <button id="btnConfirmarDelete" class="perfil-btn perfil-btn-perigo"
                  style="padding:.5rem 1rem;">
                  <i class="fa fa-trash"></i> Confirmar
                </button>
              </div>
            </div>`;

          document.getElementById('btnConfirmarDelete').addEventListener('click', async () => {
            const senha = document.getElementById('senhaConfirmDelete').value;
            const r = await apiFetch('../backend/auth/deletar-conta.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ senha }),
            });
            if (r.ok) {
              alerta(r.mensagem, 'sucesso', 'segurancaAlerta');
              setTimeout(() => { window.location.href = '../index.html'; }, 2500);
            } else {
              alerta(r.erro || 'Erro ao deletar conta.', 'erro', 'segurancaAlerta');
            }
          });
        }
      }
    );
  });
}

/* Adicionar aos listeners de DOMContentLoaded existentes */
document.addEventListener('DOMContentLoaded', async () => {
  // Aguardar inicialização principal (definida no perfil.js original)
  setTimeout(() => {
    carregarFavoritos();
    carregarDownloads();
    iniciarDeletarConta();
  }, 500);
});
