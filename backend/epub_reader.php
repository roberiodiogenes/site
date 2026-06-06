<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/epub_reader.php  v2.0
   Lê epub sem depender de ZipArchive (compatível com XAMPP padrão).
   Usa extração de ZIP em PHP puro via gzinflate().
   GET ?livro=slug&info=1   → JSON: total de partes, títulos
   GET ?livro=slug&parte=N  → JSON: HTML limpo da parte N (0-based)
   ================================================================ */

ob_start();
set_error_handler(fn() => true); // suprime notices/warnings

require_once __DIR__ . '/config.php';
ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=3600');

function jsonSair(array $d): void {
    ob_end_clean();
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ================================================================
   LEITOR ZIP PURO (sem ZipArchive)
   Lê o índice do arquivo ZIP e extrai entradas usando gzinflate().
   Compatível com qualquer PHP 5.6+.
   ================================================================ */
function zipLerIndice(string $path): array {
    $fh  = fopen($path, 'rb');
    $tam = filesize($path);

    // Encontra o End of Central Directory (EOCD) — últimos ~22 bytes
    // Busca de trás para frente pela assinatura 0x06054b50
    $eocd_offset = -1;
    for ($i = $tam - 22; $i >= max(0, $tam - 65558); $i--) {
        fseek($fh, $i);
        if (fread($fh, 4) === "\x50\x4b\x05\x06") {
            $eocd_offset = $i;
            break;
        }
    }
    if ($eocd_offset < 0) { fclose($fh); return []; }

    fseek($fh, $eocd_offset + 12);
    $cd_size   = unpack('V', fread($fh, 4))[1];
    $cd_offset = unpack('V', fread($fh, 4))[1];

    // Lê o Central Directory
    fseek($fh, $cd_offset);
    $entradas = [];

    while (ftell($fh) < $cd_offset + $cd_size) {
        $sig = fread($fh, 4);
        if ($sig !== "\x50\x4b\x01\x02") break;

        fseek($fh, ftell($fh) + 6);  // skip versões e flags
        $method    = unpack('v', fread($fh, 2))[1];
        fseek($fh, ftell($fh) + 4);  // skip mod time/date
        $crc       = fread($fh, 4);
        $comp_size = unpack('V', fread($fh, 4))[1];
        $orig_size = unpack('V', fread($fh, 4))[1];
        $name_len  = unpack('v', fread($fh, 2))[1];
        $extra_len = unpack('v', fread($fh, 2))[1];
        $comm_len  = unpack('v', fread($fh, 2))[1];
        fseek($fh, ftell($fh) + 8);  // disk, attrs
        $local_off = unpack('V', fread($fh, 4))[1];
        $name      = fread($fh, $name_len);
        fseek($fh, ftell($fh) + $extra_len + $comm_len);

        $entradas[$name] = [
            'method'    => $method,
            'comp_size' => $comp_size,
            'orig_size' => $orig_size,
            'local_off' => $local_off,
        ];
    }
    fclose($fh);
    return $entradas;
}

function zipExtrairEntrada(string $path, array $info): string|false {
    $fh = fopen($path, 'rb');
    fseek($fh, $info['local_off']);

    // Lê o cabeçalho local
    $sig = fread($fh, 4);
    if ($sig !== "\x50\x4b\x03\x04") { fclose($fh); return false; }
    fseek($fh, ftell($fh) + 22);  // skip até name_len
    $name_len  = unpack('v', fread($fh, 2))[1];
    $extra_len = unpack('v', fread($fh, 2))[1];
    fseek($fh, ftell($fh) + $name_len + $extra_len);

    $dados = fread($fh, $info['comp_size']);
    fclose($fh);

    if ($info['method'] === 0) {
        return $dados; // stored
    }
    if ($info['method'] === 8) {
        $result = @gzinflate($dados);
        return $result !== false ? $result : false;
    }
    return false; // método não suportado
}

/* ── Wrapper compatível: usa ZipArchive se disponível, senão usa puro PHP ── */
function zipAbrir(string $epubPath): array|false {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($epubPath) !== true) return false;
        return ['tipo' => 'zip_ext', 'zip' => $zip, 'path' => $epubPath];
    }
    $idx = zipLerIndice($epubPath);
    if (empty($idx)) return false;
    return ['tipo' => 'zip_puro', 'idx' => $idx, 'path' => $epubPath];
}

