<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/auth/avatar.php
   Upload e remoção de foto de perfil do usuário.

   POST multipart/form-data  { foto: File }   → faz o upload
   DELETE (body JSON)                         → remove a foto atual
   ================================================================ */

require_once __DIR__ . '/../config.php';
iniciarSessao();

if (empty($_SESSION['usuario_id'])) {
    responderErro('Não autenticado.', 401);
}

$uid    = (int) $_SESSION['usuario_id'];
$metodo = $_SERVER['REQUEST_METHOD'];

/* ──────────────────────────────────────────────────────────────
   CONFIGURAÇÕES DE UPLOAD
   ────────────────────────────────────────────────────────────── */
define('AVATAR_DIR',      __DIR__ . '/../../uploads/avatars/');
define('AVATAR_URL_BASE', SITE_URL . '/uploads/avatars/');
define('AVATAR_MAX_BYTES', 2 * 1024 * 1024);    // 2 MB
define('AVATAR_TAMANHO',   320);                 // px — redimensiona para 320x320

/* ── Garante que a pasta existe ── */
if (!is_dir(AVATAR_DIR)) {
    mkdir(AVATAR_DIR, 0755, true);

    // .htaccess de segurança dentro da pasta de uploads
    file_put_contents(AVATAR_DIR . '.htaccess',
        "Options -Indexes\n" .
        "<FilesMatch \"(?i)\.(php|phtml|php3|php4|php5|phar|shtml)$\">\n" .
        "  Deny from all\n" .
        "</FilesMatch>\n"
    );
}

/* ──────────────────────────────────────────────────────────────
   POST — Upload
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'POST') {

    if (empty($_FILES['foto'])) {
        responderErro('Nenhum arquivo enviado.');
    }

    $arquivo = $_FILES['foto'];

    /* ── Verificações básicas ── */
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros = [
            UPLOAD_ERR_INI_SIZE   => 'Arquivo muito grande (limite do servidor).',
            UPLOAD_ERR_FORM_SIZE  => 'Arquivo muito grande.',
            UPLOAD_ERR_PARTIAL    => 'Upload incompleto. Tente novamente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo recebido.',
        ];
        responderErro($erros[$arquivo['error']] ?? 'Erro no upload.');
    }

    if ($arquivo['size'] > AVATAR_MAX_BYTES) {
        responderErro('Imagem muito grande. Máximo: 2 MB.');
    }

    /* ── Verifica tipo real do arquivo (não confiar no MIME do cliente) ── */
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($arquivo['tmp_name']);
    $mimesOk  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    if (!in_array($mimeReal, $mimesOk, true)) {
        responderErro('Formato não suportado. Use JPG, PNG, WebP ou GIF.');
    }

    $ext = match($mimeReal) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };

    /* ── Redimensiona com GD (se disponível) ── */
    $nomeArquivo = 'avatar_' . $uid . '_' . time() . '.' . $ext;
    $caminhoFinal = AVATAR_DIR . $nomeArquivo;

    if (extension_loaded('gd')) {
        $imgOriginal = match($mimeReal) {
            'image/jpeg' => imagecreatefromjpeg($arquivo['tmp_name']),
            'image/png'  => imagecreatefrompng($arquivo['tmp_name']),
            'image/webp' => imagecreatefromwebp($arquivo['tmp_name']),
            'image/gif'  => imagecreatefromgif($arquivo['tmp_name']),
            default      => false,
        };

        if ($imgOriginal) {
            $largOrig = imagesx($imgOriginal);
            $altOrig  = imagesy($imgOriginal);
            $tam      = AVATAR_TAMANHO;

            // Recorte quadrado centralizado
            $lado = min($largOrig, $altOrig);
            $x    = (int) (($largOrig - $lado) / 2);
            $y    = (int) (($altOrig  - $lado) / 2);

            $imgNova = imagecreatetruecolor($tam, $tam);

            // Preserva transparência em PNG/WebP
            if (in_array($mimeReal, ['image/png', 'image/webp', 'image/gif'])) {
                imagealphablending($imgNova, false);
                imagesavealpha($imgNova, true);
                $transparente = imagecolorallocatealpha($imgNova, 0, 0, 0, 127);
                imagefill($imgNova, 0, 0, $transparente);
            }

            imagecopyresampled($imgNova, $imgOriginal, 0, 0, $x, $y, $tam, $tam, $lado, $lado);

            // Salva como JPEG (menor tamanho) exceto PNG/WebP/GIF
            if ($mimeReal === 'image/png') {
                imagepng($imgNova, $caminhoFinal, 8);
            } elseif ($mimeReal === 'image/webp') {
                imagewebp($imgNova, $caminhoFinal, 85);
            } elseif ($mimeReal === 'image/gif') {
                imagegif($imgNova, $caminhoFinal);
            } else {
                // JPG
                $nomeArquivo  = 'avatar_' . $uid . '_' . time() . '.jpg';
                $caminhoFinal = AVATAR_DIR . $nomeArquivo;
                imagejpeg($imgNova, $caminhoFinal, 88);
            }

            imagedestroy($imgOriginal);
            imagedestroy($imgNova);
        } else {
            // GD falhou em processar — salva original
            move_uploaded_file($arquivo['tmp_name'], $caminhoFinal);
        }
    } else {
        // GD não disponível — salva original diretamente
        move_uploaded_file($arquivo['tmp_name'], $caminhoFinal);
    }

    /* ── Remove avatar anterior (se existir e não for URL Google) ── */
    $pdo    = db();
    $stOld  = $pdo->prepare("SELECT foto_url FROM usuarios WHERE id = ?");
    $stOld->execute([$uid]);
    $urlAntiga = $stOld->fetchColumn();

    if ($urlAntiga && str_contains($urlAntiga, '/uploads/avatars/')) {
        $nomeAntigo = basename($urlAntiga);
        $caminhoAnt = AVATAR_DIR . $nomeAntigo;
        if (is_file($caminhoAnt)) @unlink($caminhoAnt);
    }

    /* ── Atualiza banco ── */
    $novaUrl = AVATAR_URL_BASE . $nomeArquivo;
    $pdo->prepare("UPDATE usuarios SET foto_url = ? WHERE id = ?")
        ->execute([$novaUrl, $uid]);

    // Atualiza sessão
    $_SESSION['usuario_foto'] = $novaUrl;

    responderOk([
        'foto_url' => $novaUrl,
        'mensagem' => 'Foto atualizada com sucesso!',
    ]);
}

/* ──────────────────────────────────────────────────────────────
   DELETE — Remover foto
   ────────────────────────────────────────────────────────────── */
if ($metodo === 'DELETE') {
    $pdo   = db();
    $stOld = $pdo->prepare("SELECT foto_url FROM usuarios WHERE id = ?");
    $stOld->execute([$uid]);
    $urlAtual = $stOld->fetchColumn();

    // Remove arquivo apenas se for upload local (não Google)
    if ($urlAtual && str_contains($urlAtual, '/uploads/avatars/')) {
        $caminho = AVATAR_DIR . basename($urlAtual);
        if (is_file($caminho)) @unlink($caminho);
    }

    $pdo->prepare("UPDATE usuarios SET foto_url = NULL WHERE id = ?")
        ->execute([$uid]);

    $_SESSION['usuario_foto'] = null;

    responderOk(['mensagem' => 'Foto removida.']);
}

responderErro('Método não permitido.', 405);
