<?php
/* ================================================================
   TESTE DE E-MAIL — Robério Diógenes
   ⚠ USE SOMENTE PARA DIAGNÓSTICO. APAGUE DEPOIS.
   Acesse: https://roberiodiogenes.com/backend/teste_email.php
   ================================================================ */

// Proteção básica: só Robério pode rodar isso
define('ACESSO_DIRETO', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE DE E-MAIL ===\n\n";
echo "Ambiente : " . AMBIENTE . "\n";
echo "SMTP Host: " . SMTP_HOST . "\n";
echo "SMTP Port: " . SMTP_PORT . "\n";
echo "SMTP User: " . SMTP_USER . "\n";
echo "SMTP Pass: " . (SMTP_PASS ? str_repeat('*', strlen(SMTP_PASS)) : '(vazio)') . "\n";
echo "Segurança: " . (SMTP_SEGURO ?: '(nenhuma)') . "\n";

// Verifica se PHPMailer carregou (ou se está usando stub)
$usandoStub = !class_exists('PHPMailer\PHPMailer\PHPMailer');
echo "PHPMailer: " . ($usandoStub ? "NÃO carregado (usando stub silencioso)" : "CARREGADO") . "\n\n";

if ($usandoStub) {
    echo "PROBLEMA: PHPMailer não encontrado.\n";
    echo "Certifique-se de que backend/lib/PHPMailer/src/ existe no servidor.\n";
    exit;
}

echo "--- Tentando enviar e-mail de teste... ---\n\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

$mail = new PHPMailer(true);
$mail->SMTPDebug = SMTP::DEBUG_SERVER; // Mostra todo o diálogo SMTP

ob_start();

try {
    if (defined('SMTP_DRIVER') && SMTP_DRIVER === 'mail') {
        $mail->isMail();
    } else {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPDebug  = SMTP::DEBUG_SERVER;

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

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NOME);
    $mail->addAddress(MAIL_FROM_EMAIL, MAIL_FROM_NOME); // Envia para você mesmo
    $mail->Subject = '[TESTE] E-mail do site funcionando';
    $mail->isHTML(true);
    $mail->Body    = '<p>Este é um <strong>e-mail de teste</strong> do site roberiodiogenes.com.</p>';
    $mail->AltBody = 'Este é um e-mail de teste do site roberiodiogenes.com.';

    $mail->send();

    $debugOutput = ob_get_clean();
    echo "RESULTADO: SUCESSO — e-mail enviado!\n\n";
    echo "=== DIÁLOGO SMTP ===\n";
    echo $debugOutput;

} catch (PHPMailerException $e) {
    $debugOutput = ob_get_clean();
    echo "RESULTADO: FALHOU\n";
    echo "Erro: " . $mail->ErrorInfo . "\n\n";
    echo "=== DIÁLOGO SMTP ===\n";
    echo $debugOutput;
}
