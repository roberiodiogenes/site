<?php
/* ================================================================
   ROBÉRIO DIÓGENES — _404.php
   Serve a página 404 com o status HTTP correto (404).
   Funciona em XAMPP (site em subpasta) e em produção (raiz).
   ================================================================ */
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . '/404.html');
exit;
