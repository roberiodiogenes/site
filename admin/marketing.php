<?php
/* ================================================================
   admin/marketing.php
   AJAX handlers ANTES de qualquer include que emita HTML.
   ================================================================ */

/* ── Detecta AJAX: POST ou GET ?_buscar_camp ── */
$_isAjax = (
    $_SERVER['REQUEST_METHOD'] === 'POST' ||
    ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['_buscar_camp']))
);

if ($_isAjax) {
    session_name('rd_admin_sess');
    session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    $pdo = db();
}

/* ── Helpers de segmento ── */
function _contarSegmento(PDO $pdo, string $seg): int {
    return match($seg) {
        'newsletter'  => (int)$pdo->query("SELECT COUNT(*) FROM newsletter WHERE status='ativo'")->fetchColumn(),
        'compradores' => (int)$pdo->query("SELECT COUNT(DISTINCT usuario_id) FROM compras WHERE status='aprovada'")->fetchColumn(),
        'assinantes'  => (int)$pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW()")->fetchColumn(),
        'inativos_30' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login<DATE_SUB(NOW(),INTERVAL 30 DAY))")->fetchColumn(),
        'inativos_90' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login<DATE_SUB(NOW(),INTERVAL 90 DAY))")->fetchColumn(),
        'sem_compra'  => (int)$pdo->query("SELECT COUNT(*) FROM usuarios u WHERE ativo=1 AND NOT EXISTS(SELECT 1 FROM compras WHERE usuario_id=u.id AND status='aprovada')")->fetchColumn(),
        default       => (int)$pdo->query("SELECT COUNT(DISTINCT email) FROM (SELECT email FROM usuarios WHERE ativo=1 UNION SELECT email FROM newsletter WHERE status='ativo') t")->fetchColumn(),
    };
}

function _emailsSegmento(PDO $pdo, string $seg): array {
    // 1. O match apenas define a String da Query SQL de forma limpa
    $sql = match($seg) {
        'newsletter'  => "SELECT email, COALESCE(nome,'') AS nome FROM newsletter WHERE status='ativo'",
        'compradores' => "SELECT DISTINCT u.email, u.nome FROM usuarios u JOIN compras c ON c.usuario_id=u.id WHERE c.status='aprovada' AND u.ativo=1",
        'assinantes'  => "SELECT u.email, u.nome FROM usuarios u JOIN assinaturas a ON a.usuario_id=u.id WHERE a.status='ativa' AND a.expira_em>NOW() AND u.ativo=1",
        'inativos_30' => "SELECT email, nome FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login<DATE_SUB(NOW(),INTERVAL 30 DAY))",
        'inativos_90' => "SELECT email, nome FROM usuarios WHERE ativo=1 AND (ultimo_login IS NULL OR ultimo_login<DATE_SUB(NOW(),INTERVAL 90 DAY))",
        'sem_compra'  => "SELECT u.email, u.nome FROM usuarios u WHERE ativo=1 AND NOT EXISTS(SELECT 1 FROM compras WHERE usuario_id=u.id AND status='aprovada')",
        default       => "SELECT email, nome FROM usuarios WHERE ativo=1 UNION SELECT email, COALESCE(nome,'') AS nome FROM newsletter WHERE status='ativo'",
    };
    
    // 2. Executa a query garantindo o FETCH_ASSOC para todos os casos
    $stmt = $pdo->query($sql);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}


