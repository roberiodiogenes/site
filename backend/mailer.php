<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/mailer.php
   Serviço centralizado de envio de e-mails com PHPMailer.

   USO (nos outros backends):
     require_once __DIR__ . '/mailer.php';
     Mailer::enviar([
       'para_email'  => 'leitor@email.com',
       'para_nome'   => 'João',
       'assunto'     => 'Bem-vindo!',
       'html'        => '<h1>Olá, João!</h1>',
       'texto'       => 'Olá, João!',  // opcional — fallback plain text
     ]);

   CONFIGURAÇÃO:
     Preencha as constantes SMTP_* abaixo com as credenciais do
     seu serviço de e-mail (recomendamos SendGrid ou Zoho Mail).
     Na Hostgator, você pode usar o próprio servidor de e-mail da
     hospedagem (SMTP local) com SMTP_HOST = 'localhost'.
   ================================================================ */

/* ──────────────────────────────────────────────────────────────
   CREDENCIAIS SMTP
   ────────────────────────────────────────────────────────────── */
// Detecta ambiente (config.php já define a constante AMBIENTE)
if (!defined('AMBIENTE')) {
    define('AMBIENTE', isset($_SERVER['SERVER_NAME']) &&
        str_contains($_SERVER['SERVER_NAME'], 'localhost') ? 'local' : 'producao');
}

if (AMBIENTE === 'local') {
    /* Opção 1: Mailpit / MailHog (intercepta e-mails localmente sem enviar)
       Instale: https://mailpit.axllent.org
       Interface web: http://localhost:8025 */
    define('SMTP_HOST',       'localhost');
    define('SMTP_PORT',       1025);
    define('SMTP_USER',       '');
    define('SMTP_PASS',       '');
    define('SMTP_SEGURO',     '');   // '', 'ssl' ou 'tls'
    define('SMTP_MODO_DEBUG', 0);    // 0 = silencioso, 2 = verbose

    /* Opção 2: Comentar o bloco acima e descomentar abaixo para usar Gmail de teste
    define('SMTP_HOST',       'smtp.gmail.com');
    define('SMTP_PORT',       587);
    define('SMTP_USER',       'seu@gmail.com');
    define('SMTP_PASS',       'sua-senha-de-app-gmail');   // App Password, não a senha normal
    define('SMTP_SEGURO',     'tls');
    define('SMTP_MODO_DEBUG', 0);
    */
} else {
    /* Produção — PHP mail() nativo (sendmail do HostGator, sem autenticação SMTP) */
    define('SMTP_DRIVER',     'mail');
    define('SMTP_HOST',       '');
    define('SMTP_PORT',       25);
    define('SMTP_USER',       '');
    define('SMTP_PASS',       '');
    define('SMTP_SEGURO',     '');
    define('SMTP_MODO_DEBUG', 0);

    /* SMTP autenticado — reativar quando autenticação cPanel estiver funcionando
    define('SMTP_DRIVER',     'smtp');
    define('SMTP_HOST',       'mail.roberiodiogenes.com');
    define('SMTP_PORT',       465);
    define('SMTP_USER',       'contato@roberiodiogenes.com');
    define('SMTP_PASS',       '#pelopes1');
    define('SMTP_SEGURO',     'ssl');
    define('SMTP_MODO_DEBUG', 0);
    */

    /* Alternativa produção — SendGrid (alta entregabilidade, recomendado a longo prazo)
    define('SMTP_DRIVER',     'smtp');
    define('SMTP_HOST',       'smtp.sendgrid.net');
    define('SMTP_PORT',       587);
    define('SMTP_USER',       'apikey');
    define('SMTP_PASS',       'SUA_API_KEY_SENDGRID');
    define('SMTP_SEGURO',     'tls');
    define('SMTP_MODO_DEBUG', 0);
    */
}

