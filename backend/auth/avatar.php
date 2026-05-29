<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/avatar.php
   Upload e remoção de foto de perfil.

   POST multipart/form-data { foto: File }  → faz upload e processa
   DELETE                                   → remove foto atual

   Garantias:
     • Arquivo final sempre ≤ 100 KB (ajuste automático de qualidade)
     • Saída sempre JPEG (menor tamanho, suporte universal)
     • Corte quadrado centralizado inteligente
     • Suporta JPEG, PNG, WebP, GIF, BMP, AVIF
     • Corrige orientação EXIF (fotos de celular deitadas)
     • Protege contra PHP disfarçado de imagem
   ================================================================ */

require_once __DIR__ . '/../config.php';
iniciarSessao();

if (empty($_SESSION['usuario_id'])) {
    responderErro('Não autenticado.', 401);
}

$uid    = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

/* ── Configurações ─────────────────────────────────────────── */
define('AVATAR_DIR',       __DIR__ . '/../../uploads/avatars/');
define('AVATAR_URL_BASE',  SITE_URL . '/uploads/avatars/');
define('AVATAR_MAX_INPUT', 8 * 1024 * 1024);  // 8 MB entrada
define('AVATAR_MAX_SAIDA', 100 * 1024);        // 100 KB saída
define('AVATAR_DIMENSAO',  240);               // 240×240 px
define('AVATAR_QUAL_INI',  82);                // qualidade JPEG inicial
define('AVATAR_QUAL_MIN',  40);                // qualidade mínima

/* ── Cria pasta com proteção ─────────────────────────────────── */
if (!is_dir(AVATAR_DIR)) {
    mkdir(AVATAR_DIR, 0755, true);
}
if (!file_exists(AVATAR_DIR . '.htaccess')) {
    file_put_contents(AVATAR_DIR . '.htaccess',
        "Options -Indexes\n" .
        "<FilesMatch \"(?i)\\.(php|phtml|php[2-9]|phar|cgi|pl|py|rb|sh|shtml)$\">\n" .
        "  Require all denied\n" .
        "</FilesMatch>\n"
    );
}

/* ──────────────────────────────────────────────────────────────
   POST — Upload
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'POST') {

    if (empty($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
        responderErro('Nenhum arquivo enviado.');
    }
    $arq = $_FILES['foto'];

    $erros = [
        UPLOAD_ERR_INI_SIZE   => 'Arquivo muito grande (limite do servidor PHP).',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo muito grande.',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto. Tente novamente.',
        UPLOAD_ERR_NO_TMP_DIR => 'Erro no servidor (sem pasta temporária).',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao gravar arquivo no servidor.',
    ];
    if ($arq['error'] !== UPLOAD_ERR_OK) {
        responderErro($erros[$arq['error']] ?? 'Erro no upload (código ' . $arq['error'] . ').');
    }
    if ($arq['size'] > AVATAR_MAX_INPUT) {
        responderErro('Imagem muito grande. Máximo aceito: 8 MB.');
    }

    /* ── Detecta tipo REAL (ignora extensão e MIME declarado pelo cliente) ── */
    $mimeReal = _detectarMime($arq['tmp_name']);
    $mimesOk  = [
        'image/jpeg','image/jpg','image/pjpeg',
        'image/png','image/x-png',
        'image/gif',
        'image/webp',
        'image/bmp','image/x-bmp','image/x-ms-bmp',
        'image/avif',
        'image/tiff',
    ];
    if (!$mimeReal || !in_array($mimeReal, $mimesOk, true)) {
        responderErro('Formato não suportado. Use JPG, PNG, WebP, GIF ou BMP.');
    }

    /* ── GD disponível? ── */
    if (!extension_loaded('gd')) {
        $nome  = 'avatar_' . $uid . '_' . time() . '.jpg';
        $dest  = AVATAR_DIR . $nome;
        move_uploaded_file($arq['tmp_name'], $dest);
        _salvarBanco($uid, AVATAR_URL_BASE . $nome);
        responderOk(['foto_url' => AVATAR_URL_BASE . $nome,
                     'mensagem' => 'Foto salva (sem redimensionamento — extensão GD ausente no servidor).']);
    }

    /* ── Carrega imagem ── */
    $imgOrig = _carregar($arq['tmp_name'], $mimeReal);
    if (!$imgOrig) {
        responderErro('Não foi possível processar esta imagem. Tente outro arquivo (JPG ou PNG recomendados).');
    }

    /* ── Corrige rotação EXIF (fotos de celular) ── */
    if (function_exists('exif_read_data') && str_contains($mimeReal, 'jpeg')) {
        $imgOrig = _corrigirExif($imgOrig, $arq['tmp_name']);
    }

    /* ── Recorte quadrado centralizado ── */
    $largO = imagesx($imgOrig);
    $altO  = imagesy($imgOrig);
    $lado  = min($largO, $altO);
    $srcX  = (int)(($largO - $lado) / 2);
    $srcY  = (int)(($altO  - $lado) / 2);

    $dim = AVATAR_DIMENSAO;
    $img = imagecreatetruecolor($dim, $dim);

    // Fundo branco (PNG/WebP/GIF com transparência ficam opacos ao virar JPEG)
    imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));

    imagecopyresampled($img, $imgOrig, 0, 0, $srcX, $srcY, $dim, $dim, $lado, $lado);
    imagedestroy($imgOrig);

    /* ── Salva JPEG com qualidade decrescente até ≤ 100 KB ── */
    $nome     = 'avatar_' . $uid . '_' . time() . '.jpg';
    $destFinal = AVATAR_DIR . $nome;
    $qual      = AVATAR_QUAL_INI;
    $tamanho   = PHP_INT_MAX;
    $tmp       = sys_get_temp_dir() . '/rd_avatar_' . $uid . '_' . time() . '.jpg';

    do {
        imagejpeg($img, $tmp, $qual);
        $tamanho = filesize($tmp);
        if ($tamanho <= AVATAR_MAX_SAIDA) break;
        $qual -= 8;
    } while ($qual >= AVATAR_QUAL_MIN);

    imagedestroy($img);
    rename($tmp, $destFinal);
    @unlink($tmp); // limpa caso rename tenha falhado

    /* ── Remove avatar anterior e salva no banco ── */
    _removerAnterior($uid);
    _salvarBanco($uid, AVATAR_URL_BASE . $nome);

    responderOk([
        'foto_url' => AVATAR_URL_BASE . $nome,
        'tamanho'  => round($tamanho / 1024, 1) . ' KB',
        'mensagem' => 'Foto atualizada com sucesso!',
    ]);
}

