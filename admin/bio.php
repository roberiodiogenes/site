<?php
/* ================================================================
   admin/bio.php — Editor da bio + Analytics do Ateliê de Histórias
   ================================================================ */
ob_start();

/* ── AJAX GET (endpoints JSON para o painel) ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    ini_set('display_errors', '0');
    ob_end_clean();
    session_name('rd_admin_sess');
    session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => 'Sessão expirada.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    header('Content-Type: application/json; charset=utf-8');
    $pdo   = db();
    $acao  = $_GET['acao'];
    $dias  = max(7, min(365, (int)($_GET['dias'] ?? 30)));

    /* ── Cliques nos links da bio (legado) ── */
    if ($acao === 'cliques') {
        try {
            $stL = $pdo->prepare(
                "SELECT COALESCE(bc.link_slug, CONCAT('link-', bc.link_id)) AS slug,
                        COALESCE(bl.titulo, bc.link_slug, CONCAT('Link #', bc.link_id)) AS titulo,
                        COUNT(*) AS total,
                        COUNT(DISTINCT bc.ip_hash) AS unicos
                   FROM bio_clicks bc
                   LEFT JOIN bio_links bl ON bl.id = bc.link_id
                  WHERE bc.clicado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY slug, titulo
                  ORDER BY total DESC LIMIT 20"
            );
            $stL->execute([$dias]);
            $porLink = $stL->fetchAll(PDO::FETCH_ASSOC);

            $stO = $pdo->prepare(
                "SELECT COALESCE(origem, 'Direto') AS origem, COUNT(*) AS total
                   FROM bio_clicks
                  WHERE clicado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY origem ORDER BY total DESC LIMIT 10"
            );
            $stO->execute([$dias]);
            $porOrigem = $stO->fetchAll(PDO::FETCH_ASSOC);

            $stT = $pdo->prepare("SELECT COUNT(*), COUNT(DISTINCT ip_hash) FROM bio_clicks WHERE clicado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stT->execute([$dias]);
            [$totalCliques, $totalUnicos] = $stT->fetch(PDO::FETCH_NUM);

            echo json_encode([
                'ok' => true,
                'por_link'   => $porLink,
                'por_origem' => $porOrigem,
                'total'      => (int)$totalCliques,
                'unicos'     => (int)$totalUnicos,
                'periodo_dias' => $dias,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'por_link' => [], 'por_origem' => [], 'total' => 0, 'unicos' => 0, 'periodo_dias' => $dias]);
        }
        exit;
    }

    /* ── Analytics do Ateliê de Histórias ── */
    if ($acao === 'atelie') {
        try {
            $generos = ['drama', 'romance', 'terror', 'autoajuda'];

            // Funil geral
            $funil = [];
            $eventos_funil = ['pagina_aberta', 'card_aberto', 'conto_lido_50pct', 'conto_lido_100pct', 'email_capturado', 'compartilhamento', 'embaixador_gerado'];
            foreach ($eventos_funil as $ev) {
                $st = $pdo->prepare("SELECT COUNT(*) FROM bio_eventos WHERE evento=? AND criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)");
                $st->execute([$ev, $dias]);
                $funil[$ev] = (int)$st->fetchColumn();
            }

            // Por gênero
            $porGenero = [];
            foreach ($generos as $g) {
                $st = $pdo->prepare(
                    "SELECT
                       SUM(evento='card_aberto')       AS abertos,
                       SUM(evento='conto_lido_50pct')  AS leram_50,
                       SUM(evento='conto_lido_100pct') AS leram_100
                     FROM bio_eventos
                    WHERE genero=? AND criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)"
                );
                $st->execute([$g, $dias]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                $porGenero[$g] = [
                    'abertos'   => (int)($row['abertos']   ?? 0),
                    'leram_50'  => (int)($row['leram_50']  ?? 0),
                    'leram_100' => (int)($row['leram_100'] ?? 0),
                ];
            }

            // Leads recentes
            $stLeads = $pdo->prepare(
                "SELECT email, tipo, genero, captado_em
                   FROM bio_leads
                  ORDER BY captado_em DESC LIMIT 20"
            );
            $stLeads->execute();
            $leads = $stLeads->fetchAll(PDO::FETCH_ASSOC);

            // Leads por tipo
            $stTipos = $pdo->query("SELECT tipo, COUNT(*) AS total FROM bio_leads GROUP BY tipo ORDER BY total DESC");
            $leadsPorTipo = $stTipos->fetchAll(PDO::FETCH_ASSOC);

            // Sessões únicas (total visitantes)
            $stSess = $pdo->prepare("SELECT COUNT(DISTINCT sessao_id) FROM bio_eventos WHERE evento='pagina_aberta' AND criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stSess->execute([$dias]);
            $sessoes = (int)$stSess->fetchColumn();

            echo json_encode([
                'ok'           => true,
                'funil'        => $funil,
                'por_genero'   => $porGenero,
                'leads'        => $leads,
                'leads_por_tipo' => $leadsPorTipo,
                'sessoes'      => $sessoes,
                'periodo_dias' => $dias,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'erro' => $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'erro' => 'Ação desconhecida.']);
    exit;
}

/* ── AJAX POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', '0');
    ob_end_clean();
    session_name('rd_admin_sess');
    session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'erro'=>'Sessão expirada.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    // Delegar para backend/bio.php
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    try {
        $pdo  = db();
        $acao = trim($body['acao'] ?? $_POST['acao'] ?? '');

        if ($acao === 'salvar_config') {
            $dados  = $body['dados'] ?? [];
            $campos = ['nome','subtitulo','foto','instagram','whatsapp','telegram','linkedin','email'];
            $stmt   = $pdo->prepare("INSERT INTO bio_config (chave, valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)");
            foreach ($campos as $c) {
                if (array_key_exists($c, $dados)) $stmt->execute([$c, trim($dados[$c] ?? '')]);
            }
            echo json_encode(['ok'=>true,'mensagem'=>'Configurações salvas.']);
            exit;
        }

        if ($acao === 'salvar_link') {
            $id        = (int)($body['id'] ?? 0);
            $titulo    = trim($body['titulo']    ?? '');
            $subtitulo = trim($body['subtitulo'] ?? '');
            $url       = trim($body['url']       ?? '');
            $icone     = trim($body['icone']     ?? '');
            $tipo      = in_array($body['tipo']??'',['link','destaque']) ? $body['tipo'] : 'link';
            $ativo     = (int)($body['ativo']    ?? 1);
            $ordem     = (int)($body['ordem']    ?? 99);

            if (!$titulo) { echo json_encode(['ok'=>false,'erro'=>'Título obrigatório.']); exit; }
            if (!$url)    { echo json_encode(['ok'=>false,'erro'=>'URL obrigatória.']);    exit; }

            if ($id) {
                $pdo->prepare("UPDATE bio_links SET titulo=?,subtitulo=?,url=?,icone=?,tipo=?,ativo=?,ordem=? WHERE id=?")
                    ->execute([$titulo,$subtitulo,$url,$icone,$tipo,$ativo,$ordem,$id]);
            } else {
                $pdo->prepare("INSERT INTO bio_links (titulo,subtitulo,url,icone,tipo,ativo,ordem) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$titulo,$subtitulo,$url,$icone,$tipo,$ativo,$ordem]);
                $id = (int)$pdo->lastInsertId();
            }
            echo json_encode(['ok'=>true,'mensagem'=>'Link salvo.','id'=>$id]);
            exit;
        }

        if ($acao === 'deletar_link') {
            $id = (int)($body['id'] ?? 0);
            if ($id) $pdo->prepare("DELETE FROM bio_links WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Link removido.']);
            exit;
        }

        if ($acao === 'toggle_ativo') {
            $id = (int)($body['id'] ?? 0);
            $pdo->prepare("UPDATE bio_links SET ativo=1-ativo WHERE id=?")->execute([$id]);
            $novo = (int)$pdo->query("SELECT ativo FROM bio_links WHERE id=$id")->fetchColumn();
            echo json_encode(['ok'=>true,'ativo'=>$novo]);
            exit;
        }

        if ($acao === 'reordenar') {
            $ids  = array_filter(array_map('intval', $body['ids'] ?? []));
            $stmt = $pdo->prepare("UPDATE bio_links SET ordem=? WHERE id=?");
            foreach (array_values($ids) as $pos => $lid) $stmt->execute([$pos+1, $lid]);
            echo json_encode(['ok'=>true,'mensagem'=>'Ordem salva.']);
            exit;
        }

        echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]);
    }
    exit;
}

/* ── HTML ── */
$ADMIN_PAGE = 'bio';
require_once __DIR__ . '/_admin.php';

/* Carregar dados */
$config = [];
$links  = [];
try {
    $rows = $pdo->query("SELECT chave, valor FROM bio_config")->fetchAll();
    foreach ($rows as $r) $config[$r['chave']] = $r['valor'];
    $links = $pdo->query("SELECT * FROM bio_links ORDER BY ordem ASC, id ASC")->fetchAll();
} catch (Throwable $e) { /* tabela não existe ainda */ }

$cfg = fn(string $k, string $def = '') => htmlspecialchars($config[$k] ?? $def, ENT_QUOTES);
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="page-titulo"><i class="fa fa-link"></i> Bio — Link da Bio</h1>
    <p class="page-sub">Página para o Instagram e redes sociais — <a href="../bio.html" target="_blank" style="color:var(--ouro)">ver ao vivo ↗</a></p>
  </div>
  <button class="btn btn-primario" onclick="abrirModalLink(0)">
    <i class="fa fa-plus"></i> Novo link
  </button>
</div>

<!-- ── CONFIGURAÇÕES GERAIS ── -->
<div class="secao" style="margin-bottom:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-user-pen"></i> Perfil e redes sociais</span>
    <button class="btn btn-primario btn-sm" onclick="salvarConfig()">
      <i class="fa fa-floppy-disk"></i> Salvar
    </button>
  </div>
  <div style="padding:1.25rem;display:grid;grid-template-columns:1fr 1fr;gap:.85rem">
    <div class="modal-campo">
      <label>Nome exibido</label>
      <input type="text" id="cfg_nome" value="<?= $cfg('nome','Robério Diógenes') ?>">
    </div>
    <div class="modal-campo">
      <label>Subtítulo</label>
      <input type="text" id="cfg_subtitulo" value="<?= $cfg('subtitulo','Escritor · Literatura Brasileira') ?>">
    </div>
    <div class="modal-campo">
      <label>Foto (caminho relativo)</label>
      <input type="text" id="cfg_foto" value="<?= $cfg('foto','img/autor2.jpg') ?>" placeholder="img/autor2.jpg">
    </div>
    <div class="modal-campo">
      <label><i class="fa-brands fa-instagram" style="color:#E1306C"></i> Instagram (URL)</label>
      <input type="url" id="cfg_instagram" value="<?= $cfg('instagram','https://instagram.com/diogenesroberio') ?>">
    </div>
    <div class="modal-campo">
      <label><i class="fa-brands fa-whatsapp" style="color:#25D366"></i> WhatsApp (URL wa.me)</label>
      <input type="url" id="cfg_whatsapp" value="<?= $cfg('whatsapp','https://wa.me/5585996409818') ?>">
    </div>
    <div class="modal-campo">
      <label><i class="fa-brands fa-telegram" style="color:#0088cc"></i> Telegram (URL t.me)</label>
      <input type="url" id="cfg_telegram" value="<?= $cfg('telegram','https://t.me/5585996409818') ?>">
    </div>
    <div class="modal-campo">
      <label><i class="fa-brands fa-linkedin-in" style="color:#0A66C2"></i> LinkedIn (URL)</label>
      <input type="url" id="cfg_linkedin" value="<?= $cfg('linkedin','https://linkedin.com/in/roberio-diogenes') ?>">
    </div>
    <div class="modal-campo">
      <label><i class="fa fa-envelope" style="color:var(--ouro)"></i> E-mail (mailto:)</label>
      <input type="text" id="cfg_email" value="<?= $cfg('email','mailto:contato@roberiodiogenes.com') ?>">
    </div>
  </div>
</div>

<!-- ── LISTA DE LINKS ── -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-list"></i> Links (arraste para reordenar)</span>
    <a href="../bio.html" target="_blank" class="btn btn-ghost btn-sm">
      <i class="fa fa-eye"></i> Ver página
    </a>
  </div>

  <?php if (!$links): ?>
    <div class="estado-vazio">
      <i class="fa fa-link"></i>
      <p>Nenhum link cadastrado. Execute a migration_bio.sql e clique em "Novo link".</p>
    </div>
  <?php else: ?>
  <div id="listaLinks" style="padding:.5rem">
    <?php foreach ($links as $l): ?>
    <div class="link-row" data-id="<?= (int)$l['id'] ?>"
         style="display:flex;align-items:center;gap:.75rem;padding:.7rem .85rem;margin-bottom:.4rem;
                background:var(--fundo-input);border:1px solid <?= $l['ativo']?'var(--borda)':'rgba(192,57,43,.25)' ?>;
                border-radius:var(--raio);cursor:grab;">
      <i class="fa fa-grip-vertical" style="color:var(--texto-3);font-size:.8rem;cursor:grab"></i>
      <div class="bio-link-icone-preview"
           style="width:30px;height:30px;border-radius:6px;background:rgba(184,134,11,.12);
                  display:flex;align-items:center;justify-content:center;color:var(--ouro);font-size:.8rem;flex-shrink:0">
        <i class="fa <?= adm_esc($l['icone'] ?: 'fa-link') ?>"></i>
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-size:.85rem;font-weight:600;color:<?= $l['tipo']==='destaque'?'var(--ouro)':'var(--texto)' ?>">
          <?= adm_esc($l['titulo']) ?>
          <?php if ($l['tipo']==='destaque'): ?>
            <span class="badge badge-ouro" style="margin-left:.4rem">Destaque</span>
          <?php endif; ?>
        </div>
        <div style="font-size:.7rem;color:var(--texto-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= adm_esc($l['url']) ?>
        </div>
      </div>
      <div style="display:flex;gap:.3rem;flex-shrink:0">
        <button class="btn btn-sm btn-ghost" onclick="abrirModalLink(<?= (int)$l['id'] ?>)" title="Editar">
          <i class="fa fa-pencil"></i>
        </button>
        <button class="btn btn-sm btn-ghost" onclick="toggleAtivo(<?= (int)$l['id'] ?>, this)"
                style="color:<?= $l['ativo']?'#4CAF50':'var(--texto-3)' ?>"
                title="<?= $l['ativo']?'Desativar':'Ativar' ?>">
          <i class="fa <?= $l['ativo']?'fa-eye':'fa-eye-slash' ?>"></i>
        </button>
        <button class="btn btn-sm btn-danger" onclick="deletarLink(<?= (int)$l['id'] ?>, '<?= adm_esc(addslashes($l['titulo'])) ?>')" title="Deletar">
          <i class="fa fa-trash"></i>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Preview ao vivo -->
<div style="text-align:center;margin-top:1.25rem">
  <p style="font-size:.78rem;color:var(--texto-3);margin-bottom:.5rem">
    <i class="fa fa-mobile-screen-button" style="color:var(--ouro)"></i>
    Link para compartilhar no Instagram:
  </p>
  <code style="background:var(--fundo-input);padding:.35rem .8rem;border-radius:var(--raio);font-size:.82rem;color:var(--ouro)">
    https://roberiodiogenes.com/bio.html
  </code>
</div>

<!-- ── ATELIÊ DE HISTÓRIAS — ANALYTICS ── -->
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-book-open"></i> Ateliê de Histórias — Analytics</span>
    <div style="display:flex;gap:.5rem;align-items:center">
      <select id="selPeriodoAtelie" onchange="carregarAtelie()" style="padding:.25rem .5rem;background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);color:var(--texto);font-size:.78rem">
        <option value="7">7 dias</option>
        <option value="30" selected>30 dias</option>
        <option value="90">90 dias</option>
      </select>
    </div>
  </div>
  <div id="atelieConteudo" style="padding:1rem">
    <p style="color:var(--texto-3);font-size:.82rem;text-align:center">Carregando…</p>
  </div>
</div>

<!-- ── PAINEL DE CLIQUES (links bio legado) ── -->
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-chart-bar"></i> Cliques nos Links (legado)</span>
    <div style="display:flex;gap:.5rem;align-items:center">
      <select id="selPeriodo" onchange="carregarCliques()" style="padding:.25rem .5rem;background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);color:var(--texto);font-size:.78rem">
        <option value="7">7 dias</option>
        <option value="30" selected>30 dias</option>
        <option value="90">90 dias</option>
      </select>
    </div>
  </div>
  <div id="cliquesConteudo" style="padding:1rem">
    <p style="color:var(--texto-3);font-size:.82rem;text-align:center">Carregando estatísticas…</p>
  </div>
</div>

<!-- ── MODAL LINK ── -->
<div class="modal-overlay" id="modalLink">
  <div class="modal-box" style="max-width:520px;width:100%">
    <h2 class="modal-titulo" id="modalLinkTitulo"><i class="fa fa-link"></i> Novo Link</h2>

    <form id="formLink" onsubmit="salvarLink(event)">
      <input type="hidden" id="linkId" value="0">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo" style="grid-column:1/-1">
          <label>Título *</label>
          <input type="text" id="linkTitulo" placeholder="Ex: Biblioteca de Obras" required>
        </div>
        <div class="modal-campo" style="grid-column:1/-1">
          <label>Subtítulo (opcional)</label>
          <input type="text" id="linkSubtitulo" placeholder="Ex: Romances e ficção literária">
        </div>
        <div class="modal-campo" style="grid-column:1/-1">
          <label>URL *</label>
          <input type="text" id="linkUrl" placeholder="Ex: /livros.html ou https://..." required>
        </div>
        <div class="modal-campo">
          <label>Ícone Font Awesome</label>
          <input type="text" id="linkIcone" placeholder="fa-book" list="iconesLista">
          <datalist id="iconesLista">
            <option value="fa-book">fa-book</option>
            <option value="fa-pen-nib">fa-pen-nib</option>
            <option value="fa-book-open">fa-book-open</option>
            <option value="fa-crown">fa-crown</option>
            <option value="fa-user-pen">fa-user-pen</option>
            <option value="fa-gift">fa-gift</option>
            <option value="fa-link">fa-link</option>
            <option value="fa-star">fa-star</option>
            <option value="fa-heart">fa-heart</option>
          </datalist>
        </div>
        <div class="modal-campo">
          <label>Tipo</label>
          <select id="linkTipo">
            <option value="link">Link normal</option>
            <option value="destaque">Destaque (dourado)</option>
          </select>
        </div>
        <div class="modal-campo">
          <label>Status</label>
          <select id="linkAtivo">
            <option value="1">Ativo (visível)</option>
            <option value="0">Inativo (oculto)</option>
          </select>
        </div>
        <div class="modal-campo">
          <label>Ordem</label>
          <input type="number" id="linkOrdem" value="99" min="1">
        </div>
      </div>

      <div class="modal-btns">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modalLink')">Cancelar</button>
        <button type="submit" class="btn btn-primario" id="btnSalvarLink">
          <i class="fa fa-floppy-disk"></i> Salvar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
/* ── Dados dos links para edição ── */
const LINKS_DATA = <?= json_encode(array_map(fn($l) => [
    'id'        => (int)$l['id'],
    'titulo'    => $l['titulo'],
    'subtitulo' => $l['subtitulo'] ?? '',
    'url'       => $l['url'],
    'icone'     => $l['icone'] ?? '',
    'tipo'      => $l['tipo'],
    'ativo'     => (int)$l['ativo'],
    'ordem'     => (int)$l['ordem'],
], $links)) ?>;

function abrirModalLink(id) {
  const l = LINKS_DATA.find(x => x.id === id);
  document.getElementById('modalLinkTitulo').innerHTML =
    id ? '<i class="fa fa-pencil"></i> Editar Link' : '<i class="fa fa-plus"></i> Novo Link';
  document.getElementById('linkId').value        = id;
  document.getElementById('linkTitulo').value    = l?.titulo    || '';
  document.getElementById('linkSubtitulo').value = l?.subtitulo || '';
  document.getElementById('linkUrl').value       = l?.url       || '';
  document.getElementById('linkIcone').value     = l?.icone     || '';
  document.getElementById('linkTipo').value      = l?.tipo      || 'link';
  document.getElementById('linkAtivo').value     = l !== undefined ? String(l.ativo) : '1';
  document.getElementById('linkOrdem').value     = l?.ordem     || 99;
  abrirModal('modalLink');
}

async function salvarLink(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSalvarLink');
  btn.disabled = true; btn.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i>';
  try {
    const r = await fetch('bio.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        acao:      'salvar_link',
        id:        parseInt(document.getElementById('linkId').value),
        titulo:    document.getElementById('linkTitulo').value.trim(),
        subtitulo: document.getElementById('linkSubtitulo').value.trim(),
        url:       document.getElementById('linkUrl').value.trim(),
        icone:     document.getElementById('linkIcone').value.trim(),
        tipo:      document.getElementById('linkTipo').value,
        ativo:     parseInt(document.getElementById('linkAtivo').value),
        ordem:     parseInt(document.getElementById('linkOrdem').value),
      }),
    });
    const d = await r.json();
    fecharModal('modalLink');
    if (d.ok) { toast(d.mensagem || 'Salvo!'); setTimeout(()=>location.reload(), 900); }
    else toast(d.erro || 'Erro.', 'erro');
  } catch { toast('Erro de conexão.', 'erro'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar'; }
}

async function deletarLink(id, titulo) {
  if (!confirm(`Remover "${titulo}"?`)) return;
  const r = await fetch('bio.php', {method:'POST',headers:{'Content-Type':'application/json'},
    body: JSON.stringify({acao:'deletar_link',id})});
  const d = await r.json();
  if (d.ok) { toast('Link removido.'); document.querySelector(`[data-id="${id}"]`)?.remove(); }
  else toast(d.erro||'Erro.','erro');
}

async function toggleAtivo(id, btn) {
  const r = await fetch('bio.php', {method:'POST',headers:{'Content-Type':'application/json'},
    body: JSON.stringify({acao:'toggle_ativo',id})});
  const d = await r.json();
  if (d.ok) {
    btn.style.color = d.ativo ? '#4CAF50' : 'var(--texto-3)';
    btn.querySelector('i').className = 'fa ' + (d.ativo ? 'fa-eye' : 'fa-eye-slash');
    toast(d.ativo ? 'Link ativado.' : 'Link ocultado.');
  }
}

async function salvarConfig() {
  const dados = {};
  ['nome','subtitulo','foto','instagram','whatsapp','telegram','linkedin','email'].forEach(k => {
    dados[k] = document.getElementById('cfg_'+k)?.value.trim() || '';
  });
  const r = await fetch('bio.php', {method:'POST',headers:{'Content-Type':'application/json'},
    body: JSON.stringify({acao:'salvar_config',dados})});
  const d = await r.json();
  toast(d.ok ? 'Configurações salvas!' : (d.erro||'Erro.'), d.ok?'ok':'erro');
}

/* ── Ateliê de Histórias — Analytics ── */
const GENERO_LABEL = { drama:'Drama', romance:'Romance', terror:'Terror', autoajuda:'Autoajuda' };
const GENERO_COR   = { drama:'#6B7FA3', romance:'#B07A8A', terror:'#8A3A3A', autoajuda:'#8A7340' };

async function carregarAtelie() {
  const dias = document.getElementById('selPeriodoAtelie')?.value || 30;
  const el   = document.getElementById('atelieConteudo');
  if (!el) return;
  el.innerHTML = '<p style="color:var(--texto-3);font-size:.82rem;text-align:center">Carregando…</p>';
  try {
    const r = await fetch(`bio.php?acao=atelie&dias=${dias}`, { credentials: 'same-origin' });
    const d = await r.json();
    if (!d.ok) {
      el.innerHTML = '<p style="color:var(--texto-3);font-size:.82rem;text-align:center">Sem dados ainda. Execute a migration <code>database/bio_eventos.sql</code>.</p>';
      return;
    }

    const fmtN = n => Number(n).toLocaleString('pt-BR');
    const pct  = (a, b) => b > 0 ? Math.round(a/b*100) : 0;
    const f    = d.funil;

    /* ── Métricas de topo ── */
    let html = `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.65rem;margin-bottom:1.25rem">`;
    const cards = [
      { ic:'fa-eye',       val: d.sessoes,                  lbl:'Visitantes'     },
      { ic:'fa-book-open', val: f.card_aberto,              lbl:'Abriram conto'  },
      { ic:'fa-hourglass-half', val: f.conto_lido_50pct,   lbl:'Leram 50%+'     },
      { ic:'fa-check-double',   val: f.conto_lido_100pct,  lbl:'Leram tudo'     },
      { ic:'fa-envelope',  val: f.email_capturado,          lbl:'Leads capturados'},
      { ic:'fa-share-nodes',val: f.compartilhamento,        lbl:'Compartilhamentos'},
      { ic:'fa-medal',     val: f.embaixador_gerado,        lbl:'Embaixadores'   },
    ];
    cards.forEach(c => {
      html += `<div style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);padding:.75rem .85rem;text-align:center">
        <i class="fa ${c.ic}" style="color:var(--ouro);font-size:.95rem;opacity:.7;display:block;margin-bottom:.35rem"></i>
        <div style="font-size:1.4rem;font-weight:700;color:var(--texto)">${fmtN(c.val)}</div>
        <div style="font-size:.62rem;letter-spacing:.08em;text-transform:uppercase;color:var(--texto-3);margin-top:.15rem">${c.lbl}</div>
      </div>`;
    });
    html += '</div>';

    /* ── Funil visual ── */
    const etapas = [
      { label:'Visitantes',       val: d.sessoes              },
      { label:'Abriram conto',    val: f.card_aberto          },
      { label:'Leram 50%',        val: f.conto_lido_50pct     },
      { label:'Leram 100%',       val: f.conto_lido_100pct    },
      { label:'Leads',            val: f.email_capturado      },
      { label:'Shares',           val: f.compartilhamento     },
      { label:'Embaixadores',     val: f.embaixador_gerado    },
    ];
    const maxVal = etapas[0]?.val || 1;
    html += `<div style="margin-bottom:1.5rem">
      <div style="font-size:.63rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.65rem">Funil de conversão (${dias} dias)</div>`;
    etapas.forEach((et, i) => {
      const w      = maxVal > 0 ? Math.max(2, Math.round(et.val/maxVal*100)) : 0;
      const conv   = i > 0 && etapas[i-1].val > 0 ? pct(et.val, etapas[i-1].val) : null;
      html += `<div style="margin-bottom:.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.18rem">
          <span style="font-size:.79rem;color:var(--texto)">${et.label}</span>
          <span style="font-size:.75rem;color:var(--ouro);font-weight:700">${fmtN(et.val)}${conv !== null ? ` <span style="color:var(--texto-3);font-weight:400">(${conv}%)</span>` : ''}</span>
        </div>
        <div style="height:6px;background:var(--fundo-2);border-radius:3px">
          <div style="height:100%;width:${w}%;background:var(--ouro);border-radius:3px;transition:width .6s ease;opacity:${1 - i*0.1}"></div>
        </div>
      </div>`;
    });
    html += '</div>';

    /* ── Por gênero ── */
    html += `<div style="margin-bottom:1.5rem">
      <div style="font-size:.63rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.65rem">Por gênero</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.6rem">`;
    Object.entries(d.por_genero).forEach(([g, v]) => {
      const taxa = pct(v.leram_50, v.abertos);
      html += `<div style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);padding:.75rem .9rem">
        <div style="display:flex;align-items:center;gap:.45rem;margin-bottom:.55rem">
          <span style="width:8px;height:8px;border-radius:50%;background:${GENERO_COR[g]};flex-shrink:0"></span>
          <span style="font-size:.8rem;font-weight:600;color:var(--texto)">${GENERO_LABEL[g]}</span>
          <span style="margin-left:auto;font-size:.68rem;color:var(--texto-3)">${taxa}% leitura</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);text-align:center;gap:.25rem">
          <div><div style="font-size:1.05rem;font-weight:700;color:${GENERO_COR[g]}">${fmtN(v.abertos)}</div><div style="font-size:.6rem;color:var(--texto-3)">abertos</div></div>
          <div><div style="font-size:1.05rem;font-weight:700;color:${GENERO_COR[g]}">${fmtN(v.leram_50)}</div><div style="font-size:.6rem;color:var(--texto-3)">50%+</div></div>
          <div><div style="font-size:1.05rem;font-weight:700;color:${GENERO_COR[g]}">${fmtN(v.leram_100)}</div><div style="font-size:.6rem;color:var(--texto-3)">100%</div></div>
        </div>
      </div>`;
    });
    html += '</div></div>';

    /* ── Leads por tipo ── */
    if (d.leads_por_tipo.length) {
      html += `<div style="margin-bottom:1.5rem">
        <div style="font-size:.63rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.65rem">Leads por isca</div>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem">`;
      d.leads_por_tipo.forEach(t => {
        const label = {pdf:'PDF completo','contos-ineditos':'Contos inéditos',playlist:'Playlist','carta-do-autor':'Carta do autor'}[t.tipo] || t.tipo || 'Sem tipo';
        html += `<span style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:20px;padding:.2rem .75rem;font-size:.73rem;color:var(--texto-2)">
          ${label} <strong style="color:var(--ouro)">${fmtN(t.total)}</strong>
        </span>`;
      });
      html += '</div></div>';
    }

    /* ── Últimos leads ── */
    if (d.leads.length) {
      html += `<div>
        <div style="font-size:.63rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.65rem">Últimos leads capturados</div>
        <table>
          <thead><tr>
            <th>E-mail</th><th>Isca</th><th>Gênero</th><th>Captado em</th>
          </tr></thead>
          <tbody>`;
      d.leads.forEach(l => {
        const iscaLabel = {pdf:'PDF','contos-ineditos':'Contos inéditos',playlist:'Playlist','carta-do-autor':'Carta autor'}[l.tipo] || l.tipo || '—';
        const genLabel  = GENERO_LABEL[l.genero] || l.genero || '—';
        const dt        = l.captado_em ? new Date(l.captado_em).toLocaleDateString('pt-BR') : '—';
        html += `<tr>
          <td class="td-nome">${l.email}</td>
          <td>${iscaLabel}</td>
          <td>${genLabel}</td>
          <td style="color:var(--texto-3)">${dt}</td>
        </tr>`;
      });
      html += '</tbody></table></div>';
    } else {
      html += `<p style="text-align:center;color:var(--texto-3);font-size:.82rem;padding:1rem 0">Nenhum lead capturado ainda.</p>`;
    }

    el.innerHTML = html;
  } catch (e) {
    el.innerHTML = '<p style="color:var(--texto-3);font-size:.82rem;text-align:center">Erro ao carregar analytics do Ateliê.</p>';
  }
}

/* ── Painel de cliques (links legado) ── */
async function carregarCliques() {
  const dias = document.getElementById('selPeriodo')?.value || 30;
  const el   = document.getElementById('cliquesConteudo');
  if (!el) return;
  el.innerHTML = '<p style="color:var(--texto-3);font-size:.82rem;text-align:center">Carregando…</p>';
  try {
    const r = await fetch(`bio.php?acao=cliques&dias=${dias}`, { credentials: 'same-origin' });
    const d = await r.json();
    if (!d.ok) { el.innerHTML='<p style="color:var(--texto-3);font-size:.82rem;text-align:center">Sem dados ainda.</p>'; return; }

    const fmtN = n => Number(n).toLocaleString('pt-BR');

    let html = `
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.25rem">
        <div style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);padding:.85rem 1rem;text-align:center">
          <div style="font-size:1.6rem;font-weight:700;color:var(--ouro)">${fmtN(d.total)}</div>
          <div style="font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--texto-3)">Cliques</div>
        </div>
        <div style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);padding:.85rem 1rem;text-align:center">
          <div style="font-size:1.6rem;font-weight:700;color:var(--ouro)">${fmtN(d.unicos)}</div>
          <div style="font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--texto-3)">Únicos</div>
        </div>
        <div style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);padding:.85rem 1rem;text-align:center">
          <div style="font-size:1.6rem;font-weight:700;color:var(--ouro)">${d.por_link.length}</div>
          <div style="font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--texto-3)">Links</div>
        </div>
      </div>`;

    if (d.por_link.length) {
      const maxTotal = Math.max(...d.por_link.map(l=>l.total));
      html += `<div style="margin-bottom:1.25rem">
        <div style="font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.6rem">Por link</div>`;
      d.por_link.forEach(l => {
        const w = maxTotal > 0 ? Math.round((l.total/maxTotal)*100) : 0;
        html += `<div style="margin-bottom:.55rem">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem">
            <span style="font-size:.82rem;color:var(--texto);max-width:65%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${l.titulo}</span>
            <span style="font-size:.78rem;color:var(--ouro);font-weight:700">${fmtN(l.total)}</span>
          </div>
          <div style="height:5px;background:var(--fundo-2);border-radius:3px">
            <div style="height:100%;width:${w}%;background:var(--ouro);border-radius:3px;transition:width .5s ease"></div>
          </div>
        </div>`;
      });
      html += '</div>';
    }

    if (d.por_origem.length) {
      html += `<div style="font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.6rem">Por origem</div>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem">`;
      d.por_origem.forEach(o => {
        html += `<span style="background:var(--fundo-input);border:1px solid var(--borda);border-radius:20px;padding:.2rem .65rem;font-size:.72rem;color:var(--texto-2)">${o.origem} <strong style="color:var(--ouro)">${fmtN(o.total)}</strong></span>`;
      });
      html += '</div>';
    }

    if (!d.total) {
      html += `<p style="text-align:center;color:var(--texto-3);font-size:.82rem;padding:1rem 0">Nenhum clique nos últimos ${dias} dias.</p>`;
    }

    el.innerHTML = html;
  } catch (e) {
    el.innerHTML = '<p style="color:var(--texto-3);font-size:.82rem;text-align:center">Erro ao carregar estatísticas.</p>';
  }
}

carregarAtelie();
carregarCliques();
</script>

<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
