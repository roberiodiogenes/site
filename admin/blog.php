<?php
ob_start();

function _admin_push_novo_post(PDO $pdo, int $postId): void {
    /* Silencioso — não bloqueia o fluxo do admin se falhar */
    try {
        $pushFile = __DIR__ . '/../backend/push.php';
        if (!file_exists($pushFile)) return;
        require_once $pushFile;

        /* Buscar dados do post */
        $st = $pdo->prepare("SELECT titulo, subtitulo, slug, imagem_url FROM posts WHERE id=? LIMIT 1");
        $st->execute([$postId]);
        $post = $st->fetch();
        if (!$post) return;

        $titulo   = 'Novo no Diário — ' . mb_substr($post['titulo'], 0, 50, 'UTF-8');
        $mensagem = $post['subtitulo']
                  ? mb_substr($post['subtitulo'], 0, 150, 'UTF-8')
                  : 'Robério Diógenes publicou um novo post. Leia agora.';
        $url      = SITE_URL . '/blog/' . $post['slug'] . '.html';
        $imagem   = $post['imagem_url'] ? (SITE_URL . '/img/' . $post['imagem_url']) : '';

        PushNotification::enviar([
            'titulo'   => $titulo,
            'mensagem' => $mensagem,
            'url'      => $url,
            'imagem'   => $imagem,
            'segmento' => 'todos',
        ]);
    } catch (Throwable $e) {
        error_log('[Push blog] ' . $e->getMessage());
    }
}

function _admin_ping_sitemap(): void {
    if(AMBIENTE !== 'producao') return; // não pingar no XAMPP local
    $sm = 'https://roberiodiogenes.com/sitemap.xml';
    $urls = [
        'https://www.google.com/ping?sitemap=' . urlencode($sm),
        'https://www.bing.com/ping?sitemap='   . urlencode($sm),
    ];
    foreach($urls as $u){
        $ctx = stream_context_create(['http'=>['method'=>'GET','timeout'=>3,'ignore_errors'=>true]]);
        @file_get_contents($u, false, $ctx);
    }
}

