/* ================================================================
 * ROBÉRIO DIÓGENES — js/api-client.js (v2.0 REFATORADO)
 * Cliente JS para comunicação com backend PHP
 * 
 * Melhorias implementadas:
 * ✓ Tratamento robusto de erros com try/catch
 * ✓ Async/await consistente (sem Promises manuais)
 * ✓ Validação de entrada de dados
 * ✓ Modularização com SOLID principles
 * ✓ Logging estruturado
 * ✓ Retry automático em falhas de rede
 * ✓ Timeout configurável
 * ✓ Cache de sessão
 * ================================================================ */

'use strict';

// ── CONFIGURAÇÃO GLOBAL ──────────────────────────────────────
// BASE_URL calculado dinamicamente: funciona em qualquer nível de pasta
// index.html       → backend/
// leitor/perfil.html → ../backend/
const _depth = window.location.pathname.split('/').filter(Boolean).length;
const _sitePath = window.location.pathname.includes('/leitor/') ? '../backend' : 'backend';

const CONFIG = {
  BASE_URL: _sitePath,
  TIMEOUT_MS: 10000,
  MAX_RETRIES: 3,
  RETRY_DELAY_MS: 1000,
  CACHE_DURATION_MS: 5 * 60 * 1000, // 5 minutos
};

// ── CACHE SIMPLES ────────────────────────────────────────────
const CACHE = new Map();

/**
 * Recupera valor do cache se válido
 * @param {string} key - Chave do cache
 * @returns {any|null}
 */
function obterDoCache(key) {
  const item = CACHE.get(key);
  if (!item) return null;

  if (Date.now() > item.expira) {
    CACHE.delete(key);
    return null;
  }

  return item.valor;
}

/**
 * Armazena valor no cache com expiração
 * @param {string} key - Chave do cache
 * @param {any} valor - Valor a armazenar
 * @param {number} duracao - Duração em ms
 */
function salvarNoCache(key, valor, duracao = CONFIG.CACHE_DURATION_MS) {
  CACHE.set(key, {
    valor,
    expira: Date.now() + duracao,
  });
}

// ── LOGGER ───────────────────────────────────────────────────
const Logger = {
  log: (msg, dados = null) => {
    if (typeof console === 'undefined') return;
    console.log(`[API] ${msg}`, dados || '');
  },

  warn: (msg, erro = null) => {
    if (typeof console === 'undefined') return;
    console.warn(`[API WARN] ${msg}`, erro || '');
  },

  erro: (msg, erro = null) => {
    if (typeof console === 'undefined') return;
    console.error(`[API ERRO] ${msg}`, erro || '');
  },
};

// ── VALIDADORES ──────────────────────────────────────────────
const Validadores = {
  /**
   * Valida e-mail
   * @param {string} email
   * @returns {boolean}
   */
  email: (email) => {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  },

  /**
   * Valida senha (mínimo 8 caracteres, letra e número)
   * @param {string} senha
   * @returns {boolean}
   */
  senha: (senha) => {
    return (
      typeof senha === 'string' &&
      senha.length >= 8 &&
      /[A-Za-z]/.test(senha) &&
      /[0-9]/.test(senha)
    );
  },

  /**
   * Valida nome (mínimo 2 caracteres)
   * @param {string} nome
   * @returns {boolean}
   */
  nome: (nome) => {
    return typeof nome === 'string' && nome.trim().length >= 2;
  },

  /**
   * Valida token
   * @param {string} token
   * @returns {boolean}
   */
  token: (token) => {
    return typeof token === 'string' && token.length > 0;
  },
};

// ── REQUISIÇÃO GENÉRICA COM RETRY ───────────────────────────
/**
 * Executa requisição HTTP com retry automático
 * @param {string} endpoint
 * @param {object} opcoes
 * @returns {Promise<object>}
 */
