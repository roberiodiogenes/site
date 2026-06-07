<?php
/* ================================================================
   admin/comentarios.php — Moderação de comentários
   ================================================================ */
ob_start(); // Buffer para não quebrar handlers AJAX com saída HTML acidental

/* ── AJAX GET: log de um comentário (deve vir antes do HTML) ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['acao'] ?? '') === 'log') {
    ini_set('display_errors', '0');
    ob_end_clean();
    session_name('rd_admin_sess');
    session_start();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'erro'=>'Não autenticado.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_GET['id'] ?? 0);
    try {
        $pdo = db();
        $st  = $pdo->prepare("SELECT * FROM comentarios_flags_log WHERE comentario_id=? ORDER BY criado_em DESC LIMIT 1");
        $st->execute([$id]);
        $log = $st->fetch();
        echo json_encode($log ? ['ok'=>true,'log'=>$log] : ['ok'=>false,'erro'=>'Log não encontrado.']);
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]);
    }
    exit;
}

/* ── AJAX POST handlers ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', '0');
    ob_start();
    session_name('rd_admin_sess');
    session_start();
    ob_end_clean();
    if (empty($_SESSION['admin_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'erro'=>'Não autenticado.']);
        exit;
    }
    require_once __DIR__ . '/../backend/config.php';
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo  = db();
        $acao = $_POST['acao'] ?? '';
        $id   = (int)($_POST['id'] ?? 0);

        if ($acao === 'aprovar') {
            $pdo->prepare("UPDATE comentarios SET aprovado=1 WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Comentário aprovado.']);
            exit;
        }
        if ($acao === 'desaprovar') {
            $pdo->prepare("UPDATE comentarios SET aprovado=0 WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Comentário ocultado.']);
            exit;
        }
        if ($acao === 'deletar') {
            $pdo->prepare("DELETE FROM comentarios WHERE id=?")->execute([$id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Comentário removido permanentemente.']);
            exit;
        }
        if ($acao === 'marcar_revisado') {
            $adminNomeAcao = $_SESSION['admin_nome'] ?? 'Admin';
            $pdo->prepare(
                "UPDATE comentarios_flags_log SET acao_tomada=?, revisado_em=NOW(), revisado_por=? WHERE comentario_id=?"
            )->execute([$_POST['acao_log'] ?? 'mantido', $adminNomeAcao, $id]);
            echo json_encode(['ok'=>true,'mensagem'=>'Revisão registrada.']);
            exit;
        }
        echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
    } catch (Throwable $e) {
        echo json_encode(['ok'=>false,'erro'=>$e->getMessage()]);
    }
    exit;
}

/* ── HTML ── */
$ADMIN_PAGE = 'comentarios';
require_once __DIR__ . '/_admin.php';

/* ── Parâmetros de filtro ── */
$filtro     = $_GET['filtro'] ?? 'todos';
$pagina     = max(1, (int)($_GET['p'] ?? 1));
$porPagina  = 30;
$offset     = ($pagina - 1) * $porPagina;

/* ── Queries ── */
$whereMap = [
    'todos'     => '1=1',
    'flagged'   => 'c.flagged = 1',
    'pendentes' => 'c.flagged = 1 AND NOT EXISTS (SELECT 1 FROM comentarios_flags_log fl WHERE fl.comentario_id = c.id AND fl.acao_tomada != "pendente")',
    'ocultos'   => 'c.aprovado = 0',
    'respostas' => 'c.parent_id IS NOT NULL',
];
$whereSQL = $whereMap[$filtro] ?? '1=1';

