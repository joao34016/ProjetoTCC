<?php
// -----------------------------------------
// Patas & Lar — criação do primeiro administrador
//
// 1) Troque o nome, e-mail e senha abaixo pelos dados reais do admin.
// 2) Rode este arquivo UMA VEZ pelo navegador, ex:
//    http://localhost/ProjetoTCC/criar_admin.php
// 3) Depois disso, APAGUE este arquivo do servidor por segurança.
// -----------------------------------------

require_once "conexao.php";

$nomeAdmin  = "Administrador";
$emailAdmin = "admin@pataselar.com";
$senhaAdmin = "adm123";

$stmt = $pdo->prepare("SELECT id_admin FROM administrador WHERE email = ?");
$stmt->execute(array($emailAdmin));

if ($stmt->rowCount() > 0) {
    echo "Já existe um administrador cadastrado com esse e-mail.";
} else {
    $stmt = $pdo->prepare("INSERT INTO administrador (nome, email, senha) VALUES (?, ?, ?)");
    $stmt->execute(array($nomeAdmin, $emailAdmin, gerarHashSenha($senhaAdmin)));
    echo "Administrador criado com sucesso! Agora apague o arquivo criar_admin.php do servidor.";
}
