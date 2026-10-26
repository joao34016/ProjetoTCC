<?php
// -----------------------------------------
// Patas & Lar — diagnóstico do login de administrador
// Rode pelo navegador, veja o resultado e DEPOIS APAGUE este arquivo.
// -----------------------------------------

require_once "conexao.php";

echo "<h2>1) Linhas na tabela administrador</h2>";

try {
    $linhas = $pdo->query("SELECT id_admin, nome, email, senha FROM administrador")->fetchAll(PDO::FETCH_ASSOC);

    if (count($linhas) === 0) {
        echo "<p><strong>Nenhum administrador encontrado.</strong> O criar_admin.php não chegou a inserir nada, ou a tabela está vazia.</p>";
    } else {
        echo "<table border='1' cellpadding='6' style='border-collapse:collapse;'>";
        echo "<tr><th>id_admin</th><th>nome</th><th>email</th><th>formato da senha</th></tr>";
        foreach ($linhas as $l) {
            $formatoOk = (strpos($l["senha"], ":") !== false) ? "ok (salt:hash)" : "❌ formato estranho";
            echo "<tr><td>{$l['id_admin']}</td><td>" . htmlspecialchars($l['nome']) . "</td><td>" . htmlspecialchars($l['email']) . "</td><td>{$formatoOk}</td></tr>";
        }
        echo "</table>";
        echo "<p>Confira se o e-mail acima é <strong>exatamente</strong> igual ao que você digita no login (sem espaço extra, maiúsculas/minúsculas, etc).</p>";
    }
} catch (PDOException $e) {
    echo "<p><strong>Erro ao consultar a tabela administrador:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Isso costuma acontecer quando o <code>atualizacoes_banco.sql</code> ainda não foi executado no phpMyAdmin.</p>";
}

echo "<hr><h2>2) Testar um e-mail/senha manualmente</h2>";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $senha = isset($_POST["senha"]) ? $_POST["senha"] : "";

    $stmt = $pdo->prepare("SELECT * FROM administrador WHERE email = ?");
    $stmt->execute(array($email));
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "<p style='color:red;'><strong>Não existe nenhum administrador com esse e-mail exato.</strong></p>";
    } elseif (verificarSenha($senha, $admin["senha"])) {
        echo "<p style='color:green;'><strong>Login bateu certinho!</strong> Esse e-mail e senha funcionam. Se mesmo assim o login do site der erro, o problema é de sessão/CSRF, não de credenciais — me avise.</p>";
    } else {
        echo "<p style='color:red;'><strong>E-mail encontrado, mas a senha não confere</strong> com o hash salvo para ele.</p>";
    }
}
?>

<form method="post">
  <label>E-mail: <input type="email" name="email" required></label><br><br>
  <label>Senha: <input type="text" name="senha" required></label><br><br>
  <button type="submit">Testar</button>
</form>
