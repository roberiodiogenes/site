<?php
/* ================================================================
   ROBÉRIO DIÓGENES — backend/bio-lead.php
   Captura e-mails do Baú do Escritor e envia e-mail de confirmação.

   POST { email, tipo, genero, sessao_id }
   tipo: 'contos-ineditos' | 'playlist' | 'carta-do-autor' | 'pdf'
   ================================================================ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$email = trim($body['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['erro' => 'E-mail inválido']);
    exit;
}

$tipo      = substr($body['tipo']      ?? '', 0, 30);
$genero    = substr($body['genero']    ?? '', 0, 20);
$sessao_id = substr($body['sessao_id'] ?? '', 0, 64);
$ip        = $_SERVER['REMOTE_ADDR'] ?? null;

/* ── Salvar no banco ─────────────────────────────────────────── */
try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO bio_leads
           (email, tipo, genero, sessao_id, ip)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $email,
        $tipo      ?: null,
        $genero    ?: null,
        $sessao_id ?: null,
        $ip,
    ]);
} catch (Throwable $e) {
    error_log('[bio-lead] Erro DB: ' . $e->getMessage());
    /* Continua mesmo assim — pelo menos tenta enviar o e-mail */
}

/* ── Enviar e-mail de confirmação ────────────────────────────── */
try {
    _enviarConfirmacaoBau($email, $tipo, $genero);
} catch (Throwable $e) {
    error_log('[bio-lead] Erro ao enviar e-mail: ' . $e->getMessage());
    /* Não bloqueia a resposta ao usuário */
}

echo json_encode(['ok' => true]);

/* ================================================================
   TEMPLATES POR TIPO
   ================================================================ */

