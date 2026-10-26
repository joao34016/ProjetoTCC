<?php
// Encerra a sessão do cliente e volta pra home
session_start();
$_SESSION = array();
session_destroy();
header("Location: index.php");
exit;