function rd_slugify(string $t): string {
    $m=['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','é'=>'e','ê'=>'e','í'=>'i',
        'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
    $t=mb_strtolower(strtr($t,$m),'UTF-8');
    $t=preg_replace('/[^a-z0-9\s-]/','', $t);
    return substr(preg_replace('/[\s-]+/','-',trim($t)),0,160);
}
function rd_flash(string $msg, string $tipo='ok'): void {
    if(session_status()!==PHP_SESSION_ACTIVE){session_name('rd_admin_sess');session_start();}
    $_SESSION['blog_flash']=['msg'=>$msg,'tipo'=>$tipo];
}

/* ══ BLOCO POST — retorna JSON antes de qualquer HTML ══════════ */
if($_SERVER['REQUEST_METHOD']==='POST'){
    ini_set('display_errors','0');
    session_name('rd_admin_sess');
    session_start();
    if(empty($_SESSION['admin_id'])){
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>false,'erro'=>'Sessão expirada.']);
        exit;
    }
    require_once __DIR__.'/../backend/config.php';
    $pdo=db();
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $acao=trim($_POST['acao']??'');
    $cats=['bastidores','reflexao','escritor','livros'];

    /* ── ping ── */
    if($acao==='ping'){
        echo json_encode(['ok'=>true,'msg'=>'OK','sessao'=>'admin_id='.($_SESSION['admin_id']??'?')]);
        exit;
    }

    /* ── criar ── */
    if($acao==='criar'){
        $titulo  =trim($_POST['titulo']  ??'');
        $conteudo=trim($_POST['conteudo']??'');
        if(!$titulo||!$conteudo){echo json_encode(['ok'=>false,'erro'=>'Título e conteúdo obrigatórios.']);exit;}
        $subtitulo =trim($_POST['subtitulo'] ??'');
        $categoria =in_array($_POST['categoria']??'',$cats)?$_POST['categoria']:'reflexao';
        $resumo    =trim($_POST['resumo']    ??'');
        $imagem_url=trim($_POST['imagem_url']??'');
        $audio_url =trim($_POST['audio_url'] ??'');
        $livro_slug=trim($_POST['livro_slug']??'');
        $html_ext  =trim($_POST['html_externo']??'');
        $tempo     =max(1,(int)($_POST['tempo_leitura']??5));
        $status    =in_array($_POST['status']??'',['rascunho','publicado','oculto','agendado'])?$_POST['status']:'rascunho';
        $destaque  =!empty($_POST['destaque'])?1:0;
        $exclusivo =!empty($_POST['exclusivo'])?1:0;
        $enquete_id=(int)($_POST['enquete_id']??0)?:(null);
        $cluster_id=(int)($_POST['cluster_id']??0)?:(null);
        $slug      =trim($_POST['slug']??'')?:rd_slugify($titulo);
        $stC=$pdo->prepare("SELECT id FROM posts WHERE slug=? LIMIT 1");$stC->execute([$slug]);
        if($stC->fetchColumn()) $slug.='-'.substr(md5(uniqid('',true)),0,5);
        $pub=null;
        if($status==='publicado'||$status==='agendado') $pub=trim($_POST['publicado_em']??'')?:date('Y-m-d H:i:s');
        try{
            // Tenta INSERT com todas as colunas (migration_blog_avancado executada)
            try{
                $pdo->prepare("INSERT INTO posts(slug,titulo,subtitulo,categoria,resumo,conteudo,imagem_url,audio_url,tempo_leitura,livro_slug,html_externo,status,destaque,exclusivo,enquete_id,cluster_id,publicado_em) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$slug,$titulo,$subtitulo,$categoria,$resumo,$conteudo,$imagem_url,$audio_url,$tempo,$livro_slug,$html_ext,$status,$destaque,$exclusivo,$enquete_id,$cluster_id,$pub]);
            }catch(PDOException $ex1){
                // Fallback 2: sem colunas da migration avançada, mas com html_externo
                try{
                    $pdo->prepare("INSERT INTO posts(slug,titulo,subtitulo,categoria,resumo,conteudo,imagem_url,audio_url,tempo_leitura,livro_slug,html_externo,status,destaque,publicado_em) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                       ->execute([$slug,$titulo,$subtitulo,$categoria,$resumo,$conteudo,$imagem_url,$audio_url,$tempo,$livro_slug,$html_ext,$status,$destaque,$pub]);
                }catch(PDOException $ex2){
                    // Fallback 3: schema base (sem html_externo, sem colunas avançadas)
                    $pdo->prepare("INSERT INTO posts(slug,titulo,subtitulo,categoria,resumo,conteudo,imagem_url,audio_url,tempo_leitura,livro_slug,status,destaque,publicado_em) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
                       ->execute([$slug,$titulo,$subtitulo,$categoria,$resumo,$conteudo,$imagem_url,$audio_url,$tempo,$livro_slug,$status,$destaque,$pub]);
                }
            }
            rd_flash('Post criado!');
            echo json_encode(['ok'=>true,'msg'=>'Post criado!']);
        }catch(PDOException $e){
            error_log('[blog] criar: '.$e->getMessage());
            echo json_encode(['ok'=>false,'erro'=>'Banco: '.$e->getMessage()]);
        }
        exit;
    }

    /* ── editar ── */
    if($acao==='editar'){
        $id      =(int)($_POST['id']??0);
        $titulo  =trim($_POST['titulo']  ??'');
        $conteudo=trim($_POST['conteudo']??'');
        if(!$id||!$titulo||!$conteudo){echo json_encode(['ok'=>false,'erro'=>'ID/título/conteúdo obrigatórios.']);exit;}
        $subtitulo =trim($_POST['subtitulo'] ??'');
        $categoria =in_array($_POST['categoria']??'',$cats)?$_POST['categoria']:'reflexao';
        $resumo    =trim($_POST['resumo']    ??'');
        $imagem_url=trim($_POST['imagem_url']??'');
        $audio_url =trim($_POST['audio_url'] ??'');
        $livro_slug=trim($_POST['livro_slug']??'');
        $html_ext  =trim($_POST['html_externo']??'');
        $tempo     =max(1,(int)($_POST['tempo_leitura']??5));
        $status    =in_array($_POST['status']??'',['rascunho','publicado','oculto','agendado'])?$_POST['status']:'rascunho';
        $destaque  =!empty($_POST['destaque'])?1:0;
        $exclusivo =!empty($_POST['exclusivo'])?1:0;
        $enquete_id=(int)($_POST['enquete_id']??0)?:(null);
        $cluster_id=(int)($_POST['cluster_id']??0)?:(null);
        $slug      =trim($_POST['slug']??'')?:rd_slugify($titulo);
        $pub=null;
        if($status==='publicado'||$status==='agendado') $pub=trim($_POST['publicado_em']??'')?:date('Y-m-d H:i:s');
        try{
            // Tenta UPDATE com todas as colunas (migration_blog_avancado executada)
            try{
                $pdo->prepare("UPDATE posts SET slug=?,titulo=?,subtitulo=?,categoria=?,resumo=?,conteudo=?,imagem_url=?,audio_url=?,tempo_leitura=?,livro_slug=?,html_externo=?,status=?,destaque=?,exclusivo=?,enquete_id=?,cluster_id=?,publicado_em=? WHERE id=?")
                   ->execute([$slug,$titulo,$subtitulo,$categoria,$resumo,$conteudo,$imagem_url,$audio_url,$tempo,$livro_slug,$html_ext,$status,$destaque,$exclusivo,$enquete_id,$cluster_id,$pub,$id]);
            }catch(PDOException $ex1){
                // Fallback 2: sem colunas da migration avançada, mas com html_externo
                try{
                    $pdo->prepare("UPDATE posts SET slug=?,titulo=?,subtitulo=?,categoria=?,resumo=?,conteudo=?,imagem_url=?,audio_url=?,tempo_leitura=?,livro_slug=?,html_externo=?,status=?,destaque=?,publicado_em=? WHERE id=?")
                       ->execute([$slug,$titulo,$subtitulo,$categoria,$resumo,$conteudo,$imagem_url,$audio_url,$tempo,$livro_slug,$html_ext,$status,$destaque,$pub,$id]);
                }catch(PDOException $ex2){
                    // Fallback 3: schema base (sem html_externo, sem colunas avançadas)
                    $pdo->prepare("UPDATE posts SET slug=?,titulo=?,subtitulo=?,categoria=?,resumo=?,conteudo=?,imagem_url=?,audio_url=?,tempo_leitura=?,livro_slug=?,status=?,destaque=?,publicado_em=? WHERE id=?")
                       ->execute([$slug,$titulo,$subtitulo,$categoria,$resumo,$conteudo,$imagem_url,$audio_url,$tempo,$livro_slug,$status,$destaque,$pub,$id]);
                }
            }
            rd_flash('Post atualizado!');
            echo json_encode(['ok'=>true,'msg'=>'Post atualizado!']);
        }catch(PDOException $e){
            error_log('[blog] editar: '.$e->getMessage());
            echo json_encode(['ok'=>false,'erro'=>'Banco: '.$e->getMessage()]);
        }
        exit;
    }

    /* ── excluir ── */
    if($acao==='excluir'){
        $id=(int)($_POST['id']??0);
        if(!$id){echo json_encode(['ok'=>false,'erro'=>'ID inválido.']);exit;}
        $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$id]);
        rd_flash('Post excluído.');
        echo json_encode(['ok'=>true]);
        exit;
    }

    /* ── toggle_status ── */
    if($acao==='toggle_status'){
        $id =(int)($_POST['id']??0);
        $ns =trim($_POST['status']??'oculto');
        if(!in_array($ns,['publicado','oculto','rascunho','agendado'])) $ns='oculto';
        $ex=$ns==='publicado'?', publicado_em=COALESCE(publicado_em,NOW())':'';
        $pdo->prepare("UPDATE posts SET status=? $ex WHERE id=?")->execute([$ns,$id]);
        rd_flash('Status: '.ucfirst($ns).'.');
        // Notifica buscadores ao publicar
        if($ns==='publicado') _admin_ping_sitemap();
        // Notificação push automática ao publicar
        if($ns==='publicado') _admin_push_novo_post($pdo, $id);
        echo json_encode(['ok'=>true,'novo_status'=>$ns]);
        exit;
    }

    /* ── ping_sitemap (manual) ── */
    if($acao==='ping_sitemap'){
        _admin_ping_sitemap();
        echo json_encode(['ok'=>true,'msg'=>'Sitemaps notificados.']);
        exit;
    }

    /* ── newsletter ── */
    if($acao==='newsletter'){
        $pid=(int)($_POST['post_id']??0);
        if(!$pid){echo json_encode(['ok'=>false,'erro'=>'ID inválido.']);exit;}
        $stC=$pdo->prepare("SELECT id FROM newsletter_posts WHERE post_id=?");$stC->execute([$pid]);
        if($stC->fetchColumn()){echo json_encode(['ok'=>false,'erro'=>'Newsletter já enviada.']);exit;}
        $stP=$pdo->prepare("SELECT * FROM posts WHERE id=? AND status='publicado' LIMIT 1");$stP->execute([$pid]);
        $post=$stP->fetch(PDO::FETCH_ASSOC);
        if(!$post){echo json_encode(['ok'=>false,'erro'=>'Post não encontrado/publicado.']);exit;}
        $col='pref_'.$post['categoria'];
        $colsOk=['pref_bastidores','pref_reflexao','pref_escritor','pref_livros'];
        if(!in_array($col,$colsOk)){echo json_encode(['ok'=>false,'erro'=>'Categoria inválida.']);exit;}
        $stN=$pdo->prepare("SELECT email,nome FROM newsletter WHERE status='ativo' AND `$col`=1");$stN->execute();
        $subs=$stN->fetchAll(PDO::FETCH_ASSOC);
        if(empty($subs)){echo json_encode(['ok'=>true,'enviados'=>0,'msg'=>'Sem assinantes.']);exit;}
        require_once __DIR__.'/../backend/mailer.php';
        $url=(defined('SITE_URL')?SITE_URL:'https://www.roberiodiogenes.com').'/blog/'.$post['slug'].'.html';
        $env=0;$err=0;
        foreach($subs as $s){
            $nome=explode(' ',trim($s['nome']??'Leitor'))[0];
            $ok=Mailer::enviar(['para_email'=>$s['email'],'para_nome'=>$s['nome']??'','assunto'=>'Novo texto: '.$post['titulo'],'html'=>"<p>Olá <strong>$nome</strong>, há um novo texto: <a href='$url'>".$post['titulo']."</a></p>",'texto'=>"Novo texto: ".$post['titulo']."\n$url"]);
            $ok?$env++:$err++;
        }
        $pdo->prepare("INSERT INTO newsletter_posts(post_id,total_envios) VALUES(?,?)")->execute([$pid,$env]);
        rd_flash("Newsletter enviada! $env e-mails.");
        echo json_encode(['ok'=>true,'enviados'=>$env,'erros'=>$err]);
        exit;
    }

    echo json_encode(['ok'=>false,'erro'=>'Ação desconhecida.']);
    exit;
}