try {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM comentarios c WHERE $whereSQL")->fetchColumn();
    $stmt  = $pdo->prepare(
        "SELECT c.id, c.parent_id, c.texto, c.aprovado, c.flagged, c.flag_motivo,
                c.curtidas_count, c.criado_em, c.referencia, c.ip_hash,
                u.nome AS autor_nome, u.email AS autor_email, u.foto_url,
                pc.texto AS texto_pai, pu.nome AS autor_pai_nome
         FROM comentarios c
         JOIN usuarios u ON u.id = c.usuario_id
         LEFT JOIN comentarios pc ON pc.id = c.parent_id
         LEFT JOIN usuarios pu ON pu.id = pc.usuario_id
         WHERE $whereSQL
         ORDER BY c.flagged DESC, c.criado_em DESC
         LIMIT $porPagina OFFSET $offset"
    );
    $stmt->execute();
    $comentarios = $stmt->fetchAll();
} catch (Throwable $e) {
    $comentarios = [];
    $total = 0;
}

// Contadores para os filtros
try {
    $nTodos     = (int)$pdo->query("SELECT COUNT(*) FROM comentarios")->fetchColumn();
    $nFlagged   = (int)$pdo->query("SELECT COUNT(*) FROM comentarios WHERE flagged=1")->fetchColumn();
    $nPendentes = (int)$pdo->query(
        "SELECT COUNT(*) FROM comentarios c WHERE c.flagged=1
         AND NOT EXISTS (SELECT 1 FROM comentarios_flags_log fl WHERE fl.comentario_id=c.id AND fl.acao_tomada!='pendente')"
    )->fetchColumn();
    $nOcultos   = (int)$pdo->query("SELECT COUNT(*) FROM comentarios WHERE aprovado=0")->fetchColumn();
} catch (Throwable $e) {
    $nTodos = $nFlagged = $nPendentes = $nOcultos = 0;
}

$totalPaginas = max(1, (int)ceil($total / $porPagina));
?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="page-titulo"><i class="fa fa-comments"></i> Moderação de Comentários</h1>
    <p class="page-sub">Gerencie, modere e revise conteúdo flagged automaticamente</p>
  </div>
</div>

<!-- Stats -->
<div class="stats-grade" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
  <div class="stat-card">
    <i class="fa fa-comments stat-icone"></i>
    <div><div class="stat-valor"><?= $nTodos ?></div><div class="stat-label">Total</div></div>
  </div>
  <div class="stat-card" style="<?= $nPendentes > 0 ? 'border-color:rgba(192,57,43,.5)' : '' ?>">
    <i class="fa fa-flag stat-icone" style="<?= $nPendentes > 0 ? 'color:#e74c3c' : '' ?>"></i>
    <div>
      <div class="stat-valor" style="<?= $nPendentes > 0 ? 'color:#e74c3c' : '' ?>"><?= $nPendentes ?></div>
      <div class="stat-label">Pendentes de revisão</div>
    </div>
  </div>
  <div class="stat-card">
    <i class="fa fa-shield-halved stat-icone"></i>
    <div><div class="stat-valor"><?= $nFlagged ?></div><div class="stat-label">Flagged (total)</div></div>
  </div>
  <div class="stat-card">
    <i class="fa fa-eye-slash stat-icone"></i>
    <div><div class="stat-valor"><?= $nOcultos ?></div><div class="stat-label">Ocultos</div></div>
  </div>
</div>

<?php if ($nPendentes > 0): ?>
<div style="background:rgba(192,57,43,.12);border:1px solid rgba(192,57,43,.35);border-radius:var(--raio-lg);padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem">
  <i class="fa fa-triangle-exclamation" style="color:#e74c3c;font-size:1.2rem"></i>
  <div>
    <strong style="color:#e74c3c"><?= $nPendentes ?> comentário<?= $nPendentes > 1 ? 's' : '' ?> flaggado<?= $nPendentes > 1 ? 's' : '' ?> aguardando revisão.</strong>
    <span style="color:var(--texto-3);font-size:.82rem"> O filtro automático detectou conteúdo potencialmente nocivo. Revise e tome uma ação.</span>
  </div>
  <a href="?filtro=pendentes" class="btn btn-sm btn-danger" style="margin-left:auto;white-space:nowrap">Ver pendentes</a>
</div>
<?php endif; ?>

