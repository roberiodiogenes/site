<?php
$ADMIN_PAGE = 'dashboard';
require_once __DIR__ . '/_admin.php';

/* ═══════════════════════════════════════════════════════════════════
   ABA 1 — VISÃO GERAL (queries existentes)
   ══════════════════════════════════════════════════════════════════ */
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

$receita6m = $pdo->query(
    "SELECT DATE_FORMAT(comprado_em,'%Y-%m') AS mes, SUM(preco_pago) AS total
     FROM compras WHERE status='aprovada' AND comprado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mes ORDER BY mes ASC"
)->fetchAll();

$ultimosUsuarios = $pdo->query(
    "SELECT id,nome,email,created_at,
     (SELECT COUNT(*) FROM compras WHERE usuario_id=u.id AND status='aprovada') AS n_compras
     FROM usuarios u ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$ultimasCompras = $pdo->query(
    "SELECT c.id,u.nome,u.email,c.livro_slug,c.preco_pago,c.status,c.comprado_em,l.titulo
     FROM compras c JOIN usuarios u ON u.id=c.usuario_id
     LEFT JOIN livros l ON l.slug=c.livro_slug
     ORDER BY c.comprado_em DESC LIMIT 6"
)->fetchAll();

$aVencer = $pdo->query(
    "SELECT a.id,u.nome,u.email,p.nome AS plano,a.expira_em,DATEDIFF(a.expira_em,NOW()) AS dias_rest
     FROM assinaturas a JOIN usuarios u ON u.id=a.usuario_id JOIN planos p ON p.id=a.plano_id
     WHERE a.status='ativa' AND a.expira_em > NOW() AND DATEDIFF(a.expira_em,NOW()) <= 7
     ORDER BY a.expira_em ASC LIMIT 8"
)->fetchAll();

$comentPend = $pdo->query(
    "SELECT c.id,c.referencia,c.tipo,c.texto,c.criado_em,COALESCE(u.nome,c.nome) AS autor
     FROM comentarios c LEFT JOIN usuarios u ON u.id=c.usuario_id
     WHERE c.aprovado=0 ORDER BY c.criado_em DESC LIMIT 6"
)->fetchAll();

$topLivro = $pdo->query(
    "SELECT l.titulo,COUNT(*) AS total FROM compras c JOIN livros l ON l.slug=c.livro_slug
     WHERE c.status='aprovada' AND MONTH(c.comprado_em)=MONTH(NOW()) AND YEAR(c.comprado_em)=YEAR(NOW())
     GROUP BY c.livro_slug ORDER BY total DESC LIMIT 1"
)->fetch();

/* ═══════════════════════════════════════════════════════════════════
   ABA 2 — TRÁFEGO
   ══════════════════════════════════════════════════════════════════ */
$totalVisitas = 0;
try { $totalVisitas = (int)$pdo->query("SELECT total FROM visitas WHERE id=1")->fetchColumn(); } catch (Throwable $e) {}

$sessoesHoje = 0;
try { $sessoesHoje = (int)$pdo->query("SELECT COUNT(*) FROM analytics_sessoes WHERE DATE(iniciada_em)=CURDATE()")->fetchColumn(); } catch (Throwable $e) {}

$sessoesSemana = 0;
try { $sessoesSemana = (int)$pdo->query("SELECT COUNT(*) FROM analytics_sessoes WHERE iniciada_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(); } catch (Throwable $e) {}

$visitasDiarias = [];
try {
    $visitasDiarias = $pdo->query(
        "SELECT visit_date AS dia, COUNT(*) AS total FROM visitas_log
         WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         GROUP BY visit_date ORDER BY visit_date ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$sessoesDiarias = [];
try {
    $sessoesDiarias = $pdo->query(
        "SELECT DATE(iniciada_em) AS dia, COUNT(*) AS total
         FROM analytics_sessoes WHERE iniciada_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY dia ORDER BY dia ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$dispositivos = [];
try {
    $dispositivos = $pdo->query(
        "SELECT dispositivo, COUNT(*) AS total FROM analytics_sessoes GROUP BY dispositivo ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$referrers = [];
try {
    $referrers = $pdo->query(
        "SELECT
            CASE
                WHEN referrer IS NULL OR referrer='' THEN 'Direto'
                WHEN referrer LIKE '%google%' THEN 'Google'
                WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                WHEN referrer LIKE '%facebook%' OR referrer LIKE '%fb.com%' THEN 'Facebook'
                WHEN referrer LIKE '%twitter%' OR referrer LIKE '%t.co%' THEN 'Twitter/X'
                WHEN referrer LIKE '%roberiodiogenes%' THEN 'Interno'
                ELSE 'Outros'
            END AS origem,
            COUNT(*) AS total
         FROM analytics_sessoes GROUP BY origem ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$utmCampanhas = [];
try {
    $utmCampanhas = $pdo->query(
        "SELECT utm_source, COALESCE(utm_campaign,'—') AS utm_campaign, COUNT(*) AS sessoes
         FROM analytics_sessoes WHERE utm_source IS NOT NULL AND utm_source != ''
         GROUP BY utm_source, utm_campaign ORDER BY sessoes DESC LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$topLandingPages = [];
try {
    $topLandingPages = $pdo->query(
        "SELECT landing_page, COUNT(*) AS sessoes FROM analytics_sessoes
         WHERE landing_page IS NOT NULL AND landing_page != ''
         GROUP BY landing_page ORDER BY sessoes DESC LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* ═══════════════════════════════════════════════════════════════════
   ABA 3 — CONTEÚDO
   ══════════════════════════════════════════════════════════════════ */
$emailCliques = [];
$totalEmailCliques = 0;
try {
    $emailCliques = $pdo->query(
        "SELECT acao, COUNT(*) AS total FROM email_cliques GROUP BY acao ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $totalEmailCliques = array_sum(array_column($emailCliques, 'total'));
} catch (Throwable $e) {}

$emailCliquesDiarios = [];
try {
    $emailCliquesDiarios = $pdo->query(
        "SELECT DATE(clicado_em) AS dia, COUNT(*) AS total FROM email_cliques
         WHERE clicado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY dia ORDER BY dia ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$pdfCliques = [];
$totalPdfCliques = 0;
try {
    $pdfCliques = $pdo->query(
        "SELECT pdf_nome, COUNT(*) AS total FROM pdf_cliques GROUP BY pdf_nome ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    $totalPdfCliques = array_sum(array_column($pdfCliques, 'total'));
} catch (Throwable $e) {}

$downloadsPorLivro = [];
$totalDownloads = 0;
try {
    $downloadsPorLivro = $pdo->query(
        "SELECT livro_slug, COUNT(*) AS total, COUNT(DISTINCT usuario_id) AS usuarios_unicos
         FROM downloads_log GROUP BY livro_slug ORDER BY total DESC LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);
    $totalDownloads = (int)$pdo->query("SELECT COUNT(*) FROM downloads_log")->fetchColumn();
} catch (Throwable $e) {}

$downloadsPorFormato = [];
try {
    $downloadsPorFormato = $pdo->query(
        "SELECT formato, COUNT(*) AS total FROM downloads_log GROUP BY formato"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$topPaginas = [];
try {
    $topPaginas = $pdo->query(
        "SELECT conteudo_slug, COUNT(DISTINCT session_id) AS visitas,
                ROUND(AVG(tempo_permanencia)) AS tempo_medio_seg
         FROM analytics_eventos WHERE tipo_evento='ViewContent' AND conteudo_slug IS NOT NULL
         GROUP BY conteudo_slug ORDER BY visitas DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* ═══════════════════════════════════════════════════════════════════
   ABA 4 — USUÁRIOS
   ══════════════════════════════════════════════════════════════════ */
$cadastrosPorMes = [];
try {
    $cadastrosPorMes = $pdo->query(
        "SELECT DATE_FORMAT(created_at,'%Y-%m') AS mes, COUNT(*) AS total
         FROM usuarios GROUP BY mes ORDER BY mes ASC LIMIT 12"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$usuariosAtivos30d = 0;
try {
    $usuariosAtivos30d = (int)$pdo->query(
        "SELECT COUNT(DISTINCT usuario_id) FROM analytics_sessoes
         WHERE usuario_id IS NOT NULL AND iniciada_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    )->fetchColumn();
} catch (Throwable $e) {}

$tempoMedioPagina = 0;
try {
    $tempoMedioPagina = (float)$pdo->query(
        "SELECT AVG(tempo_permanencia) FROM analytics_eventos
         WHERE tempo_permanencia > 0 AND tipo_evento IN ('ViewContent','Tempo_Pagina')"
    )->fetchColumn();
} catch (Throwable $e) {}

$eventosTipo = [];
try {
    $eventosTipo = $pdo->query(
        "SELECT tipo_evento, COUNT(*) AS total FROM analytics_eventos GROUP BY tipo_evento ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$canalRetencao = [];
try {
    $canalRetencao = $pdo->query("SELECT * FROM bi_canal_retencao LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$conteudoEngajamento = [];
try {
    $conteudoEngajamento = $pdo->query("SELECT * FROM bi_conteudo_engajamento LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* ── Montar payload JS ── */
function _dias30(): array {
    $dias = [];
    for ($i = 29; $i >= 0; $i--) {
        $dias[] = date('Y-m-d', strtotime("-{$i} days"));
    }
    return $dias;
}
function _indexByKey(array $rows, string $key, string $val = 'total'): array {
    $out = [];
    foreach ($rows as $r) $out[$r[$key]] = $r[$val];
    return $out;
}

$diasLabels = _dias30();
$visitasIdx  = _indexByKey($visitasDiarias,  'dia');
$sessoesIdx  = _indexByKey($sessoesDiarias,  'dia');

$ANALYTICS = [
    'visitasDias'    => array_map(fn($d) => ['dia' => date('d/m', strtotime($d)), 'total' => (int)($visitasIdx[$d] ?? 0)], $diasLabels),
    'sessoesDias'    => array_map(fn($d) => (int)($sessoesIdx[$d] ?? 0), $diasLabels),
    'dispositivos'   => array_map(fn($r) => ['label' => ucfirst($r['dispositivo']), 'total' => (int)$r['total']], $dispositivos),
    'referrers'      => array_map(fn($r) => ['origem' => $r['origem'], 'total' => (int)$r['total']], $referrers),
    'emailCliques'   => array_map(fn($r) => ['acao' => $r['acao'], 'total' => (int)$r['total']], $emailCliques),
    'emailDias'      => (function() use ($emailCliquesDiarios, $diasLabels) {
        $idx = _indexByKey($emailCliquesDiarios, 'dia');
        return array_map(fn($d) => (int)($idx[$d] ?? 0), $diasLabels);
    })(),
    'cadastrosMes'   => array_map(fn($r) => ['mes' => $r['mes'], 'total' => (int)$r['total']], $cadastrosPorMes),
    'eventosTipo'    => array_map(fn($r) => ['tipo' => $r['tipo_evento'], 'total' => (int)$r['total']], $eventosTipo),
    'diasLabels'     => array_map(fn($d) => date('d/m', strtotime($d)), $diasLabels),
];
?>

<style>
/* ── TABS ── */
.tab-nav { display:flex; gap:0; border-bottom:1px solid var(--borda); margin-bottom:1.5rem; overflow-x:auto; }
.tab-btn {
  padding:.65rem 1.25rem; background:none; border:none; color:var(--texto-3);
  font-size:.78rem; font-weight:600; letter-spacing:.06em; text-transform:uppercase;
  cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px;
  white-space:nowrap; transition:all .15s;
}
.tab-btn:hover  { color:var(--ouro); }
.tab-btn.ativo  { color:var(--ouro); border-bottom-color:var(--ouro); }
.tab-panel { display:none; }
.tab-panel.ativo { display:block; }

/* ── CHARTS ── */
.chart-wrap { position:relative; padding:1.25rem; }
.chart-wrap canvas { max-height:220px; }
.chart-wrap-tall canvas { max-height:280px; }
.empty-chart { text-align:center; padding:2.5rem 1rem; color:var(--texto-3); font-size:.82rem; }
.empty-chart i { display:block; font-size:1.8rem; color:var(--ouro); opacity:.2; margin-bottom:.5rem; }

/* ── MÉTRICAS KPI ── */
.kpi-grade { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:1.25rem; }
.kpi { background:var(--fundo-card); border:1px solid var(--borda); border-radius:var(--raio-lg);
       padding:.9rem 1.1rem; }
.kpi-val { font-size:1.55rem; font-weight:700; color:var(--texto); line-height:1; }
.kpi-lbl { font-size:.62rem; color:var(--texto-3); text-transform:uppercase; letter-spacing:.08em; margin-top:.2rem; }
.kpi-sub { font-size:.68rem; color:var(--ouro); margin-top:.15rem; }

/* ── TABELAS ANALYTICS ── */
.t-analytics { width:100%; border-collapse:collapse; font-size:.8rem; }
.t-analytics th { font-size:.6rem; text-transform:uppercase; letter-spacing:.08em; color:var(--texto-3);
                  padding:.5rem 1rem; border-bottom:1px solid var(--borda); text-align:left; }
.t-analytics td { padding:.55rem 1rem; color:var(--texto-2); border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; }
.t-analytics tr:last-child td { border-bottom:none; }
.t-analytics tr:hover td { background:rgba(184,134,11,.04); }
.bar-inline { display:inline-block; background:var(--ouro); opacity:.4; border-radius:2px; height:6px; vertical-align:middle; margin-left:.4rem; }
</style>

<div class="page-header">
  <h1 class="page-titulo">Dashboard</h1>
  <p class="page-sub"><?= date('l, d \d\e F \d\e Y') ?></p>
</div>

<!-- ── NAV ABAS ── -->
<div class="tab-nav" role="tablist">
  <button class="tab-btn ativo" data-tab="visao-geral"  role="tab" onclick="ativarTab('visao-geral')"><i class="fa fa-gauge" style="margin-right:.4rem"></i>Visão Geral</button>
  <button class="tab-btn"       data-tab="trafego"      role="tab" onclick="ativarTab('trafego')"><i class="fa fa-chart-line" style="margin-right:.4rem"></i>Tráfego</button>
  <button class="tab-btn"       data-tab="conteudo"     role="tab" onclick="ativarTab('conteudo')"><i class="fa fa-file-arrow-down" style="margin-right:.4rem"></i>Conteúdo</button>
  <button class="tab-btn"       data-tab="usuarios-tab" role="tab" onclick="ativarTab('usuarios-tab')"><i class="fa fa-users" style="margin-right:.4rem"></i>Usuários</button>
</div>

<!-- ══════════════════════════════════════════════════════════════
     ABA 1 — VISÃO GERAL
     ══════════════════════════════════════════════════════════════ -->
<div id="tab-visao-geral" class="tab-panel ativo">

<div class="stats-grade">
  <div class="stat-card"><i class="fa fa-users stat-icone"></i><div><div class="stat-valor"><?= number_format((int)$sUsuarios) ?></div><div class="stat-label">Usuários ativos</div></div></div>
  <div class="stat-card"><i class="fa fa-crown stat-icone"></i><div><div class="stat-valor"><?= number_format((int)$sAssinat) ?></div><div class="stat-label">Assinantes ativos</div></div></div>
  <div class="stat-card"><i class="fa fa-cart-shopping stat-icone"></i><div><div class="stat-valor"><?= number_format((int)$sCompras) ?></div><div class="stat-label">Compras aprovadas</div></div></div>
  <div class="stat-card"><i class="fa fa-dollar-sign stat-icone"></i><div><div class="stat-valor">R$&nbsp;<?= number_format($receitaMes,0,',','.') ?></div><div class="stat-label">Receita este mês</div></div></div>
</div>

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

<?php if ($receita6m): ?>
<div class="secao" style="margin-bottom:1.25rem">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-chart-line"></i> Receita — últimos 6 meses</span></div>
  <div style="padding:1.25rem;display:flex;align-items:flex-end;gap:.5rem;height:110px">
    <?php
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
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-users"></i> Últimos usuários</span><a href="usuarios.php" class="btn btn-ghost btn-sm">Ver todos</a></div>
  <table><thead><tr><th>Nome</th><th>Cadastro</th><th>Compras</th></tr></thead><tbody>
  <?php foreach ($ultimosUsuarios as $u): ?>
  <tr><td><div class="td-nome"><?= adm_esc($u['nome']) ?></div><div class="td-sub"><?= adm_esc($u['email']) ?></div></td>
  <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($u['created_at']) ?></td>
  <td style="text-align:center"><?= $u['n_compras'] ?: '—' ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-cart-shopping"></i> Últimas compras</span><a href="compras.php" class="btn btn-ghost btn-sm">Ver todas</a></div>
  <table><thead><tr><th>Cliente</th><th>Livro</th><th>Valor</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($ultimasCompras as $c): ?>
  <tr><td><div class="td-nome"><?= adm_esc($c['nome']) ?></div><div class="td-sub"><?= adm_esc($c['email']) ?></div></td>
  <td style="font-size:.78rem"><?= adm_esc($c['titulo'] ?? $c['livro_slug']) ?></td>
  <td style="font-size:.78rem;white-space:nowrap">R$&nbsp;<?= number_format((float)$c['preco_pago'],2,',','.') ?></td>
  <td><?= adm_badge($c['status']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
</div>

<?php if ($aVencer): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-clock" style="color:var(--vermelho)"></i> Assinaturas vencendo em 7 dias</span><a href="assinaturas.php?status=ativas" class="btn btn-ghost btn-sm">Ver todas</a></div>
  <table><thead><tr><th>Assinante</th><th>Plano</th><th>Vence em</th><th>Dias</th></tr></thead><tbody>
  <?php foreach ($aVencer as $a): ?>
  <tr><td><div class="td-nome"><?= adm_esc($a['nome']) ?></div><div class="td-sub"><?= adm_esc($a['email']) ?></div></td>
  <td><span class="badge badge-amarelo"><?= adm_esc($a['plano']) ?></span></td>
  <td style="font-size:.75rem;white-space:nowrap"><?= adm_data($a['expira_em']) ?></td>
  <td><span class="badge badge-vermelho"><?= $a['dias_rest'] ?>d</span></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>

<?php if ($comentPend): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-comment-dots" style="color:var(--ouro)"></i> Comentários aguardando moderação</span><a href="blog.php?tipo=aguard" class="btn btn-primario btn-sm">Moderar todos</a></div>
  <table><thead><tr><th>Autor</th><th>Onde</th><th>Trecho</th><th>Data</th><th>Ação</th></tr></thead><tbody>
  <?php foreach ($comentPend as $c): ?>
  <tr>
    <td style="font-size:.82rem;font-weight:500;color:var(--texto)"><?= adm_esc($c['autor']) ?></td>
    <td><span class="badge <?= $c['tipo']==='blog'?'badge-azul':'badge-amarelo' ?>"><?= $c['tipo']==='blog'?'Blog':'Livro' ?></span><div style="font-size:.68rem;color:var(--texto-3)"><?= adm_esc($c['referencia'] ?? '') ?></div></td>
    <td style="font-size:.8rem;color:var(--texto-2);max-width:240px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"><?= adm_esc($c['texto']) ?></td>
    <td style="font-size:.72rem;white-space:nowrap"><?= adm_data($c['criado_em'],'d/m H:i') ?></td>
    <td><div style="display:flex;gap:.3rem">
      <button class="btn btn-sm btn-primario" onclick="aprovarDash(<?= $c['id'] ?>)" title="Aprovar"><i class="fa fa-check"></i></button>
      <button class="btn btn-sm btn-danger"   onclick="deletarDash(<?= $c['id'] ?>)" title="Deletar"><i class="fa fa-trash"></i></button>
    </div></td>
  </tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>

</div><!-- /tab-visao-geral -->


<!-- ══════════════════════════════════════════════════════════════
     ABA 2 — TRÁFEGO
     ══════════════════════════════════════════════════════════════ -->
<div id="tab-trafego" class="tab-panel">

<div class="kpi-grade">
  <div class="kpi"><div class="kpi-val"><?= number_format($totalVisitas) ?></div><div class="kpi-lbl">Visitantes únicos (total)</div></div>
  <div class="kpi"><div class="kpi-val"><?= number_format($sessoesHoje) ?></div><div class="kpi-lbl">Sessões hoje</div></div>
  <div class="kpi"><div class="kpi-val"><?= number_format($sessoesSemana) ?></div><div class="kpi-lbl">Sessões últimos 7 dias</div></div>
  <div class="kpi"><div class="kpi-val"><?= count($visitasDiarias) > 0 ? number_format(round(array_sum(array_column($visitasDiarias,'total')) / max(1,count($visitasDiarias)),1)) : '—' ?></div><div class="kpi-lbl">Média diária (30d)</div></div>
</div>

<div class="grade-2">

<!-- Visitantes únicos por dia -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-chart-area"></i> Visitantes únicos — 30 dias</span></div>
  <?php if (!empty($visitasDiarias)): ?>
  <div class="chart-wrap chart-wrap-tall"><canvas id="chartVisitas"></canvas></div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-chart-area"></i><p>Nenhuma visita registrada ainda.</p></div>
  <?php endif; ?>
</div>

<!-- Dispositivos -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-mobile-screen"></i> Dispositivos</span></div>
  <?php if (!empty($dispositivos)): ?>
  <div class="chart-wrap" style="display:flex;align-items:center;justify-content:center;gap:1.5rem;flex-wrap:wrap;min-height:180px">
    <canvas id="chartDispositivos" style="max-width:160px;max-height:160px"></canvas>
    <div id="legendDispositivos" style="font-size:.78rem;display:flex;flex-direction:column;gap:.5rem"></div>
  </div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-mobile-screen"></i><p>Dados de sessão não disponíveis ainda.</p></div>
  <?php endif; ?>
</div>

</div><!-- /grade-2 -->

<div class="grade-2" style="margin-top:1.25rem">

<!-- Origem do tráfego -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-route"></i> Origem do tráfego</span></div>
  <?php if (!empty($referrers)): ?>
  <div class="chart-wrap"><canvas id="chartReferrers"></canvas></div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-route"></i><p>Dados de sessão não disponíveis ainda.</p></div>
  <?php endif; ?>
</div>

<!-- Campanhas UTM -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-tags"></i> Campanhas UTM</span></div>
  <?php if (!empty($utmCampanhas)): ?>
  <table class="t-analytics">
    <thead><tr><th>Fonte</th><th>Campanha</th><th style="text-align:right">Sessões</th></tr></thead>
    <tbody>
    <?php $maxUtm = max(array_column($utmCampanhas,'sessoes')); foreach ($utmCampanhas as $u): ?>
    <tr>
      <td><span class="badge badge-azul"><?= adm_esc($u['utm_source']) ?></span></td>
      <td style="color:var(--texto-3)"><?= adm_esc($u['utm_campaign']) ?></td>
      <td style="text-align:right">
        <?= number_format($u['sessoes']) ?>
        <span class="bar-inline" style="width:<?= round(($u['sessoes']/$maxUtm)*60) ?>px"></span>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-tags"></i><p>Nenhuma campanha UTM registrada ainda.</p></div>
  <?php endif; ?>
</div>

</div><!-- /grade-2 -->

<!-- Top páginas de entrada -->
<?php if (!empty($topLandingPages)): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-door-open"></i> Páginas de entrada mais acessadas</span></div>
  <?php $maxLP = max(array_column($topLandingPages,'sessoes')); ?>
  <table class="t-analytics">
    <thead><tr><th>URL de entrada</th><th style="text-align:right">Sessões</th></tr></thead>
    <tbody>
    <?php foreach ($topLandingPages as $lp): $url = preg_replace('#https?://[^/]+#', '', $lp['landing_page']); ?>
    <tr>
      <td style="max-width:380px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= adm_esc($lp['landing_page']) ?>"><?= adm_esc($url ?: '/') ?></td>
      <td style="text-align:right"><?= number_format($lp['sessoes']) ?><span class="bar-inline" style="width:<?= round(($lp['sessoes']/$maxLP)*60) ?>px"></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

</div><!-- /tab-trafego -->


<!-- ══════════════════════════════════════════════════════════════
     ABA 3 — CONTEÚDO
     ══════════════════════════════════════════════════════════════ -->
<div id="tab-conteudo" class="tab-panel">

<div class="kpi-grade">
  <div class="kpi"><div class="kpi-val"><?= number_format($totalDownloads) ?></div><div class="kpi-lbl">Downloads totais</div></div>
  <div class="kpi"><div class="kpi-val"><?= number_format($totalEmailCliques) ?></div><div class="kpi-lbl">Cliques em e-mail</div></div>
  <div class="kpi"><div class="kpi-val"><?= number_format($totalPdfCliques) ?></div><div class="kpi-lbl">Cliques no PDF</div></div>
  <div class="kpi"><div class="kpi-val"><?= count($topPaginas) ?></div><div class="kpi-lbl">Páginas rastreadas</div></div>
</div>

<div class="grade-2">

<!-- Cliques de e-mail por ação -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-envelope-open-text"></i> Cliques de e-mail por ação</span></div>
  <?php if (!empty($emailCliques)): ?>
  <div class="chart-wrap" style="display:flex;align-items:center;justify-content:center;gap:1.5rem;flex-wrap:wrap;min-height:180px">
    <canvas id="chartEmailCliques" style="max-width:160px;max-height:160px"></canvas>
    <div id="legendEmail" style="font-size:.78rem;display:flex;flex-direction:column;gap:.5rem"></div>
  </div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-envelope-open-text"></i><p>Nenhum clique de e-mail registrado ainda.</p></div>
  <?php endif; ?>
</div>

<!-- Cliques de e-mail diários -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-chart-line"></i> Engajamento de e-mail — 30 dias</span></div>
  <?php $emailTemDados = array_sum(array_column($emailCliquesDiarios,'total')) > 0 || !empty($emailCliques); ?>
  <?php if ($emailTemDados): ?>
  <div class="chart-wrap chart-wrap-tall"><canvas id="chartEmailDiario"></canvas></div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-chart-line"></i><p>Sem dados de clique ainda.</p></div>
  <?php endif; ?>
</div>

</div><!-- /grade-2 -->

<!-- Cliques no PDF -->
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-file-pdf"></i> Cliques no PDF por origem</span></div>
  <?php if (!empty($pdfCliques)): ?>
  <table class="t-analytics">
    <thead><tr><th>Arquivo / Origem</th><th style="text-align:right">Cliques</th></tr></thead>
    <?php $maxPdf = max(array_column($pdfCliques,'total')); ?>
    <tbody>
    <?php foreach ($pdfCliques as $pc): ?>
    <tr>
      <td><i class="fa fa-file-pdf" style="color:var(--ouro);margin-right:.4rem;opacity:.5"></i><?= adm_esc($pc['pdf_nome']) ?></td>
      <td style="text-align:right"><?= number_format($pc['total']) ?><span class="bar-inline" style="width:<?= round(($pc['total']/$maxPdf)*60) ?>px"></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty-chart">
    <i class="fa fa-file-pdf"></i>
    <p>Nenhum clique no PDF ainda.<br>
    <span style="font-size:.72rem">Certifique-se de criar a tabela <code>pdf_cliques</code> no phpMyAdmin.</span></p>
  </div>
  <?php endif; ?>
</div>

<!-- Downloads por livro -->
<div class="grade-2" style="margin-top:1.25rem">
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-download"></i> Downloads por obra</span></div>
  <?php if (!empty($downloadsPorLivro)): ?>
  <table class="t-analytics">
    <thead><tr><th>Livro</th><th>Formato(s)</th><th style="text-align:right">Downloads</th><th style="text-align:right">Leitores únicos</th></tr></thead>
    <?php $maxDl = max(array_column($downloadsPorLivro,'total')); ?>
    <tbody>
    <?php foreach ($downloadsPorLivro as $dl): ?>
    <tr>
      <td><?= adm_esc($dl['livro_slug']) ?></td>
      <td><span class="badge badge-azul">pdf/epub</span></td>
      <td style="text-align:right"><?= number_format($dl['total']) ?><span class="bar-inline" style="width:<?= round(($dl['total']/$maxDl)*50) ?>px"></span></td>
      <td style="text-align:right"><?= number_format($dl['usuarios_unicos']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-download"></i><p>Nenhum download registrado ainda.</p></div>
  <?php endif; ?>
</div>

<!-- Top páginas (analytics_eventos) -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-eye"></i> Páginas mais visitadas</span></div>
  <?php if (!empty($topPaginas)): ?>
  <table class="t-analytics">
    <thead><tr><th>Página</th><th style="text-align:right">Visitas</th><th style="text-align:right">Tempo médio</th></tr></thead>
    <?php $maxPg = max(array_column($topPaginas,'visitas')); ?>
    <tbody>
    <?php foreach ($topPaginas as $pg): $seg = (int)$pg['tempo_medio_seg']; ?>
    <tr>
      <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= adm_esc($pg['conteudo_slug']) ?></td>
      <td style="text-align:right"><?= number_format($pg['visitas']) ?><span class="bar-inline" style="width:<?= round(($pg['visitas']/$maxPg)*40) ?>px"></span></td>
      <td style="text-align:right;color:var(--texto-3)"><?= $seg > 0 ? ($seg >= 60 ? floor($seg/60).'min '.($seg%60).'s' : $seg.'s') : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-eye"></i><p>Dados de visualização não disponíveis ainda.</p></div>
  <?php endif; ?>
</div>
</div>

</div><!-- /tab-conteudo -->


<!-- ══════════════════════════════════════════════════════════════
     ABA 4 — USUÁRIOS
     ══════════════════════════════════════════════════════════════ -->
<div id="tab-usuarios-tab" class="tab-panel">

<div class="kpi-grade">
  <div class="kpi"><div class="kpi-val"><?= number_format((int)$sUsuarios) ?></div><div class="kpi-lbl">Cadastros totais</div><div class="kpi-sub">usuários ativos</div></div>
  <div class="kpi"><div class="kpi-val"><?= number_format($usuariosAtivos30d) ?></div><div class="kpi-lbl">Ativos últimos 30 dias</div><div class="kpi-sub">com sessão registrada</div></div>
  <div class="kpi">
    <div class="kpi-val"><?= $tempoMedioPagina > 0 ? ($tempoMedioPagina >= 60 ? floor($tempoMedioPagina/60).'m '.round(fmod($tempoMedioPagina,60)).'s' : round($tempoMedioPagina).'s') : '—' ?></div>
    <div class="kpi-lbl">Tempo médio na página</div>
  </div>
  <div class="kpi"><div class="kpi-val"><?= count($eventosTipo) ?></div><div class="kpi-lbl">Tipos de eventos rastreados</div></div>
</div>

<div class="grade-2">

<!-- Cadastros por mês -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-user-plus"></i> Novos cadastros por mês</span></div>
  <?php if (!empty($cadastrosPorMes)): ?>
  <div class="chart-wrap chart-wrap-tall"><canvas id="chartCadastros"></canvas></div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-user-plus"></i><p>Nenhum cadastro ainda.</p></div>
  <?php endif; ?>
</div>

<!-- Tipos de eventos -->
<div class="secao">
  <div class="secao-header"><span class="secao-titulo"><i class="fa fa-circle-dot"></i> Eventos comportamentais</span></div>
  <?php if (!empty($eventosTipo)): ?>
  <div class="chart-wrap" style="display:flex;align-items:center;justify-content:center;gap:1.5rem;flex-wrap:wrap;min-height:200px">
    <canvas id="chartEventos" style="max-width:160px;max-height:160px"></canvas>
    <div id="legendEventos" style="font-size:.78rem;display:flex;flex-direction:column;gap:.5rem"></div>
  </div>
  <?php else: ?>
  <div class="empty-chart"><i class="fa fa-circle-dot"></i><p>Nenhum evento rastreado ainda.</p></div>
  <?php endif; ?>
</div>

</div><!-- /grade-2 -->

<!-- Retenção por canal (bi_canal_retencao) -->
<?php if (!empty($canalRetencao)): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-funnel-dollar"></i> Retenção por canal — últimos 90 dias</span>
    <span style="font-size:.65rem;color:var(--texto-3)">Qual canal traz leitores que ficam mais tempo?</span>
  </div>
  <div style="overflow-x:auto">
  <table class="t-analytics">
    <thead><tr><th>Canal</th><th>Meio</th><th style="text-align:right">Sessões</th><th style="text-align:right">Leitores ativos</th><th style="text-align:right">Tempo médio/pág</th><th style="text-align:right">% Leitura médio</th></tr></thead>
    <tbody>
    <?php foreach ($canalRetencao as $cr): ?>
    <tr>
      <td><span class="badge badge-amarelo"><?= adm_esc($cr['canal']) ?></span></td>
      <td style="color:var(--texto-3)"><?= adm_esc($cr['meio'] ?? '(none)') ?></td>
      <td style="text-align:right"><?= number_format((int)$cr['total_sessoes']) ?></td>
      <td style="text-align:right"><?= number_format((int)($cr['leitores_ativos'] ?? 0)) ?></td>
      <td style="text-align:right">
        <?php $s = (int)($cr['media_segundos_pagina'] ?? 0); echo $s > 0 ? ($s >= 60 ? floor($s/60).'m '.($s%60).'s' : $s.'s') : '—'; ?>
      </td>
      <td style="text-align:right"><?= $cr['media_percentual_leitura'] ? round($cr['media_percentual_leitura']).'%' : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<!-- Conteúdo com mais engajamento (bi_conteudo_engajamento) -->
<?php if (!empty($conteudoEngajamento)): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="secao-header">
    <span class="secao-titulo"><i class="fa fa-book-open"></i> Conteúdo com mais engajamento — 90 dias</span>
    <span style="font-size:.65rem;color:var(--texto-3)">Qual livro/post retém mais o leitor?</span>
  </div>
  <div style="overflow-x:auto">
  <table class="t-analytics">
    <thead><tr><th>Conteúdo</th><th style="text-align:right">Visualizações</th><th style="text-align:right">Tempo médio</th><th style="text-align:right">Downloads</th><th style="text-align:right">Leads</th></tr></thead>
    <tbody>
    <?php foreach ($conteudoEngajamento as $ce): ?>
    <tr>
      <td>
        <div style="font-size:.82rem;color:var(--texto)"><?= adm_esc($ce['titulo'] ?? $ce['conteudo_slug']) ?></div>
        <div style="font-size:.68rem;color:var(--texto-3)"><?= adm_esc($ce['conteudo_slug']) ?></div>
      </td>
      <td style="text-align:right"><?= number_format((int)$ce['total_visualizacoes']) ?></td>
      <td style="text-align:right">
        <?php $s = (int)($ce['media_segundos'] ?? 0); echo $s > 0 ? ($s >= 60 ? floor($s/60).'m '.($s%60).'s' : $s.'s') : '—'; ?>
      </td>
      <td style="text-align:right"><?= number_format((int)($ce['downloads_amostra'] ?? 0)) ?></td>
      <td style="text-align:right"><?= number_format((int)($ce['leads_gerados'] ?? 0)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<!-- Estado vazio geral para usuários -->
<?php if (empty($canalRetencao) && empty($conteudoEngajamento) && empty($eventosTipo) && empty($cadastrosPorMes)): ?>
<div class="secao" style="margin-top:1.25rem">
  <div class="empty-chart" style="padding:3rem">
    <i class="fa fa-chart-bar"></i>
    <p>Os dados de comportamento aparecerão aqui assim que o <code>js/tracking.js</code> registrar as primeiras sessões.</p>
  </div>
</div>
<?php endif; ?>

</div><!-- /tab-usuarios-tab -->


<!-- ══════════════════════════════════════════════════════════════
     SCRIPTS
     ══════════════════════════════════════════════════════════════ -->
<script>
/* Dados do dashboard (gerado pelo PHP) */
var ANALYTICS = <?= json_encode($ANALYTICS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

/* ── Tab switching ── */
var _chartsInicializados = {};
function ativarTab(slug) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('ativo', b.dataset.tab === slug));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('ativo', p.id === 'tab-' + slug));
    history.replaceState(null, '', '#' + slug);
    if (!_chartsInicializados[slug]) {
        _chartsInicializados[slug] = true;
        if (typeof Chart !== 'undefined') initCharts(slug);
        else {
            /* Chart.js ainda carregando — aguarda */
            var inter = setInterval(function() {
                if (typeof Chart !== 'undefined') { clearInterval(inter); initCharts(slug); }
            }, 80);
        }
    }
}

/* Restaura aba pelo hash da URL */
(function() {
    var hash = location.hash.replace('#','');
    var validos = ['visao-geral','trafego','conteudo','usuarios-tab'];
    if (validos.includes(hash)) ativarTab(hash);
})();

/* ── Dashboard existente ── */
async function aprovarDash(id) {
    var r = await fetch('blog.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'acao=aprovar&id='+id});
    var d = await r.json();
    if (d.ok) { toast('Comentário aprovado!'); setTimeout(()=>location.reload(),1200); }
    else toast(d.erro||'Erro.','erro');
}
async function deletarDash(id) {
    if (!confirm('Deletar este comentário?')) return;
    var r = await fetch('blog.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'acao=deletar&id='+id});
    var d = await r.json();
    if (d.ok) { toast('Comentário deletado.'); setTimeout(()=>location.reload(),1200); }
    else toast(d.erro||'Erro.','erro');
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" crossorigin="anonymous" defer></script>
<script>
/* Executa após Chart.js carregar (defer) */
document.currentScript.addEventListener('load', function(){});
window.addEventListener('load', function() {
    /* Inicializa a aba ativa no carregamento */
    var abaAtiva = document.querySelector('.tab-btn.ativo');
    if (abaAtiva && !_chartsInicializados[abaAtiva.dataset.tab]) {
        _chartsInicializados[abaAtiva.dataset.tab] = true;
        initCharts(abaAtiva.dataset.tab);
    }
});

/* Paleta de cores do painel */
var OURO       = '#B8860B';
var VERDE      = '#4CAF50';
var AZUL       = '#42A5F5';
var VERMELHO   = '#e74c3c';
var ROXO       = '#AB47BC';
var LARANJA    = '#FF8F00';
var CIANO      = '#00ACC1';
var CINZA      = '#78909C';
var CORES_MULTI = [OURO, AZUL, VERDE, VERMELHO, ROXO, LARANJA, CIANO, CINZA];

Chart.defaults.color          = '#8C7D65';
Chart.defaults.borderColor    = 'rgba(184,134,11,0.12)';
Chart.defaults.font.family    = "'Segoe UI', system-ui, sans-serif";
Chart.defaults.font.size      = 11;

function mkLegend(containerId, labels, colors) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = labels.map((l,i) =>
        '<div style="display:flex;align-items:center;gap:.4rem">' +
        '<span style="width:10px;height:10px;border-radius:50%;background:'+colors[i%colors.length]+';flex-shrink:0"></span>' +
        '<span style="color:#C8B898">'+l+'</span></div>'
    ).join('');
}

function initCharts(slug) {
    if (slug === 'trafego') initChartsTrafego();
    else if (slug === 'conteudo') initChartsConteudo();
    else if (slug === 'usuarios-tab') initChartsUsuarios();
}

/* ──────────────────────────────────────────────────────────────
   TRÁFEGO
   ────────────────────────────────────────────────────────────── */
function initChartsTrafego() {
    /* Visitantes únicos por dia */
    var cvVisitas = document.getElementById('chartVisitas');
    if (cvVisitas && ANALYTICS.visitasDias.length) {
        new Chart(cvVisitas, {
            type: 'line',
            data: {
                labels: ANALYTICS.visitasDias.map(d => d.dia),
                datasets: [{
                    label: 'Visitantes únicos',
                    data: ANALYTICS.visitasDias.map(d => d.total),
                    borderColor: OURO,
                    backgroundColor: 'rgba(184,134,11,0.08)',
                    borderWidth: 2,
                    pointRadius: 2,
                    fill: true,
                    tension: 0.3
                }, {
                    label: 'Sessões',
                    data: ANALYTICS.sessoesDias,
                    borderColor: AZUL,
                    backgroundColor: 'rgba(66,165,245,0.06)',
                    borderWidth: 1.5,
                    pointRadius: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { position: 'top', labels: { boxWidth: 10, padding: 10 } } },
                scales: {
                    x: { grid: { color:'rgba(255,255,255,.04)' }, ticks: { maxTicksLimit: 8 } },
                    y: { grid: { color:'rgba(255,255,255,.04)' }, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    /* Dispositivos */
    var cvDisp = document.getElementById('chartDispositivos');
    if (cvDisp && ANALYTICS.dispositivos.length) {
        var labels = ANALYTICS.dispositivos.map(d => d.label);
        var vals   = ANALYTICS.dispositivos.map(d => d.total);
        var cores  = [OURO, AZUL, VERDE, CIANO];
        new Chart(cvDisp, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: vals, backgroundColor: cores, borderWidth: 0 }] },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                cutout: '60%'
            }
        });
        mkLegend('legendDispositivos', labels.map((l,i) => l + ' — ' + vals[i]), cores);
    }

    /* Origem do tráfego (bar horizontal) */
    var cvRef = document.getElementById('chartReferrers');
    if (cvRef && ANALYTICS.referrers.length) {
        new Chart(cvRef, {
            type: 'bar',
            data: {
                labels: ANALYTICS.referrers.map(r => r.origem),
                datasets: [{ label: 'Sessões', data: ANALYTICS.referrers.map(r => r.total),
                    backgroundColor: ANALYTICS.referrers.map((_,i) => CORES_MULTI[i%CORES_MULTI.length]+'BB'),
                    borderRadius: 4, borderWidth: 0 }]
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { color:'rgba(255,255,255,.04)' }, beginAtZero: true, ticks: { precision: 0 } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
}

/* ──────────────────────────────────────────────────────────────
   CONTEÚDO
   ────────────────────────────────────────────────────────────── */
function initChartsConteudo() {
    /* Cliques de e-mail (doughnut) */
    var cvEmail = document.getElementById('chartEmailCliques');
    if (cvEmail && ANALYTICS.emailCliques.length) {
        var nomeAcao = {'baixar_conto':'Baixar conto','visitar_biblioteca':'Visitar biblioteca','clicar_pdf':'Clicar no PDF'};
        var labels = ANALYTICS.emailCliques.map(e => nomeAcao[e.acao] || e.acao);
        var vals   = ANALYTICS.emailCliques.map(e => e.total);
        var cores  = [OURO, AZUL, VERDE, VERMELHO];
        new Chart(cvEmail, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: vals, backgroundColor: cores, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, cutout: '60%' }
        });
        mkLegend('legendEmail', labels.map((l,i) => l+' — '+vals[i]), cores);
    }

    /* Engajamento de e-mail por dia (linha) */
    var cvEmailDia = document.getElementById('chartEmailDiario');
    if (cvEmailDia) {
        new Chart(cvEmailDia, {
            type: 'bar',
            data: {
                labels: ANALYTICS.diasLabels,
                datasets: [{
                    label: 'Cliques de e-mail',
                    data: ANALYTICS.emailDias,
                    backgroundColor: 'rgba(184,134,11,0.55)',
                    borderRadius: 3,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } },
                    y: { grid: { color:'rgba(255,255,255,.04)' }, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
}

/* ──────────────────────────────────────────────────────────────
   USUÁRIOS
   ────────────────────────────────────────────────────────────── */
function initChartsUsuarios() {
    /* Cadastros por mês */
    var cvCad = document.getElementById('chartCadastros');
    if (cvCad && ANALYTICS.cadastrosMes.length) {
        new Chart(cvCad, {
            type: 'bar',
            data: {
                labels: ANALYTICS.cadastrosMes.map(m => m.mes),
                datasets: [{
                    label: 'Cadastros',
                    data: ANALYTICS.cadastrosMes.map(m => m.total),
                    backgroundColor: 'rgba(184,134,11,0.65)',
                    borderRadius: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { grid: { color:'rgba(255,255,255,.04)' }, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    /* Tipos de eventos (doughnut) */
    var cvEv = document.getElementById('chartEventos');
    if (cvEv && ANALYTICS.eventosTipo.length) {
        var labels = ANALYTICS.eventosTipo.map(e => e.tipo);
        var vals   = ANALYTICS.eventosTipo.map(e => e.total);
        new Chart(cvEv, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: vals, backgroundColor: CORES_MULTI, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, cutout: '55%' }
        });
        mkLegend('legendEventos', labels.map((l,i) => l+' ('+vals[i]+')'), CORES_MULTI);
    }
}
</script>

<?= $ADMIN_FOOTER_HTML ?>
</main></body></html>
