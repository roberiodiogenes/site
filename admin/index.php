<?php
$ADMIN_PAGE = 'dashboard';
require_once __DIR__ . '/_admin.php';

/* ── Estatísticas principais ── */
$sUsuarios  = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo=1")->fetchColumn();
$sAssinat   = $pdo->query("SELECT COUNT(*) FROM assinaturas WHERE status='ativa' AND expira_em>NOW()")->fetchColumn();
$sCompras   = $pdo->query("SELECT COUNT(*) FROM compras WHERE status='aprovada'")->fetchColumn();
$sComents   = $pdo->query("SELECT COUNT(*) FROM comentarios WHERE aprovado=0")->fetchColumn();

$receitaComprasMes = (float)$pdo->query(
    "SELECT COALESCE(SUM(preco_pago),0) FROM compras
     WHERE status='aprovada' AND MONTH(comprado_em)=MONTH(NOW()) AND YEAR(comprado_em)=YEAR(NOW())"
)->fetchColumn();

$receitaAssinMes = (float)$pdo->query(
    "SELECT COALESCE(SUM(p.preco),0) FROM assinaturas a
     JOIN planos p ON p.id=a.plano_id
     WHERE a.status='ativa' AND MONTH(a.inicio_em)=MONTH(NOW()) AND YEAR(a.inicio_em)=YEAR(NOW())"
)->fetchColumn();

$receitaMes = $receitaComprasMes + $receitaAssinMes;

/* ── Receita últimos 6 meses (para gráfico) ── */
$receita6m = $pdo->query(
    "SELECT DATE_FORMAT(comprado_em,'%Y-%m') AS mes,
            SUM(preco_pago) AS total
     FROM compras WHERE status='aprovada'
       AND comprado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mes ORDER BY mes ASC"
)->fetchAll();