<!-- Filtros -->
<div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem">
  <?php
  $abas = [
      'todos'     => ['label'=>'Todos',           'n'=>$nTodos],
      'flagged'   => ['label'=>'🚩 Flagged',       'n'=>$nFlagged],
      'pendentes' => ['label'=>'⚠️ Pend. revisão', 'n'=>$nPendentes],
      'ocultos'   => ['label'=>'👁 Ocultos',       'n'=>$nOcultos],
      'respostas' => ['label'=>'↩ Respostas',      'n'=>null],
  ];
  foreach ($abas as $key => $aba):
  ?>
    <a href="?filtro=<?= $key ?>"
       class="btn btn-sm <?= $filtro===$key?'btn-primario':'btn-ghost' ?>"
       <?= ($key==='flagged'||$key==='pendentes') && ($aba['n']??0) > 0 ? 'style="border-color:#e74c3c;color:#e74c3c"' : '' ?>>
      <?= $aba['label'] ?><?= $aba['n'] !== null ? ' ('.$aba['n'].')' : '' ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Tabela -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-list"></i> <?= $total ?> comentário<?= $total !== 1 ? 's' : '' ?></span>
  </div>

  <?php if (!$comentarios): ?>
    <div class="estado-vazio"><i class="fa fa-comments"></i><p>Nenhum comentário neste filtro.</p></div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Autor</th>
        <th>Comentário</th>
        <th>Post / Referência</th>
        <th>Status</th>
        <th>Data</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($comentarios as $c): ?>
    <tr id="row-<?= $c['id'] ?>" style="<?= $c['flagged'] ? 'background:rgba(192,57,43,.04)' : '' ?>">
      <td style="min-width:120px">
        <div class="td-nome" style="font-size:.78rem"><?= adm_esc($c['autor_nome']) ?></div>
        <div class="td-sub"><?= adm_esc($c['autor_email']) ?></div>
        <?php if ($c['ip_hash']): ?>
          <div class="td-sub" title="SHA256 do IP (LGPD)" style="font-family:monospace;font-size:.58rem;opacity:.5"><?= substr($c['ip_hash'],0,12) ?>…</div>
        <?php endif; ?>
      </td>
      <td style="max-width:380px">
        <?php if ($c['parent_id']): ?>
          <div style="font-size:.65rem;color:var(--texto-3);margin-bottom:.3rem">
            ↩ Resposta a <strong><?= adm_esc($c['autor_pai_nome'] ?? '?') ?></strong>:
            <em style="opacity:.7">"<?= adm_esc(mb_substr($c['texto_pai'] ?? '', 0, 60)) ?>…"</em>
          </div>
        <?php endif; ?>
        <div style="font-size:.82rem;line-height:1.5;color:var(--texto)"><?= nl2br(adm_esc(mb_substr($c['texto'],0,280))) ?><?= mb_strlen($c['texto'])>280 ? '…' : '' ?></div>
        <?php if ($c['flagged']): ?>
          <div style="margin-top:.4rem;padding:.3rem .6rem;background:rgba(192,57,43,.1);border-radius:var(--raio);font-size:.65rem;color:#e74c3c">
            <i class="fa fa-flag"></i> <strong>Flag:</strong> <?= adm_esc($c['flag_motivo']) ?>
          </div>
        <?php endif; ?>
        <?php if ($c['curtidas_count'] > 0): ?>
          <div style="font-size:.65rem;color:var(--texto-3);margin-top:.2rem"><i class="fa fa-heart"></i> <?= $c['curtidas_count'] ?> curtida<?= $c['curtidas_count']>1?'s':'' ?></div>
        <?php endif; ?>
      </td>
      <td style="font-size:.75rem;max-width:140px">
        <a href="<?= SITE_URL ?>/diario/<?= adm_esc($c['referencia']) ?>" target="_blank"
           style="color:var(--ouro);word-break:break-all"><?= adm_esc($c['referencia']) ?></a>
      </td>
      <td>
        <?php if ($c['flagged']): ?>
          <span class="badge badge-vermelho"><i class="fa fa-flag"></i> Flagged</span>
        <?php endif; ?>
        <?= adm_badge($c['aprovado'] ? 'ativo' : 'inativo') ?>
      </td>
      <td style="font-size:.72rem;white-space:nowrap"><?= adm_data($c['criado_em'],'d/m/Y H:i') ?></td>
      <td>
        <div style="display:flex;gap:.3rem;flex-wrap:wrap">
          <?php if (!$c['aprovado']): ?>
            <button class="btn btn-sm btn-ghost" style="color:#4CAF50;border-color:rgba(46,125,50,.4)"
                    onclick="moderar(<?= $c['id'] ?>,'aprovar')" title="Publicar">
              <i class="fa fa-check"></i>
            </button>
          <?php else: ?>
            <button class="btn btn-sm btn-ghost"
                    onclick="moderar(<?= $c['id'] ?>,'desaprovar')" title="Ocultar">
              <i class="fa fa-eye-slash"></i>
            </button>
          <?php endif; ?>
          <button class="btn btn-sm btn-danger"
                  onclick="moderar(<?= $c['id'] ?>,'deletar',true)" title="Deletar">
            <i class="fa fa-trash"></i>
          </button>
          <?php if ($c['flagged']): ?>
            <button class="btn btn-sm btn-ghost" style="color:var(--ouro);border-color:var(--borda-2)"
                    onclick="verLog(<?= $c['id'] ?>,'<?= adm_esc(addslashes($c['autor_nome'])) ?>')" title="Ver log completo">
              <i class="fa fa-file-lines"></i>
            </button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPaginas > 1): ?>
  <div class="paginacao">
    <span class="pag-info">Página <?= $pagina ?> de <?= $totalPaginas ?> (<?= $total ?> registros)</span>
    <div class="pag-btns">
      <?php if ($pagina > 1): ?>
        <a href="?filtro=<?= $filtro ?>&p=<?= $pagina-1 ?>" class="pag-btn">‹ Anterior</a>
      <?php endif; ?>
      <?php for ($i=max(1,$pagina-2); $i<=min($totalPaginas,$pagina+2); $i++): ?>
        <a href="?filtro=<?= $filtro ?>&p=<?= $i ?>" class="pag-btn <?= $i===$pagina?'ativo':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($pagina < $totalPaginas): ?>
        <a href="?filtro=<?= $filtro ?>&p=<?= $pagina+1 ?>" class="pag-btn">Próxima ›</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<!-- Modal Log Completo -->