function zipLer(array &$ctx, string $name): string|false {
    if ($ctx['tipo'] === 'zip_ext') {
        $r = $ctx['zip']->getFromName($name);
        return $r !== false ? $r : false;
    }
    // zip puro
    if (!isset($ctx['idx'][$name])) return false;
    return zipExtrairEntrada($ctx['path'], $ctx['idx'][$name]);
}

function zipFechar(array &$ctx): void {
    if ($ctx['tipo'] === 'zip_ext') $ctx['zip']->close();
}

/* ================================================================
   LÓGICA PRINCIPAL
   ================================================================ */
try {
    iniciarSessao();

    $slug = trim($_GET['livro'] ?? '');
    if (!$slug) jsonSair(['ok'=>false,'erro'=>'Parâmetro livro não informado.']);

    $pdo = db();
    $stL = $pdo->prepare(
        "SELECT id, COALESCE(gratuito,0) AS gratuito, pasta_conteudo, arquivo_epub
         FROM livros WHERE slug=? AND ativo=1 LIMIT 1"
    );
    $stL->execute([$slug]);
    $livro = $stL->fetch();
    if (!$livro) jsonSair(['ok'=>false,'erro'=>'Livro não encontrado.']);

    $uid = (int)($_SESSION['usuario_id'] ?? 0);
    if (!$livro['gratuito']) {
        if (!$uid) jsonSair(['ok'=>false,'erro'=>'Não autenticado.','motivo'=>'nao_logado']);
        $stC = $pdo->prepare("SELECT 1 FROM compras WHERE usuario_id=? AND livro_slug=? AND status='aprovada' LIMIT 1");
        $stC->execute([$uid, $slug]);
        if (!$stC->fetch()) {
            $stA = $pdo->prepare("SELECT 1 FROM assinaturas WHERE usuario_id=? AND status='ativa' AND expira_em>NOW() LIMIT 1");
            $stA->execute([$uid]);
            if (!$stA->fetch()) jsonSair(['ok'=>false,'erro'=>'Sem acesso.','motivo'=>'sem_acesso']);
        }
    }

    $pasta    = rtrim($livro['pasta_conteudo'] ?: "livros-conteudo/{$slug}", '/');
    $epubFile = $livro['arquivo_epub'] ?: "{$slug}.epub";
    $epubPath = __DIR__ . "/../{$pasta}/{$epubFile}";

    if (!file_exists($epubPath)) {
        jsonSair(['ok'=>false,'erro'=>"Epub não encontrado: {$pasta}/{$epubFile}"]);
    }

    $ctx = zipAbrir($epubPath);
    if (!$ctx) jsonSair(['ok'=>false,'erro'=>'Não foi possível abrir o epub. Arquivo corrompido ou formato inválido.']);

    // Lê o container.xml
    $container = zipLer($ctx, 'META-INF/container.xml');
    if (!$container) { zipFechar($ctx); jsonSair(['ok'=>false,'erro'=>'container.xml não encontrado.']); }

    preg_match('/full-path="([^"]+)"/', $container, $m);
    $opfPath = $m[1] ?? 'content.opf';
    $opfDir  = dirname($opfPath);
    if ($opfDir === '.') $opfDir = '';

    $opf = zipLer($ctx, $opfPath);
    if (!$opf) { zipFechar($ctx); jsonSair(['ok'=>false,'erro'=>'content.opf não encontrado.']); }

    // Manifest: id → href
    $manifest = [];
    preg_match_all('/<item\s[^>]*\bid="([^"]+)"[^>]*\bhref="([^"]+)"[^>]*\bmedia-type="application\/xhtml[^"]*"/i', $opf, $mItems, PREG_SET_ORDER);
    if (empty($mItems)) {
        preg_match_all('/<item\s[^>]*\bid="([^"]+)"[^>]*\bhref="([^"]+\.(x?html))"[^>]*/i', $opf, $mItems, PREG_SET_ORDER);
    }
    foreach ($mItems as $item) $manifest[$item[1]] = $item[2];

    // Spine
    preg_match_all('/<itemref\s[^>]*\bidref="([^"]+)"/i', $opf, $mSpine, PREG_SET_ORDER);
    $spine = [];
    foreach ($mSpine as $s) {
        $idref = $s[1];
        if (!isset($manifest[$idref])) continue;
        $href  = $manifest[$idref];
        $full  = $opfDir ? ltrim("{$opfDir}/{$href}", '/') : $href;
        $spine[] = ['href'=>$full,'id'=>$idref];
    }
    if (empty($spine)) { zipFechar($ctx); jsonSair(['ok'=>false,'erro'=>'Spine vazia.','manifest'=>count($manifest)]); }

    /* ── Endpoint: info ── */
    if (!empty($_GET['info'])) {
        $titulos = [];
        preg_match_all('/<item\s[^>]*media-type="application\/x-dtbncx[^"]*"[^>]*\bhref="([^"]+)"/i', $opf, $ncxM);
        if (!empty($ncxM[1][0])) {
            $ncxHref = $opfDir ? "{$opfDir}/{$ncxM[1][0]}" : $ncxM[1][0];
            $ncx = zipLer($ctx, $ncxHref);
            if ($ncx) {
                preg_match_all('/<navPoint[^>]*>.*?<navLabel>\s*<text>([^<]+)<\/text>.*?<content[^>]+src="([^"#]+)/si', $ncx, $navM, PREG_SET_ORDER);
                foreach ($navM as $nav) $titulos[] = ['titulo'=>trim($nav[1]),'href'=>basename($nav[2])];
            }
        }
        zipFechar($ctx);
        jsonSair(['ok'=>true,'total'=>count($spine),'titulos'=>$titulos,'slug'=>$slug]);
    }

    /* ── Endpoint: conteúdo de uma parte ── */
    $parte = max(0, (int)($_GET['parte'] ?? 0));
    if ($parte >= count($spine)) {
        zipFechar($ctx);
        jsonSair(['ok'=>false,'erro'=>'Parte fora do intervalo.','total'=>count($spine)]);
    }

    $rawHtml = zipLer($ctx, $spine[$parte]['href']);
    zipFechar($ctx);
    if ($rawHtml === false) jsonSair(['ok'=>false,'erro'=>"Não encontrado: {$spine[$parte]['href']}"]);

    /* ── Limpa o HTML ── */
    $html = preg_replace('/^<\?xml[^>]*\?>\s*/i', '',  $rawHtml) ?? $rawHtml;
    $html = preg_replace('/<head\b[^>]*>.*?<\/head>/si', '', $html) ?? $html;
    $html = preg_replace('/<\/?(html|body)\b[^>]*>/i',   '', $html) ?? $html;
    $html = preg_replace('/<div[^>]*class="frame_[^"]*"[^>]*>.*?<\/div>/si', '', $html) ?? $html;
    $html = preg_replace('/\s+class="[^"]*"/',  '', $html) ?? $html;
    $html = preg_replace('/\s+style="[^"]*"/',  '', $html) ?? $html;
    $html = preg_replace('/\s+xmlns[^=]*="[^"]*"/', '', $html) ?? $html;
    $html = preg_replace('/\s+xml:[a-z]+="[^"]*"/', '', $html) ?? $html;
    $html = preg_replace('/<p(\s[^>]*)?>/',     '<p>', $html) ?? $html;
    $html = preg_replace('/\n{3,}/', "\n\n", trim($html)) ?? $html;

    jsonSair(['ok'=>true,'parte'=>$parte,'total'=>count($spine),'html'=>$html,'href'=>$spine[$parte]['href']]);

} catch (Throwable $e) {
    $extra = ob_get_clean();
    echo json_encode(['ok'=>false,'erro'=>$e->getMessage(),
        'file'=>basename($e->getFile()).':'.$e->getLine(),
        '_extra'=>$extra?substr($extra,0,300):null], JSON_UNESCAPED_UNICODE);
    exit;
}
