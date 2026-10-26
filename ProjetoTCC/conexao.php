<?php
// -----------------------------------------
// Patas & Lar — Conexão com o banco de dados
// Compatível com PHP 5.3 (EasyPHP)
// Ajuste $host, $usuario e $senhaBanco conforme
// a configuração do seu servidor MySQL.
// -----------------------------------------

// inicia a sessão (usada pro login do cliente) caso ainda não tenha iniciado
// (session_id() em vez de session_status(), que só existe a partir do PHP 5.4)
if (session_id() === '') {
    session_start();
}

// true quando um administrador está logado (login em admin_login.php)
$logadoAdmin = isset($_SESSION["id_admin"]);

$host       = "localhost";
$dbname     = "patas_lar";
$usuario    = "root";
$senhaBanco = "";

try {
    $pdo = new PDO(
        "mysql:host=" . $host . ";dbname=" . $dbname . ";charset=utf8",
        $usuario,
        $senhaBanco
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// -----------------------------------------
// Funções de hash de senha compatíveis com PHP 5.3
// (o crypt()/bcrypt desse EasyPHP não funciona
// direito no Windows, então usamos SHA-256 com
// salt aleatório por usuário, via hash(), que
// existe desde o PHP 5.1 e é confiável aqui)
// -----------------------------------------
if (!function_exists('gerarHashSenha')) {
    function gerarHashSenha($senha) {
        // salt aleatório de 32 caracteres
        $salt = '';
        for ($i = 0; $i < 32; $i++) {
            $salt .= dechex(mt_rand(0, 15));
        }
        $hash = hash('sha256', $salt . $senha);
        // guardamos "salt:hash" no mesmo campo (cabe em VARCHAR(255))
        return $salt . ':' . $hash;
    }
}

if (!function_exists('verificarSenha')) {
    function verificarSenha($senha, $hashArmazenado) {
        $partes = explode(':', $hashArmazenado);
        if (count($partes) !== 2) {
            return false;
        }
        list($salt, $hashSalvo) = $partes;
        $hashCalculado = hash('sha256', $salt . $senha);
        return $hashCalculado === $hashSalvo;
    }
}


// -----------------------------------------
// Notificações do dono do animal (botão do sino)
// Conta os pedidos de adoção ainda sem resposta
// para os animais cadastrados pelo cliente logado.
// -----------------------------------------
$notifPendentes = 0;
if (isset($_SESSION["id_cliente"]) && !isset($_SESSION["id_admin"])) {
    try {
        $stmtNotif = $pdo->prepare(
            "SELECT COUNT(*) FROM adocao a INNER JOIN animal an ON an.id_animal = a.id_animal
             WHERE an.id_cliente = ? AND a.status_dono = 'Pendente'"
        );
        $stmtNotif->execute(array($_SESSION["id_cliente"]));
        $notifPendentes = (int) $stmtNotif->fetchColumn();
    } catch (PDOException $e) {
        $notifPendentes = 0; // coluna status_dono ainda não criada
    }
}

if (!function_exists('sinoNotificacoes')) {
    function sinoNotificacoes($qtd, $ativo = false) {
        $html = '<a href="notificacoes.php" class="sino-btn' . ($ativo ? ' active-link' : '') . '" title="Notificações" aria-label="Notificações">🔔';
        if ($qtd > 0) {
            $html .= '<span class="sino-badge">' . (int) $qtd . '</span>';
        }
        return $html . '</a>';
    }
}
