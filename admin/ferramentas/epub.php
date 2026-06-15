<?php
/* ================================================================
   ROBÉRIO DIÓGENES — admin/ferramentas/epub.php
   Gerador de EPUB integrado ao painel administrativo.
   Não requer extensão ZipArchive — usa ZIP puro em PHP.
   ================================================================ */

// ═══════════════════════════════════════════════════════════════
//  CLASSE ZIP PURO (sem ZipArchive)
// ═══════════════════════════════════════════════════════════════
class ZipWriter
{
    private array  $entries    = [];
    private string $centralDir = '';
    private int    $offset     = 0;

    public function add(string $name, string $data, bool $store = false): void
    {
        $crc  = crc32($data);
        $size = strlen($data);

        if ($store || $size === 0) {
            $comp = $data; $cSize = $size; $method = 0;
        } else {
            $comp = gzdeflate($data, 9); $cSize = strlen($comp); $method = 8;
        }

        $time = $this->dosTime();

        $lhdr = "\x50\x4b\x03\x04"
            . pack('v', 20) . pack('v', 0) . pack('v', $method)
            . pack('V', $time) . pack('V', $crc)
            . pack('V', $cSize) . pack('V', $size)
            . pack('v', strlen($name)) . pack('v', 0) . $name;

        $this->entries[] = $lhdr . $comp;

        $this->centralDir .= "\x50\x4b\x01\x02"
            . pack('v', 20) . pack('v', 20) . pack('v', 0) . pack('v', $method)
            . pack('V', $time) . pack('V', $crc)
            . pack('V', $cSize) . pack('V', $size)
            . pack('v', strlen($name)) . pack('v', 0) . pack('v', 0)
            . pack('v', 0) . pack('v', 0) . pack('V', 0)
            . pack('V', $this->offset) . $name;

        $this->offset += strlen($lhdr) + $cSize;
    }

    public function bytes(): string
    {
        $body   = implode('', $this->entries);
        $cdSize = strlen($this->centralDir);
        $n      = count($this->entries);

        return $body . $this->centralDir
            . "\x50\x4b\x05\x06"
            . pack('v', 0) . pack('v', 0)
            . pack('v', $n) . pack('v', $n)
            . pack('V', $cdSize) . pack('V', $this->offset)
            . pack('v', 0);
    }

    private function dosTime(): int
    {
        $t = getdate();
        return (($t['year'] - 1980) << 25) | ($t['mon'] << 21) | ($t['mday'] << 16)
             | ($t['hours'] << 11) | ($t['minutes'] << 5) | ($t['seconds'] >> 1);
    }
}

// ═══════════════════════════════════════════════════════════════
//  MARKDOWN → XHTML
// ═══════════════════════════════════════════════════════════════
function epub_inlineFmt(string $t): string
{
    $t = htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $t = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $t);
    $t = preg_replace('/\*\*(.+?)\*\*/',     '<strong>$1</strong>',          $t);
    $t = preg_replace('/\*(.+?)\*/',         '<em>$1</em>',                  $t);
    return $t;
}

function epub_mdToXhtml(string $md): string
{
    $lines = explode("\n", $md);
    $html  = '';
    $inBq  = false;
    $phase = 0; // 0=aguarda H1 | 1=pula subtítulo | 2=pula primeiro --- | 3=inclui

    foreach ($lines as $line) {
        $t = trim($line);

        if ($phase < 3) {
            if ($phase === 0) { if (preg_match('/^#\s+/', $t)) $phase = 1; continue; }
            if ($phase === 1) { if ($t === '') continue; $phase = 2; continue; }
            if ($phase === 2) {
                if ($t === '') continue;
                if ($t === '---') { $phase = 3; continue; }
                $phase = 3; // sem ---, inclui já
            }
        }

        if ($inBq && !preg_match('/^>\s?/', $line)) {
            $html .= "</blockquote>\n"; $inBq = false;
        }
        if ($t === '') {
            if ($inBq) { $html .= "</blockquote>\n"; $inBq = false; }
            continue;
        }
        if (preg_match('/^-{3,}$/', $t)) { $html .= "<hr/>\n"; continue; }
        if (preg_match('/^(#{1,4})\s+(.+)/', $line, $m)) {
            if ($inBq) { $html .= "</blockquote>\n"; $inBq = false; }
            $lvl = strlen($m[1]);
            $html .= "<h{$lvl}>" . epub_inlineFmt($m[2]) . "</h{$lvl}>\n";
            continue;
        }
        if (preg_match('/^>\s?(.*)/', $line, $m)) {
            if (!$inBq) { $html .= "<blockquote>\n"; $inBq = true; }
            $inner = trim($m[1]);
            if ($inner !== '') $html .= '<p>' . epub_inlineFmt($inner) . "</p>\n";
            continue;
        }
        $html .= '<p>' . epub_inlineFmt($line) . "</p>\n";
    }
    if ($inBq) $html .= "</blockquote>\n";
    return $html;
}