/* ── GET: Buscar campanha para o modal de edição ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['_buscar_camp'])) {
    header('Content-Type: application/json; charset=utf-8');
    $id  = (int)$_GET['_buscar_camp'];
    $row = $pdo->prepare('SELECT * FROM campanhas WHERE id=?');
    $row->execute([$id]);
    $camp = $row->fetch();
    echo json_encode($camp
        ? ['ok' => true,  'camp' => $camp]
        : ['ok' => false, 'erro' => 'Não encontrada.']
    );
    exit;
}

/* ── POST: Ações AJAX ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_campanha') {
        $id      = (int)($_POST['id'] ?? 0);
        $nome    = trim($_POST['nome']          ?? '');
        $tipo    = trim($_POST['tipo']          ?? 'newsletter');
        $seg     = trim($_POST['segmento']      ?? 'todos');
        $assunto = trim($_POST['assunto_email'] ?? '');
        $html    = trim($_POST['corpo_html']    ?? '');
        $texto   = trim($_POST['corpo_texto']   ?? '');
        $agend   = trim($_POST['agendado_para'] ?? '') ?: null;
        $status  = in_array($_POST['status']??'',['rascunho','agendada']) ? $_POST['status'] : 'rascunho';

        if (!$nome)    { echo json_encode(['ok'=>false,'erro'=>'Nome obrigatório.']);    exit; }
        if (!$assunto) { echo json_encode(['ok'=>false,'erro'=>'Assunto obrigatório.']); exit; }

        if ($id) {
            $pdo->prepare(
                "UPDATE campanhas SET nome=?,tipo=?,segmento=?,assunto_email=?,corpo_html=?,corpo_texto=?,agendado_para=?,status=? WHERE id=?"
            )->execute([$nome,$tipo,$seg,$assunto,$html,$texto,$agend,$status,$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Campanha atualizada!']);
        } else {
            $pdo->prepare(
                "INSERT INTO campanhas (nome,tipo,segmento,assunto_email,corpo_html,corpo_texto,agendado_para,status) VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$nome,$tipo,$seg,$assunto,$html,$texto,$agend,$status]);
            echo json_encode(['ok'=>true,'mensagem'=>'Campanha criada!','id'=>(int)$pdo->lastInsertId()]);
        }
        exit;
    }

    if ($acao === 'cancelar_campanha') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE campanhas SET status='cancelada' WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]);
        exit;
    }

    if ($acao === 'contar_segmento') {
        $seg = trim($_POST['segmento'] ?? 'todos');
        $n   = _contarSegmento($pdo, $seg);
        echo json_encode(['ok'=>true,'total'=>$n]);
        exit;
    }

    if ($acao === 'exportar_leads') {
        $seg    = trim($_POST['segmento'] ?? 'todos');
        $emails = _emailsSegmento($pdo, $seg);
        echo json_encode(['ok'=>true,'emails'=>$emails,'total'=>count($emails)]);
        exit;
    }

    if ($acao === 'descadastrar') {
        $email = trim($_POST['email'] ?? '');
        if ($email) {
            $pdo->prepare("UPDATE newsletter SET status='descadastrado',descad_em=NOW() WHERE email=?")->execute([$email]);
            $pdo->prepare("UPDATE usuarios SET ativo=0 WHERE email=?")->execute([$email]);
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
    exit;
}

/* ── HTML da página: inclui _admin.php agora ── */
$ADMIN_PAGE = 'marketing';
require_once __DIR__ . '/_admin.php';

/* ── Dados da página ── */
$campanhas = $pdo->query(
    "SELECT c.*,
     (SELECT COUNT(*) FROM campanhas_envios WHERE campanha_id=c.id) AS n_envios
     FROM campanhas c ORDER BY c.criado_em DESC LIMIT 50"
)->fetchAll();

// Contadores de segmentos
$segs = [
    'todos'       => _contarSegmento($pdo,'todos'),
    'newsletter'  => _contarSegmento($pdo,'newsletter'),
    'compradores' => _contarSegmento($pdo,'compradores'),
    'assinantes'  => _contarSegmento($pdo,'assinantes'),
    'inativos_30' => _contarSegmento($pdo,'inativos_30'),
    'inativos_90' => _contarSegmento($pdo,'inativos_90'),
    'sem_compra'  => _contarSegmento($pdo,'sem_compra'),
];

// Últimos leads da newsletter
$ultimosLeads = $pdo->query(
    "SELECT email,COALESCE(nome,'') AS nome,origem,status,created_at
     FROM newsletter ORDER BY created_at DESC LIMIT 10"
)->fetchAll();

// Últimos usuários cadastrados
$ultimosCad = $pdo->query(
    "SELECT nome,email,created_at FROM usuarios ORDER BY created_at DESC LIMIT 8"
)->fetchAll();