/* Remetente padrão */
define('MAIL_FROM_EMAIL', 'contato@roberiodiogenes.com');
define('MAIL_FROM_NOME',  'Robério Diógenes');
define('MAIL_REPLY_TO',   'contato@roberiodiogenes.com');

/* ──────────────────────────────────────────────────────────────
   INSTALAÇÃO DO PHPMAILER
   Rode no terminal (dentro da pasta raiz do site):
     composer require phpmailer/phpmailer
   Isso cria vendor/autoload.php automaticamente.

   Se não tiver Composer na Hostgator, baixe os arquivos:
     https://github.com/PHPMailer/PHPMailer/releases
   E coloque em: backend/lib/PHPMailer/
   Então ajuste o require abaixo.
   ────────────────────────────────────────────────────────────── */
$autoloadComposer = __DIR__ . '/../../vendor/autoload.php';
$autoloadManual   = __DIR__ . '/lib/PHPMailer/src/';

if (file_exists($autoloadComposer)) {
    require_once $autoloadComposer;
} elseif (is_dir($autoloadManual)) {
    require_once $autoloadManual . 'Exception.php';
    require_once $autoloadManual . 'PHPMailer.php';
    require_once $autoloadManual . 'SMTP.php';
} else {
    // PHPMailer não instalado — define stub para não quebrar
    class Mailer {
        public static function enviar(array $dados): bool {
            error_log('[Mailer] PHPMailer não instalado. E-mail não enviado para: ' . ($dados['para_email'] ?? '?'));
            return false;
        }
        public static function enviarRecuperacaoSenha(string $email, string $nome, string $link): bool { return false; }
        public static function enviarBoasVindas(string $email, string $nome, string $token = ''): bool { return false; }
        public static function enviarConfirmacaoCompra(string $email, string $nome, string $titulo, float $preco): bool { return false; }
        public static function enviarConfirmacaoAssinatura(string $email, string $nome, string $plano, string $expira): bool { return false; }
        public static function enviarContato(array $dados): bool { return false; }
    }
    return; // Não executa o restante
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/* ──────────────────────────────────────────────────────────────
   CLASSE MAILER
   ────────────────────────────────────────────────────────────── */
class Mailer {

    /* ── Método base ── */
    public static function enviar(array $dados): bool {
        $mail = new PHPMailer(true);

        try {
            $driver = defined('SMTP_DRIVER') ? SMTP_DRIVER : 'smtp';

            if ($driver === 'mail') {
                // PHP mail() nativo — funciona em shared hosting sem configuração extra
                $mail->isMail();
            } else {
                // SMTP autenticado
                $mail->isSMTP();
                $mail->Host      = SMTP_HOST;
                $mail->Port      = SMTP_PORT;
                $mail->SMTPDebug = SMTP_MODO_DEBUG;

                if (SMTP_USER && SMTP_PASS) {
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                }

                if (SMTP_SEGURO) {
                    $mail->SMTPSecure = SMTP_SEGURO === 'ssl'
                        ? PHPMailer::ENCRYPTION_SMTPS
                        : PHPMailer::ENCRYPTION_STARTTLS;
                }
            }

            $mail->SMTPDebug = SMTP_MODO_DEBUG;
            $mail->CharSet   = 'UTF-8';
            $mail->Encoding  = 'base64';

            // Remetente
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NOME);
            $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NOME);

            // Destinatário
            $mail->addAddress(
                $dados['para_email'],
                $dados['para_nome'] ?? ''
            );

            // Conteúdo
            $mail->Subject = $dados['assunto'];
            $mail->isHTML(true);
            $mail->Body    = self::wrapTemplate($dados['html'] ?? '', $dados['assunto']);
            $mail->AltBody = $dados['texto'] ?? strip_tags($dados['html'] ?? '');

            $mail->send();
            return true;

        } catch (PHPMailerException $e) {
            error_log('[Mailer] Erro ao enviar para ' . ($dados['para_email'] ?? '?') . ': ' . $mail->ErrorInfo);
            return false;
        }
    }

    /* ────────────────────────────────────────────────────────────
       TEMPLATES DE E-MAIL
       ────────────────────────────────────────────────────────── */

    /** Recuperação de senha */
    public static function enviarRecuperacaoSenha(string $email, string $nome, string $link): bool {
        $primeiroNome = explode(' ', trim($nome))[0];
        return self::enviar([
            'para_email' => $email,
            'para_nome'  => $nome,
            'assunto'    => 'Redefinição de senha — Robério Diógenes',
            'html'       => "
                <p>Olá, <strong>{$primeiroNome}</strong>.</p>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
                <p style='text-align:center;margin:2rem 0'>
                  <a href='{$link}' class='btn-email'>Redefinir minha senha</a>
                </p>
                <p>Este link é válido por <strong>2 horas</strong>. Se você não solicitou, ignore este e-mail — sua senha não será alterada.</p>
            ",
            'texto' => "Olá {$primeiroNome},\n\nAcesse o link para redefinir sua senha:\n{$link}\n\nVálido por 2 horas.",
        ]);
    }

    /** Boas-vindas após cadastro — com presente (conto) e rastreamento */
    public static function enviarBoasVindas(string $email, string $nome, string $token = ''): bool {
        $primeiroNome = explode(' ', trim($nome))[0];
        $base         = defined('SITE_URL') ? SITE_URL : 'https://roberiodiogenes.com';

        // Links rastreados via tracker.php (também confirmam o e-mail ao clicar)
        $linkConto      = $token
            ? "{$base}/backend/tracker.php?token={$token}&acao=baixar_conto"
            : "{$base}/backend/tracker.php?acao=baixar_conto";
        $linkBiblioteca = $token
            ? "{$base}/backend/tracker.php?token={$token}&acao=visitar_biblioteca"
            : "{$base}/livros.html";

        $html = "
<p style='font-size:1rem;line-height:1.75;margin:0 0 1.25em'>
  Olá, <strong style='color:#2C2418'>{$primeiroNome}</strong>.
</p>
<p style='font-size:1rem;line-height:1.75;margin:0 0 1.25em'>
  Sua conta foi criada. Fico feliz em tê-lo aqui.
</p>
<p style='font-size:1rem;line-height:1.75;margin:0 0 1.75em'>
  Para começar, preparei um presente para você: um conto inédito, disponível agora mesmo.
</p>

<!-- Card do conto -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin:0 0 2em'>
  <tr>
    <td style='background:#F5F0E8;border-radius:10px;padding:1.75rem 1.5rem;border-left:4px solid #B8860B'>
      <p style='font-size:.65rem;letter-spacing:.22em;text-transform:uppercase;color:#8C7D65;margin:0 0 .5em'>
        Seu presente de boas-vindas
      </p>
      <!-- Capa do conto -->
      <div style='text-align:center;margin-bottom:1.25em'>
        <img src='{$base}/img/contos/o-colecionador-de-paginas.jpg'
             alt='O Colecionador de Páginas — conto de Robério Diógenes'
             width='180'
             style='width:180px;max-width:100%;height:auto;border-radius:6px;display:block;margin:0 auto;box-shadow:0 4px 16px rgba(0,0,0,.15)'>
      </div>
      <p style='font-family:Georgia,\"Times New Roman\",serif;font-size:1.3rem;color:#2C2418;font-weight:bold;margin:0 0 .35em;line-height:1.3'>
        O Colecionador de Páginas
      </p>
      <p style='font-size:.8rem;color:#8C7D65;margin:0 0 1.25em;font-style:italic'>
        Um conto de Robério Diógenes
      </p>
      <p style='font-size:.88rem;color:#4A3728;line-height:1.65;margin:0 0 1.5em'>
        Um homem que passa a vida inteira guardando livros que nunca leu.
        Até que chega o dia em que precisa escolher um para abrir.
      </p>
      <table cellpadding='0' cellspacing='0'>
        <tr>
          <td style='padding-right:.75rem'>
            <a href='{$linkConto}'
               style='display:inline-block;background:#B8860B;color:#1A0F00 !important;padding:.65rem 1.4rem;border-radius:5px;text-decoration:none;font-size:.78rem;letter-spacing:.09em;text-transform:uppercase;font-family:Arial,sans-serif;font-weight:700'>
              Baixar o conto →
            </a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<hr style='border:none;border-top:1px solid #E8E0D0;margin:0 0 1.75em'>

<p style='font-size:1rem;line-height:1.75;margin:0 0 1em;color:#4A3728'>
  Quando terminar, a biblioteca completa está disponível para você explorar.
</p>

<table width='100%' cellpadding='0' cellspacing='0' style='margin:0 0 2em'>
  <tr>
    <td align='center'>
      <a href='{$linkBiblioteca}'
         style='display:inline-block;background:transparent;color:#B8860B !important;padding:.65rem 1.6rem;border-radius:5px;border:2px solid #B8860B;text-decoration:none;font-size:.78rem;letter-spacing:.09em;text-transform:uppercase;font-family:Arial,sans-serif;font-weight:700'>
        Explorar a biblioteca
      </a>
    </td>
  </tr>
</table>

<p style='font-size:.82rem;color:#8C7D65;line-height:1.6;margin:0'>
  Se tiver qualquer dúvida, basta responder este e-mail. Estou sempre por aqui.
</p>
";

        return self::enviar([
            'para_email' => $email,
            'para_nome'  => $nome,
            'assunto'    => 'Um presente para você — Robério Diógenes',
            'html'       => $html,
            'texto'      => "Olá {$primeiroNome},\n\nSua conta foi criada com sucesso!\n\nSeu presente: o conto \"O Colecionador de Páginas\" está disponível em:\n{$linkConto}\n\nExplore a biblioteca em:\n{$linkBiblioteca}\n\nRobério Diógenes",
        ]);
    }

    /** Confirmação de compra avulsa */
    public static function enviarConfirmacaoCompra(string $email, string $nome, string $titulo, float $preco): bool {
        $primeiroNome = explode(' ', trim($nome))[0];
        $precoFmt     = 'R$ ' . number_format($preco, 2, ',', '.');
        return self::enviar([
            'para_email' => $email,
            'para_nome'  => $nome,
            'assunto'    => "Compra confirmada: {$titulo}",
            'html'       => "
                <p>Olá, <strong>{$primeiroNome}</strong>.</p>
                <p>Sua compra foi confirmada. ✓</p>
                <table style='width:100%;border-collapse:collapse;margin:1.5rem 0'>
                  <tr><td style='padding:.5rem 0;color:#888'>Livro</td><td style='padding:.5rem 0;font-weight:bold'>{$titulo}</td></tr>
                  <tr><td style='padding:.5rem 0;color:#888'>Valor</td><td style='padding:.5rem 0'>{$precoFmt}</td></tr>
                  <tr><td style='padding:.5rem 0;color:#888'>Acesso</td><td style='padding:.5rem 0'>Vitalício — disponível no leitor online</td></tr>
                </table>
                <p style='text-align:center;margin:2rem 0'>
                  <a href='" . SITE_URL . "/leitor/livro.php?livro=lumen' class='btn-email'>Começar a ler agora</a>
                </p>
            ",
            'texto' => "Olá {$primeiroNome},\n\nSua compra de \"{$titulo}\" ({$precoFmt}) foi confirmada.\n\nAcesse o leitor: " . SITE_URL . "/leitor/",
        ]);
    }

    /** Confirmação de assinatura */
    public static function enviarConfirmacaoAssinatura(string $email, string $nome, string $plano, string $expira): bool {
        $primeiroNome = explode(' ', trim($nome))[0];
        return self::enviar([
            'para_email' => $email,
            'para_nome'  => $nome,
            'assunto'    => "Assinatura ativada — {$plano}",
            'html'       => "
                <p>Olá, <strong>{$primeiroNome}</strong>.</p>
                <p>Sua assinatura foi ativada com sucesso! Toda a biblioteca está disponível para você.</p>
                <table style='width:100%;border-collapse:collapse;margin:1.5rem 0'>
                  <tr><td style='padding:.5rem 0;color:#888'>Plano</td><td style='padding:.5rem 0;font-weight:bold'>{$plano}</td></tr>
                  <tr><td style='padding:.5rem 0;color:#888'>Válido até</td><td style='padding:.5rem 0'>{$expira}</td></tr>
                  <tr><td style='padding:.5rem 0;color:#888'>Acesso</td><td style='padding:.5rem 0'>Todos os livros da biblioteca</td></tr>
                </table>
                <p style='text-align:center;margin:2rem 0'>
                  <a href='" . SITE_URL . "/leitor/' class='btn-email'>Ir para a biblioteca</a>
                </p>
            ",
            'texto' => "Olá {$primeiroNome},\n\nSua assinatura ({$plano}) foi ativada e é válida até {$expira}.\n\nAcesse: " . SITE_URL . "/leitor/",
        ]);
    }

    /** Aviso de assinatura próxima do vencimento */
    public static function enviarAvisoVencimento(string $email, string $nome, string $plano, string $expira, int $diasRestantes): bool {
        $primeiroNome = explode(' ', trim($nome))[0];
        return self::enviar([
            'para_email' => $email,
            'para_nome'  => $nome,
            'assunto'    => "Sua assinatura vence em {$diasRestantes} dias",
            'html'       => "
                <p>Olá, <strong>{$primeiroNome}</strong>.</p>
                <p>Sua assinatura <strong>{$plano}</strong> vence em <strong>{$diasRestantes} dias</strong> ({$expira}).</p>
                <p>Renove agora para continuar com acesso a todos os livros sem interrupção.</p>
                <p style='text-align:center;margin:2rem 0'>
                  <a href='" . SITE_URL . "/pagamento/assinatura.html' class='btn-email'>Renovar minha assinatura</a>
                </p>
            ",
            'texto' => "Olá {$primeiroNome},\n\nSua assinatura ({$plano}) vence em {$diasRestantes} dias ({$expira}).\n\nRenove em: " . SITE_URL . "/pagamento/assinatura.html",
        ]);
    }

    /** Lembrete de carrinho abandonado */
    public static function enviarLembreteCarrinho(string $email, string $nome, array $titulos, string $totalFmt): bool {
        $primeiroNome = explode(' ', trim($nome))[0];
        $listaHtml    = implode('', array_map(
            fn($t) => "<li style='margin-bottom:6px;color:#2C2418'>📖 " . htmlspecialchars($t, ENT_QUOTES) . "</li>",
            $titulos
        ));
        return self::enviar([
            'para_email' => $email,
            'para_nome'  => $nome,
            'assunto'    => 'Você deixou algo para trás — Robério Diógenes',
            'html'       => "
<p>Olá, <strong>{$primeiroNome}</strong>.</p>
<p>Você adicionou livros ao carrinho e ainda não concluiu a compra. Suas histórias estão esperando:</p>
<ul style='background:#F5F0E8;border-radius:8px;padding:1rem 1rem 1rem 2rem;margin:1.25rem 0;line-height:1.9'>
  {$listaHtml}
</ul>
<p>Total do pedido: <strong style='color:#B8860B'>{$totalFmt}</strong></p>
<p style='text-align:center;margin:2rem 0'>
  <a href='" . SITE_URL . "/carrinho.html' class='btn-email'>Finalizar minha compra →</a>
</p>
<p style='font-size:.82rem;color:#8C7D65'>
  Se não quiser mais receber estes lembretes,
  <a href='" . SITE_URL . "/contato.html' style='color:#8C7D65'>entre em contato</a>.
</p>
",
            'texto' => "Olá {$primeiroNome},\n\nVocê deixou itens no carrinho ({$totalFmt}).\nFinalize sua compra em: " . SITE_URL . "/carrinho.html",
        ]);
    }

    /** Reencaminha mensagem do formulário de contato */
    public static function enviarContato(array $dados): bool {
        $nome    = htmlspecialchars($dados['nome']    ?? 'Visitante', ENT_QUOTES);
        $email   = htmlspecialchars($dados['email']   ?? '',           ENT_QUOTES);
        $assunto = htmlspecialchars($dados['assunto'] ?? 'Contato',    ENT_QUOTES);
        $msg     = nl2br(htmlspecialchars($dados['mensagem'] ?? '', ENT_QUOTES));

        return self::enviar([
            'para_email' => MAIL_FROM_EMAIL,
            'para_nome'  => MAIL_FROM_NOME,
            'assunto'    => "Contato via site: {$assunto}",
            'html'       => "
                <p><strong>Nome:</strong> {$nome}</p>
                <p><strong>E-mail:</strong> <a href='mailto:{$email}'>{$email}</a></p>
                <p><strong>Assunto:</strong> {$assunto}</p>
                <hr style='border:none;border-top:1px solid #eee;margin:1rem 0'>
                <p>{$msg}</p>
            ",
            'texto' => "De: {$nome} <{$email}>\nAssunto: {$assunto}\n\n" . ($dados['mensagem'] ?? ''),
        ]);
    }

    /** Certificado de Leitor Embaixador */
    public static function enviarCertificadoEmbaixador(string $email, string $genero, int $numero): bool {
        $base = defined('SITE_URL') ? SITE_URL : 'https://roberiodiogenes.com';

        $mapa = [
            'drama'     => ['label' => 'Drama',     'cor' => '#6B7FA3', 'icone' => '✦'],
            'romance'   => ['label' => 'Romance',   'cor' => '#B07A8A', 'icone' => '✉'],
            'terror'    => ['label' => 'Terror',    'cor' => '#8A3A3A', 'icone' => '◈'],
            'autoajuda' => ['label' => 'Autoajuda', 'cor' => '#8A7340', 'icone' => '◎'],
        ];
        $g      = $mapa[$genero] ?? ['label' => 'Ficção', 'cor' => '#B8860B', 'icone' => '✦'];
        $numFmt = $numero > 0 ? '#' . str_pad((string)$numero, 4, '0', STR_PAD_LEFT) : '';
        $numMsg = $numFmt ? "Você é o Leitor Embaixador <strong style='color:{$g['cor']}'>{$numFmt}</strong>." : '';

        return self::enviar([
            'para_email' => $email,
            'para_nome'  => '',
            'assunto'    => 'Certificado de Leitor Embaixador — Robério Diógenes',
            'html'       => "
<!-- Badge visual CSS (iframes e canvas não funcionam em e-mail) -->
<div style='text-align:center;margin-bottom:1.75rem'>
  <div style='display:inline-block;width:148px;height:148px;border-radius:50%;
              background:#12090c;border:3px solid {$g['cor']};
              vertical-align:middle;padding:18px 0;box-sizing:border-box'>
    <div style='font-size:30px;line-height:1;margin-bottom:6px'>{$g['icone']}</div>
    <div style='font-size:7.5px;letter-spacing:.22em;color:{$g['cor']};font-family:Arial,sans-serif;text-transform:uppercase'>LEITOR</div>
    <div style='font-size:19px;font-family:Georgia,serif;font-style:italic;color:#d6c8ae;margin:3px 0'>Embaixador</div>
    " . ($numFmt ? "<div style='font-size:11px;color:{$g['cor']};font-family:Arial,sans-serif;font-weight:bold;letter-spacing:.05em'>{$numFmt}</div>" : '') . "
    <div style='font-size:7px;color:{$g['cor']};font-family:Arial,sans-serif;margin-top:5px'>✦ &nbsp; ✦ &nbsp; ✦</div>
  </div>
</div>

<p style='font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:#8C7D65;text-align:center;margin-bottom:1.5rem'>
  Certificado · {$g['icone']} {$g['label']}
</p>

<p>{$numMsg}</p>

<p>Você leu um conto, sentiu algo, e decidiu compartilhar.<br>
Esse gesto fez uma história chegar a alguém que talvez precisasse dela naquele exato momento.</p>

<p style='background:#F5F0E8;border-left:3px solid {$g['cor']};padding:1rem 1.25rem;
          border-radius:0 6px 6px 0;font-style:italic;color:#4A3728;line-height:1.8'>
  Cada história precisa de alguém que a leve adiante.<br>
  Você foi esse alguém.
</p>

<p>Se quiser conhecer mais histórias, a biblioteca está esperando por você:</p>
<p style='text-align:center;margin:1.5rem 0'>
  <a href='{$base}/bio.html' class='btn-email'>Ler os contos →</a>
</p>

<p style='margin-top:2rem;font-family:Georgia,serif;font-size:.95rem;color:#4A3728'>
  Com respeito e afeto,
</p>
<p style='font-family:Georgia,serif;font-size:1.15rem;color:#B8860B;font-style:italic;margin:.25rem 0'>
  Robério Diógenes
</p>
<p style='font-size:.78rem;color:#8C7D65'>Fortaleza, Ceará</p>",
            'texto' => "Certificado de Leitor Embaixador" . ($numFmt ? " — {$numFmt}" : '') . "\n\nVocê leu um conto, sentiu algo, e decidiu compartilhar. Esse gesto fez uma história chegar a alguém que talvez precisasse dela.\n\nCada história precisa de alguém que a leve adiante. Você foi esse alguém.\n\nCom respeito e afeto,\nRobério Diógenes\n\n{$base}/bio.html",
        ]);
    }

    /* ──────────────────────────────────────────────────────────
       TEMPLATE HTML BASE
       Envolve todos os e-mails com um layout consistente.
       ────────────────────────────────────────────────────────── */
    private static function wrapTemplate(string $conteudo, string $assunto): string {
        $ano = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width">
  <title>{$assunto}</title>
  <style>
    body { margin:0; padding:0; background:#F5F0E8; font-family:Georgia,'Times New Roman',serif; color:#2C2418; }
    .wrapper { max-width:600px; margin:0 auto; padding:2rem 1rem; }
    .card { background:#FFFFFF; border-radius:8px; padding:2.5rem 2rem; border-top:4px solid #B8860B; }
    .logo { font-family:Georgia,serif; font-size:1.5rem; color:#B8860B; text-align:center; margin-bottom:1.5rem; letter-spacing:0.05em; }
    .logo small { display:block; font-size:0.6rem; letter-spacing:0.3em; text-transform:uppercase; color:#8C7D65; margin-top:0.25rem; }
    p { line-height:1.7; margin:0 0 1em; font-size:0.95rem; }
    strong { color:#2C2418; }
    .btn-email { display:inline-block; background:#B8860B; color:#1A0F00 !important; padding:.75rem 1.75rem; border-radius:6px; text-decoration:none; font-size:.85rem; letter-spacing:.08em; text-transform:uppercase; font-family:Arial,sans-serif; font-weight:700; }
    .rodape { text-align:center; font-size:.75rem; color:#8C7D65; margin-top:1.5rem; line-height:1.6; }
    .rodape a { color:#8C7D65; }
    hr { border:none; border-top:1px solid #E8E0D0; margin:1.5rem 0; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="card">
      <div class="logo">
        Robério Diógenes
        <small>Literatura brasileira</small>
      </div>
      {$conteudo}
    </div>
    <div class="rodape">
      <p>© {$ano} Robério Diógenes · <a href="https://roberiodiogenes.com">roberiodiogenes.com</a></p>
      <p>Você está recebendo este e-mail porque possui uma conta no site.</p>
    </div>
  </div>
</body>
</html>
HTML;
    }
}