/* ──────────────────────────────────────────────────────────────
   DELETE — Remover foto
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'DELETE') {
    _removerAnterior($uid);
    db()->prepare("UPDATE usuarios SET foto_url = NULL WHERE id = ?")->execute([$uid]);
    $_SESSION['usuario_foto'] = null;
    responderOk(['mensagem' => 'Foto removida.']);
}

responderErro('Método não permitido.', 405);

/* ──────────────────────────────────────────────────────────────
   HELPERS
   ────────────────────────────────────────────────────────────── */

function _detectarMime(string $path): ?string
{
    // Tenta exif_imagetype primeiro (mais rápido e confiável para imagens)
    if (function_exists('exif_imagetype')) {
        $mapa = [
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_GIF  => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
            IMAGETYPE_BMP  => 'image/bmp',
            IMAGETYPE_TIFF_II => 'image/tiff',
            IMAGETYPE_TIFF_MM => 'image/tiff',
        ];
        if (defined('IMAGETYPE_AVIF')) {
            $mapa[IMAGETYPE_AVIF] = 'image/avif';
        }
        $tipo = @exif_imagetype($path);
        if ($tipo && isset($mapa[$tipo])) return $mapa[$tipo];
    }
    // Fallback com finfo
    if (class_exists('finfo')) {
        return (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: null;
    }
    return null;
}

function _carregar(string $path, string $mime): mixed
{
    // imagecreatefromstring é o mais universal — tenta primeiro
    $dados = @file_get_contents($path);
    if ($dados) {
        $img = @imagecreatefromstring($dados);
        if ($img) return $img;
    }
    // Fallback específico por formato
    return match(true) {
        str_contains($mime, 'jpeg') => @imagecreatefromjpeg($path),
        $mime === 'image/png'       => @imagecreatefrompng($path),
        $mime === 'image/gif'       => @imagecreatefromgif($path),
        $mime === 'image/webp'      => @imagecreatefromwebp($path),
        str_contains($mime, 'bmp')  => @imagecreatefrombmp($path),
        $mime === 'image/avif' && function_exists('imagecreatefromavif') => @imagecreatefromavif($path),
        default => false,
    };
}

function _corrigirExif(mixed $img, string $path): mixed
{
    try {
        $exif = @exif_read_data($path);
        return match((int)($exif['Orientation'] ?? 1)) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img,  90, 0),
            default => $img,
        };
    } catch (\Throwable) {
        return $img;
    }
}

function _removerAnterior(int $uid): void
{
    $stmt = db()->prepare("SELECT foto_url FROM usuarios WHERE id = ?");
    $stmt->execute([$uid]);
    $url = $stmt->fetchColumn();
    if ($url && str_contains($url, '/uploads/avatars/')) {
        $f = AVATAR_DIR . basename(parse_url($url, PHP_URL_PATH));
        if (is_file($f)) @unlink($f);
    }
}

function _salvarBanco(int $uid, string $url): void
{
    db()->prepare("UPDATE usuarios SET foto_url = ? WHERE id = ?")->execute([$url, $uid]);
    $_SESSION['usuario_foto'] = $url;
}