function epub_parseMeta(string $md): array
{
    $titulo = ''; $genero = '';
    foreach (explode("\n", $md) as $line) {
        $t = trim($line);
        if (!$titulo && preg_match('/^#\s+(.+)/', $t, $m)) $titulo = trim($m[1]);
        if (!$genero && preg_match('/\*\*Gênero:\s*(.+?)\*\*/', $t, $m)) $genero = trim($m[1]);
        if ($titulo && $genero) break;
    }
    return compact('titulo', 'genero');
}

function epub_uuid(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

const EPUB_CSS_CONTENT = <<<'CSS'
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Georgia,'Palatino Linotype',Palatino,'Book Antiqua',serif;font-size:1em;line-height:1.85;color:#1c1c1c;background-color:#FAFAF6;}
.cover-page{margin:0;padding:0;text-align:center;background:#000;}
.cover-page img{width:100%;display:block;max-height:100vh;object-fit:contain;}
.title-page{text-align:center;padding:4em 2.5em 3em;min-height:85vh;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.title-page .rotulo{font-size:.68em;letter-spacing:.22em;text-transform:uppercase;color:#999;margin-bottom:2em;}
.title-page h1{font-size:2em;font-weight:normal;line-height:1.25;color:#1c1c1c;margin:0 0 1.2em;}
.title-page .linha{width:36px;height:1px;background:#bbb;margin:0 auto 1.8em;}
.title-page .autor{font-size:.88em;font-style:italic;color:#666;}
.content{padding:2.2em 2em 4em;}
p{text-align:justify;-webkit-hyphens:auto;hyphens:auto;margin-bottom:0;text-indent:1.6em;}
h1+p,h2+p,h3+p,h4+p,hr+p,blockquote+p,.content>p:first-child{text-indent:0;}
h1{font-size:1.6em;font-weight:normal;color:#1c1c1c;margin:1.6em 0 .8em;text-align:center;text-indent:0;line-height:1.3;}
h2{font-size:1.05em;font-weight:normal;font-style:italic;color:#444;margin:2.2em 0 .9em;text-align:center;text-indent:0;}
h3,h4{font-size:1em;font-weight:bold;color:#333;margin:1.5em 0 .6em;text-indent:0;}
hr{border:none;border-top:1px solid #d8d4cc;margin:2.2em auto;width:35%;display:block;}
blockquote{margin:1.8em 1.8em;padding-left:1em;border-left:2px solid #d8d4cc;color:#555;font-style:italic;}
blockquote p{text-indent:0;margin-bottom:.4em;}
blockquote p:last-child{margin-bottom:0;}
em{font-style:italic;}strong{font-weight:bold;}
CSS;

function epub_montar(array $p): string
{
    $lingua = 'pt-BR';
    $uid    = 'urn:uuid:' . epub_uuid();
    $data   = date('Y-m-d');

    $tX = htmlspecialchars($p['titulo'], ENT_XML1, 'UTF-8');
    $gX = htmlspecialchars($p['genero'], ENT_XML1, 'UTF-8');
    $aX = htmlspecialchars($p['autor'],  ENT_XML1, 'UTF-8');
    $dX = htmlspecialchars($p['desc'],   ENT_XML1, 'UTF-8');
    $eX = htmlspecialchars($p['editor'], ENT_XML1, 'UTF-8');

    $conteudo  = epub_mdToXhtml($p['md_text']);
    $capaBytes = $p['capa_bytes'] ?? null;
    $capaExt   = 'jpg';
    $capaMime  = 'image/jpeg';

    // Converte imagem → JPEG (Kindle não suporta WebP)
    if ($capaBytes && function_exists('imagecreatefromstring')) {
        $gd = @imagecreatefromstring($capaBytes);
        if ($gd !== false) {
            ob_start(); imagejpeg($gd, null, 92); $capaBytes = ob_get_clean();
            imagedestroy($gd);
        } else {
            $capaExt  = strtolower($p['capa_ext'] ?? 'jpg');
            $capaMime = match($capaExt) { 'png' => 'image/png', 'jpg','jpeg' => 'image/jpeg', default => 'image/webp' };
        }
    } elseif ($capaBytes) {
        $capaExt  = strtolower($p['capa_ext'] ?? 'jpg');
        $capaMime = match($capaExt) { 'png' => 'image/png', 'jpg','jpeg' => 'image/jpeg', default => 'image/webp' };
    }

    $zip = new ZipWriter();
    $zip->add('mimetype', 'application/epub+zip', true); // STORED + PRIMEIRO

    $zip->add('META-INF/container.xml', '<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/package.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>');

    $zip->add('OEBPS/style.css', EPUB_CSS_CONTENT);

    if ($capaBytes) $zip->add("OEBPS/img/cover.{$capaExt}", $capaBytes);

    $coverBody = $capaBytes
        ? "<div class=\"cover-page\"><img src=\"img/cover.{$capaExt}\" alt=\"Capa\"/></div>"
        : "<div class=\"title-page\" style=\"min-height:100vh;\"><h1>{$tX}</h1><p class=\"autor\">{$aX}</p></div>";

    $zip->add('OEBPS/cover.xhtml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE html>
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lingua}\">
<head><meta charset=\"UTF-8\"/><title>Capa</title><link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\"/></head>
<body>{$coverBody}</body></html>");

    $zip->add('OEBPS/title.xhtml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE html>
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lingua}\">
<head><meta charset=\"UTF-8\"/><title>{$tX}</title><link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\"/></head>
<body><div class=\"title-page\">
  <p class=\"rotulo\">{$gX}</p>
  <h1>{$tX}</h1>
  <div class=\"linha\"></div>
  <p class=\"autor\">{$aX}</p>
</div></body></html>");

    $zip->add('OEBPS/content.xhtml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE html>
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"{$lingua}\">
<head><meta charset=\"UTF-8\"/><title>{$tX}</title><link rel=\"stylesheet\" type=\"text/css\" href=\"style.css\"/></head>
<body><div class=\"content\">\n{$conteudo}\n</div></body></html>");

    $zip->add('OEBPS/nav.xhtml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE html>
<html xmlns=\"http://www.w3.org/1999/xhtml\" xmlns:epub=\"http://www.idpf.org/2007/ops\" xml:lang=\"{$lingua}\">
<head><meta charset=\"UTF-8\"/><title>{$tX}</title></head>
<body><nav epub:type=\"toc\" id=\"toc\"><ol>
  <li><a href=\"cover.xhtml\">Capa</a></li>
  <li><a href=\"title.xhtml\">{$tX}</a></li>
  <li><a href=\"content.xhtml\">Conto</a></li>
</ol></nav></body></html>");

    $zip->add('OEBPS/toc.ncx', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!DOCTYPE ncx PUBLIC \"-//NISO//DTD ncx 2005-1//EN\" \"http://www.daisy.org/z3986/2005/ncx-2005-1.dtd\">
<ncx xmlns=\"http://www.daisy.org/z3986/2005/ncx/\" version=\"2005-1\">
<head>
  <meta name=\"dtb:uid\" content=\"{$uid}\"/>
  <meta name=\"dtb:depth\" content=\"1\"/>
  <meta name=\"dtb:totalPageCount\" content=\"0\"/>
  <meta name=\"dtb:maxPageNumber\" content=\"0\"/>
</head>
<docTitle><text>{$tX}</text></docTitle>
<navMap>
  <navPoint id=\"n1\" playOrder=\"1\"><navLabel><text>Capa</text></navLabel><content src=\"cover.xhtml\"/></navPoint>
  <navPoint id=\"n2\" playOrder=\"2\"><navLabel><text>{$tX}</text></navLabel><content src=\"title.xhtml\"/></navPoint>
  <navPoint id=\"n3\" playOrder=\"3\"><navLabel><text>Conto</text></navLabel><content src=\"content.xhtml\"/></navPoint>
</navMap></ncx>");

    $imgItem = $capaBytes
        ? "<item id=\"cover-img\" href=\"img/cover.{$capaExt}\" media-type=\"{$capaMime}\" properties=\"cover-image\"/>"
        : '';

    $zip->add('OEBPS/package.opf', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<package xmlns=\"http://www.idpf.org/2007/opf\" version=\"3.0\" unique-identifier=\"uid\">
  <metadata xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
    <dc:identifier id=\"uid\">{$uid}</dc:identifier>
    <dc:title>{$tX}</dc:title>
    <dc:creator id=\"creator\">{$aX}</dc:creator>
    <meta refines=\"#creator\" property=\"role\" scheme=\"marc:relators\">aut</meta>
    <dc:publisher>{$eX}</dc:publisher>
    <dc:language>{$lingua}</dc:language>
    <dc:date>{$data}</dc:date>
    <dc:description>{$dX}</dc:description>
    <dc:subject>{$gX}</dc:subject>
    <meta property=\"dcterms:modified\">{$data}T00:00:00Z</meta>
    <meta name=\"cover\" content=\"cover-img\"/>
  </metadata>
  <manifest>
    <item id=\"ncx\"     href=\"toc.ncx\"       media-type=\"application/x-dtbncx+xml\"/>
    <item id=\"nav\"     href=\"nav.xhtml\"      media-type=\"application/xhtml+xml\" properties=\"nav\"/>
    <item id=\"cover\"   href=\"cover.xhtml\"    media-type=\"application/xhtml+xml\"/>
    <item id=\"title\"   href=\"title.xhtml\"    media-type=\"application/xhtml+xml\"/>
    <item id=\"content\" href=\"content.xhtml\"  media-type=\"application/xhtml+xml\"/>
    <item id=\"css\"     href=\"style.css\"      media-type=\"text/css\"/>
    {$imgItem}
  </manifest>
  <spine toc=\"ncx\">
    <itemref idref=\"cover\"   linear=\"yes\"/>
    <itemref idref=\"title\"   linear=\"yes\"/>
    <itemref idref=\"content\" linear=\"yes\"/>
  </spine>
</package>");

    return $zip->bytes();
}

// ═══════════════════════════════════════════════════════════════
//  AUTENTICAÇÃO + PRÉ-PROCESSAMENTO (antes do HTML)
// ═══════════════════════════════════════════════════════════════
session_name('rd_admin_sess');
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

$SITE_ROOT = realpath(__DIR__ . '/../../');

// ── Deletar EPUB ────────────────────────────────────────────────
if (($_GET['acao'] ?? '') === 'deletar' && !empty($_GET['arq'])) {
    $arqNome = basename($_GET['arq']);
    if (preg_match('/^[\w\-]+\.epub$/i', $arqNome)) {
        $epubDir = $SITE_ROOT . DIRECTORY_SEPARATOR . trim($_GET['pasta'] ?? 'contos/epub', '/\\');
        $alvo    = realpath($epubDir . DIRECTORY_SEPARATOR . $arqNome);
        // Verificação de segurança: o arquivo deve estar dentro do SITE_ROOT
        if ($alvo && str_starts_with($alvo, $SITE_ROOT)) {
            @unlink($alvo);
        }
    }
    header('Location: epub.php');
    exit;
}

// ── Gerar EPUB (POST) ───────────────────────────────────────────
$resultado = null; // ['ok', 'msg', 'dl_url', 'tamanho']
$preVals   = [];   // valores para repopular o form

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo  = trim($_POST['titulo']  ?? '');
    $genero  = trim($_POST['genero']  ?? '');
    $autor   = trim($_POST['autor']   ?? 'Robério Diógenes');
    $desc    = trim($_POST['desc']    ?? '');
    $slug    = trim($_POST['slug']    ?? '');
    $outDir  = trim($_POST['out_dir'] ?? 'contos/epub');
    $editor  = 'Robério Diógenes Escritor';
    $preVals = compact('titulo', 'genero', 'autor', 'desc', 'slug', 'outDir');

    // Arquivo .md
    $mdText = '';
    if (!empty($_FILES['md_file']['tmp_name']) && $_FILES['md_file']['error'] === UPLOAD_ERR_OK) {
        $mdText = file_get_contents($_FILES['md_file']['tmp_name']);
        if (empty($titulo) || empty($genero)) {
            $meta   = epub_parseMeta($mdText);
            $titulo = $titulo ?: $meta['titulo'];
            $genero = $genero ?: $meta['genero'];
            $preVals['titulo'] = $titulo;
            $preVals['genero'] = $genero;
        }
    } else {
        $resultado = ['ok' => false, 'msg' => 'Selecione o arquivo .md do conto.'];
    }

    // Imagem de capa
    $capaBytes = null; $capaExt = 'webp';
    if (empty($resultado) && !empty($_FILES['capa']['tmp_name']) && $_FILES['capa']['error'] === UPLOAD_ERR_OK) {
        $capaBytes = file_get_contents($_FILES['capa']['tmp_name']);
        $capaExt   = strtolower(pathinfo($_FILES['capa']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    }

    if (empty($resultado)) {
        // Gera slug a partir do título se não informado
        if (empty($slug)) {
            $slug = strtolower($titulo ?: 'conto');
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            $preVals['slug'] = $slug;
        }

        try {
            $epubBytes = epub_montar([
                'titulo'     => $titulo ?: 'Sem título',
                'genero'     => $genero ?: 'Conto',
                'autor'      => $autor,
                'desc'       => $desc,
                'editor'     => $editor,
                'md_text'    => $mdText,
                'capa_bytes' => $capaBytes,
                'capa_ext'   => $capaExt,
            ]);

            // Salva na pasta
            $absOut = $SITE_ROOT . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $outDir), DIRECTORY_SEPARATOR);
            if (!is_dir($absOut)) @mkdir($absOut, 0755, true);
            $outFile = $absOut . DIRECTORY_SEPARATOR . $slug . '.epub';
            $bytes   = file_put_contents($outFile, $epubBytes);

            if ($bytes !== false) {
                // URL de download relativa ao domínio
                $relDir  = ltrim(str_replace('\\', '/', $outDir), '/');
                $dlUrl   = '/' . $relDir . '/' . $slug . '.epub';
                $resultado = [
                    'ok'      => true,
                    'msg'     => "<strong>{$slug}.epub</strong> gerado com sucesso.",
                    'dl_url'  => $dlUrl,
                    'tamanho' => round($bytes / 1024, 1) . ' KB',
                    'pasta'   => $outDir,
                ];
            } else {
                $resultado = ['ok' => false, 'msg' => "Não foi possível salvar em <code>{$absOut}</code>. Verifique o caminho e as permissões."];
            }
        } catch (Throwable $e) {
            $resultado = ['ok' => false, 'msg' => 'Erro interno: ' . htmlspecialchars($e->getMessage())];
        }
    }
}

// ── Lista EPUBs existentes ──────────────────────────────────────
function listarEpubs(string $siteRoot, string $pasta = 'contos/epub'): array
{
    $dir = $siteRoot . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pasta), DIRECTORY_SEPARATOR);
    if (!is_dir($dir)) return [];
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.epub') ?: [];
    usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
    return array_map(fn($f) => [
        'nome'   => basename($f),
        'bytes'  => filesize($f),
        'data'   => date('d/m/Y H:i', filemtime($f)),
        'url'    => '/' . $pasta . '/' . basename($f),
    ], $files);
}

$pastaListagem = $preVals['outDir'] ?? 'contos/epub';
$epubsExistentes = listarEpubs($SITE_ROOT, $pastaListagem);

// ═══════════════════════════════════════════════════════════════
//  HTML — PAINEL ADMIN
// ═══════════════════════════════════════════════════════════════
$ADM_HREF   = '../';
$ADM_ROOT   = '../../';
$ADMIN_PAGE = 'ferramentas-epub';
require_once __DIR__ . '/../_admin.php';
?>

<style>
/* ── Estilos específicos da ferramenta EPUB ── */
.epub-grid { display:grid; grid-template-columns:1fr 380px; gap:1.5rem; align-items:start; }
@media(max-width:1100px){ .epub-grid { grid-template-columns:1fr; } }

.campo { margin-bottom:1rem; }
.campo:last-child { margin-bottom:0; }
.campo label {
  display:block; font-size:.62rem; font-weight:700;
  letter-spacing:.12em; text-transform:uppercase;
  color:var(--texto-3); margin-bottom:.35rem;
}
.campo input[type=text],
.campo input[type=file],
.campo select,
.campo textarea {
  width:100%; padding:.58rem .8rem;
  background:var(--fundo-input); border:1px solid var(--borda);
  border-radius:var(--raio); color:var(--texto); font-size:.85rem;
  font-family:inherit; transition:border-color .2s;
}
.campo input:focus,.campo select:focus,.campo textarea:focus { outline:none; border-color:var(--ouro); }
.campo textarea { min-height:64px; resize:vertical; line-height:1.5; }
.campo .hint { font-size:.68rem; color:var(--texto-3); margin-top:.3rem; }

.campo-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
@media(max-width:600px){ .campo-row { grid-template-columns:1fr; } }

/* Upload zones */
.upload-zone {
  position:relative; border:1px dashed var(--borda);
  border-radius:var(--raio); background:var(--fundo-input);
  transition:border-color .2s, background .2s; overflow:hidden;
}
.upload-zone:hover, .upload-zone.dragover {
  border-color:var(--ouro); background:var(--ouro-bg);
}
.upload-zone input[type=file] {
  position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; z-index:2;
}
.upload-label {
  display:flex; align-items:center; gap:.75rem;
  padding:.75rem 1rem; pointer-events:none;
}
.upload-icone { font-size:1.3rem; color:var(--ouro); opacity:.6; flex-shrink:0; }
.upload-texto { font-size:.78rem; }
.upload-texto strong { display:block; color:var(--texto-2); margin-bottom:.1rem; }
.upload-texto span   { color:var(--texto-3); font-size:.7rem; }

/* Capa preview */
.capa-preview-wrap {
  display:none; margin-top:.65rem;
  border:1px solid var(--borda); border-radius:var(--raio);
  overflow:hidden; max-width:140px;
}
.capa-preview-wrap img { width:100%; display:block; }

/* Resultado */
.resultado-card {
  border-radius:var(--raio-lg); padding:1rem 1.25rem;
  margin-bottom:1.25rem; display:flex; align-items:flex-start; gap:.85rem;
}
.resultado-card.ok  { background:rgba(46,125,50,.1); border:1px solid rgba(46,125,50,.25); }
.resultado-card.err { background:rgba(192,57,43,.1); border:1px solid rgba(192,57,43,.25); }
.resultado-card .ri { font-size:1.3rem; flex-shrink:0; margin-top:.1rem; }
.resultado-card.ok .ri  { color:#4CAF50; }
.resultado-card.err .ri { color:#e74c3c; }
.resultado-card .rt { font-size:.85rem; color:var(--texto-2); line-height:1.5; }
.resultado-card .rt strong { color:var(--texto); }
.resultado-card .btn-dl {
  display:inline-flex; align-items:center; gap:.35rem; margin-top:.6rem;
  padding:.35rem .75rem; background:var(--ouro); color:#1A0F00;
  border-radius:var(--raio); font-size:.75rem; font-weight:700;
  text-decoration:none; transition:opacity .15s;
}
.resultado-card .btn-dl:hover { opacity:.85; }

/* Lista de EPUBs */
.epub-item {
  display:flex; align-items:center; gap:.75rem;
  padding:.7rem 1.25rem; border-bottom:1px solid rgba(255,255,255,.04);
  transition:background .12s;
}
.epub-item:last-child { border-bottom:none; }
.epub-item:hover { background:rgba(184,134,11,.04); }
.epub-icone { font-size:1.1rem; color:var(--ouro); opacity:.5; flex-shrink:0; }
.epub-info  { flex:1; min-width:0; }
.epub-nome  { font-size:.82rem; color:var(--texto); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.epub-meta  { font-size:.68rem; color:var(--texto-3); margin-top:.1rem; }
.epub-acoes { display:flex; gap:.35rem; flex-shrink:0; }

.btn-gerar {
  width:100%; padding:.85rem; margin-top:1.4rem;
  background:var(--ouro); color:#1A0F00; border:none;
  border-radius:var(--raio-lg); font-size:.92rem; font-weight:700;
  letter-spacing:.04em; cursor:pointer; transition:opacity .15s, transform .1s;
  display:flex; align-items:center; justify-content:center; gap:.5rem;
}
.btn-gerar:hover { opacity:.88; }
.btn-gerar:active { transform:scale(.98); }
.btn-gerar i { font-size:1rem; }

.secao-vazio { text-align:center; padding:2.5rem 1.5rem; color:var(--texto-3); }
.secao-vazio i { font-size:1.8rem; color:var(--ouro); opacity:.2; display:block; margin-bottom:.6rem; }
.secao-vazio p { font-size:.8rem; }
</style>

<!-- Cabeçalho da página -->
<div class="page-header">
  <div style="display:flex;align-items:center;gap:.75rem;">
    <i class="fa fa-scroll" style="font-size:1.4rem;color:var(--ouro);opacity:.7;"></i>
    <div>
      <h1 class="page-titulo">Gerador de EPUB</h1>
      <p class="page-sub">Converte contos em .md para EPUB elegante — tipografia serif, capa, compatível com Kindle</p>
    </div>
  </div>
</div>

<?php if ($resultado): ?>
<div class="resultado-card <?= $resultado['ok'] ? 'ok' : 'err' ?>">
  <div class="ri"><i class="fa <?= $resultado['ok'] ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i></div>
  <div class="rt">
    <?= $resultado['msg'] ?>
    <?php if ($resultado['ok']): ?>
      <br><span style="font-size:.75rem;color:var(--texto-3);">
        Salvo em <code style="background:rgba(255,255,255,.06);padding:.1em .3em;border-radius:3px;font-size:.9em"><?= htmlspecialchars($resultado['pasta'] . '/' . basename($resultado['dl_url'])) ?></code>
        &nbsp;·&nbsp; <?= $resultado['tamanho'] ?>
      </span>
      <br>
      <a class="btn-dl" href="<?= htmlspecialchars($resultado['dl_url']) ?>" download>
        <i class="fa fa-download"></i> Baixar agora
      </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="epub-grid">

  <!-- ══ COLUNA ESQUERDA: Formulário ══ -->
  <form method="POST" enctype="multipart/form-data">

    <!-- Conteúdo -->
    <div class="secao" style="margin-bottom:1rem;">
      <div class="secao-header">
        <div class="secao-titulo"><i class="fa fa-file-lines"></i> Conteúdo do conto</div>
      </div>
      <div style="padding:1.25rem;">

        <div class="campo">
          <label>Arquivo .md <span style="color:#e74c3c">*</span></label>
          <div class="upload-zone" id="zMd">
            <input type="file" name="md_file" accept=".md,text/markdown,text/plain" id="inpMd" required>
            <div class="upload-label">
              <div class="upload-icone"><i class="fa fa-file-code"></i></div>
              <div class="upload-texto">
                <strong id="mdNome">Escolher arquivo .md</strong>
                <span>Título e gênero são detectados automaticamente</span>
              </div>
            </div>
          </div>
        </div>

        <div class="campo-row">
          <div class="campo">
            <label>Título</label>
            <input type="text" name="titulo" id="fTitulo"
                   value="<?= htmlspecialchars($preVals['titulo'] ?? '') ?>"
                   placeholder="Detectado do arquivo">
          </div>
          <div class="campo">
            <label>Gênero</label>
            <input type="text" name="genero" id="fGenero"
                   value="<?= htmlspecialchars($preVals['genero'] ?? '') ?>"
                   placeholder="Drama, Romance, Terror…">
          </div>
        </div>

        <div class="campo-row">
          <div class="campo">
            <label>Autor</label>
            <input type="text" name="autor"
                   value="<?= htmlspecialchars($preVals['autor'] ?? 'Robério Diógenes') ?>">
          </div>
          <div class="campo">
            <label>Nome do arquivo <span style="color:var(--texto-3);font-weight:400;text-transform:none;letter-spacing:0">(sem .epub)</span></label>
            <input type="text" name="slug" id="fSlug"
                   value="<?= htmlspecialchars($preVals['slug'] ?? '') ?>"
                   placeholder="gerado-do-titulo">
          </div>
        </div>

        <div class="campo">
          <label>Descrição <span style="color:var(--texto-3);font-weight:400;text-transform:none;letter-spacing:0">(metadados, opcional)</span></label>
          <textarea name="desc" placeholder="Uma linha sobre o conto…"><?= htmlspecialchars($preVals['desc'] ?? '') ?></textarea>
        </div>

      </div>
    </div>

    <!-- Capa -->
    <div class="secao" style="margin-bottom:1rem;">
      <div class="secao-header">
        <div class="secao-titulo"><i class="fa fa-image"></i> Imagem de capa</div>
        <span style="font-size:.68rem;color:var(--texto-3);">webp · jpg · png — convertida para JPEG automaticamente</span>
      </div>
      <div style="padding:1.25rem;">
        <div class="campo">
          <div class="upload-zone" id="zCapa">
            <input type="file" name="capa" accept="image/*" id="inpCapa">
            <div class="upload-label">
              <div class="upload-icone"><i class="fa fa-image"></i></div>
              <div class="upload-texto">
                <strong id="capaNome">Escolher imagem de capa</strong>
                <span>Opcional — sem capa o EPUB usa página de título</span>
              </div>
            </div>
          </div>
          <div class="capa-preview-wrap" id="capaPreviewWrap">
            <img id="capaPreviewImg" src="" alt="preview">
          </div>
        </div>
      </div>
    </div>

    <!-- Saída -->
    <div class="secao" style="margin-bottom:0;">
      <div class="secao-header">
        <div class="secao-titulo"><i class="fa fa-folder-open"></i> Pasta de saída</div>
      </div>
      <div style="padding:1.25rem;">
        <div class="campo" style="margin-bottom:0;">
          <label>Caminho relativo à raiz do site</label>
          <input type="text" name="out_dir" id="fOutDir"
                 value="<?= htmlspecialchars($preVals['outDir'] ?? 'contos/epub') ?>"
                 placeholder="contos/epub">
          <p class="hint">Ex: <code>contos/epub</code> &nbsp;·&nbsp; <code>downloads/ebooks</code></p>
        </div>
      </div>
    </div>

    <button type="submit" class="btn-gerar">
      <i class="fa fa-bolt"></i> Gerar EPUB
    </button>

  </form>

  <!-- ══ COLUNA DIREITA: EPUBs existentes ══ -->
  <div>
    <div class="secao">
      <div class="secao-header">
        <div class="secao-titulo"><i class="fa fa-books"></i> EPUBs gerados</div>
        <span style="font-size:.68rem;color:var(--texto-3);" id="pastaLabel">
          <?= htmlspecialchars($pastaListagem) ?>
        </span>
      </div>

      <div id="epubLista">
        <?php if (empty($epubsExistentes)): ?>
          <div class="secao-vazio">
            <i class="fa fa-scroll"></i>
            <p>Nenhum EPUB encontrado<br>em <code><?= htmlspecialchars($pastaListagem) ?></code></p>
          </div>
        <?php else: ?>
          <?php foreach ($epubsExistentes as $e): ?>
            <div class="epub-item">
              <div class="epub-icone"><i class="fa fa-book-open"></i></div>
              <div class="epub-info">
                <div class="epub-nome" title="<?= htmlspecialchars($e['nome']) ?>"><?= htmlspecialchars($e['nome']) ?></div>
                <div class="epub-meta"><?= round($e['bytes']/1024,1) ?> KB &nbsp;·&nbsp; <?= $e['data'] ?></div>
              </div>
              <div class="epub-acoes">
                <a href="<?= htmlspecialchars($e['url']) ?>" download
                   class="btn btn-ghost btn-sm" title="Baixar">
                  <i class="fa fa-download"></i>
                </a>
                <a href="epub.php?acao=deletar&arq=<?= urlencode($e['nome']) ?>&pasta=<?= urlencode($pastaListagem) ?>"
                   class="btn btn-danger btn-sm"
                   title="Deletar"
                   onclick="return confirm('Deletar <?= htmlspecialchars(addslashes($e['nome'])) ?>?')">
                  <i class="fa fa-trash"></i>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Dica de compatibilidade -->
    <div class="secao" style="margin-top:1rem;">
      <div class="secao-header">
        <div class="secao-titulo"><i class="fa fa-circle-info"></i> Compatibilidade</div>
      </div>
      <div style="padding:1rem 1.25rem;">
        <div style="font-size:.76rem;color:var(--texto-3);line-height:1.7;">
          <p style="margin-bottom:.5rem;">
            <i class="fa fa-check" style="color:#4CAF50;width:14px;"></i>
            <strong style="color:var(--texto-2);">Kindle Previewer</strong> — suportado (com NCX)
          </p>
          <p style="margin-bottom:.5rem;">
            <i class="fa fa-check" style="color:#4CAF50;width:14px;"></i>
            <strong style="color:var(--texto-2);">Apple Books</strong> — suportado
          </p>
          <p style="margin-bottom:.5rem;">
            <i class="fa fa-check" style="color:#4CAF50;width:14px;"></i>
            <strong style="color:var(--texto-2);">Calibre / Kobo</strong> — suportado
          </p>
          <p style="margin-bottom:.5rem;">
            <i class="fa fa-check" style="color:#4CAF50;width:14px;"></i>
            <strong style="color:var(--texto-2);">Google Play Books</strong> — suportado
          </p>
          <p style="color:var(--texto-3);font-size:.7rem;margin-top:.75rem;padding-top:.75rem;border-top:1px solid var(--borda);">
            Imagens WebP são convertidas automaticamente para JPEG via GD.
          </p>
        </div>
      </div>
    </div>
  </div>

</div><!-- /epub-grid -->

<script>
// ── Upload .md: atualiza label + auto-preenche título/gênero/slug ──
document.getElementById('inpMd').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  document.getElementById('mdNome').textContent = file.name;

  const reader = new FileReader();
  reader.onload = e => {
    const text  = e.target.result;
    const fT    = document.getElementById('fTitulo');
    const fG    = document.getElementById('fGenero');
    const fS    = document.getElementById('fSlug');

    const hm = text.match(/^#\s+(.+)/m);
    if (hm && !fT.value) fT.value = hm[1].trim();

    const gm = text.match(/\*\*Gênero:\s*(.+?)\*\*/);
    if (gm && !fG.value) fG.value = gm[1].trim();

    if (fT.value && !fS.value) fS.value = slugify(fT.value);
  };
  reader.readAsText(file, 'UTF-8');
});

// ── Upload capa: preview ──
document.getElementById('inpCapa').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;
  document.getElementById('capaNome').textContent = file.name;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('capaPreviewImg').src = e.target.result;
    document.getElementById('capaPreviewWrap').style.display = 'block';
  };
  reader.readAsDataURL(file);
});

// ── Drag & drop visual feedback ──
['zMd','zCapa'].forEach(id => {
  const z = document.getElementById(id);
  z.addEventListener('dragover',  e => { e.preventDefault(); z.classList.add('dragover'); });
  z.addEventListener('dragleave', () => z.classList.remove('dragover'));
  z.addEventListener('drop',      () => z.classList.remove('dragover'));
});

// ── Slug helper ──
function slugify(s) {
  return s.normalize('NFD').replace(/[̀-ͯ]/g,'')
    .toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}

// ── Atualiza lista ao mudar pasta de saída ──
document.getElementById('fOutDir').addEventListener('change', function() {
  document.getElementById('pastaLabel').textContent = this.value;
});

<?php if ($resultado && $resultado['ok']): ?>
// Toast de sucesso
setTimeout(() => toast('EPUB gerado com sucesso!'), 200);
<?php elseif ($resultado && !$resultado['ok']): ?>
setTimeout(() => toast('<?= addslashes(strip_tags($resultado['msg'])) ?>', 'erro'), 200);
<?php endif; ?>
</script>

<?= $ADMIN_FOOTER_HTML ?>
</main>
</body>
</html>