function _enviarConfirmacaoBau(string $email, string $tipo, string $genero): void
{
    $base = defined('SITE_URL') ? SITE_URL : 'https://roberiodiogenes.com';

    switch ($tipo) {

        /* ── Contos inéditos ─────────────────────────────────── */
        case 'contos-ineditos':
            Mailer::enviar([
                'para_email' => $email,
                'para_nome'  => '',
                'assunto'    => 'Você está na lista — Robério Diógenes',
                'html'       => "
<p>Olá.</p>
<p>Há histórias que escrevo sem saber se algum dia vão sair da gaveta.</p>
<p>São contos que ficaram incompletos por meses, recomeçados às três da manhã, abandonados e retomados tantas vezes que já não sei exatamente quando nasceram. Não os publico porque ainda não estão prontos, ou porque não encontrei o leitor certo.</p>
<p>Você acabou de mudar isso.</p>
<p style='background:#F5F0E8;border-left:3px solid #B8860B;padding:1rem 1.25rem;border-radius:0 6px 6px 0;font-style:italic;color:#4A3728'>
  Quando o próximo conto inédito estiver pronto para o mundo, você será o primeiro a receber.
  Antes de qualquer publicação. Antes de qualquer anúncio.
</p>
<p>Obrigado por querer entrar.</p>
<p style='margin-top:2rem;font-size:.85rem;color:#8C7D65'>
  — Robério Diógenes
</p>
<hr style='border:none;border-top:1px solid #E8E0D0;margin:1.5rem 0'>
<p>Enquanto aguarda, os contos da <em>Ateliê de Histórias</em> estão disponíveis:</p>
<p>
  <a href='{$base}/bio.html' class='btn-email'>Ler os contos →</a>
</p>",
                'texto' => "Olá.\n\nVocê está na lista dos primeiros leitores dos contos inéditos.\n\nQuando o próximo conto estiver pronto, você receberá antes de qualquer publicação.\n\nEnquanto aguarda: {$base}/bio.html\n\n— Robério Diógenes",
            ]);
            break;

        /* ── Playlist de leitura ─────────────────────────────── */
        case 'playlist':
            $ytId  = 'LONrti4Qxf8';
            $ytUrl = 'https://youtu.be/' . $ytId . '?si=4Z-XOLgLNQyAcK5s';
            $ytThumb = 'https://img.youtube.com/vi/' . $ytId . '/maxresdefault.jpg';
            Mailer::enviar([
                'para_email' => $email,
                'para_nome'  => '',
                'assunto'    => 'A playlist de leitura chegou — Robério Diógenes',
                'html'       => "
<!-- Foto do autor -->
<div style='text-align:center;margin-bottom:2rem'>
  <img src='{$base}/img/autor-logo.jpg'
       alt='Robério Diógenes'
       width='120' height='120'
       style='width:120px;height:120px;border-radius:50%;object-fit:cover;
              border:3px solid #B8860B;display:inline-block' />
</div>

<p style='font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:#8C7D65;margin-bottom:1.5rem'>
  Playlist de leitura
</p>

<p>Olá.</p>

<p>Tem uma coisa que aprendi lendo em horas improváveis, dentro de ônibus lotado,
esperando numa fila, nos dez minutos antes de dormir:
o silêncio certo transforma qualquer lugar numa sala de leitura.</p>

<p>Esta playlist foi montada para acompanhar os contos.
Não é fundo musical aleatório. É o som que eu ouço enquanto escrevo,
o mesmo que ajuda a criar o clima que você vai encontrar nas páginas.</p>

<p>Coloque para tocar. Abra qualquer um dos contos.<br>
Deixe a música fazer o que ela sabe fazer.</p>

<!-- Thumbnail clicável do YouTube (iframes não funcionam em e-mail) -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin:2rem 0'>
  <tr>
    <td align='center'>
      <a href='{$ytUrl}' target='_blank' rel='noopener'
         style='display:inline-block;position:relative;text-decoration:none'>
        <img src='{$ytThumb}'
             alt='Playlist de leitura — Robério Diógenes'
             width='480'
             style='width:100%;max-width:480px;border-radius:10px;
                    display:block;border:none' />
        <!-- Botão play sobreposto -->
        <div style='position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                    background:rgba(0,0,0,.72);border-radius:50%;
                    width:64px;height:64px;display:flex;align-items:center;justify-content:center'>
          <div style='width:0;height:0;border-style:solid;
                      border-width:12px 0 12px 22px;
                      border-color:transparent transparent transparent #ffffff;
                      margin-left:4px'></div>
        </div>
      </a>
    </td>
  </tr>
  <tr>
    <td align='center' style='padding-top:.75rem'>
      <a href='{$ytUrl}' target='_blank' rel='noopener'
         style='font-size:.75rem;color:#B8860B;text-decoration:none;letter-spacing:.05em'>
        ▶ Abrir no YouTube
      </a>
    </td>
  </tr>
</table>

<p style='font-size:.85rem;color:#4A3728;line-height:1.8;
          background:#F5F0E8;border-left:3px solid #B8860B;
          padding:1rem 1.25rem;border-radius:0 6px 6px 0'>
  <em>Funciona melhor com fone de ouvido.<br>
  E com uma boa história na mão.</em>
</p>

<hr style='border:none;border-top:1px solid #E8E0D0;margin:2rem 0'>
<p style='text-align:center;font-size:.85rem;color:#4A3728;margin-bottom:1rem'>
  Escolha um conto. Aperte play. Comece:
</p>
<p style='text-align:center'>
  <a href='{$base}/bio.html' class='btn-email'>Ler os contos →</a>
</p>",
                'texto' => "Playlist de leitura — Robério Diógenes\n\nOlá.\n\nEsta playlist foi montada para acompanhar os contos. É o som que ouço enquanto escrevo.\n\nOuça aqui: {$ytUrl}\n\nDepois, escolha um conto: {$base}/bio.html\n\n— Robério Diógenes",
            ]);
            break;

        /* ── Carta do autor ──────────────────────────────────── */
        case 'carta-do-autor':
            Mailer::enviar([
                'para_email' => $email,
                'para_nome'  => '',
                'assunto'    => 'Uma carta para você — Robério Diógenes',
                'html'       => "
<!-- Foto do autor -->
<div style='text-align:center;margin-bottom:2rem'>
  <img src='{$base}/img/autor-logo.jpg'
       alt='Robério Diógenes'
       width='120' height='120'
       style='width:120px;height:120px;border-radius:50%;object-fit:cover;
              border:3px solid #B8860B;display:inline-block' />
</div>

<p style='font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:#8C7D65;margin-bottom:1.5rem'>
  Carta do autor
</p>

<p>Olá.</p>

<p>Cresci em Fortaleza, numa família simples onde livros não eram prioridade.<br>
A prioridade era a comida.</p>

<p>Meu pai acordava cedo. Minha mãe também. Aprendi cedo que as coisas não chegam
sozinhas — elas precisam ser buscadas, carregadas, entregues.</p>

<p>Tive uma infância normal, dos anos 80. Sem internet, sem smartphone.<br>
A gente conversava. Olhava nos olhos. Matava a tarde na calçada,
inventando mundos com o que tinha à mão.<br>
Já trabalhei como professor de informática, como fotógrafo.<br>
Hoje sou entregador. Cada fase me ensinou algo diferente sobre as pessoas —
e tudo isso acabou indo parar nas histórias que escrevo.</p>

<p>Entre uma corrida e outra, entre o trânsito e o calor do Ceará,
fui esse menino que lia o que encontrava.<br>
Livros na biblioteca do colégio. Trecho de jornal velho.<br>
A contracapa de um livro que não era meu.<br>
Qualquer palavra era melhor que o silêncio pesado de uma tarde sem perspectiva.</p>

<p>Comecei pela Coleção Vagalume — aquelas capas coloridas que passavam de mão em mão.<br>
Depois fui escalando: Paulo Coelho, H. G. Wells.<br>
Mais tarde, <em>O Nome da Rosa</em>, <em>O Pequeno Príncipe</em>.<br>
Cada livro abriu uma porta que eu não sabia que existia.</p>

<p>Foi assim que a ficção me salvou — não de forma dramática, sem farol do céu.<br>
Ela me salvou devagar, do jeito que as coisas reais funcionam:<br>
uma história de cada vez, uma frase que ficou, um personagem que me ensinou
algo que ninguém ao redor soube me dizer.</p>

<p>Escrevo porque ainda sou aquele menino.<br>
Porque ainda acredito que uma boa história pode encontrar alguém
no momento exato em que ela precisa ser encontrada.</p>

<p>E você, que pediu essa carta, me diz que ainda existem pessoas
dispostas a parar — no meio do barulho, da correria, das notificações —
e deixar uma voz estranha entrar.</p>

<p style='background:#F5F0E8;border-left:3px solid #B8860B;padding:1rem 1.25rem;
          border-radius:0 6px 6px 0;color:#4A3728;line-height:1.8;font-size:.9rem'>
  Se algo aqui te tocar, ou se quiser me contar o que achou dos contos,
  pode me deixar uma mensagem pelo site. Não faço promessas de responder a todos,
  mas lerei. E, na medida do possível, responderei.
</p>

<p style='font-size:1rem;line-height:1.8;margin-top:1.5rem'>
  Isso não é pouca coisa.<br>
  <strong>Isso é tudo.</strong>
</p>

<p style='margin-top:2rem;font-family:Georgia,serif;font-size:.95rem;color:#4A3728'>
  Com respeito e afeto,
</p>
<p style='font-family:Georgia,serif;font-size:1.15rem;color:#B8860B;font-style:italic;margin-top:.25rem'>
  Robério Diógenes
</p>
<p style='font-size:.78rem;color:#8C7D65;margin-top:.25rem'>Fortaleza, Ceará</p>

<hr style='border:none;border-top:1px solid #E8E0D0;margin:2rem 0'>
<p style='text-align:center;font-size:.85rem;color:#4A3728;margin-bottom:1rem'>
  Os contos estão esperando por você:
</p>
<p style='text-align:center'>
  <a href='{$base}/bio.html' class='btn-email'>Ler os contos →</a>
</p>",
                'texto' => "Carta do autor\n\nOlá.\n\nCresci em Fortaleza, numa família simples onde livros não eram prioridade.\nA prioridade era a comida.\n\nMeu pai acordava cedo. Minha mãe também. Aprendi cedo que as coisas não chegam sozinhas — elas precisam ser buscadas, carregadas, entregues.\n\nTive uma infância normal, dos anos 80. Sem internet, sem smartphone. A gente conversava. Olhava nos olhos. Matava a tarde na calçada, inventando mundos com o que tinha à mão. Já trabalhei como professor de informática, como fotógrafo. Hoje sou entregador. Cada fase me ensinou algo diferente sobre as pessoas — e tudo isso acabou indo parar nas histórias que escrevo.\n\nEntre uma corrida e outra, entre o trânsito e o calor do Ceará, fui esse menino que lia o que encontrava. Livros na biblioteca do colégio. Trecho de jornal velho. A contracapa de um livro que não era meu. Qualquer palavra era melhor que o silêncio pesado de uma tarde sem perspectiva.\n\nComecei pela Coleção Vagalume. Depois fui escalando: Paulo Coelho, H. G. Wells. Mais tarde, O Nome da Rosa, O Pequeno Príncipe. Cada livro abriu uma porta que eu não sabia que existia.\n\nFoi assim que a ficção me salvou — devagar, uma história de cada vez.\n\nEscrevo porque ainda sou aquele menino. Porque ainda acredito que uma boa história pode encontrar alguém no momento exato em que ela precisa ser encontrada.\n\nSe algo aqui te tocar, pode me deixar uma mensagem pelo site. Não faço promessas de responder a todos, mas lerei.\n\nIsso não é pouca coisa. Isso é tudo.\n\nCom respeito e afeto,\nRobério Diógenes\nFortaleza, Ceará\n\nLeia os contos: {$base}/bio.html",
            ]);
            break;

        /* ── PDF / fallback ──────────────────────────────────── */
        default:
            Mailer::enviar([
                'para_email' => $email,
                'para_nome'  => '',
                'assunto'    => 'Combinado — Robério Diógenes',
                'html'       => "
<p>Olá.</p>
<p>Recebi seu e-mail. Assim que o conteúdo estiver disponível, você será um dos primeiros a saber.</p>
<p>Enquanto isso, os contos da <em>Ateliê de Histórias</em> estão disponíveis:</p>
<p style='text-align:center;margin-top:1.5rem'>
  <a href='{$base}/bio.html' class='btn-email'>Ler os contos →</a>
</p>",
                'texto' => "Olá.\n\nRecebi seu e-mail. Assim que o conteúdo estiver disponível, você será avisado.\n\nLeia os contos em: {$base}/bio.html\n\n— Robério Diógenes",
            ]);
            break;
    }
}