/* ── Últimos 6 usuários ── */
$ultimosUsuarios = $pdo->query(
    "SELECT id,nome,email,created_at,
     (SELECT COUNT(*) FROM compras WHERE usuario_id=u.id AND status='aprovada') AS n_compras
     FROM usuarios u ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

/* ── Últimas 6 compras ── */
$ultimasCompras = $pdo->query(
    "SELECT c.id,u.nome,u.email,c.livro_slug,c.preco_pago,c.status,c.comprado_em,l.titulo
     FROM compras c
     JOIN usuarios u ON u.id=c.usuario_id
     LEFT JOIN livros l ON l.slug=c.livro_slug
     ORDER BY c.comprado_em DESC LIMIT 6"
)->fetchAll();

/* ── Assinaturas a vencer em 7 dias ── */
$aVencer = $pdo->query(
    "SELECT a.id,u.nome,u.email,p.nome AS plano,a.expira_em,
     DATEDIFF(a.expira_em,NOW()) AS dias_rest
     FROM assinaturas a
     JOIN usuarios u ON u.id=a.usuario_id
     JOIN planos   p ON p.id=a.plano_id
     WHERE a.status='ativa' AND a.expira_em > NOW()
       AND DATEDIFF(a.expira_em,NOW()) <= 7
     ORDER BY a.expira_em ASC LIMIT 8"
)->fetchAll();

/* ── Comentários pendentes ── */
$comentPend = $pdo->query(
    "SELECT c.id,c.referencia,c.tipo,c.texto,c.criado_em,
     COALESCE(u.nome,c.nome) AS autor
     FROM comentarios c
     LEFT JOIN usuarios u ON u.id=c.usuario_id
     WHERE c.aprovado=0
     ORDER BY c.criado_em DESC LIMIT 6"
)->fetchAll();

/* ── Livro mais vendido do mês ── */
$topLivro = $pdo->query(
    "SELECT l.titulo,COUNT(*) AS total
     FROM compras c JOIN livros l ON l.slug=c.livro_slug
     WHERE c.status='aprovada'
       AND MONTH(c.comprado_em)=MONTH(NOW()) AND YEAR(c.comprado_em)=YEAR(NOW())
     GROUP BY c.livro_slug ORDER BY total DESC LIMIT 1"
)->fetch();

$mesAtual = date('F Y');
?>

<div class="page-header">
  <h1 class="page-titulo">Dashboard</h1>
  <p class="page-sub"><?= date('l, d \d\e F \d\e Y') ?></p>
</div>

<!-- ── 4 CARDS PRINCIPAIS ── -->
<div class="stats-grade">
  <div class="stat-card">
    <i class="fa fa-users stat-icone"></i>
    <div><div class="stat-valor"><?= number_format((int)$sUsuarios) ?></div><div class="stat-label">Usuários ativos</div></div>
  </div>
  <div class="stat-card">
    <i class="fa fa-crown stat-icone"></i>
    <div><div class="stat-valor"><?= number_format((int)$sAssinat) ?></div><div class="stat-label">Assinantes ativos</div></div>
  </div>
  <div class="stat-card">
    <i class="fa fa-cart-shopping stat-icone"></i>
    <div><div class="stat-valor"><?= number_format((int)$sCompras) ?></div><div class="stat-label">Compras aprovadas</div></div>
  </div>
  <div class="stat-card">
    <i class="fa fa-dollar-sign stat-icone"></i>
    <div><div class="stat-valor">R$&nbsp;<?= number_format($receitaMes,0,',','.') ?></div><div class="stat-label">Receita este mês</div></div>
  </div>
</div>

<!-- ── ALERTAS ── -->
<?php if ((int)$sComents > 0): ?>
<div style="background:rgba(184,134,11,.08);border:1px solid var(--borda-2);border-radius:var(--raio-lg);padding:.7rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;font-size:.85rem">
  <i class="fa fa-comment-dots" style="color:var(--ouro)"></i>
  <span><?= $sComents ?> comentário<?= (int)$sComents>1?'s':'' ?> aguardando moderação.</span>
  <a href="blog.php?tipo=aguard" class="btn btn-sm btn-primario" style="margin-left:auto">Moderar</a>
</div>
<?php endif; ?>
<?php if (count($aVencer) > 0): ?>
<div style="background:rgba(183,28,28,.07);border:1px solid rgba(183,28,28,.25);border-radius:var(--raio-lg);padding:.7rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;font-size:.85rem">
  <i class="fa fa-triangle-exclamation" style="color:var(--vermelho)"></i>
  <span><?= count($aVencer) ?> assinatura<?= count($aVencer)>1?'s':'' ?> vencem em até 7 dias.</span>
  <a href="assinaturas.php?status=ativas" class="btn btn-sm btn-danger" style="margin-left:auto">Ver</a>
</div>
<?php endif; ?>
<?php if ($topLivro): ?>
<div style="background:var(--ouro-bg);border:1px solid var(--borda-2);border-radius:var(--raio-lg);padding:.7rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem;font-size:.85rem">
  <i class="fa fa-trophy" style="color:var(--ouro)"></i>
  <span>Livro mais vendido em <?= date('F') ?>: <strong><?= adm_esc($topLivro['titulo']) ?></strong> (<?= $topLivro['total'] ?> vendas)</span>
</div>
<?php endif; ?>

<!-- ── GRÁFICO DE RECEITA ── -->
<?php if ($receita6m): ?>
<div class="secao" style="margin-bottom:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-chart-line"></i> Receita — últimos 6 meses</span>
  </div>
  <div style="padding:1.25rem;display:flex;align-items:flex-end;gap:.5rem;height:110px">
    <?php
    // CORREÇÃO: Força o valor máximo a ser pelo menos 1 usando a função max() do PHP de forma estrita
    $maiorValorEncontrado = (float)max(array_column($receita6m, 'total'));
    $maxVal = $maiorValorEncontrado > 0 ? $maiorValorEncontrado : 1;

    foreach ($receita6m as $rm):
      $pct = round(((float)$rm['total'] / $maxVal) * 100);
      $mes = date('M', strtotime($rm['mes'].'-01'));
    ?>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:.3rem">
      <span style="font-size:.62rem;color:var(--ouro)">R$<?= number_format((float)$rm['total'],0,',','.') ?></span>
      <div style="width:100%;background:var(--ouro);border-radius:3px 3px 0 0;height:<?= max(4,$pct) ?>px;transition:height .3s"></div>
      <span style="font-size:.62rem;color:var(--texto-3)"><?= $mes ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="grade-2">

<!-- ── ÚLTIMOS USUÁRIOS ── -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-users"></i> Últimos usuários</span>
    <a href="usuarios.php" class="btn btn-ghost btn-sm">Ver todos</a>
  </div>
  <table>
    <thead><tr><th>Nome</th><th>Cadastro</th><th>Compras</th></tr></thead>
    <tbody>
    <?php foreach ($ultimosUsuarios as $u): ?>
    <tr>
      <td>
        <div class="td-nome"><?= adm_esc($u['nome']) ?></div>
        <div class="td-sub"><?= adm_esc($u['email']) ?></div>
      </td>
      <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($u['created_at']) ?></td>
      <td style="text-align:center"><?= $u['n_compras'] ?: '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ── ÚLTIMAS COMPRAS ── -->
<div class="secao">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-cart-shopping"></i> Últimas compras</span>
    <a href="compras.php" class="btn btn-ghost btn-sm">Ver todas</a>
  </div>
  <table>
    <thead><tr><th>Cliente</th><th>Livro</th><th>Valor</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($ultimasCompras as $c): ?>
    <tr>
      <td>
        <div class="td-nome"><?= adm_esc($c['nome']) ?></div>
        <div class="td-sub"><?= adm_esc($c['email']) ?></div>
      </td>
      <td style="font-size:.78rem"><?= adm_esc($c['titulo'] ?? $c['livro_slug']) ?></td>
      <td style="font-size:.78rem;white-space:nowrap">R$&nbsp;<?= number_format((float)$c['preco_pago'],2,',','.') ?></td>
      <td><?= adm_badge($c['status']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

</div><!-- /.grade-2 -->

<!-- ── ASSINATURAS A VENCER ── -->
<?php if ($aVencer): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-clock" style="color:var(--vermelho)"></i> Assinaturas vencendo em 7 dias</span>
    <a href="assinaturas.php?status=ativas" class="btn btn-ghost btn-sm">Ver todas</a>
  </div>
  <table>
    <thead><tr><th>Assinante</th><th>Plano</th><th>Vence em</th><th>Dias</th></tr></thead>
    <tbody>
    <?php foreach ($aVencer as $a): ?>
    <tr>
      <td><div class="td-nome"><?= adm_esc($a['nome']) ?></div><div class="td-sub"><?= adm_esc($a['email']) ?></div></td>
      <td><span class="badge badge-amarelo"><?= adm_esc($a['plano']) ?></span></td>
      <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($a['expira_em']) ?></td>
      <td><span class="badge badge-vermelho"><?= $a['dias_rest'] ?>d</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- ── COMENTÁRIOS PENDENTES ── -->
<?php if ($comentPend): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-comment-dots" style="color:var(--ouro)"></i> Comentários aguardando moderação</span>
    <a href="blog.php?tipo=aguard" class="btn btn-primario btn-sm">Moderar todos</a>
  </div>
  <table>
    <thead><tr><th>Autor</th><th>Onde</th><th>Trecho</th><th>Data</th><th>Ação</th></tr></thead>
    <tbody>
    <?php foreach ($comentPend as $c): ?>
    <tr>
      <td style="font-size:.82rem;font-weight:500;color:var(--texto)"><?= adm_esc($c['autor']) ?></td>
      <td>
        <span class="badge <?= $c['tipo']==='blog'?'badge-azul':'badge-amarelo' ?>">
          <?= $c['tipo']==='blog' ? 'Blog' : 'Livro' ?>
        </span>
        <div style="font-size:.68rem;color:var(--texto-3)"><?= adm_esc($c['referencia'] ?? '') ?></div>
      </td>
      <td style="font-size:.8rem;color:var(--texto-2);max-width:240px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">
        <?= adm_esc($c['texto']) ?>
      </td>
      <td style="font-size:.72rem;white-space:nowrap"><?= adm_data($c['criado_em'],'d/m H:i') ?></td>
      <td>
        <div style="display:flex;gap:.3rem">
          <button class="btn btn-sm btn-primario" onclick="aprovarDash(<?= $c['id'] ?>)" title="Aprovar"><i class="fa fa-check"></i></button>
          <button class="btn btn-sm btn-danger"   onclick="deletarDash(<?= $c['id'] ?>)" title="Deletar"><i class="fa fa-trash"></i></button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
async function aprovarDash(id) {
  const r = await fetch('blog.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=aprovar&id=${id}`});
  const d = await r.json();
  if (d.ok) { toast('Comentário aprovado!'); document.querySelector(`[data-cid="${id}"]`)?.remove(); setTimeout(()=>location.reload(),1200); }
  else toast(d.erro||'Erro.','erro');
}
async function deletarDash(id) {
  if (!confirm('Deletar este comentário?')) return;
  const r = await fetch('blog.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`acao=deletar&id=${id}`});
  const d = await r.json();
  if (d.ok) { toast('Comentário deletado.'); setTimeout(()=>location.reload(),1200); }
  else toast(d.erro||'Erro.','erro');
}
</script>
<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