async function requisicao(endpoint, opcoes = {}) {
  const {
    metodo = 'POST',
    dados = null,
    semCache = false,
    tentativa = 0,
  } = opcoes;

  const url = `${CONFIG.BASE_URL}/${endpoint}`;
  const chaveCache = `${metodo}:${endpoint}`;

  // Verificar cache para GET
  if (metodo === 'GET' && !semCache) {
    const cacheado = obterDoCache(chaveCache);
    if (cacheado) {
      Logger.log(`Cache hit: ${endpoint}`);
      return cacheado;
    }
  }

  try {
    const config = {
      method: metodo,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      signal: AbortSignal.timeout?.(CONFIG.TIMEOUT_MS),
    };

    if (dados !== null) {
      config.body = JSON.stringify(dados);
    }

    Logger.log(`${metodo} ${endpoint}`, dados);

    const resposta = await fetch(url, config);

    // Resposta não-JSON
    if (!resposta.headers.get('content-type')?.includes('application/json')) {
      throw new Error(`Resposta não-JSON: ${resposta.status}`);
    }

    const json = await resposta.json();

    // Erro na resposta
    if (!resposta.ok) {
      Logger.warn(`${metodo} ${endpoint} (${resposta.status})`, json);
      return json;
    }

    // Cache para GET bem-sucedido
    if (metodo === 'GET' && !semCache) {
      salvarNoCache(chaveCache, json);
    }

    Logger.log(`${metodo} ${endpoint} ✓`);
    return json;
  } catch (erro) {
    // Timeout ou erro de rede
    if (erro.name === 'AbortError' || !navigator.onLine) {
      if (tentativa < CONFIG.MAX_RETRIES) {
        Logger.warn(
          `Retry ${tentativa + 1}/${CONFIG.MAX_RETRIES} para ${endpoint}`
        );
        await new Promise((resolve) => setTimeout(resolve, CONFIG.RETRY_DELAY_MS));
        return requisicao(endpoint, { ...opcoes, tentativa: tentativa + 1 });
      }
    }

    Logger.erro(`${metodo} ${endpoint}`, erro);
    return {
      ok: false,
      erro: 'Falha na conexão. Verifique sua internet.',
      detalhes: erro.message,
    };
  }
}

