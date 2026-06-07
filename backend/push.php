<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/push.php
   Wrapper para envio de notificações push via OneSignal REST API.

   ┌─ CONFIGURAÇÃO OBRIGATÓRIA ──────────────────────────────────┐
   │ Preencha as constantes abaixo com os dados do OneSignal.    │
   │ Painel OneSignal → Settings → Keys & IDs                    │
   └─────────────────────────────────────────────────────────────┘

   Uso interno (chamado pelo admin e pelo blog publish):
     PushNotification::enviar([...])

   POST direto (admin com sessão autenticada):
     { titulo, mensagem, url, segmento, imagem }
   ================================================================ */

/* ── Credenciais OneSignal ──────────────────────────────────── */
define('ONESIGNAL_APP_ID',       'SEU_ONESIGNAL_APP_ID');       // ← preencher
define('ONESIGNAL_REST_API_KEY', 'SUA_ONESIGNAL_REST_API_KEY'); // ← preencher
define('ONESIGNAL_API_URL',      'https://onesignal.com/api/v1/notifications');

/* ── Segmentos disponíveis (espelham as tags do JS) ─────────── */
define('PUSH_SEGS', [
    'todos'          => 'Subscribed Users',
    'blog'           => 'contexto=blog',
    'leitor'         => 'contexto=leitor',
    'home'           => 'contexto=home',
    'livro'          => 'contexto=livro',
    'pre_lancamento' => 'contexto=pre_lancamento',
]);

/* ================================================================
   CLASSE PushNotification
   ================================================================ */
class PushNotification {

    /**
     * Envia uma notificação push via OneSignal.
     *
     * @param array $dados {
     *   titulo   string   Título da notificação
     *   mensagem string   Corpo do texto
     *   url      string   URL de destino ao clicar
     *   imagem   string   URL da imagem (opcional — recomendado: capa do livro)
     *   icone    string   URL do ícone (opcional)
     *   segmento string   Chave de PUSH_SEGS ou 'todos' (default)
     *   filtros  array    Filtros avançados do OneSignal (opcional)
     * }
     * @return array { ok: bool, id?: string, erro?: string, total?: int }
     */
    public static function enviar(array $dados): array {
        if (!defined('ONESIGNAL_APP_ID') || ONESIGNAL_APP_ID === 'SEU_ONESIGNAL_APP_ID') {
            return ['ok' => false, 'erro' => 'OneSignal não configurado. Preencha as constantes em backend/push.php.'];
        }

        $titulo   = mb_substr(trim($dados['titulo']   ?? ''), 0, 100, 'UTF-8');
        $mensagem = mb_substr(trim($dados['mensagem'] ?? ''), 0, 200, 'UTF-8');
        $url      = trim($dados['url']     ?? SITE_URL);
        $segKey   = trim($dados['segmento'] ?? 'todos');

        if (!$titulo || !$mensagem) {
            return ['ok' => false, 'erro' => 'Título e mensagem obrigatórios.'];
        }

        /* Montar payload */
        $payload = [
            'app_id'   => ONESIGNAL_APP_ID,
            'headings' => ['pt' => $titulo,   'en' => $titulo],
            'contents' => ['pt' => $mensagem, 'en' => $mensagem],
            'url'      => $url,
        ];

        /* Segmentação */
        if (!empty($dados['filtros']) && is_array($dados['filtros'])) {
            /* Filtros avançados por tag */
            $payload['filters'] = $dados['filtros'];
        } else {
            /* Segmento nomeado */
            $segNome = PUSH_SEGS[$segKey] ?? 'Subscribed Users';
            if (str_contains($segNome, '=')) {
                /* Tag filter: "contexto=leitor" → filter por tag */
                [$tagKey, $tagVal] = explode('=', $segNome, 2);
                $payload['filters'] = [
                    ['field' => 'tag', 'key' => $tagKey, 'relation' => '=', 'value' => $tagVal],
                ];
            } else {
                $payload['included_segments'] = [$segNome];
            }
        }

        /* Imagem e ícone opcionais */
        if (!empty($dados['imagem'])) {
            $payload['big_picture'] = $dados['imagem']; // Android
            $payload['chrome_web_image'] = $dados['imagem']; // Chrome desktop
        }
        if (!empty($dados['icone'])) {
            $payload['chrome_web_icon'] = $dados['icone'];
            $payload['firefox_icon']    = $dados['icone'];
        } else {
            /* Ícone padrão: favicon do site */
            $payload['chrome_web_icon'] = SITE_URL . '/img/favicon.png';
        }

        /* Enviar via cURL */
        return self::_chamarAPI($payload);
    }

    /**
     * Busca estatísticas de subscribers no OneSignal.
     */
    public static function stats(): array {
        if (ONESIGNAL_APP_ID === 'SEU_ONESIGNAL_APP_ID') {
            return ['ok' => false, 'erro' => 'OneSignal não configurado.'];
        }

        $ch = curl_init('https://onesignal.com/api/v1/apps/' . ONESIGNAL_APP_ID);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . ONESIGNAL_REST_API_KEY,
            ],
            CURLOPT_TIMEOUT        => 8,
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) return ['ok' => false, 'erro' => $err];
        $json = json_decode($res, true);
        return [
            'ok'          => true,
            'subscribers' => (int)($json['players'] ?? 0),
            'name'        => $json['name'] ?? '',
        ];
    }

    /** Chamada HTTP à API OneSignal */
    private static function _chamarAPI(array $payload): array {
        $ch = curl_init(ONESIGNAL_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . ONESIGNAL_REST_API_KEY,
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err)          return ['ok' => false, 'erro' => 'cURL: ' . $err];
        if ($http >= 500)  return ['ok' => false, 'erro' => "OneSignal HTTP {$http}"];

        $json = json_decode($res, true);
        if (!empty($json['errors'])) {
            return ['ok' => false, 'erro' => implode('; ', (array)$json['errors'])];
        }

        return [
            'ok'    => true,
            'id'    => $json['id']       ?? null,
            'total' => (int)($json['recipients'] ?? 0),
        ];
    }
}

/* ── Endpoint HTTP para o painel admin ──────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Não responde a GET direto — só usado como include ou POST admin
    http_response_code(405);
    exit;
}

/* Verificar sessão admin */
if (!defined('AMBIENTE')) require_once __DIR__ . '/config.php';
iniciarSessao();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autenticado.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$acao = trim($body['acao'] ?? '');

if ($acao === 'enviar') {
    $resultado = PushNotification::enviar([
        'titulo'   => $body['titulo']   ?? '',
        'mensagem' => $body['mensagem'] ?? '',
        'url'      => $body['url']      ?? SITE_URL,
        'segmento' => $body['segmento'] ?? 'todos',
        'imagem'   => $body['imagem']   ?? '',
    ]);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($acao === 'stats') {
    echo json_encode(PushNotification::stats(), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['ok' => false, 'erro' => 'Ação inválida.']);
