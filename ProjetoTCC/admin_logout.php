<?php
require_once "conexao.php";
unset($_SESSION["id_admin"]);
unset($_SESSION["admin_nome"]);
header("Location: index.php");
exit;
