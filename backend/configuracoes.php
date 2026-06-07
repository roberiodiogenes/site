<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/configuracoes.php
   API de Configurações e Temas Sazonais

   Ações públicas  (sem auth): tema_ativo
   Ações de admin  (sessão rd_admin_sess): listar, salvar,
                   listar_temas, salvar_tema, toggle_tema, reordenar_tema
   ================================================================ */

// Sessão admin deve ser nomeada antes de qualquer session_start()
session_name('rd_admin_sess');
require_once __DIR__ . '/config.php';
$pdo = db();

header('Content-Type: application/json; charset=utf-8');

$acao = $_GET['acao'] ?? '';
if (!$acao) {
    $jb   = json_decode(file_get_contents('php://input'), true) ?? [];
    $acao = $jb['acao'] ?? '';
} else {
    $jb = [];
}

/* ── Helpers ─────────────────────────────────────────────────── */

/** Converte #RRGGBB para rgba(r,g,b,alpha) */
function hexRgba(string $hex, float $alpha): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r,$g,$b,$alpha)";
}

/** Clareia ou escurece um hex somando delta em cada canal */
function hexBrilho(string $hex, int $delta): string {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $delta));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $delta));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $delta));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/** Verifica se hoje está dentro do período do tema (suporta ano cruzado) */
function temaAtivo(array $t): bool {
    $hoje  = (int)date('nd');                          // ex: 625 = 25 jun
    $ini   = (int)($t['mes_inicio'] . sprintf('%02d', $t['dia_inicio']));
    $fim   = (int)($t['mes_fim']    . sprintf('%02d', $t['dia_fim']));

    if ($ini <= $fim) {                                // período normal
        return $hoje >= $ini && $hoje <= $fim;
    } else {                                           // cruza o ano (ex: dez→jan)
        return $hoje >= $ini || $hoje <= $fim;
    }
}

/** Expande um tema com as variáveis CSS derivadas */
function expandirTema(array $t): array {
    $ouro = $t['cor_ouro'];
    $ferr = $t['cor_ferrugem'];
    return [
        'id'               => (int)$t['id'],
        'nome'             => $t['nome'],
        'slug'             => $t['slug'],
        '--ouro'           => $ouro,
        '--ouro-claro'     => hexBrilho($ouro,  35),
        '--ouro-escuro'    => hexBrilho($ouro, -30),
        '--ferrugem'       => $ferr,
        '--ornamento-cor'  => hexRgba($ouro, 0.45),
        '--particula-cor'  => hexRgba($ferr, 0.40),
        '--particula-cor2' => hexRgba($ouro, 0.28),
    ];
}

/** Exige sessão de admin válida */
function exigirAdmin(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['admin_id'])) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'erro' => 'Não autorizado']);
        exit;
    }
}

/* ════════════════════════════════════════════════════════════════
   AÇÃO PÚBLICA — tema_ativo
   Retorna as variáveis CSS do tema vigente (ou null se nenhum)
   ════════════════════════════════════════════════════════════════ */
if ($acao === 'tema_ativo') {
    $temas = $pdo->query(
        "SELECT * FROM temas_sazonais WHERE ativo = 1 ORDER BY prioridade ASC"
    )->fetchAll();

    $encontrado = null;
    foreach ($temas as $t) {
        if (temaAtivo($t)) {
            $encontrado = expandirTema($t);
            break;
        }
    }

    responderJSON(['ok' => true, 'tema' => $encontrado]);
}

/* ════════════════════════════════════════════════════════════════
   AÇÕES DE ADMIN (exigem sessão)
   ════════════════════════════════════════════════════════════════ */

exigirAdmin();

// ── listar configs ──────────────────────────────────────────────
if ($acao === 'listar') {
    $grupo = $_GET['grupo'] ?? null;
    if ($grupo) {
        $stmt = $pdo->prepare("SELECT chave, valor, tipo FROM configuracoes WHERE grupo = ?");
        $stmt->execute([$grupo]);
    } else {
        $stmt = $pdo->query("SELECT chave, valor, tipo, grupo FROM configuracoes ORDER BY grupo, chave");
    }
    $rows = $stmt->fetchAll();
    $cfg = [];
    foreach ($rows as $r) {
        $v = $r['valor'];
        if ($r['tipo'] === 'boolean') $v = (bool)(int)$v;
        if ($r['tipo'] === 'integer') $v = (int)$v;
        if ($r['tipo'] === 'json')    $v = json_decode($v, true);
        $cfg[$r['chave']] = $v;
    }
    responderJSON(['ok' => true, 'configs' => $cfg]);
}

// ── salvar configs ──────────────────────────────────────────────
if ($acao === 'salvar') {
    $configs = $jb['configs'] ?? [];
    if (!is_array($configs) || empty($configs)) {
        responderErro('Nenhum dado recebido.');
    }
    $stmt = $pdo->prepare(
        "INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
    );
    foreach ($configs as $chave => $valor) {
        // Segurança: só salva chaves que já existem na tabela
        $existe = $pdo->prepare("SELECT 1 FROM configuracoes WHERE chave = ?");
        $existe->execute([$chave]);
        if (!$existe->fetchColumn()) continue;
        $valorStr = is_bool($valor) ? ($valor ? '1' : '0') : (string)$valor;
        $stmt->execute([$chave, $valorStr]);

        // ── Modo de manutenção: escreve/apaga arquivo lock ─────────
        if ($chave === 'modo_manutencao') {
            $lock = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'manutencao.lock';
            if ($valorStr === '1') {
                file_put_contents($lock, date('Y-m-d H:i:s'));
            } elseif (file_exists($lock)) {
                @unlink($lock);
            }
        }
    }
    responderOk(['msg' => 'Configurações salvas com sucesso.']);
}

