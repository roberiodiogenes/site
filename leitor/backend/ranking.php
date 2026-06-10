<?php
/* leitor/backend/ranking.php
   GET ?acao=top&limite=10  → top leitores
   GET ?acao=minha_posicao  → posição do usuário logado */
ob_start();
require_once __DIR__ . '/../../backend/config.php';
iniciarSessao();

$pdo     = db();
$usuario = getUsuarioSessao();
$acao    = trim($_GET['acao'] ?? '');

function jRk(array $d): void { ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($acao === 'top') {
    $limite = min(50, max(5, (int)($_GET['limite'] ?? 10)));
    $st = $pdo->query(
        "SELECT SUBSTRING_INDEX(u.nome,' ',1) AS primeiro_nome,
                r.pontos, r.livros_lidos, r.contos_lidos, r.streak_dias
         FROM leitor_ranking r
         JOIN usuarios u ON u.id = r.usuario_id
         WHERE u.ativo = 1
         ORDER BY r.pontos DESC LIMIT $limite"
    );
    jRk(['ok' => true, 'ranking' => $st->fetchAll(PDO::FETCH_ASSOC)]);
}
if ($acao === 'minha_posicao') {
    if (!$usuario) jRk(['ok' => true, 'posicao' => null]);
    $uid = (int)$usuario['id'];
    $st  = $pdo->prepare("SELECT pontos FROM leitor_ranking WHERE usuario_id=? LIMIT 1");
    $st->execute([$uid]);
    $pontos = (int)($st->fetchColumn() ?: 0);
    $stPos  = $pdo->prepare("SELECT COUNT(*)+1 FROM leitor_ranking WHERE pontos > ?");
    $stPos->execute([$pontos]);
    $posicao = (int)$stPos->fetchColumn();
    jRk(['ok' => true, 'posicao' => $posicao, 'pontos' => $pontos]);
}
jRk(['ok' => false, 'erro' => 'Ação desconhecida.']);