// ── API MODULE ───────────────────────────────────────────────
const API = (() => {
  return {
    /**
     * Módulo de Autenticação
     */
    Auth: {
      /**
       * Verifica sessão ativa
       * @returns {Promise<object>}
       */
      async sessao() {
        const cacheado = obterDoCache('sessao');
        if (cacheado) return cacheado;

        const resposta = await requisicao('auth/sessao.php', { metodo: 'GET' });
        if (resposta.ok) {
          salvarNoCache('sessao', resposta, 2 * 60 * 1000); // 2 min
        }
        return resposta;
      },

      /**
       * Cadastro com e-mail e senha
       * @param {string} nome
       * @param {string} email
       * @param {string} senha
       * @param {string} confirmarSenha
       * @returns {Promise<object>}
       */
      async cadastrar(nome, email, senha, confirmarSenha) {
        // Validações
        if (!Validadores.nome(nome)) {
          return {
            ok: false,
            erro: 'Nome inválido (mínimo 2 caracteres).',
          };
        }

        if (!Validadores.email(email)) {
          return { ok: false, erro: 'E-mail inválido.' };
        }

        if (!Validadores.senha(senha)) {
          return {
            ok: false,
            erro: 'Senha inválida (mín. 8 caracteres, letra e número).',
          };
        }

        if (senha !== confirmarSenha) {
          return { ok: false, erro: 'As senhas não conferem.' };
        }

        return requisicao('auth/register.php', {
          dados: {
            nome: nome.trim(),
            email: email.toLowerCase().trim(),
            senha,
            confirmar_senha: confirmarSenha,
          },
        });
      },

      /**
       * Login com e-mail e senha
       * @param {string} email
       * @param {string} senha
       * @returns {Promise<object>}
       */
      async entrar(email, senha) {
        if (!Validadores.email(email)) {
          return { ok: false, erro: 'E-mail inválido.' };
        }

        if (!senha || senha.length === 0) {
          return { ok: false, erro: 'Senha obrigatória.' };
        }

        return requisicao('auth/login.php', {
          dados: {
            email: email.toLowerCase().trim(),
            senha,
          },
        });
      },

      /**
       * Logout
       * @returns {Promise<object>}
       */
      async sair() {
        CACHE.delete('sessao');
        return requisicao('auth/logout.php', { dados: {} });
      },

      /**
       * Login com Google
       * @returns {Promise<void>}
       */
      async loginGoogle() {
        try {
          const resposta = await requisicao('auth/google-url.php', {
            metodo: 'GET',
            semCache: true,
          });

          if (resposta.ok && resposta.url) {
            window.location.href = resposta.url;
          } else {
            mostrarToast(
              'Não foi possível conectar ao Google. Tente novamente.',
              'erro'
            );
          }
        } catch (erro) {
          Logger.erro('loginGoogle', erro);
          mostrarToast('Erro ao conectar com Google.', 'erro');
        }
      },

      /**
       * Solicitar recuperação de senha
       * @param {string} email
       * @returns {Promise<object>}
       */
      async recuperar(email) {
        if (!Validadores.email(email)) {
          return { ok: false, erro: 'E-mail inválido.' };
        }

        return requisicao('auth/recuperar.php', {
          dados: { email: email.toLowerCase().trim() },
        });
      },

      /**
       * Redefinir senha com token
       * @param {string} token
       * @param {string} novaSenha
       * @returns {Promise<object>}
       */
      async resetarSenha(token, novaSenha) {
        if (!Validadores.token(token)) {
          return { ok: false, erro: 'Token inválido ou expirado.' };
        }

        if (!Validadores.senha(novaSenha)) {
          return {
            ok: false,
            erro: 'Senha inválida (mín. 8 caracteres, letra e número).',
          };
        }

        return requisicao('auth/resetar-senha.php', {
          dados: { token, senha: novaSenha },
        });
      },

      /**
       * Salvar/atualizar perfil do usuário
       * @param {object} dados - {sexo, data_nascimento, cidade, estado, pais, whatsapp, ...}
       * @returns {Promise<object>}
       */
      async salvarPerfil(dados) {
        if (!dados || typeof dados !== 'object') {
          return { ok: false, erro: 'Dados inválidos.' };
        }

        CACHE.delete('sessao');
        return requisicao('auth/perfil.php', { dados });
      },
    },

    /**
     * Módulo de Newsletter
     */
    Newsletter: {
      /**
       * Inscrever na newsletter
       * @param {string} email
       * @returns {Promise<object>}
       */
      async inscrever(email) {
        if (!Validadores.email(email)) {
          return { ok: false, erro: 'E-mail inválido.' };
        }

        return requisicao('newsletter.php', {
          dados: { email: email.toLowerCase().trim() },
        });
      },
    },

    /**
     * Módulo de Estado do Usuário
     */
    Usuario: {
      dados: null,

      /**
       * Inicializar estado do usuário
       * @returns {Promise<void>}
       */
      async iniciar() {
        try {
          const resposta = await API.Auth.sessao();

          if (resposta.ok && resposta.logado) {
            this.dados = resposta.usuario;
            this._atualizarUI();
          }
        } catch (erro) {
          Logger.erro('Usuario.iniciar', erro);
        }
      },

      /**
       * Atualizar UI com dados do usuário
       * @private
       */
      _atualizarUI() {
        if (!this.dados) return;

        const btnEntrar = document.querySelectorAll('.btn-entrar');
        if (btnEntrar.length === 0) return;

        // Detectar se estamos em subpasta (leitor/) para ajustar os links
        const emSubpasta = window.location.pathname.includes('/leitor/');
        const prefixo = emSubpasta ? '../' : '';

        btnEntrar.forEach((btn) => {
          try {
            const nome = this.dados.nome.split(' ')[0];
            const foto = this.dados.foto
              ? `<img src="${this.dados.foto}" alt="${nome}" class="nav-avatar" />`
              : `<i class="fa fa-user-circle nav-avatar-icone" aria-hidden="true"></i>`;

            btn.outerHTML = `
              <div class="nav-usuario-menu" id="navUsuarioMenu">
                <button class="nav-usuario-btn" aria-expanded="false" aria-haspopup="true">
                  ${foto}
                  <span>${nome}</span>
                  <i class="fa fa-chevron-down nav-usuario-seta" aria-hidden="true"></i>
                </button>
                <div class="nav-usuario-dropdown" hidden role="menu">
                  <a href="${prefixo}leitor/index.html" role="menuitem">
                    <i class="fa fa-book" aria-hidden="true"></i> Área do Leitor
                  </a>
                  <a href="${prefixo}leitor/perfil.html" role="menuitem">
                    <i class="fa fa-user" aria-hidden="true"></i> Meu Perfil
                  </a>
                  <button id="btnSair" class="nav-usuario-sair" role="menuitem">
                    <i class="fa fa-sign-out-alt" aria-hidden="true"></i> Sair
                  </button>
                </div>
              </div>
            `;

            this._configurarEventos();
          } catch (erro) {
            Logger.erro('_atualizarUI', erro);
          }
        });
      },

      /**
       * Configurar eventos do menu
       * @private
       */
      _configurarEventos() {
        const menu = document.getElementById('navUsuarioMenu');
        const btnMenu = menu?.querySelector('.nav-usuario-btn');
        const dropdown = menu?.querySelector('.nav-usuario-dropdown');
        const btnSair = document.getElementById('btnSair');

        if (!btnMenu || !dropdown) return;

        // Toggle dropdown
        btnMenu.addEventListener('click', () => {
          const aberto = !dropdown.hidden;
          dropdown.hidden = aberto;
          btnMenu.setAttribute('aria-expanded', String(!aberto));
        });

        // Fechar ao clicar fora
        document.addEventListener('click', (evento) => {
          if (!menu.contains(evento.target)) {
            dropdown.hidden = true;
            btnMenu.setAttribute('aria-expanded', 'false');
          }
        });

        // Logout
        btnSair?.addEventListener('click', async () => {
          try {
            await API.Auth.sair();
            window.location.reload();
          } catch (erro) {
            Logger.erro('logout', erro);
            mostrarToast('Erro ao fazer logout.', 'erro');
          }
        });
      },
    },

    /**
     * Módulo de Visitas
     */
    Visitas: {
      /**
       * Registrar visita única
       * @returns {Promise<number|null>}
       */
      async registrar() {
        try {
          const resposta = await requisicao('visitas.php', { metodo: 'GET' });
          return resposta.ok ? resposta.total : null;
        } catch (erro) {
          Logger.erro('Visitas.registrar', erro);
          return null;
        }
      },
    },
  };
})();

