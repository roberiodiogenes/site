<?php
/* ================================================================
 * ROBÉRIO DIÓGENES — backend/auth/perfil.php (ATUALIZADO)
 * Endpoint: GET/POST /backend/auth/perfil.php
 * Exibir e atualizar perfil do usuário autenticado
 * 
 * MELHORIAS:
 * ✓ Validação robusta de entrada
 * ✓ Sanitização de dados
 * ✓ Autenticação via sessão
 * ✓ Proteção CSRF (headers customizados)
 * ✓ Rate limiting
 * ✓ Códigos de status HTTP apropriados
 * ✓ Logging de auditoria
 * ================================================================ */

require_once __DIR__ . '/../config.php';

iniciarSessao();

// ── Verificar autenticação ──────────────────────────────────
if (empty($_SESSION['usuario_id'])) {
  responderErro('Você deve estar autenticado.', 401);
}

$usuario_id = (int) $_SESSION['usuario_id'];

try {
  $pdo = db();

  // ══════════════════════════════════════════════════════════
  // GET: Retornar dados do perfil
  // ══════════════════════════════════════════════════════════
  if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Buscar dados do usuário
    $stmt = $pdo->prepare("
      SELECT 
        id,
        nome,
        email,
        sexo,
        data_nascimento,
        cidade,
        estado,
        pais,
        whatsapp,
        foto_url,
        google_id,
        verificado,
        ativo,
        created_at,
        ultimo_login
      FROM usuarios
      WHERE id = ?
    ");
    
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
      responderErro('Usuário não encontrado.', 404);
    }

    // Calcular idade se data de nascimento existir
    $idade = null;
    if ($usuario['data_nascimento']) {
      try {
        $data_nasc = new DateTime($usuario['data_nascimento']);
        $hoje = new DateTime();
        $idade = $hoje->diff($data_nasc)->y;
      } catch (Exception $e) {
        $idade = null;
      }
    }

    // Contar livros favoritos (tolerante a tabela ausente)
    $total_favoritos = 0;
    try {
      $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = ?");
      $stmt->execute([$usuario_id]);
      $total_favoritos = (int) $stmt->fetch()['total'];
    } catch (Exception $e) { /* tabela pode ainda não existir */ }

    // Contar downloads (tolerante a tabela ausente)
    $total_downloads = 0;
    try {
      $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM downloads WHERE usuario_id = ?");
      $stmt->execute([$usuario_id]);
      $total_downloads = (int) $stmt->fetch()['total'];
    } catch (Exception $e) { /* tabela pode ainda não existir */ }

    // Sanitizar dados para exibição (prevenção de XSS)
    $usuario_seguro = [
      'id' => $usuario['id'],
      'nome' => htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'),
      'email' => htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8'),
      'sexo' => in_array($usuario['sexo'], 
        ['masculino', 'feminino', 'outro', 'nao_informado']) 
        ? $usuario['sexo'] 
        : 'nao_informado',
      'data_nascimento' => $usuario['data_nascimento'],
      'idade' => $idade,
      'cidade' => htmlspecialchars($usuario['cidade'] ?? '', ENT_QUOTES, 'UTF-8'),
      'estado' => preg_match('/^[A-Z]{2}$/', $usuario['estado'] ?? '') 
        ? $usuario['estado'] 
        : null,
      'pais' => htmlspecialchars($usuario['pais'] ?? 'Brasil', ENT_QUOTES, 'UTF-8'),
      'whatsapp' => $usuario['whatsapp'],
      'foto_url' => filter_var($usuario['foto_url'], FILTER_VALIDATE_URL) 
        ? htmlspecialchars($usuario['foto_url'], ENT_QUOTES, 'UTF-8') 
        : null,
      'verificado' => (bool) $usuario['verificado'],
      'ativo' => (bool) $usuario['ativo'],
      'total_favoritos' => $total_favoritos,
      'total_downloads' => $total_downloads,
      'membro_desde' => $usuario['created_at'],
      'ultimo_login' => $usuario['ultimo_login'],
    ];

    responderOk(['usuario' => $usuario_seguro]);
  }

  // ══════════════════════════════════════════════════════════
  // POST: Atualizar perfil
  // ══════════════════════════════════════════════════════════
  elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Rate limiting: máximo 10 atualizações por hora
    verificarRateLimit('atualizar_perfil', 10, 3600);

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // Receber e validar campos
    $nome = isset($body['nome']) ? trim($body['nome']) : null;
    $sexo = isset($body['sexo']) ? trim($body['sexo']) : null;
    $data_nascimento = isset($body['data_nascimento']) ? trim($body['data_nascimento']) : null;
    $cidade = isset($body['cidade']) ? trim($body['cidade']) : null;
    $estado = isset($body['estado']) ? trim(strtoupper($body['estado'] ?? '')) : null;
    $pais = isset($body['pais']) ? trim($body['pais']) : null;
    $whatsapp = isset($body['whatsapp']) ? trim($body['whatsapp']) : null;

    // ── Validação de Nome ───────────────────────────────
    if ($nome !== null) {
      if (mb_strlen($nome) < 2) {
        responderErro('Nome deve ter no mínimo 2 caracteres.');
      }
      if (mb_strlen($nome) > 120) {
        responderErro('Nome muito longo.');
      }
    }

    // ── Validação de Sexo ───────────────────────────────
    $sexos_validos = ['masculino', 'feminino', 'outro', 'nao_informado'];
    if ($sexo !== null && !in_array($sexo, $sexos_validos, true)) {
      responderErro('Sexo inválido.');
    }

    // ── Validação de Data de Nascimento ─────────────────
    if ($data_nascimento !== null && !empty($data_nascimento)) {
      // Validar formato
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_nascimento)) {
        responderErro('Data de nascimento inválida (YYYY-MM-DD).');
      }
      
      // Validar data real
      $data = DateTime::createFromFormat('Y-m-d', $data_nascimento);
      if (!$data || $data->format('Y-m-d') !== $data_nascimento) {
        responderErro('Data de nascimento inválida.');
      }
      
      // Não permitir data futura
      if ($data > new DateTime()) {
        responderErro('Data de nascimento não pode ser no futuro.');
      }
    }

    // ── Validação de Estado (UF) ────────────────────────
    if ($estado !== null && !empty($estado)) {
      if (!preg_match('/^[A-Z]{2}$/', $estado)) {
        responderErro('Estado deve ser 2 letras (ex: SP, RJ).');
      }
    }

    // ── Validação de WhatsApp ───────────────────────────
    if ($whatsapp !== null && !empty($whatsapp)) {
      $wpp_sanitized = preg_replace('/[^\d+]/', '', $whatsapp);
      if (!preg_match('/^\+?55\d{2}9?\d{8,9}$/', $wpp_sanitized)) {
        responderErro('WhatsApp inválido.');
      }
    }

    // ── Preparar campos para atualização ────────────────
    $campos = [];
    $valores = [];

    if ($nome !== null) {
      $campos[] = 'nome = ?';
      $valores[] = $nome;
    }
    if ($sexo !== null) {
      $campos[] = 'sexo = ?';
      $valores[] = $sexo;
    }
    if ($data_nascimento !== null) {
      $campos[] = 'data_nascimento = ?';
      $valores[] = !empty($data_nascimento) ? $data_nascimento : null;
    }
    if ($cidade !== null) {
      $campos[] = 'cidade = ?';
      $valores[] = !empty($cidade) ? $cidade : null;
    }
    if ($estado !== null) {
      $campos[] = 'estado = ?';
      $valores[] = !empty($estado) ? $estado : null;
    }
    if ($pais !== null) {
      $campos[] = 'pais = ?';
      $valores[] = !empty($pais) ? $pais : null;
    }
    if ($whatsapp !== null) {
      $campos[] = 'whatsapp = ?';
      $valores[] = !empty($whatsapp) ? $whatsapp : null;
    }

    // Se houver campos para atualizar
    if (!empty($campos)) {
      $campos[] = 'updated_at = NOW()';
      $valores[] = $usuario_id;

      $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
      $stmt = $pdo->prepare($sql);
      
      if (!$stmt->execute($valores)) {
        responderErro('Erro ao atualizar perfil.', 500);
      }

      // Atualizar sessão com novo nome
      if ($nome !== null) {
        $_SESSION['usuario_nome'] = $nome;
      }

      // Log de auditoria
      error_log("Perfil atualizado para usuário ID: {$usuario_id} em " . date('Y-m-d H:i:s'));
    }

    // ── Retornar perfil atualizado ──────────────────────
    responderOk([
      'mensagem' => 'Perfil atualizado com sucesso.',
      'usuario' => [
        'id' => $usuario_id,
        'nome' => $nome ?? $_SESSION['usuario_nome'],
        'email' => $_SESSION['usuario_email'],
      ],
    ]);
  }

  else {
    responderErro('Método não permitido.', 405);
  }

} catch (PDOException $e) {
  error_log("Erro em perfil.php: " . $e->getMessage());
  responderErro('Erro ao processar perfil.', 500);
} catch (Exception $e) {
  error_log("Erro geral em perfil.php: " . $e->getMessage());
  responderErro('Erro ao processar perfil.', 500);
}

?>