<?php
session_name('rd_admin_sess');
session_start();
session_destroy();
header('Location: login.php');
exit;
