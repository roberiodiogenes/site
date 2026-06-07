<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/pre-lancamento.php
   API do sistema de Lista de Espera / Pré-lançamento.

   GET  ?acao=dados&slug=X        → dados públicos da campanha
   POST {acao:'inscrever', slug, nome, email} → lead + brinde imediato
   POST admin {acao:'criar'|'editar'|'toggle'|'disparar', ...}
   GET  admin ?acao=leads&id=X    → lista de leads da campanha
   ================================================================ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';
iniciarSessao();

$metodo = $_SERVER['REQUEST_METHOD'];
$pdo    = db();

/* ── helpers ─────────────────────────────────────────────────── */
function _pl_ok(array $d = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => true], $d), JSON_UNESCAPED_UNICODE);
    exit;
}
function _pl_erro(string $msg, int $st = 400): void {
    http_response_code($st);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'erro' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ================================================================
   GET público: dados de uma campanha
   ================================================================ */
if ($metodo === 'GET' && ($_GET['acao'] ?? '') === 'dados') {
    $slug = trim($_GET['slug'] ?? '');
    if (!$slug) _pl_erro('Slug obrigatório.');
    try {
        $st = $pdo->prepare(
            "SELECT id, slug, titulo, subtitulo, descricao, capa_img,
                    data_lancamento, brinde_titulo, ativo, lancado,
                    (SELECT COUNT(*) FROM pre_lancamento_leads WHERE lancamento_id=p.id) AS total_leads
             FROM pre_lancamentos p WHERE slug=? LIMIT 1"
        );
        $st->execute([$slug]);
        $camp = $st->fetch(PDO::FETCH_ASSOC);
        if (!$camp || !$camp['ativo']) _pl_erro('Campanha não encontrada.', 404);
        $camp['total_leads'] = (int)$camp['total_leads'];
        _pl_ok(['campanha' => $camp]);
    } catch (Throwable $e) { _pl_erro('Erro: '.$e->getMessage(), 500); }
}

/* ================================================================
   GET admin: lista leads de uma campanha
   ================================================================ */
if ($metodo === 'GET' && ($_GET['acao'] ?? '') === 'leads') {
    if (empty($_SESSION['admin_id'])) _pl_erro('Não autorizado.', 401);
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) _pl_erro('ID inválido.');
    try {
        $st = $pdo->prepare(
            "SELECT id, nome, email, brinde_enviado, lancamento_enviado, inscrito_em
             FROM pre_lancamento_leads WHERE lancamento_id=? ORDER BY inscrito_em DESC"
        );
        $st->execute([$id]);
        _pl_ok(['leads' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) { _pl_erro('Erro: '.$e->getMessage(), 500); }
}

/* ================================================================
   GET admin: listar todas as campanhas
   ================================================================ */
if ($metodo === 'GET' && ($_GET['acao'] ?? '') === 'listar') {
    if (empty($_SESSION['admin_id'])) _pl_erro('Não autorizado.', 401);
    try {
        $st = $pdo->query(
            "SELECT p.*, (SELECT COUNT(*) FROM pre_lancamento_leads WHERE lancamento_id=p.id) AS total_leads
             FROM pre_lancamentos p ORDER BY p.id DESC"
        );
        _pl_ok(['campanhas' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) { _pl_ok(['campanhas' => []]); }
}

/* ================================================================
   POST: inscrever lead + enviar brinde
   ================================================================ */
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = trim($body['acao'] ?? $_POST['acao'] ?? '');

if ($metodo === 'POST' && $acao === 'inscrever') {
    $slug  = trim($body['slug']  ?? '');
    $nome  = mb_substr(trim($body['nome']  ?? ''), 0, 120, 'UTF-8');
    $email = trim(strtolower($body['email'] ?? ''));

    if (!$slug)  _pl_erro('Slug obrigatório.');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) _pl_erro('E-mail inválido.');

    try {
        // Buscar campanha
        $stC = $pdo->prepare("SELECT * FROM pre_lancamentos WHERE slug=? AND ativo=1 LIMIT 1");
        $stC->execute([$slug]);
        $camp = $stC->fetch(PDO::FETCH_ASSOC);
        if (!$camp) _pl_erro('Campanha não encontrada.', 404);

        $ipHash = hash('sha256', getIP());

        // Inserir lead (ignora duplicata silenciosamente)
        $jaExistia = false;
        try {
            $pdo->prepare(
                "INSERT INTO pre_lancamento_leads (lancamento_id, nome, email, ip_hash) VALUES (?,?,?,?)"
            )->execute([$camp['id'], $nome ?: null, $email, $ipHash]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $jaExistia = true; // já inscrito — ok, só não reenvia brinde
            } else {
                throw $e;
            }
        }

        // Enviar brinde imediatamente se não estava inscrito
        if (!$jaExistia && !empty($camp['brinde_html'])) {
            $primeiroNome = explode(' ', trim($nome ?: 'Leitor'))[0];
            $brindeTitle  = $camp['brinde_titulo'] ?: 'Presente exclusivo para você';
            $dataLanc = $camp['data_lancamento']
                ? date('d/m/Y', strtotime($camp['data_lancamento']))
                : 'em breve';

            $ok = Mailer::enviar([
                'para_email' => $email,
                'para_nome'  => $nome ?: 'Leitor',
                'assunto'    => "[{$brindeTitle}] {$camp['titulo']} — Robério Diógenes",
                'html'       => "
<div style='font-family:Georgia,serif;max-width:560px;margin:0 auto;padding:2rem;background:#FAF7F2;border-radius:8px'>
  <p style='font-family:Cinzel,serif;color:#B8860B;font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;margin-bottom:1.5rem'>Lista de Espera · {$camp['titulo']}</p>
  <h2 style='font-family:Georgia,serif;color:#2C2418;font-size:1.4rem;font-weight:400;margin-bottom:.5rem'>{$brindeTitle}</h2>
  <p style='color:#5C4F3A;line-height:1.7;margin-bottom:1.5rem'>Olá, <strong>{$primeiroNome}</strong>. Como prometido, aqui está seu presente exclusivo por se inscrever na lista de espera de <em>{$camp['titulo']}</em>.</p>
  <hr style='border-color:#E4DBC8;margin:1.5rem 0'>
  <div style='color:#2C2418;line-height:1.8;font-size:1rem'>
    {$camp['brinde_html']}
  </div>
  <hr style='border-color:#E4DBC8;margin:1.5rem 0'>
  <p style='color:#5C4F3A;line-height:1.7'>O lançamento está previsto para <strong>{$dataLanc}</strong>. Você receberá uma mensagem especial antes de qualquer outra pessoa assim que o livro estiver disponível.</p>
  <p style='margin-top:1.5rem;color:#8C7D65;font-size:.85rem'>— Robério Diógenes</p>
  <hr style='border-color:#E4DBC8;margin:1.5rem 0'>
  <p style='color:#B8A888;font-size:.75rem'>Você recebe este e-mail por ter se inscrito na lista de espera de <em>{$camp['titulo']}</em>.<br>Robério Diógenes · Escritor Independente · <a href='" . SITE_URL . "/privacidade.html' style='color:#B8A888'>Privacidade</a></p>
</div>",
                'texto' => "Olá {$primeiroNome},\n\n{$brindeTitle} de {$camp['titulo']}\n\nO lançamento está previsto para {$dataLanc}.\n\n— Robério Diógenes",
            ]);

            if ($ok) {
                $pdo->prepare(
                    "UPDATE pre_lancamento_leads SET brinde_enviado=1 WHERE lancamento_id=? AND email=?"
                )->execute([$camp['id'], $email]);
            }
        }

        $msg = $jaExistia
            ? 'Você já está na lista! Aguarde o lançamento.'
            : ($camp['brinde_titulo']
                ? "Inscrito! Enviamos seu {$camp['brinde_titulo']} por e-mail."
                : 'Inscrito com sucesso! Aguarde o lançamento.');

        _pl_ok(['mensagem' => $msg, 'ja_existia' => $jaExistia]);
    } catch (Throwable $e) {
        _pl_erro('Erro ao processar inscrição: '.$e->getMessage(), 500);
    }
}

/* ================================================================
   POST admin: criar campanha
   ================================================================ */
if ($metodo === 'POST' && $acao === 'criar') {
    if (empty($_SESSION['admin_id'])) _pl_erro('Não autorizado.', 401);
    $titulo     = trim($body['titulo']      ?? '');
    $subtitulo  = trim($body['subtitulo']   ?? '');
    $descricao  = trim($body['descricao']   ?? '');
    $capa       = trim($body['capa_img']    ?? '');
    $dataLanc   = trim($body['data_lancamento'] ?? '') ?: null;
    $brindeT    = trim($body['brinde_titulo'] ?? '');
    $brindeH    = trim($body['brinde_html']   ?? '');
    if (!$titulo) _pl_erro('Título obrigatório.');
    $slug = preg_replace('/[^a-z0-9-]/','', str_replace(' ','-', mb_strtolower($titulo,'UTF-8')));
    $slug = substr($slug, 0, 100);
    try {
        $stCk = $pdo->prepare("SELECT id FROM pre_lancamentos WHERE slug=? LIMIT 1");
        $stCk->execute([$slug]);
        if ($stCk->fetchColumn()) $slug .= '-'.substr(md5(uniqid()),0,5);
        $pdo->prepare("INSERT INTO pre_lancamentos (slug,titulo,subtitulo,descricao,capa_img,data_lancamento,brinde_titulo,brinde_html) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$slug,$titulo,$subtitulo,$descricao,$capa,$dataLanc,$brindeT,$brindeH]);
        _pl_ok(['id' => (int)$pdo->lastInsertId(), 'slug' => $slug, 'mensagem' => 'Campanha criada!']);
    } catch (Throwable $e) { _pl_erro('Erro: '.$e->getMessage(), 500); }
}

/* ================================================================
   POST admin: editar campanha
   ================================================================ */
if ($metodo === 'POST' && $acao === 'editar') {
    if (empty($_SESSION['admin_id'])) _pl_erro('Não autorizado.', 401);
    $id = (int)($body['id'] ?? 0);
    if (!$id) _pl_erro('ID inválido.');
    $titulo    = trim($body['titulo']      ?? '');
    $subtitulo = trim($body['subtitulo']   ?? '');
    $descricao = trim($body['descricao']   ?? '');
    $capa      = trim($body['capa_img']    ?? '');
    $dataLanc  = trim($body['data_lancamento'] ?? '') ?: null;
    $brindeT   = trim($body['brinde_titulo'] ?? '');
    $brindeH   = trim($body['brinde_html']   ?? '');
    if (!$titulo) _pl_erro('Título obrigatório.');
    try {
        $pdo->prepare("UPDATE pre_lancamentos SET titulo=?,subtitulo=?,descricao=?,capa_img=?,data_lancamento=?,brinde_titulo=?,brinde_html=? WHERE id=?")
            ->execute([$titulo,$subtitulo,$descricao,$capa,$dataLanc,$brindeT,$brindeH,$id]);
        _pl_ok(['mensagem' => 'Campanha atualizada!']);
    } catch (Throwable $e) { _pl_erro('Erro: '.$e->getMessage(), 500); }
}

/* ================================================================
   POST admin: toggle ativo
   ================================================================ */
if ($metodo === 'POST' && $acao === 'toggle') {
    if (empty($_SESSION['admin_id'])) _pl_erro('Não autorizado.', 401);
    $id = (int)($body['id'] ?? 0); $v = (int)($body['ativo'] ?? 0);
    $pdo->prepare("UPDATE pre_lancamentos SET ativo=? WHERE id=?")->execute([$v, $id]);
    _pl_ok();
}

/* ================================================================
   POST admin: disparar e-mail de lançamento para toda a lista
   ================================================================ */
if ($metodo === 'POST' && $acao === 'disparar_lancamento') {
    if (empty($_SESSION['admin_id'])) _pl_erro('Não autorizado.', 401);
    $id = (int)($body['id'] ?? 0);
    if (!$id) _pl_erro('ID inválido.');
    try {
        $stC = $pdo->prepare("SELECT * FROM pre_lancamentos WHERE id=? LIMIT 1");
        $stC->execute([$id]);
        $camp = $stC->fetch(PDO::FETCH_ASSOC);
        if (!$camp) _pl_erro('Campanha não encontrada.', 404);

        // Buscar URL do livro (se slug existir na tabela livros)
        $urlLivro = SITE_URL . '/livros.html';
        try {
            $stL = $pdo->prepare("SELECT slug FROM livros WHERE titulo LIKE ? AND ativo=1 LIMIT 1");
            $stL->execute(['%' . $camp['titulo'] . '%']);
            $livroSlug = $stL->fetchColumn();
            if ($livroSlug) $urlLivro = SITE_URL . '/livros/' . $livroSlug . '.html';
        } catch (Throwable $e) {}

        // Leads que ainda não receberam o email de lançamento
        $stLeads = $pdo->prepare(
            "SELECT id, nome, email FROM pre_lancamento_leads
             WHERE lancamento_id=? AND lancamento_enviado=0"
        );
        $stLeads->execute([$id]);
        $leads = $stLeads->fetchAll(PDO::FETCH_ASSOC);

        if (empty($leads)) _pl_ok(['enviados' => 0, 'mensagem' => 'Todos já receberam o e-mail.']);

        $enviados = 0; $erros = 0;
        foreach ($leads as $lead) {
            $primeiroNome = explode(' ', trim($lead['nome'] ?: 'Leitor'))[0];
            $ok = Mailer::enviar([
                'para_email' => $lead['email'],
                'para_nome'  => $lead['nome'] ?: 'Leitor',
                'assunto'    => "[Chegou!] {$camp['titulo']} — disponível agora",
                'html'       => "
<div style='font-family:Georgia,serif;max-width:560px;margin:0 auto;padding:2rem;background:#FAF7F2;border-radius:8px'>
  <p style='font-family:Cinzel,serif;color:#B8860B;font-size:.7rem;letter-spacing:.2em;text-transform:uppercase;margin-bottom:1.5rem'>Lançamento · Lista Exclusiva</p>
  <h2 style='font-family:Georgia,serif;color:#2C2418;font-size:1.6rem;font-weight:400;margin-bottom:.5rem'>{$camp['titulo']}</h2>
  <p style='font-family:Georgia,serif;color:#5C4F3A;font-size:1.1rem;font-style:italic;margin-bottom:1.5rem'>Você estava esperando. Chegou o dia.</p>
  <p style='color:#5C4F3A;line-height:1.7'>Olá, <strong>{$primeiroNome}</strong>. Como prometido, você é o primeiro a saber: <em>{$camp['titulo']}</em> está disponível agora.</p>
  <div style='text-align:center;margin:2.5rem 0'>
    <a href='{$urlLivro}' style='background:#B8860B;color:#1A0F00;padding:.85rem 2.5rem;border-radius:6px;text-decoration:none;font-weight:700;font-family:Georgia,serif;font-size:1rem;display:inline-block'>
      Adquirir agora →
    </a>
  </div>
  <p style='color:#5C4F3A;line-height:1.7'>" . (mb_substr(strip_tags($camp['descricao'] ?? ''), 0, 300)) . "</p>
  <hr style='border-color:#E4DBC8;margin:1.5rem 0'>
  <p style='margin-top:1rem;color:#8C7D65;font-size:.85rem'>— Robério Diógenes</p>
  <p style='color:#B8A888;font-size:.75rem;margin-top:1rem'>Você recebe este e-mail por ter se inscrito na lista de espera de <em>{$camp['titulo']}</em>.</p>
</div>",
                'texto' => "Olá {$primeiroNome},\n\n{$camp['titulo']} chegou!\n\nAdquira agora: {$urlLivro}\n\n— Robério Diógenes",
            ]);
            if ($ok) {
                $pdo->prepare("UPDATE pre_lancamento_leads SET lancamento_enviado=1 WHERE id=?")->execute([$lead['id']]);
                $enviados++;
            } else { $erros++; }
        }

        // Marcar campanha como lançada
        $pdo->prepare("UPDATE pre_lancamentos SET lancado=1 WHERE id=?")->execute([$id]);

        _pl_ok([
            'enviados'  => $enviados,
            'erros'     => $erros,
            'mensagem'  => "E-mail de lançamento enviado para {$enviados} lead(s)!",
        ]);
    } catch (Throwable $e) { _pl_erro('Erro: '.$e->getMessage(), 500); }
}

_pl_erro('Ação inválida.', 405);