$TIPOS_CAMP = [
    'lancamento'    => '📢 Lançamento',
    'promocao'      => '🏷️ Promoção',
    'newsletter'    => '📰 Newsletter',
    'reengajamento' => '🔄 Reengajamento',
    'boas_vindas'   => '👋 Boas-vindas',
    'recompensa'    => '🎁 Recompensa',
    'destaque'      => '⭐ Destaque',
    'outro'         => '📌 Outro',
];
$SEGS_LABEL = [
    'todos'       => 'Toda a base',
    'newsletter'  => 'Inscritos newsletter',
    'compradores' => 'Compradores',
    'assinantes'  => 'Assinantes ativos',
    'inativos_30' => 'Inativos 30 dias',
    'inativos_90' => 'Inativos 90 dias',
    'sem_compra'  => 'Cadastrados sem compra',
    'personalizado'=> 'Personalizado',
];
?>
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="page-titulo">Marketing</h1>
    <p class="page-sub">Leads, campanhas e comunicação com leitores</p>
  </div>
  <button class="btn btn-primario" onclick="abrirModal('modalCampanha')">
    <i class="fa fa-plus"></i> Nova campanha
  </button>
</div>

<!-- ══ SEGMENTOS ════════════════════════════════════════════════ -->
<h2 style="font-family:Georgia,serif;font-size:1rem;color:var(--ouro);margin-bottom:.85rem;letter-spacing:.05em">
  <i class="fa fa-users"></i> Base de leads por segmento
</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.85rem;margin-bottom:2rem">
  <?php
  $icones = ['todos'=>'fa-globe','newsletter'=>'fa-envelope','compradores'=>'fa-cart-shopping',
             'assinantes'=>'fa-crown','inativos_30'=>'fa-clock','inativos_90'=>'fa-hourglass',
             'sem_compra'=>'fa-user-plus'];
  foreach ($segs as $s => $n):
    $urgente = ($s==='inativos_30'||$s==='inativos_90') && $n>0;
  ?>
  <div class="stat-card" style="<?= $urgente?'border-color:rgba(183,28,28,.3)':'' ?>;flex-direction:column;align-items:flex-start;gap:.35rem;cursor:pointer"
       onclick="exportar('<?= $s ?>')" title="Exportar e-mails deste segmento">
    <div style="display:flex;align-items:center;gap:.5rem;width:100%">
      <i class="fa <?= $icones[$s]??'fa-users' ?>" style="color:<?= $urgente?'var(--vermelho)':'var(--ouro)' ?>;font-size:1rem"></i>
      <span style="font-size:1.4rem;font-weight:700;color:<?= $urgente?'var(--vermelho)':'var(--texto)' ?>"><?= number_format((int)$n) ?></span>
    </div>
    <span style="font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;color:var(--texto-3)"><?= $SEGS_LABEL[$s] ?></span>
    <span style="font-size:.62rem;color:var(--ouro);opacity:.7"><i class="fa fa-download" style="font-size:.58rem"></i> Exportar CSV</span>
  </div>
  <?php endforeach; ?>
</div>

<div class="grade-2">

<!-- ══ ÚLTIMOS LEADS NEWSLETTER ═════════════════════════════ -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-envelope"></i> Últimos leads (newsletter)</span>
    <button class="btn btn-ghost btn-sm" onclick="exportar('newsletter')">
      <i class="fa fa-download"></i> Exportar
    </button>
  </div>
  <?php if (!$ultimosLeads): ?>
    <div class="estado-vazio"><i class="fa fa-envelope"></i><p>Nenhum inscrito ainda.</p></div>
  <?php else: ?>
  <table>
    <thead><tr><th>E-mail</th><th>Origem</th><th>Data</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($ultimosLeads as $l): ?>
    <tr>
      <td>
        <div style="font-size:.82rem;color:var(--texto)"><?= adm_esc($l['email']) ?></div>
        <?php if ($l['nome']): ?><div class="td-sub"><?= adm_esc($l['nome']) ?></div><?php endif; ?>
      </td>
      <td style="font-size:.72rem;color:var(--texto-3)"><?= adm_esc($l['origem'] ?? '—') ?></td>
      <td style="font-size:.72rem;white-space:nowrap"><?= adm_data($l['created_at'],'d/m/Y') ?></td>
      <td><?= adm_badge($l['status']==='ativo'?'ativo':'inativo') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- ══ ÚLTIMOS CADASTROS ════════════════════════════════════ -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-user-plus"></i> Últimos cadastros</span>
    <button class="btn btn-ghost btn-sm" onclick="exportar('todos')">
      <i class="fa fa-download"></i> Exportar todos
    </button>
  </div>
  <table>
    <thead><tr><th>Usuário</th><th>Cadastro</th></tr></thead>
    <tbody>
    <?php foreach ($ultimosCad as $u): ?>
    <tr>
      <td>
        <div class="td-nome"><?= adm_esc($u['nome']) ?></div>
        <div class="td-sub"><?= adm_esc($u['email']) ?></div>
      </td>
      <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($u['created_at'],'d/m H:i') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

