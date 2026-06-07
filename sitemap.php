<?php
/* ================================================================
   ROBÉRIO DIÓGENES — sitemap.php
   Sitemap XML dinâmico. Inclui:
     • Páginas estáticas
     • Posts do blog publicados (tabela posts)
     • Livros ativos (tabela livros)
     • Landing pages de gênero
   Acesso: https://roberiodiogenes.com/sitemap.xml (via .htaccess)
   ================================================================ */

require_once __DIR__ . '/backend/config.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex'); // o próprio sitemap não precisa ser indexado

$base = 'https://roberiodiogenes.com'; // sempre HTTPS em produção
$now  = date('Y-m-d');

/* ── Auxiliares ──────────────────────────────────────────────── */
function url(string $loc, string $lastmod, string $freq, float $prio): string {
    return "  <url>\n"
         . "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n"
         . "    <lastmod>{$lastmod}</lastmod>\n"
         . "    <changefreq>{$freq}</changefreq>\n"
         . "    <priority>" . number_format($prio, 1) . "</priority>\n"
         . "  </url>\n";
}

$urls = '';

/* ── Páginas estáticas ───────────────────────────────────────── */
$estaticas = [
    ['/',              'weekly',  1.0],
    ['/livros.html',   'weekly',  0.9],
    ['/blog.html',     'daily',   0.9],
    ['/autor.html',    'monthly', 0.7],
    ['/contato.html',  'monthly', 0.6],
    ['/bio.html',      'weekly',  0.6],
    ['/livros/suspense-psicologico-nacional.html', 'monthly', 0.8],
    ['/livros/terror-gotico-brasileiro.html',      'monthly', 0.8],
];
foreach ($estaticas as [$path, $freq, $prio]) {
    $urls .= url($base . $path, $now, $freq, $prio);
}

/* ── Páginas individuais de livros ───────────────────────────── */
$livrosSlags = [
    'lumen','a-setima-lei','o-abismo-das-almas','jogo-das-mascaras',
    'a-marca-da-besta','caminhos-de-outono','cartas-do-passado',
    'das-coisas-que-o-amor-faz','genesis','mares-secretas-do-amor',
    'rosas-e-espinhos','o-farol-do-afogado','o-quarto-das-moscas','linhas-e-agulhas',
];
// Tenta buscar do banco (inclui livros adicionados posteriormente)
try {
    $pdo = db();
    $stL = $pdo->query("SELECT slug, criado_em FROM livros WHERE ativo=1 ORDER BY id ASC");
    $livrosDB = $stL->fetchAll(PDO::FETCH_ASSOC);
    foreach ($livrosDB as $l) {
        $lastmod = $l['criado_em'] ? date('Y-m-d', strtotime($l['criado_em'])) : $now;
        $urls .= url("{$base}/livros/{$l['slug']}.html", $lastmod, 'monthly', 0.85);
    }
} catch (Throwable $e) {
    // Fallback estático
    foreach ($livrosSlags as $slug) {
        $urls .= url("{$base}/livros/{$slug}.html", $now, 'monthly', 0.85);
    }
}

/* ── Posts do blog publicados ────────────────────────────────── */
try {
    $pdo = $pdo ?? db();
    $stP = $pdo->query(
        "SELECT slug, publicado_em, updated_at
         FROM posts WHERE status='publicado'
         ORDER BY publicado_em DESC LIMIT 500"
    );
    $posts = $stP->fetchAll(PDO::FETCH_ASSOC);
    foreach ($posts as $p) {
        $lastmod = $p['updated_at'] ?: $p['publicado_em'];
        $lastmod = $lastmod ? date('Y-m-d', strtotime($lastmod)) : $now;
        // Posts antigos têm arquivo estático, novos usam post-template
        $slugsEstaticos = ['post-01','post-02','post-03','post-04','post-05','post-06','post-07'];
        if (in_array($p['slug'], $slugsEstaticos)) {
            $loc = "{$base}/blog/{$p['slug']}.html";
        } else {
            $loc = "{$base}/blog/post-template.html?slug=" . urlencode($p['slug']);
        }
        $urls .= url($loc, $lastmod, 'monthly', 0.75);
    }
} catch (Throwable $e) { /* posts não disponíveis */ }

/* ── Saída XML ───────────────────────────────────────────────── */
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
echo $urls;
echo '</urlset>';