<div class="modal-overlay" id="modalLog">
  <div class="modal-box" style="max-width:680px;width:100%">
    <h2 class="modal-titulo"><i class="fa fa-file-lines"></i> Log completo — <span id="logAutor"></span></h2>
    <div id="logConteudo" style="font-size:.82rem;line-height:1.7;color:var(--texto-2)">
      <p style="color:var(--texto-3)">Carregando…</p>
    </div>
    <div class="modal-btns">
      <button class="btn btn-ghost" onclick="fecharModal('modalLog')">Fechar</button>
    </div>
  </div>
</div>

<script>
async function moderar(id, acao, confirmar = false) {
  const msgs = {
    aprovar: 'Publicar este comentário?',
    desaprovar: 'Ocultar este comentário?',
    deletar: 'Deletar permanentemente? Esta ação não pode ser desfeita.',
  };
  if (confirmar && !confirm(msgs[acao] || 'Confirmar?')) return;

  const fd = new FormData();
  fd.append('acao', acao);
  fd.append('id', id);

  try {
    const r = await fetch('comentarios.php', {method:'POST', body: new URLSearchParams(fd)});
    const d = await r.json();
    if (d.ok) {
      toast(d.mensagem || 'Feito!');
      if (acao === 'deletar') {
        document.getElementById('row-'+id)?.remove();
      } else {
        setTimeout(() => location.reload(), 900);
      }
    } else {
      toast(d.erro || 'Erro.', 'erro');
    }
  } catch {
    toast('Erro de conexão.', 'erro');
  }
}

