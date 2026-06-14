<?php
/* leitor/backend/erros.php
   POST { slug, cfi, trecho, descricao } → reportar erro ortográfico */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/mailer.php';
iniciarSessao();

$usuario = getUsuarioSessao();
if (!$usuario) { ob_end_clean(); http_response_code(401); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>false,'erro'=>'Login necessário.']); exit; }

$pdo  = db();
$uid  = (int)$usuario['id'];
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$slug = preg_replace('/[^a-z0-9_-]/', '', trim($body['slug'] ?? ''));
$cfi  = trim($body['cfi'] ?? '');
$trecho    = trim($body['trecho']    ?? '');
$descricao = trim($body['descricao'] ?? '');

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!$slug) { echo json_encode(['ok'=>false,'erro'=>'Dados insuficientes.']); exit; }

try {
    // Rate limit: máx 5 erros por hora por usuário
    $stR = $pdo->prepare("SELECT COUNT(*) FROM leitor_erros WHERE usuario_id=? AND criado_em > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stR->execute([$uid]);
    if ((int)$stR->fetchColumn() >= 5) {
        echo json_encode(['ok'=>false,'erro'=>'Limite de reportes atingido. Tente em 1 hora.']); exit;
    }

    $pdo->prepare("INSERT INTO leitor_erros (usuario_id, livro_slug, cfi, trecho, descricao) VALUES(?,?,?,?,?)")
        ->execute([$uid, $slug, $cfi, $trecho, $descricao]);

    // Notificação por e-mail ao autor
    $leitorNome  = htmlspecialchars($usuario['nome'] ?? 'Leitor');
    $leitorEmail = htmlspecialchars($usuario['email'] ?? '');
    $trechoHtml  = $trecho   ? '<blockquote style="border-left:3px solid #B8860B;margin:0;padding:.5rem 1rem;color:#5C4A38;font-style:italic">' . htmlspecialchars($trecho) . '</blockquote>' : '<em style="color:#9A8E7C">Nenhum trecho selecionado</em>';
    $descHtml    = $descricao ? htmlspecialchars($descricao) : '<em style="color:#9A8E7C">Sem descrição</em>';
    $linkAdmin   = 'https://www.roberiodiogenes.com/leitor/?livro=' . urlencode($slug);

    Mailer::enviar([
        'para_email' => MAIL_FROM_EMAIL,
        'para_nome'  => MAIL_FROM_NOME,
        'assunto'    => "⚠ Erro reportado em \"$slug\" por $leitorNome",
        'html'       => "
            <p><strong>Livro:</strong> $slug</p>
            <p><strong>Leitor:</strong> $leitorNome ($leitorEmail)</p>
            <p><strong>Trecho com erro:</strong></p>
            $trechoHtml
            <p style='margin-top:1rem'><strong>Descrição:</strong><br>$descHtml</p>
            <p style='margin-top:1.5rem'>
              <a href='$linkAdmin' style='padding:.5rem 1rem;background:#B8860B;color:#fff;border-radius:6px;text-decoration:none'>
                Abrir livro no leitor →
              </a>
            </p>",
        'texto'      => "Erro reportado em \"$slug\" por $leitorNome.\nTrecho: $trecho\nDescrição: $descricao",
    ]);

    echo json_encode(['ok' => true, 'mensagem' => 'Erro reportado. Obrigado pela colaboração!']);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'erro' => 'Erro no banco de dados: ' . $e->getMessage()]);
}