</div><!-- /.grade-2 -->

<!-- ══ CAMPANHAS ════════════════════════════════════════════════ -->
<div class="secao" style="margin-top:1.5rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-paper-plane"></i> Campanhas de e-mail</span>
  </div>
  <?php if (!$campanhas): ?>
    <div class="estado-vazio"><i class="fa fa-paper-plane"></i><p>Nenhuma campanha criada ainda.</p></div>
  <?php else: ?>
  <table>
    <thead>
      <tr><th>Campanha</th><th>Tipo</th><th>Segmento</th><th>Status</th><th>Agendado</th><th>Enviados</th><th>Abertura</th><th>Ações</th></tr>
    </thead>
    <tbody>
    <?php foreach ($campanhas as $c):
      $taxaAbert = $c['total_enviados'] > 0
        ? round(($c['total_abertos'] / $c['total_enviados']) * 100) . '%'
        : '—';
    ?>
    <tr>
      <td>
        <div class="td-nome"><?= adm_esc($c['nome']) ?></div>
        <div class="td-sub"><?= adm_esc($c['assunto_email']) ?></div>
      </td>
      <td style="font-size:.75rem"><?= $TIPOS_CAMP[$c['tipo']] ?? adm_esc($c['tipo']) ?></td>
      <td style="font-size:.75rem"><?= $SEGS_LABEL[$c['segmento']] ?? adm_esc($c['segmento']) ?></td>
      <td>
        <?php
        $badgeCamp = ['rascunho'=>'badge-cinza','agendada'=>'badge-azul','enviando'=>'badge-amarelo','enviada'=>'badge-verde','cancelada'=>'badge-vermelho'];
        echo '<span class="badge '.($badgeCamp[$c['status']]??'badge-cinza').'">'.ucfirst($c['status']).'</span>';
        ?>
      </td>
      <td style="font-size:.72rem;white-space:nowrap"><?= $c['agendado_para'] ? adm_data($c['agendado_para'],'d/m/Y H:i') : '—' ?></td>
      <td style="text-align:center;font-size:.82rem"><?= $c['total_enviados'] ?: '—' ?></td>
      <td style="text-align:center;font-size:.82rem"><?= $taxaAbert ?></td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <?php if (in_array($c['status'],['rascunho','agendada'])): ?>
          <button class="btn btn-sm btn-ghost" onclick="editarCampanha(<?= $c['id'] ?>)" title="Editar">
            <i class="fa fa-pencil"></i>
          </button>
          <?php endif; ?>
          <?php if ($c['status']==='rascunho'): ?>
          <button class="btn btn-sm btn-primario" onclick="confirmarEnvio(<?= $c['id'] ?>, '<?= adm_esc(addslashes($c['nome'])) ?>', <?= $segs[$c['segmento']] ?? 0 ?>)" title="Enviar agora">
            <i class="fa fa-paper-plane"></i>
          </button>
          <?php endif; ?>
          <?php if (in_array($c['status'],['rascunho','agendada'])): ?>
          <button class="btn btn-sm btn-danger" onclick="cancelarCampanha(<?= $c['id'] ?>)" title="Cancelar">
            <i class="fa fa-ban"></i>
          </button>
          <?php endif; ?>
          <button class="btn btn-sm btn-ghost" onclick="verHTML(<?= $c['id'] ?>)" title="Preview do e-mail">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- ══ MODAL CAMPANHA ══════════════════════════════════════════ -->