// ── INICIALIZAÇÃO ────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  API.Usuario.iniciar();
});

// ── HANDLERS GLOBAIS DA NEWSLETTER ───────────────────────────
/**
 * Handler para formulário de newsletter
 * @param {Event} evento
 */
window.inscricaoEmail = async function (evento) {
  evento.preventDefault();

  const input = document.getElementById('nl-email');
  const btn = evento.target.querySelector('[type="submit"]');

  if (!input?.value) {
    mostrarToast('Informe seu e-mail.', 'erro');
    return;
  }

  try {
    if (btn) {
      btn.disabled = true;
      btn.innerHTML =
        '<i class="fa fa-spinner fa-spin"></i> Aguarde…';
    }

    const resposta = await API.Newsletter.inscrever(input.value);

    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check"></i> Inscrever-me';
    }

    if (resposta.ok) {
      mostrarToast(resposta.mensagem || 'Inscrição realizada!', 'sucesso');
      input.value = '';

      const check = evento.target.querySelector('input[type="checkbox"]');
      if (check) check.checked = false;
    } else {
      mostrarToast(resposta.erro || 'Erro na inscrição.', 'erro');
    }
  } catch (erro) {
    Logger.erro('inscricaoEmail', erro);
    mostrarToast('Erro ao processar inscrição.', 'erro');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check"></i> Inscrever-me';
    }
  }
};

/**
 * Handler alternativo para formulário de newsletter
 * @param {Event} evento
 */
window.submeterNewsletter = async function (evento) {
  evento.preventDefault();

  const input = evento.target.querySelector('input[type="email"]');
  const btn = evento.target.querySelector('[type="submit"]');

  if (!input?.value) {
    mostrarToast('Informe seu e-mail.', 'erro');
    return;
  }

  try {
    if (btn) {
      btn.disabled = true;
      btn.innerHTML =
        '<i class="fa fa-spinner fa-spin"></i> Aguarde…';
    }

    const resposta = await API.Newsletter.inscrever(input.value);

    if (btn) {
      btn.disabled = false;
      btn.innerHTML = 'Inscrever-se';
    }

    if (resposta.ok) {
      mostrarToast(resposta.mensagem || 'Inscrição realizada!', 'sucesso');
      input.value = '';
    } else {
      mostrarToast(resposta.erro || 'Erro na inscrição.', 'erro');
    }
  } catch (erro) {
    Logger.erro('submeterNewsletter', erro);
    mostrarToast('Erro ao processar inscrição.', 'erro');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = 'Inscrever-se';
    }
  }
};

// ── HELPER: TOAST NOTIFICATIONS ──────────────────────────────
/**
 * Exibir notificação toast
 * @param {string} mensagem
 * @param {string} tipo - 'sucesso', 'erro', 'info'
 * @param {number} duracao - ms
 */
function mostrarToast(mensagem, tipo = 'info', duracao = 5000) {
  const wrap = document.getElementById('toastWrap');
  if (!wrap) return;

  const toast = document.createElement('div');
  toast.className = 'toast';

  let icone = '✓';
  if (tipo === 'erro') icone = '✕';
  else if (tipo === 'info') icone = 'ℹ';

  toast.innerHTML = `<span>${icone}</span> <span>${mensagem}</span>`;
  wrap.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'toastOut 0.35s ease forwards';
    setTimeout(() => toast.remove(), 350);
  }, duracao);
}

// ── EXPORTAÇÃO (para uso em módulos) ────────────────────────
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { API, Logger, Validadores, CONFIG };
}