// ── listar temas ────────────────────────────────────────────────
if ($acao === 'listar_temas') {
    $temas = $pdo->query(
        "SELECT *, IF(ativo=1,1,0) AS ativo FROM temas_sazonais ORDER BY prioridade ASC, mes_inicio ASC, dia_inicio ASC"
    )->fetchAll();

    // Marca qual está ativo hoje
    foreach ($temas as &$t) {
        $t['ativo_hoje'] = (int)$t['ativo'] === 1 && temaAtivo($t);
    }
    unset($t);

    responderJSON(['ok' => true, 'temas' => $temas]);
}

// ── salvar/criar tema ───────────────────────────────────────────
if ($acao === 'salvar_tema') {
    $id          = isset($jb['id']) ? (int)$jb['id'] : 0;
    $nome        = trim($jb['nome']         ?? '');
    $dia_ini     = (int)($jb['dia_inicio']  ?? 0);
    $mes_ini     = (int)($jb['mes_inicio']  ?? 0);
    $dia_fim     = (int)($jb['dia_fim']     ?? 0);
    $mes_fim     = (int)($jb['mes_fim']     ?? 0);
    $cor_ouro    = preg_match('/^#[0-9a-fA-F]{6}$/', $jb['cor_ouro']    ?? '') ? $jb['cor_ouro']    : '#B8860B';
    $cor_ferr    = preg_match('/^#[0-9a-fA-F]{6}$/', $jb['cor_ferrugem'] ?? '') ? $jb['cor_ferrugem'] : '#8B3A2A';

    if (!$nome || !$dia_ini || !$mes_ini || !$dia_fim || !$mes_fim) {
        responderErro('Preencha todos os campos obrigatórios.');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare(
            "UPDATE temas_sazonais SET
             nome=?, dia_inicio=?, mes_inicio=?, dia_fim=?, mes_fim=?,
             cor_ouro=?, cor_ferrugem=?
             WHERE id=?"
        );
        $stmt->execute([$nome, $dia_ini, $mes_ini, $dia_fim, $mes_fim, $cor_ouro, $cor_ferr, $id]);
        responderOk(['msg' => 'Tema atualizado.']);
    } else {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($nome));
        $prio = (int)$pdo->query("SELECT COALESCE(MAX(prioridade),0)+1 FROM temas_sazonais")->fetchColumn();
        $stmt = $pdo->prepare(
            "INSERT INTO temas_sazonais
             (nome, slug, prioridade, dia_inicio, mes_inicio, dia_fim, mes_fim, cor_ouro, cor_ferrugem)
             VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([$nome, $slug, $prio, $dia_ini, $mes_ini, $dia_fim, $mes_fim, $cor_ouro, $cor_ferr]);
        responderOk(['msg' => 'Tema criado.', 'id' => (int)$pdo->lastInsertId()]);
    }
}

// ── toggle ativo ────────────────────────────────────────────────
if ($acao === 'toggle_tema') {
    $id = (int)($jb['id'] ?? 0);
    if (!$id) responderErro('ID inválido.');
    $pdo->prepare("UPDATE temas_sazonais SET ativo = NOT ativo WHERE id = ?")->execute([$id]);
    $novo = (int)$pdo->prepare("SELECT ativo FROM temas_sazonais WHERE id=?")->execute([$id]) && 1;
    $stmt = $pdo->prepare("SELECT ativo FROM temas_sazonais WHERE id=?");
    $stmt->execute([$id]);
    responderOk(['ativo' => (bool)$stmt->fetchColumn()]);
}

// ── reordenar prioridade ─────────────────────────────────────────
if ($acao === 'reordenar_tema') {
    $id  = (int)($jb['id']  ?? 0);
    $dir = $jb['dir'] ?? ''; // 'up' ou 'down'
    if (!$id || !in_array($dir, ['up','down'], true)) responderErro('Parâmetros inválidos.');

    $tema = $pdo->prepare("SELECT prioridade FROM temas_sazonais WHERE id=?");
    $tema->execute([$id]);
    $prio = (int)$tema->fetchColumn();

    if ($dir === 'up') {
        $vizinho = $pdo->prepare("SELECT id, prioridade FROM temas_sazonais WHERE prioridade < ? ORDER BY prioridade DESC LIMIT 1");
    } else {
        $vizinho = $pdo->prepare("SELECT id, prioridade FROM temas_sazonais WHERE prioridade > ? ORDER BY prioridade ASC LIMIT 1");
    }
    $vizinho->execute([$prio]);
    $v = $vizinho->fetch();
    if (!$v) responderOk(['msg' => 'Sem troca']);

    $pdo->prepare("UPDATE temas_sazonais SET prioridade=? WHERE id=?")->execute([$v['prioridade'], $id]);
    $pdo->prepare("UPDATE temas_sazonais SET prioridade=? WHERE id=?")->execute([$prio, $v['id']]);
    responderOk(['msg' => 'Reordenado.']);
}

responderErro('Ação desconhecida.');
