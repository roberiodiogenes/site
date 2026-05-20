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
      <div class="perfil-avatar">
        ${u.foto_url
          ? `<img src="${esc(u.foto_url)}" alt="Avatar de ${esc(u.nome)}" />`
          : `<i class="fa fa-user-circle" aria-hidden="true"></i>`}
      </div>
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