<div class="modal-overlay" id="modalCampanha" style="align-items:flex-start;padding:2rem 1rem;overflow-y:auto">
  <div class="modal-box" style="max-width:680px;width:100%;margin:auto">
    <h2 class="modal-titulo" id="tituloModalCampanha"><i class="fa fa-paper-plane"></i> Nova campanha</h2>
    <form id="formCampanha" onsubmit="salvarCampanha(event)">
      <input type="hidden" id="campId" name="id" value="0">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div class="modal-campo">
          <label>Tipo *</label>
          <select name="tipo" id="campTipo">
            <?php foreach ($TIPOS_CAMP as $v=>$l): ?>
            <option value="<?= $v ?>"><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-campo">
          <label>Status</label>
          <select name="status" id="campStatus">
            <option value="rascunho">Rascunho</option>
            <option value="agendada">Agendada</option>
          </select>
        </div>
      </div>

      <div class="modal-campo">
        <label>Nome interno da campanha *</label>
        <input type="text" name="nome" id="campNome" placeholder="Ex: Lançamento Lúmen — Maio 2025" required>
      </div>

      <div class="modal-campo">
        <label>Assunto do e-mail *</label>
        <input type="text" name="assunto_email" id="campAssunto" placeholder="O que o leitor vê na caixa de entrada" required>
      </div>

      <div class="modal-campo">
        <label>
          Segmento de destinatários
          <button type="button" onclick="contarSeg()" class="btn btn-ghost btn-sm" style="margin-left:.5rem">
            <i class="fa fa-users"></i> Contar
          </button>
          <span id="contSeg" style="font-size:.78rem;color:var(--ouro);margin-left:.5rem"></span>
        </label>
        <select name="segmento" id="campSeg" onchange="document.getElementById('contSeg').textContent=''">
          <?php foreach ($SEGS_LABEL as $v=>$l): ?>
          <option value="<?= $v ?>"><?= $l ?> (<?= number_format((int)($segs[$v]??0)) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="modal-campo">
        <label>Agendar envio (opcional)</label>
        <input type="datetime-local" name="agendado_para" id="campAgend">
      </div>

      <div class="modal-campo">
        <label>Corpo do e-mail (HTML)</label>
        <textarea name="corpo_html" id="campHTML" rows="8"
                  placeholder="Cole aqui o HTML do e-mail ou use um template abaixo…"
                  style="width:100%;padding:.65rem .75rem;background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);color:var(--texto);font-size:.82rem;font-family:monospace;resize:vertical"></textarea>
      </div>

      <!-- Templates rápidos -->
      <div style="margin-bottom:.85rem">
        <p style="font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:var(--texto-3);margin-bottom:.4rem">Templates rápidos:</p>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
          <button type="button" class="btn btn-ghost btn-sm" onclick="usarTemplate('lancamento')">📢 Lançamento</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="usarTemplate('promocao')">🏷️ Promoção</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="usarTemplate('reengajamento')">🔄 Reengajamento</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="usarTemplate('recompensa')">🎁 Recompensa</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="usarTemplate('conto_semanal')">📜 Conto semanal</button>
        </div>
      </div>

      <div class="modal-campo">
        <label>Versão texto puro (fallback para clientes sem HTML)</label>
        <textarea name="corpo_texto" id="campTexto" rows="3"
                  placeholder="Versão simplificada para caixas de entrada que não exibem HTML…"
                  style="width:100%;padding:.65rem .75rem;background:var(--fundo-input);border:1px solid var(--borda);border-radius:var(--raio);color:var(--texto);font-size:.82rem;resize:vertical"></textarea>
      </div>

      <div class="modal-btns">
        <button type="button" class="btn btn-ghost" onclick="fecharModal('modalCampanha')">Cancelar</button>
        <button type="button" class="btn btn-ghost" onclick="previewEmail()"><i class="fa fa-eye"></i> Preview</button>
        <button type="submit" class="btn btn-primario" id="btnSalvCamp">
          <i class="fa fa-floppy-disk"></i> Salvar rascunho
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ══ MODAL PREVIEW E-MAIL ════════════════════════════════════ -->
<div class="modal-overlay" id="modalPreview" style="align-items:flex-start;padding:2rem 1rem;overflow-y:auto">
  <div class="modal-box" style="max-width:640px;width:100%;margin:auto">
    <h2 class="modal-titulo"><i class="fa fa-eye"></i> Preview do e-mail</h2>
    <div id="previewFrame" style="background:#fff;border-radius:6px;padding:1rem;max-height:70vh;overflow-y:auto"></div>
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalPreview')">Fechar</button>
    </div>
  </div>
</div>

<script>
/* ── Templates de e-mail ── */
const TEMPLATES = {
  lancamento: `<h2 style="font-family:Georgia,serif;color:#B8860B">Novo lançamento! 📢</h2>
<p>Olá, {{nome}}!</p>
<p>Tenho uma novidade para compartilhar com você: <strong>[[TÍTULO DO LIVRO/CONTO]]</strong> já está disponível.</p>
<p>[[DESCRIÇÃO CURTA DA OBRA]]</p>
<p style="text-align:center;margin:2rem 0">
  <a href="[[LINK]]" style="background:#B8860B;color:#1A0F00;padding:.75rem 1.75rem;border-radius:6px;text-decoration:none;font-weight:700">Quero ler agora →</a>
</p>
<p>Até a próxima história,<br><strong>Robério Diógenes</strong></p>`,

  promocao: `<h2 style="font-family:Georgia,serif;color:#B8860B">Oferta especial para você 🏷️</h2>
<p>Olá, {{nome}}!</p>
<p>Por tempo limitado, <strong>[[TÍTULO]]</strong> está com preço especial: <strong>R$ [[PREÇO]]</strong>.</p>
<p>A promoção vai até [[DATA]].</p>
<p style="text-align:center;margin:2rem 0">
  <a href="[[LINK]]" style="background:#B8860B;color:#1A0F00;padding:.75rem 1.75rem;border-radius:6px;text-decoration:none;font-weight:700">Aproveitar agora →</a>
</p>
<p>Robério Diógenes</p>`,

  reengajamento: `<h2 style="font-family:Georgia,serif;color:#B8860B">Sentimos sua falta, {{nome}} 🔄</h2>
<p>Faz um tempo que você não aparece por aqui.</p>
<p>Enquanto isso, algumas novidades chegaram à biblioteca — contos novos, lançamentos e histórias que podem ser exatamente o que você está procurando.</p>
<p style="text-align:center;margin:2rem 0">
  <a href="https://roberiodiogenes.com/livros.html" style="background:#B8860B;color:#1A0F00;padding:.75rem 1.75rem;border-radius:6px;text-decoration:none;font-weight:700">Ver o que é novo →</a>
</p>
<p>Robério Diógenes</p>`,

  recompensa: `<h2 style="font-family:Georgia,serif;color:#B8860B">Um presente para você 🎁</h2>
<p>Olá, {{nome}}!</p>
<p>Como agradecimento pela sua fidelidade, preparei algo especial para você: [[DESCRIÇÃO DO PRESENTE/CUPOM]].</p>
<p>[[INSTRUÇÕES DE RESGATE]]</p>
<p style="text-align:center;margin:2rem 0">
  <a href="[[LINK]]" style="background:#B8860B;color:#1A0F00;padding:.75rem 1.75rem;border-radius:6px;text-decoration:none;font-weight:700">Resgatar →</a>
</p>
<p>Obrigado por fazer parte disso,<br><strong>Robério Diógenes</strong></p>`,

  conto_semanal: `<h2 style="font-family:Georgia,serif;color:#B8860B">Novo conto da semana 📜</h2>
<p>Olá, {{nome}}!</p>
<p>O conto desta semana chegou: <strong>[[TÍTULO DO CONTO]]</strong>.</p>
<p style="font-style:italic;color:#666;padding:.75rem 1.25rem;border-left:3px solid #B8860B">[[TRECHO DE ABERTURA DO CONTO]]</p>
<p>[[É gratuito / Disponível para assinantes / Disponível por R$ XX]]</p>
<p style="text-align:center;margin:2rem 0">
  <a href="[[LINK]]" style="background:#B8860B;color:#1A0F00;padding:.75rem 1.75rem;border-radius:6px;text-decoration:none;font-weight:700">Ler o conto →</a>
</p>
<p>Até semana que vem,<br><strong>Robério Diógenes</strong></p>`,
};

function usarTemplate(nome) {
  const tpl = TEMPLATES[nome];
  if (tpl) document.getElementById('campHTML').value = tpl;
}

function previewEmail() {
  const html = document.getElementById('campHTML').value;
  const assunto = document.getElementById('campAssunto').value;
  document.getElementById('previewFrame').innerHTML =
    `<div style="font-size:.8rem;color:#999;margin-bottom:.5rem;padding-bottom:.5rem;border-bottom:1px solid #eee">
       <strong>Assunto:</strong> ${esc(assunto)}
     </div>
     <div>${html}</div>`;
  abrirModal('modalPreview');
}

function verHTML(id) {
  // Reabre o modal com dados da campanha existente
  editarCampanha(id).then(() => {
    fecharModal('modalCampanha');
    previewEmail();
  });
}

/* ── Contar segmento ── */
async function contarSeg() {
  const seg = document.getElementById('campSeg').value;
  const r = await fetch('marketing.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=contar_segmento&segmento=${seg}`});
  const d = await r.json();
  if (d.ok) document.getElementById('contSeg').textContent = `→ ${d.total} destinatários`;
}

/* ── Exportar CSV ── */
async function exportar(seg) {
  toast('Preparando exportação…');
  try {
    const r = await fetch('marketing.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=exportar_leads&segmento=${seg}`});
    if (!r.ok) { toast('Erro HTTP ' + r.status,'erro'); return; }
    const d = await r.json();
    if (!d.ok) { toast(d.erro||'Erro ao exportar.','erro'); return; }
    if (!d.emails || d.emails.length === 0) { toast('Nenhum e-mail neste segmento.','ok'); return; }

    // Gera CSV no browser via data URI (compatível com todos os contextos)
    const rows = [['email','nome'], ...d.emails.map(e => [e.email||'', e.nome||''])];
    const csv  = rows.map(row => row.map(v => `"${String(v).replace(/"/g,'""')}"`).join(',')).join('\r\n');
    const bom  = '\uFEFF'; // BOM para Excel reconhecer UTF-8
    const dataUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(bom + csv);
    const a = document.createElement('a');
    a.href = dataUri;
    a.download = `leads_${seg}_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    toast(`✓ ${d.total} e-mails exportados com sucesso!`);
  } catch(e) {
    console.error('Exportar erro:', e);
    toast('Erro de conexão ao exportar.','erro');
  }
}

/* ── Confirmar envio ── */
function confirmarEnvio(id, nome, total) {
  if (!confirm(`Enviar campanha "${nome}" para ${total} destinatários?\n\nEsta ação não pode ser desfeita.`)) return;
  toast('Funcionalidade de envio em lote será implementada com PHPMailer/Cron. Campanha marcada como agendada.','ok');
  // TODO: endpoint de envio em lote (requer cron job)
}

/* ── Cancelar campanha ── */
async function cancelarCampanha(id) {
  if (!confirm('Cancelar esta campanha?')) return;
  const r = await fetch('marketing.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=cancelar_campanha&id=${id}`});
  const d = await r.json();
  if (d.ok) { toast('Campanha cancelada.'); setTimeout(()=>location.reload(),1100); }
  else toast(d.erro||'Erro.','erro');
}

/* ── Salvar campanha ── */
async function salvarCampanha(e) {
  e.preventDefault();
  const btn = document.getElementById('btnSalvCamp');
  btn.disabled=true; btn.innerHTML='<i class="fa fa-circle-notch fa-spin"></i>';
  const fd = new FormData(document.getElementById('formCampanha'));
  fd.set('acao','salvar_campanha');
  try {
    const r = await fetch('marketing.php',{method:'POST',body:new URLSearchParams(fd)});
    const d = await r.json();
    fecharModal('modalCampanha');
    if (d.ok) { toast(d.mensagem||'Salvo!'); setTimeout(()=>location.reload(),1100); }
    else toast(d.erro||'Erro.','erro');
  } catch { toast('Erro de conexão.','erro'); }
  finally { btn.disabled=false; btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar rascunho'; }
}

/* ── Editar campanha existente ── */
async function editarCampanha(id) {
  document.getElementById('tituloModalCampanha').innerHTML='<i class="fa fa-pencil"></i> Editar campanha';
  try {
    const r = await fetch(`marketing.php?_buscar_camp=${id}`, {credentials:'same-origin'});
    const d = await r.json();
    if (d.ok && d.camp) {
      const c = d.camp;
      document.getElementById('campId').value      = c.id;
      document.getElementById('campNome').value    = c.nome      || '';
      document.getElementById('campAssunto').value = c.assunto_email || '';
      document.getElementById('campTipo').value    = c.tipo      || 'newsletter';
      document.getElementById('campSeg').value     = c.segmento  || 'todos';
      document.getElementById('campStatus').value  = c.status    || 'rascunho';
      document.getElementById('campHTML').value    = c.corpo_html  || '';
      document.getElementById('campTexto').value   = c.corpo_texto || '';
      if (c.agendado_para) {
        document.getElementById('campAgend').value = c.agendado_para.replace(' ','T').slice(0,16);
      }
    } else {
      document.getElementById('campId').value = id;
    }
  } catch(e) {
    document.getElementById('campId').value = id;
  }
  abrirModal('modalCampanha');
  return Promise.resolve();
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