/* ══ BLOCO GET — HTML normal ═══════════════════════════════════ */
$ADMIN_PAGE='blog';
require_once __DIR__.'/_admin.php';

$catLabels=['bastidores'=>'Bastidores','reflexao'=>'Reflexão','escritor'=>'Do Escritor','livros'=>'Sobre os Livros'];
$livroOpts=['setima-lei'=>'A Sétima Lei','lumen'=>'Lúmen','abismo-das-almas'=>'O Abismo das Almas'];

$acao=$_GET['acao']??'listar';
$postEditar=null;
if($acao==='editar'){
    $id=(int)($_GET['id']??0);
    $st=$pdo->prepare("SELECT * FROM posts WHERE id=? LIMIT 1");$st->execute([$id]);
    $postEditar=$st->fetch(PDO::FETCH_ASSOC);
    if(!$postEditar){rd_flash('Post não encontrado.','erro');header('Location: blog.php');exit;}
}

$fStatus=$_GET['status']??'todos';
$fCat   =$_GET['cat']??'todos';
$busca  =trim($_GET['busca']??'');
$pag    =max(1,(int)($_GET['p']??1));
$pp     =15;
$wh=[];$pa=[];
if($fStatus!=='todos'){$wh[]="status=?";$pa[]=$fStatus;}
if($fCat!=='todos'){$wh[]="categoria=?";$pa[]=$fCat;}
if($busca){$wh[]="(titulo LIKE ? OR resumo LIKE ?)";$l="%$busca%";$pa[]=$l;$pa[]=$l;}
$wsql=$wh?'WHERE '.implode(' AND ',$wh):'';
$stC=$pdo->prepare("SELECT COUNT(*) FROM posts $wsql");$stC->execute($pa);
$total=(int)$stC->fetchColumn();$totP=max(1,(int)ceil($total/$pp));$off=($pag-1)*$pp;
$stL=$pdo->prepare("SELECT id,slug,titulo,categoria,status,destaque,publicado_em,audio_url,html_externo FROM posts $wsql ORDER BY created_at DESC LIMIT $pp OFFSET $off");
$stL->execute($pa);$posts=$stL->fetchAll(PDO::FETCH_ASSOC);
$stNL=$pdo->query("SELECT post_id FROM newsletter_posts");
$nlEnv=$stNL?$stNL->fetchAll(PDO::FETCH_COLUMN):[];
$flash=null;
if(!empty($_SESSION['blog_flash'])){$flash=$_SESSION['blog_flash'];unset($_SESSION['blog_flash']);}
?>
<style>
.fb{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-bottom:1.5rem}
.fb input[type=search],.fb select{padding:.45rem .85rem;background:var(--fundo-card,#1C1408);border:1px solid var(--borda-media);border-radius:6px;color:var(--texto);font-size:.85rem}
.fb input[type=search]{flex:1;min-width:160px}
.fb input:focus,.fb select:focus{outline:none;border-color:var(--ouro)}
.tbl{width:100%;border-collapse:collapse}
.tbl th{text-align:left;padding:.65rem 1rem;font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);border-bottom:1px solid var(--borda);white-space:nowrap}
.tbl td{padding:.75rem 1rem;border-bottom:1px solid var(--borda);vertical-align:middle;font-size:.85rem;color:var(--texto-2)}
.tbl tr:hover td{background:rgba(255,255,255,.02)}
.ct{max-width:230px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--texto)}
.cs{font-size:.7rem;color:var(--texto-3);margin-top:.1rem}
.ca{display:flex;gap:.3rem}
.bdg{display:inline-flex;align-items:center;padding:.2rem .55rem;border-radius:20px;font-size:.68rem;letter-spacing:.08em;text-transform:uppercase}
.bdg-publicado{background:rgba(39,174,96,.12);border:1px solid #27ae60;color:#2ecc71}
.bdg-oculto{background:rgba(149,165,166,.1);border:1px solid #7f8c8d;color:#95a5a6}
.bdg-rascunho{background:rgba(241,196,15,.1);border:1px solid #d4ac0d;color:#f1c40f}
.bdg-agendado{background:rgba(155,89,182,.12);border:1px solid #8e44ad;color:#bb8fce}
.ba{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;font-size:.78rem;cursor:pointer;border:none;text-decoration:none;transition:all .15s}
.be{background:rgba(52,152,219,.15);color:#3498db;border:1px solid #2980b9}.be:hover{background:rgba(52,152,219,.3)}
.bv{background:rgba(241,196,15,.1);color:#f1c40f;border:1px solid #d4ac0d}.bv:hover{background:rgba(241,196,15,.25)}
.bn{background:rgba(52,152,219,.1);color:#3498db;border:1px solid #2471a3}.bn:hover{background:rgba(52,152,219,.25)}
.bw{background:rgba(255,255,255,.05);color:var(--texto-3);border:1px solid var(--borda-media)}.bw:hover{border-color:var(--ouro);color:var(--ouro)}
.bd{background:rgba(231,76,60,.12);color:#e74c3c;border:1px solid #c0392b}.bd:hover{background:rgba(231,76,60,.28)}
.ba:disabled{opacity:.35;cursor:not-allowed}
.pg{display:flex;gap:.4rem;justify-content:center;margin-top:1.5rem;flex-wrap:wrap}
.pg a,.pg span{min-width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:1px solid var(--borda-media);border-radius:6px;font-size:.8rem;color:var(--texto-3);text-decoration:none;transition:all .2s}
.pg a:hover{border-color:var(--ouro);color:var(--ouro)}
.pg .at{background:var(--ouro);color:#1A0F00;border-color:var(--ouro);font-weight:700}
.fl{padding:.75rem 1.25rem;border-radius:6px;margin-bottom:1.5rem;font-size:.88rem;display:flex;align-items:center;gap:.6rem}
.fl.ok{background:rgba(39,174,96,.1);border:1px solid #27ae60;color:#2ecc71}
.fl.erro{background:rgba(231,76,60,.1);border:1px solid #c0392b;color:#e74c3c}
.sh{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem}
.st{font-family:var(--fonte-titulo,Georgia);font-size:1.3rem;font-weight:400}
.st em{color:var(--ouro);font-style:italic}
.fm{display:flex;flex-direction:column;gap:1.1rem;max-width:860px}
.fr{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fr3{grid-template-columns:1fr 1fr 1fr}
.fg{display:flex;flex-direction:column;gap:.35rem}
.fg label{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--ouro)}
.fi{padding:.6rem .85rem;background:var(--fundo-card,#1C1408);border:1px solid var(--borda-media);border-radius:6px;color:var(--texto);font-family:var(--fonte-corpo,Georgia);font-size:.88rem;transition:border-color .2s;width:100%}
.fi:focus{outline:none;border-color:var(--ouro)}
.fia{min-height:100px;resize:vertical;line-height:1.65}
.fih{min-height:380px;font-size:.85rem;font-family:monospace}
.et{display:flex;gap:.3rem;flex-wrap:wrap;padding:.45rem .75rem;background:rgba(0,0,0,.3);border:1px solid var(--borda-media);border-bottom:none;border-radius:6px 6px 0 0}
.eb{padding:.28rem .55rem;background:transparent;border:1px solid transparent;border-radius:4px;color:var(--texto-3);font-size:.75rem;cursor:pointer;transition:all .15s}
.eb:hover{border-color:var(--borda-media);color:var(--ouro)}
.fih{border-radius:0 0 6px 6px !important}
#pv{display:none;padding:1.25rem;max-height:420px;overflow-y:auto;border:1px solid var(--borda-media);border-radius:6px;background:var(--fundo-card,#1C1408);line-height:1.8}
#pv h2{color:var(--ouro);font-family:Georgia,serif;font-weight:400;margin:1.2rem 0 .4rem}
#pv p{color:var(--texto-2);margin-bottom:1em}
#pv blockquote{border-left:3px solid var(--ouro);padding:.5rem 1rem;color:var(--texto-3);font-style:italic;margin:1rem 0}
.fa2{display:flex;gap:.75rem;padding-top:.5rem;flex-wrap:wrap;align-items:center}
.bs{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.5rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;font-family:var(--fonte-display,system-ui);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;transition:opacity .2s}
.bs:hover{opacity:.85}.bs:disabled{opacity:.45;cursor:not-allowed}
.bv2{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1.25rem;background:transparent;color:var(--texto-3);border:1px solid var(--borda-media);border-radius:6px;cursor:pointer;font-family:var(--fonte-display,system-ui);font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;text-decoration:none;transition:all .2s}
.bv2:hover{border-color:var(--ouro);color:var(--ouro)}
.bn2{display:inline-flex;align-items:center;gap:.45rem;padding:.5rem 1.25rem;background:var(--ouro);color:#1A0F00;border:none;border-radius:6px;cursor:pointer;text-decoration:none;font-family:var(--fonte-display,system-ui);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.bn2:hover{opacity:.85}
.ck{display:flex;align-items:center;gap:.55rem;cursor:pointer;font-size:.88rem;color:var(--texto-2)}
.ck input{accent-color:var(--ouro);width:15px;height:15px;cursor:pointer}
.cc{font-size:.7rem;color:var(--texto-3);text-align:right;margin-top:.15rem}
/* upload */
.uw{display:flex;flex-direction:column;gap:.75rem}
.up{width:100%;max-width:300px;height:150px;border-radius:8px;overflow:hidden;border:1px solid var(--borda-media);background:var(--fundo-card,#1C1408);display:flex;align-items:center;justify-content:center}
.up img{width:100%;height:100%;object-fit:cover;display:block}
.uph{display:flex;flex-direction:column;align-items:center;gap:.5rem;color:var(--texto-3);font-size:.82rem}
.uph i{font-size:2rem;color:var(--ouro);opacity:.3}
.udz{border:2px dashed var(--borda-media);border-radius:8px;padding:1.25rem;text-align:center;cursor:pointer;transition:all .2s;display:flex;flex-direction:column;align-items:center;gap:.4rem}
.udz:hover,.udz.dg{border-color:var(--ouro);background:rgba(184,134,11,.05)}
.udz i{font-size:1.5rem;color:var(--ouro);opacity:.6}
.udt{font-size:.88rem;color:var(--texto-2)}
.udi{font-size:.72rem;color:var(--texto-3)}
.upg{height:4px;background:var(--borda);border-radius:2px;overflow:hidden;position:relative;margin-top:.5rem}
.upg::after{content:'';position:absolute;left:-100%;top:0;height:100%;width:100%;background:var(--ouro);animation:usl 1s ease-in-out infinite}
@keyframes usl{to{left:100%}}
.ur{padding:.5rem .85rem;border-radius:6px;font-size:.82rem;margin-top:.35rem}
.ur.ok{background:rgba(39,174,96,.1);border:1px solid #27ae60;color:#2ecc71}
.ur.erro{background:rgba(231,76,60,.1);border:1px solid #c0392b;color:#e74c3c}
@media(max-width:700px){.fr,.fr3{grid-template-columns:1fr}}
</style>

<?php if($flash):?>
<div class="fl <?=adm_esc($flash['tipo'])?>" role="alert">
  <i class="fa fa-<?=$flash['tipo']==='ok'?'check-circle':'triangle-exclamation'?>"></i>
  <?=adm_esc($flash['msg'])?>
</div>
<?php endif;?>

<?php if($acao==='criar'||$acao==='editar'):?>
<div class="sh">
  <h2 class="st"><?=$acao==='criar'?'Novo <em>Post</em>':'Editar <em>Post</em>'?></h2>
  <a href="blog.php" class="bv2"><i class="fa fa-arrow-left"></i> Voltar</a>
</div>

<form id="fB" class="fm" novalidate>
<input type="hidden" name="acao" value="<?=$acao?>">
<?php if($acao==='editar'):?><input type="hidden" name="id" value="<?=(int)$postEditar['id']?>"><?php endif;?>

<div class="fg">
  <label for="tit">Título *</label>
  <input type="text" id="tit" name="titulo" class="fi" required placeholder="Título do post…"
         value="<?=adm_esc($postEditar['titulo']??'')?>" oninput="aS(this.value)">
</div>
<div class="fg">
  <label for="sub">Subtítulo</label>
  <input type="text" id="sub" name="subtitulo" class="fi" placeholder="Uma linha de apoio…"
         value="<?=adm_esc($postEditar['subtitulo']??'')?>">
</div>
<div class="fr fr3">
  <div class="fg">
    <label for="slg">Slug (URL)</label>
    <input type="text" id="slg" name="slug" class="fi" placeholder="gerado-auto"
           value="<?=adm_esc($postEditar['slug']??'')?>">
  </div>
  <div class="fg">
    <label for="cat">Categoria</label>
    <select name="categoria" id="cat" class="fi">
      <?php foreach($catLabels as $v=>$l):?>
        <option value="<?=$v?>" <?=($postEditar['categoria']??'')===$v?'selected':''?>><?=$l?></option>
      <?php endforeach;?>
    </select>
  </div>
  <div class="fg">
    <label for="tmp">Leitura (min)</label>
    <input type="number" id="tmp" name="tempo_leitura" class="fi" min="1" max="60"
           value="<?=(int)($postEditar['tempo_leitura']??5)?>">
  </div>
</div>
<div class="fg">
  <label for="res">Resumo (SEO / cards)</label>
  <textarea id="res" name="resumo" class="fi fia" rows="3" maxlength="300"
            oninput="document.getElementById('rc').textContent=this.value.length"
            placeholder="Resumo curto (máx. 300 chars)…"><?=adm_esc($postEditar['resumo']??'')?></textarea>
  <div class="cc"><span id="rc"><?=strlen($postEditar['resumo']??'')?></span>/300</div>
</div>
<div class="fg">
  <label>Conteúdo HTML *</label>
  <div class="et" role="toolbar">
    <button type="button" class="eb" onclick="wS('<h2>','</h2>')"><b>H2</b></button>
    <button type="button" class="eb" onclick="wS('<h3>','</h3>')"><b>H3</b></button>
    <span style="width:1px;background:var(--borda);margin:0 .1rem"></span>
    <button type="button" class="eb" onclick="wS('<strong>','</strong>')"><b>B</b></button>
    <button type="button" class="eb" onclick="wS('<em>','</em>')"><i>I</i></button>
    <span style="width:1px;background:var(--borda);margin:0 .1rem"></span>
    <button type="button" class="eb" onclick="wS('<p>','</p>')">¶</button>
    <button type="button" class="eb" onclick="wS('<blockquote>','</blockquote>')">"</button>
    <button type="button" class="eb" onclick="wS('<ul>\n  <li>','</li>\n</ul>')">≡</button>
    <span style="width:1px;background:var(--borda);margin:0 .1rem"></span>
    <button type="button" class="eb" onclick="iL()"><i class="fa fa-link"></i></button>
    <button type="button" class="eb" onclick="tP()"><i class="fa fa-eye"></i> Preview</button>
  </div>
  <textarea id="cnt" name="conteudo" class="fi fia fih" rows="20" required
            placeholder="Conteúdo HTML…"><?=adm_esc($postEditar['conteudo']??'')?></textarea>
  <div id="pv"></div>
</div>

<!-- Upload de imagem -->
<div class="fg">
  <label>Imagem de capa</label>
  <div class="uw">
    <div class="up">
      <?php if(!empty($postEditar['imagem_url'])):?>
        <img src="../<?=adm_esc($postEditar['imagem_url'])?>" alt="Capa" id="pvImg">
      <?php else:?>
        <div class="uph" id="pvPh"><i class="fa fa-image"></i><span>Sem imagem</span></div>
        <img src="" alt="" id="pvImg" style="display:none">
      <?php endif;?>
    </div>
    <input type="file" id="iF" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
    <div class="udz" id="dZ" role="button" tabindex="0">
      <i class="fa fa-cloud-arrow-up"></i>
      <span class="udt">Arraste ou <u>clique para escolher</u></span>
      <span class="udi">JPG · PNG · WebP · max 5MB → reduzida a ≤150KB</span>
    </div>
    <div id="uPg" style="display:none"><div class="upg"></div><span style="font-size:.75rem;color:var(--texto-3)">Enviando…</span></div>
    <div id="uRs" class="ur" style="display:none"></div>
    <input type="hidden" id="iU" name="imagem_url" value="<?=adm_esc($postEditar['imagem_url']??'')?>">
    <div style="margin-top:.5rem">
      <label style="font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:var(--texto-3)">
        Ou URL manual
      </label>
      <input type="text" id="iUM" class="fi" placeholder="img/posts/meu-post.jpg"
             value="<?=adm_esc($postEditar['imagem_url']??'')?>"
             oninput="document.getElementById('iU').value=this.value;pvU(this.value)"
             style="margin-top:.3rem">
    </div>
  </div>
</div>

<div class="fr">
  <div class="fg">
    <label for="aud">URL do áudio (mp3)</label>
    <input type="text" id="aud" name="audio_url" class="fi" placeholder="audio/posts/meu-post.mp3"
           value="<?=adm_esc($postEditar['audio_url']??'')?>">
  </div>
  <div class="fg">
    <label for="hEx">HTML externo <span style="font-size:.6rem;background:rgba(184,134,11,.15);color:var(--ouro);padding:.15rem .4rem;border-radius:10px;margin-left:.3rem">Opcional</span></label>
    <input type="text" id="hEx" name="html_externo" class="fi"
           placeholder="blog/post-11.html"
           value="<?=adm_esc($postEditar['html_externo']??'')?>">
    <span style="font-size:.7rem;color:var(--texto-3);margin-top:.2rem">
      Se preenchido, o card do blog abrirá este arquivo HTML diretamente. Ideal para posts elaborados.
    </span>
  </div>
</div>
<div class="fr">
  <div class="fg">
    <label for="lv">Livro para CTA</label>
    <select name="livro_slug" id="lv" class="fi">
      <option value="">— Nenhum —</option>
      <?php foreach($livroOpts as $v=>$l):?>
        <option value="<?=$v?>" <?=($postEditar['livro_slug']??'')===$v?'selected':''?>><?=$l?></option>
      <?php endforeach;?>
    </select>
  </div>
  <div class="fg">
    <label for="pub">Data de publicação</label>
    <input type="datetime-local" id="pub" name="publicado_em" class="fi"
           value="<?=adm_esc(isset($postEditar['publicado_em'])?str_replace(' ','T',substr($postEditar['publicado_em'],0,16)):'')?>">
  </div>
</div>
<div class="fr">
  <div class="fg">
    <label for="sts">Status</label>
    <select name="status" id="sts" class="fi">
      <option value="rascunho"  <?=($postEditar['status']??'rascunho')==='rascunho' ?'selected':''?>>○ Rascunho</option>
      <option value="publicado" <?=($postEditar['status']??'')==='publicado'?'selected':''?>>✓ Publicado</option>
      <option value="agendado"  <?=($postEditar['status']??'')==='agendado' ?'selected':''?>>⏰ Agendado</option>
      <option value="oculto"   <?=($postEditar['status']??'')==='oculto'  ?'selected':''?>>● Oculto</option>
    </select>
  </div>
  <div class="fg" style="justify-content:flex-end">
    <label class="ck" style="margin-top:1.65rem">
      <input type="checkbox" name="destaque" value="1" <?=!empty($postEditar['destaque'])?'checked':''?>>
      Post em destaque
    </label>
  </div>
</div>

<!-- ── Exclusivo + Enquete + Cluster ── -->
<div class="fr" style="gap:.75rem;margin-bottom:.25rem">
  <div class="fg" style="background:rgba(184,134,11,.07);border:1px solid var(--borda);border-radius:6px;padding:.85rem">
    <label style="font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ouro);margin-bottom:.5rem;display:block">
      <i class="fa fa-crown"></i> Conteúdo exclusivo
    </label>
    <label class="ck">
      <input type="checkbox" name="exclusivo" value="1" <?=!empty($postEditar['exclusivo'])?'checked':''?>>
      Post exclusivo para assinantes
    </label>
    <p style="font-size:.65rem;color:var(--texto-3);margin-top:.35rem;line-height:1.5">
      Exibe ~35% do conteúdo publicamente e trava o restante com CTA de assinatura.
    </p>
  </div>
  <div class="fg">
    <label>Enquete vinculada</label>
    <select name="enquete_id" class="fi">
      <option value="">— Nenhuma enquete —</option>
      <?php
      try {
        $enqs = $pdo->query("SELECT id, titulo FROM enquetes WHERE ativo=1 ORDER BY id DESC")->fetchAll();
        foreach ($enqs as $eq):
      ?>
        <option value="<?=(int)$eq['id']?>" <?=(($postEditar['enquete_id']??0)==$eq['id'])?'selected':''?>>
          <?=htmlspecialchars($eq['titulo'])?>
        </option>
      <?php endforeach; } catch(Throwable $e){} ?>
    </select>
  </div>
</div>

<!-- ── Disparo de newsletter ── -->
<?php if(!empty($postEditar['id']) && ($postEditar['status']??'')==='publicado'): ?>
<div style="background:rgba(52,152,219,.07);border:1px solid rgba(52,152,219,.3);border-radius:6px;padding:.9rem 1rem;margin-bottom:.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
  <div style="flex:1">
    <div style="font-size:.75rem;font-weight:700;color:#42A5F5;margin-bottom:.2rem">
      <i class="fa fa-paper-plane"></i> Newsletter
    </div>
    <div style="font-size:.72rem;color:var(--texto-3)">
      <?= empty($postEditar['newsletter_enviado']) ? 'Ainda não enviada. Dispara e-mail para toda a lista de inscritos.' : '✓ Newsletter já enviada para este post.' ?>
    </div>
  </div>
  <?php if(empty($postEditar['newsletter_enviado'])): ?>
  <button type="button" class="ba bn" style="width:auto;padding:.45rem 1rem;font-size:.75rem;white-space:nowrap"
          onclick="dispararNewsletter('<?=htmlspecialchars($postEditar['slug']??'')?>')">
    <i class="fa fa-paper-plane"></i> Disparar newsletter
  </button>
  <?php endif; ?>
</div>
<?php endif; ?>
<div class="fa2">
  <button type="submit" class="bs" id="bSv">
    <i class="fa fa-<?=$acao==='criar'?'plus':'floppy-disk'?>"></i>
    <?=$acao==='criar'?'Criar Post':'Salvar Alterações'?>
  </button>
  <a href="blog.php" class="bv2">Cancelar</a>
  <span id="fSt" style="font-size:.82rem"></span>
</div>
</form>

<?php else:?>
<!-- LISTAGEM -->
<div class="sh">
  <h2 class="st">Gerenciar <em>Posts</em></h2>
  <a href="blog.php?acao=criar" class="bn2"><i class="fa fa-plus"></i> Novo Post</a>
</div>
<form method="GET" action="blog.php" class="fb">
  <input type="search" name="busca" placeholder="Buscar…" value="<?=adm_esc($busca)?>" aria-label="Buscar">
  <select name="status">
    <option value="todos" <?=$fStatus==='todos'?'selected':''?>>Todos</option>
    <option value="publicado" <?=$fStatus==='publicado'?'selected':''?>>Publicado</option>
    <option value="oculto" <?=$fStatus==='oculto'?'selected':''?>>Oculto</option>
    <option value="rascunho" <?=$fStatus==='rascunho'?'selected':''?>>Rascunho</option>
  </select>
  <select name="cat">
    <option value="todos" <?=$fCat==='todos'?'selected':''?>>Todas categorias</option>
    <?php foreach($catLabels as $v=>$l):?>
      <option value="<?=$v?>" <?=$fCat===$v?'selected':''?>><?=$l?></option>
    <?php endforeach;?>
  </select>
  <button type="submit" class="bv2" style="padding:.45rem 1rem"><i class="fa fa-search"></i> Filtrar</button>
  <?php if($busca||$fStatus!=='todos'||$fCat!=='todos'):?>
    <a href="blog.php" class="bv2" style="padding:.45rem 1rem"><i class="fa fa-xmark"></i> Limpar</a>
  <?php endif;?>
  <span style="margin-left:auto;font-size:.78rem;color:var(--texto-3)"><?=$total?> post(s)</span>
</form>
<div style="overflow-x:auto">
<table class="tbl">
  <thead><tr><th>#</th><th>Título</th><th>Cat.</th><th>Status</th><th>Publicado</th><th>🎧</th><th>✉</th><th>Ações</th></tr></thead>
  <tbody>
  <?php if(empty($posts)):?>
    <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--texto-3)">Nenhum post.</td></tr>
  <?php else: foreach($posts as $p): $nE=in_array($p['id'],$nlEnv);?>
  <tr id="row-<?=$p['id']?>">
    <td style="color:var(--texto-3);font-size:.78rem"><?=$p['id']?></td>
    <td>
      <div class="ct" title="<?=adm_esc($p['titulo'])?>">
        <?php if($p['destaque']):?><span style="color:var(--ouro);margin-right:.3rem">★</span><?php endif;?>
        <?=adm_esc($p['titulo'])?>
        <?php if(!empty($p['html_externo'])):?><span style="font-size:.65rem;background:rgba(184,134,11,.15);color:var(--ouro);padding:.1rem .35rem;border-radius:8px;margin-left:.3rem">HTML</span><?php endif;?>
      </div>
      <div class="cs">/blog/<?=adm_esc($p['slug'])?>.html</div>
    </td>
    <td style="font-size:.75rem;color:var(--texto-3)"><?=adm_esc($catLabels[$p['categoria']]??$p['categoria'])?></td>
    <td><span class="bdg bdg-<?=$p['status']?>" id="bdg-<?=$p['id']?>"><?=['publicado'=>'✓ Publicado','agendado'=>'⏰ Agendado','oculto'=>'● Oculto','rascunho'=>'○ Rascunho'][$p['status']]??$p['status']?></span></td>
    <td style="font-size:.78rem;white-space:nowrap"><?=$p['publicado_em']?date('d/m/y',strtotime($p['publicado_em'])):'—'?></td>
    <td style="text-align:center"><?=$p['audio_url']?'<i class="fa fa-headphones" style="color:var(--ouro)"></i>':'<span style="opacity:.3">—</span>'?></td>
    <td style="text-align:center"><?=$nE?'<i class="fa fa-circle-check" style="color:#27ae60"></i>':'<span style="opacity:.3">—</span>'?></td>
    <td>
      <div class="ca">
        <a href="blog.php?acao=editar&id=<?=$p['id']?>" class="ba be" title="Editar"><i class="fa fa-pen"></i></a>
        <button class="ba <?=$p['status']==='publicado'?'bv':'be'?>"
                title="<?=$p['status']==='publicado'?'Ocultar':'Publicar'?>"
                onclick="tS(<?=$p['id']?>,'<?=$p['status']==='publicado'?'oculto':'publicado'?>',this)">
          <i class="fa fa-<?=$p['status']==='publicado'?'eye-slash':'eye'?>"></i>
        </button>
        <button class="ba bn" title="<?=$nE?'NL enviada':'Enviar NL'?>"
                <?=($p['status']!=='publicado'||$nE)?'disabled':''?>
                onclick="eN(<?=$p['id']?>,this)"><i class="fa fa-envelope"></i></button>
        <a href="<?= !empty($p['html_externo'])
            ? '../blog/'.adm_esc($p['html_externo'])
            : '../blog/post-template.html?slug='.adm_esc($p['slug'])
        ?>" target="_blank" class="ba bw" title="Ver"><i class="fa fa-arrow-up-right-from-square"></i></a>
        <button class="ba bd" title="Excluir" onclick="eX(<?=$p['id']?>,this)"><i class="fa fa-trash"></i></button>
      </div>
    </td>
  </tr>
  <?php endforeach; endif;?>
  </tbody>
</table>
</div>
<?php if($totP>1):?>
<nav class="pg">
  <?php for($i=1;$i<=$totP;$i++):$qs=http_build_query(['busca'=>$busca,'status'=>$fStatus,'cat'=>$fCat,'p'=>$i]);?>
    <?php if($i===$pag):?><span class="at"><?=$i?></span>
    <?php else:?><a href="blog.php?<?=$qs?>"><?=$i?></a><?php endif;?>
  <?php endfor;?>
</nav>
<?php endif;?>
<?php endif;?>

<?php echo $ADMIN_FOOTER_HTML;?>

<script>
/* ── Slug auto ── */
const sEl=document.getElementById('slg');
function aS(v){if(!sEl||sEl.dataset.m)return;const m={á:'a',à:'a',â:'a',ã:'a',é:'e',ê:'e',í:'i',ó:'o',ô:'o',õ:'o',ú:'u',ü:'u',ç:'c',ñ:'n'};sEl.value=v.toLowerCase().replace(/[^\u0000-\u007E]/g,c=>m[c]||'').replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').trim().substring(0,160);}
sEl?.addEventListener('input',()=>sEl.dataset.m='1');

/* ── Editor ── */
function wS(a,f){const t=document.getElementById('cnt');if(!t)return;const s=t.selectionStart,e=t.selectionEnd,x=t.value.substring(s,e)||'texto';t.value=t.value.substring(0,s)+a+x+f+t.value.substring(e);t.focus();t.selectionStart=s+a.length;t.selectionEnd=s+a.length+x.length;}
function iL(){const u=prompt('URL:');if(!u)return;wS(`<a href="${u}" target="_blank" rel="noopener">`,'</a>');}
function tP(){const t=document.getElementById('cnt'),p=document.getElementById('pv');if(!t||!p)return;if(p.style.display==='block'){p.style.display='none';t.style.display='block';}else{p.innerHTML=t.value;p.style.display='block';t.style.display='none';}}
document.getElementById('cnt')?.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key==='b'){e.preventDefault();wS('<strong>','</strong>');}if((e.ctrlKey||e.metaKey)&&e.key==='i'){e.preventDefault();wS('<em>','</em>');}});

/* ── Submit ── */
document.getElementById('fB')?.addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=document.getElementById('bSv'),st=document.getElementById('fSt');
  btn.disabled=true;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Salvando…';st.textContent='';
  const t=document.getElementById('cnt'),p=document.getElementById('pv');
  if(t)t.style.display='block';if(p)p.style.display='none';
  try{
    const r=await fetch('blog.php',{method:'POST',body:new FormData(e.target)});
    const txt=await r.text();let d;
    try{d=JSON.parse(txt);}catch{st.style.color='#e74c3c';st.textContent='✗ Resposta inválida. Console F12.';console.error(txt.substring(0,400));btn.disabled=false;btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar';return;}
    if(d.ok){st.style.color='#2ecc71';st.textContent='✓ '+(d.msg||'Salvo!');setTimeout(()=>location.href='blog.php',900);}
    else{st.style.color='#e74c3c';st.textContent='✗ '+(d.erro||'Erro.');btn.disabled=false;btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar';}
  }catch(err){st.style.color='#e74c3c';st.textContent='✗ '+err.message;btn.disabled=false;btn.innerHTML='<i class="fa fa-floppy-disk"></i> Salvar';}
});

/* ── Ações tabela ── */
async function _p(d){const f=new FormData();Object.entries(d).forEach(([k,v])=>f.append(k,v));const r=await fetch('blog.php',{method:'POST',body:f});return JSON.parse(await r.text());}
async function tS(id,ns,btn){btn.disabled=true;try{const d=await _p({acao:'toggle_status',id,status:ns});if(d.ok){toast(ns==='publicado'?'Publicado!':'Ocultado.');setTimeout(()=>location.reload(),700);}else{toast(d.erro||'Erro.','erro');btn.disabled=false;}}catch{toast('Erro.','erro');btn.disabled=false;}}
async function eX(id,btn){if(!confirm('Excluir permanentemente?'))return;btn.disabled=true;try{const d=await _p({acao:'excluir',id});if(d.ok){const r=document.getElementById('row-'+id);if(r){r.style.opacity='0';r.style.transition='opacity .3s';setTimeout(()=>r.remove(),350);}toast('Excluído.');}else{toast(d.erro||'Erro.','erro');btn.disabled=false;}}catch{toast('Erro.','erro');btn.disabled=false;}}
async function eN(id,btn){if(!confirm('Enviar newsletter?'))return;btn.disabled=true;try{const d=await _p({acao:'newsletter',post_id:id});if(d.ok){toast(`Enviado! ${d.enviados} e-mails.`);setTimeout(()=>location.reload(),1200);}else{toast(d.erro||'Erro.','erro');btn.disabled=false;}}catch{toast('Erro.','erro');btn.disabled=false;}}

/* ── Disparar newsletter para um post ── */
async function dispararNewsletter(slug) {
  if (!confirm('Disparar newsletter agora para TODOS os inscritos?\nEsta ação não pode ser desfeita.')) return;
  try {
    const r = await fetch('../backend/blog_api.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ acao: 'enviar_newsletter', slug }),
    });
    const d = await r.json();
    if (d.ok) {
      toast(`✓ Newsletter disparada! ${d.enviados} e-mails enviados.`);
      setTimeout(() => location.reload(), 1500);
    } else {
      toast(d.erro || 'Erro ao disparar.', 'erro');
    }
  } catch { toast('Erro de conexão.', 'erro'); }
}

/* ── Upload imagem ── */
const dZ=document.getElementById('dZ'),iF=document.getElementById('iF'),uPg=document.getElementById('uPg'),uRs=document.getElementById('uRs'),iU=document.getElementById('iU'),iUM=document.getElementById('iUM');
function pvU(url){if(!url)return;const img=document.getElementById('pvImg'),ph=document.getElementById('pvPh');const src=url.startsWith('http')?url:'../'+url;if(img){img.src=src;img.style.display='block';img.onerror=()=>img.style.display='none';}if(ph)ph.style.display='none';}
async function doUp(file){
  if(!file?.type.startsWith('image/')){moRes('Arquivo inválido.','erro');return;}
  if(file.size>5*1024*1024){moRes('Máximo 5MB.','erro');return;}
  const slug=document.getElementById('slg')?.value||'post-'+Date.now();
  if(uPg)uPg.style.display='block';if(uRs)uRs.style.display='none';
  const fd=new FormData();fd.append('imagem',file);fd.append('slug',slug);
  try{const r=await fetch('../backend/blog_upload.php',{method:'POST',body:fd});const txt=await r.text();let d;try{d=JSON.parse(txt);}catch{moRes('Resposta inválida.','erro');if(uPg)uPg.style.display='none';return;}if(uPg)uPg.style.display='none';if(d.ok){if(iU)iU.value=d.url;if(iUM)iUM.value=d.url;pvU(d.url);moRes('✓ '+d.mensagem,'ok');}else{moRes('✗ '+(d.erro||'Erro.'),'erro');}}catch(err){if(uPg)uPg.style.display='none';moRes('✗ '+err.message,'erro');}
}
function moRes(msg,tipo){if(!uRs)return;uRs.textContent=msg;uRs.className='ur '+tipo;uRs.style.display='block';}
dZ?.addEventListener('click',()=>iF?.click());
dZ?.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' ')iF?.click();});
iF?.addEventListener('change',()=>{if(iF.files[0])doUp(iF.files[0]);});
dZ?.addEventListener('dragover',e=>{e.preventDefault();dZ.classList.add('dg');});
dZ?.addEventListener('dragleave',()=>dZ.classList.remove('dg'));
dZ?.addEventListener('drop',e=>{e.preventDefault();dZ.classList.remove('dg');const f=e.dataTransfer.files[0];if(f)doUp(f);});
const uA=iU?.value;if(uA)pvU(uA);
</script>