async function verLog(comentarioId, autor) {
  document.getElementById('logAutor').textContent = autor;
  document.getElementById('logConteudo').innerHTML = '<p style="color:var(--texto-3)">Carregando…</p>';
  abrirModal('modalLog');

  try {
    const r = await fetch(`?acao=log&id=${comentarioId}`);
    const d = await r.json();
    if (!d.ok) { document.getElementById('logConteudo').innerHTML = '<p style="color:#e74c3c">'+d.erro+'</p>'; return; }
    const l = d.log;
    if (!l) { document.getElementById('logConteudo').innerHTML = '<p>Log não encontrado.</p>'; return; }

    document.getElementById('logConteudo').innerHTML = `
      <table style="width:100%;font-size:.78rem;border-collapse:collapse">
        <tr><td style="padding:.4rem 0;color:var(--texto-3);width:40%">Comentário ID</td><td>${l.comentario_id}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">Autor</td><td>${escHtml(l.usuario_nome||'?')} &lt;${escHtml(l.usuario_email||'?')}&gt;</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">IP (SHA256)</td><td style="font-family:monospace;font-size:.68rem">${escHtml(l.ip_hash||'?')}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">User-Agent</td><td style="font-size:.7rem;word-break:break-all">${escHtml(l.user_agent||'?')}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">Motivo flag</td><td style="color:#e74c3c">${escHtml(l.motivo_flag)}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">Palavras detectadas</td><td style="color:#e74c3c">${escHtml(l.palavras_detectadas||'—')}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">Post</td><td>${escHtml(l.referencia_slug||'?')}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">Data do evento</td><td>${escHtml(l.criado_em)}</td></tr>
        <tr><td style="padding:.4rem 0;color:var(--texto-3)">Situação</td><td><span class="badge ${l.acao_tomada==='removido'?'badge-vermelho':l.acao_tomada==='mantido'?'badge-verde':'badge-amarelo'}">${escHtml(l.acao_tomada)}</span></td></tr>
      </table>
      <hr style="border:none;border-top:1px solid var(--borda);margin:1rem 0">
      <p style="font-size:.7rem;color:var(--texto-3)">Texto íntegro:</p>
      <div style="background:rgba(192,57,43,.07);border:1px solid rgba(192,57,43,.2);border-radius:var(--raio);padding:.75rem;font-size:.82rem;white-space:pre-wrap;word-break:break-word">${escHtml(l.texto_original)}</div>
      <div style="display:flex;gap:.5rem;margin-top:1rem;flex-wrap:wrap">
        <button class="btn btn-ghost btn-sm" onclick="registrarRevisao(${l.comentario_id},'mantido')"><i class="fa fa-check"></i> Manter publicado</button>
        <button class="btn btn-danger btn-sm" onclick="registrarRevisao(${l.comentario_id},'removido')"><i class="fa fa-trash"></i> Remover comentário</button>
      </div>
    `;
  } catch {
    document.getElementById('logConteudo').innerHTML = '<p style="color:#e74c3c">Erro ao carregar log.</p>';
  }
}

async function registrarRevisao(id, acaoLog) {
  const fd = new FormData();
  fd.append('acao', 'marcar_revisado');
  fd.append('acao_log', acaoLog);
  fd.append('id', id);
  if (acaoLog === 'removido') {
    fd.append('acao', 'deletar');
    await moderar(id, 'deletar');
  } else {
    const r = await fetch('comentarios.php', {method:'POST', body: new URLSearchParams(fd)});
    const d = await r.json();
    if (d.ok) { toast('Revisão registrada.'); fecharModal('modalLog'); setTimeout(()=>location.reload(),1000); }
    else toast(d.erro||'Erro.','erro');
  }
}

function escHtml(s) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(String(s)));
  return d.innerHTML;
}
</script>

<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
