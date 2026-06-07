<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/blog_upload.php
   Upload, redimensionamento e renomeação de imagens de posts.
   POST: imagem (arquivo) + slug (string)
   Retorna JSON: { ok, url, kb, mensagem }
   Destino: img/posts/<slug>.jpg  (sempre JPEG, max 150KB)
   ================================================================ */

ob_start();
session_name('rd_admin_sess');
session_start();

if (empty($_SESSION['admin_id'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'erro' => 'Não autorizado.']);
    exit;
}

require_once __DIR__ . '/config.php';
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

/* ── Validar envio ───────────────────────────────────────────── */
if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    $erros = [
        1 => 'Arquivo maior que upload_max_filesize no php.ini.',
        2 => 'Arquivo maior que o limite do formulário.',
        3 => 'Upload incompleto.',
        4 => 'Nenhum arquivo enviado.',
        6 => 'Pasta temporária não encontrada.',
        7 => 'Erro ao gravar arquivo temporário.',
    ];
    $code = $_FILES['imagem']['error'] ?? 4;
    echo json_encode(['ok' => false, 'erro' => $erros[$code] ?? "Erro no upload (código $code)."]);
    exit;
}

$arquivo = $_FILES['imagem'];
$slug    = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_POST['slug'] ?? '')));
if (!$slug) $slug = 'post-' . date('YmdHis');

/* ── Verificar MIME real ─────────────────────────────────────── */
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
finfo_close($finfo);

$mimesOk = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mimeReal, $mimesOk)) {
    echo json_encode(['ok' => false, 'erro' => 'Use JPG, PNG, WebP ou GIF.']);
    exit;
}
if ($arquivo['size'] > 5 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'erro' => 'Máximo 5MB por arquivo.']);
    exit;
}

/* ── Destino: WebP se GD suportar, senão JPEG ───────────────── */
$destDir  = realpath(__DIR__ . '/../') . '/img/posts/';
if (!is_dir($destDir)) mkdir($destDir, 0755, true);

$useWebp  = extension_loaded('gd') && function_exists('imagewebp');
$ext      = $useWebp ? 'webp' : 'jpg';
$destFile = $destDir . $slug . '.' . $ext;
$urlRetorno = 'img/posts/' . $slug . '.' . $ext;
$LIMITE_KB  = 100; // ← 100 KB máximo

/* ── Sem GD: apenas mover ────────────────────────────────────── */
if (!extension_loaded('gd')) {
    move_uploaded_file($arquivo['tmp_name'], $destFile);
    echo json_encode(['ok' => true, 'url' => $urlRetorno,
                      'mensagem' => 'Salvo (GD não disponível, sem otimização).']);
    exit;
}

/* ── Carregar em memória ─────────────────────────────────────── */
$src = match($mimeReal) {
    'image/jpeg' => @imagecreatefromjpeg($arquivo['tmp_name']),
    'image/png'  => @imagecreatefrompng($arquivo['tmp_name']),
    'image/webp' => @imagecreatefromwebp($arquivo['tmp_name']),
    'image/gif'  => @imagecreatefromgif($arquivo['tmp_name']),
    default      => false,
};
if (!$src) {
    echo json_encode(['ok' => false, 'erro' => 'Não foi possível abrir a imagem.']);
    exit;
}

/* ── Redimensionar mantendo proporção (max 1200×630) ─────────── */
$w     = imagesx($src);
$h     = imagesy($src);
$ratio = min(1200 / $w, 630 / $h, 1.0);
$nw    = (int)round($w * $ratio);
$nh    = (int)round($h * $ratio);

$dst = imagecreatetruecolor($nw, $nh);
// Fundo branco (importante para PNG com transparência)
imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
imagedestroy($src);

/* ── Salvar com qualidade progressiva até ≤ LIMITE_KB ──────── */
$tmpFile  = $destDir . $slug . '_tmp.' . $ext;
$limiteB  = $LIMITE_KB * 1024;
$q = 85;

do {
    if ($useWebp) {
        imagewebp($dst, $tmpFile, $q);
    } else {
        imagejpeg($dst, $tmpFile, $q);
    }
    $sz = filesize($tmpFile);
    $q -= 5;
} while ($sz > $limiteB && $q >= 25);

/* ── Se ainda acima do limite, reduzir dimensões ────────────── */
if ($sz > $limiteB) {
    $fator = sqrt($limiteB / $sz);
    $nw2   = max(320, (int)round($nw * $fator));
    $nh2   = max(180, (int)round($nh * $fator));
    $dst2  = imagecreatetruecolor($nw2, $nh2);
    imagefill($dst2, 0, 0, imagecolorallocate($dst2, 255, 255, 255));
    $tmp2  = $useWebp ? @imagecreatefromwebp($tmpFile) : @imagecreatefromjpeg($tmpFile);
    if ($tmp2) {
        imagecopyresampled($dst2, $tmp2, 0, 0, 0, 0, $nw2, $nh2, $nw, $nh);
        imagedestroy($tmp2);
    }
    if ($useWebp) imagewebp($dst2, $tmpFile, 70);
    else          imagejpeg($dst2, $tmpFile, 70);
    imagedestroy($dst2);
    $sz = filesize($tmpFile);
    $nw = $nw2; $nh = $nh2;
}

imagedestroy($dst);
rename($tmpFile, $destFile);

$kb = round($sz / 1024, 1);
echo json_encode([
    'ok'       => true,
    'url'      => $urlRetorno,
    'kb'       => $kb,
    'mensagem' => "Imagem salva em {$ext}: {$kb}KB ({$nw}×{$nh}px).",
]);
