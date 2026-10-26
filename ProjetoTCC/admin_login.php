<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");

// admin já logado vai direto para o painel
if (isset($_SESSION["id_admin"])) {
    header("Location: solicitacoes.php");
    exit;
}

$erro     = "";
$valEmail = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $valEmail = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $senha    = isset($_POST["senha"]) ? $_POST["senha"] : "";

    if ($valEmail === "" || $senha === "") {
        $erro = "Preencha e-mail e senha para continuar.";
    } else {

        $stmt = $pdo->prepare("SELECT id_admin, nome, senha FROM administrador WHERE email = ?");
        $stmt->execute(array($valEmail));
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $senhaOk = false;

        if ($admin) {
            if (strpos($admin["senha"], ":") !== false && verificarSenha($senha, $admin["senha"])) {
                // senha já guardada com hash (salt:hash)
                $senhaOk = true;
            } elseif ($senha === $admin["senha"]) {
                // senha ainda em texto puro (ex: inserida direto no banco):
                // aceita e já converte para hash para ficar segura
                $senhaOk = true;
                $upd = $pdo->prepare("UPDATE administrador SET senha = ? WHERE id_admin = ?");
                $upd->execute(array(gerarHashSenha($senha), $admin["id_admin"]));
            }
        }

        if (!$senhaOk) {
            $erro = "E-mail ou senha incorretos.";
        } else {
            $_SESSION["id_admin"]   = $admin["id_admin"];
            $_SESSION["nome_admin"] = $admin["nome"];
            unset($_SESSION["id_cliente"], $_SESSION["nome"]);

            header("Location: solicitacoes.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrar admin — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="login.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <img src="imagens/logo.png.png" alt="Logo Patas & Lar" class="logo-img">
    </a>
    <nav class="main-nav">
      <a href="login.php">Entrar</a>
      <a href="cadastro.php">Cadastrar</a>
      <a href="animais.php">Ver animais</a>
      <a href="admin_login.php" class="active-link">Entrar admin</a>
    </nav>
  </div>
</header>

<main id="topo">

  <section class="login-section">
    <div class="container login-grid">

      <!-- lado ilustrado -->
      <div class="login-art-wrapper">

        <div class="login-art" aria-hidden="true">
          <div class="art-blob"></div>

          <div class="art-card lg-card-1">🐕</div>
          <div class="art-card lg-card-2">🐈</div>
          <div class="art-card lg-card-3">🐾</div>
          <div class="art-card lg-card-4">🦴</div>
          <div class="art-card lg-card-5">😺</div>

          <svg class="paw-path" viewBox="0 0 400 400" aria-hidden="true">
            <text x="100" y="150" class="paw-mark" transform="rotate(-12 20 60)">🐾</text>
            <text x="330" y="80"  class="paw-mark" transform="rotate(15 330 120)">🐾</text>
            <text x="25"  y="330" class="paw-mark" transform="rotate(8 60 330)">🐾</text>
            <text x="330" y="330" class="paw-mark" transform="rotate(-10 300 300)">🐾</text>
          </svg>
        </div>

        <div class="login-art-copy">
          <h2>
            Cada adoção começa com <span class="highlight">uma boa decisão</span>.
          </h2>
          <p class="login-art-sub">
            Entre como administrador para analisar as solicitações de adoção.
          </p>
        </div>

      </div>

      <!-- card de login admin -->
      <div class="login-card">

        <h1>Entrar como admin</h1>
        <p class="login-sub">Acesso exclusivo para quem gerencia as adoções.</p>

        <?php if ($erro !== ""): ?>
          <p style="background:var(--amarelo-100); color:var(--verde-900); border:1px solid var(--amarelo-500); border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; font-weight:700; margin:0 0 18px;">
            <?php echo htmlspecialchars($erro); ?>
          </p>
        <?php endif; ?>

        <form class="login-form" action="" method="post" novalidate>
          <label class="form-field">
            <span>E-mail do administrador</span>
            <input type="email" name="email" placeholder="admin@exemplo.com" value="<?php echo htmlspecialchars($valEmail); ?>" required>
          </label>

          <label class="form-field">
            <span>Senha</span>
            <input type="password" name="senha" placeholder="Digite sua senha" required>
          </label>

          <button type="submit" class="paw-btn primary login-submit">
            <span class="paw-btn-icon">🔑</span>
            <span class="paw-btn-text">
              <strong>Entrar</strong>
              <small>Acessar o painel</small>
            </span>
          </button>
        </form>

        <div class="login-divider"><span>ou</span></div>

        <p class="login-footer-text">
          Não é administrador?
          <a href="login.php" class="link-forte">Entrar como adotante</a>
        </p>
      </div>

    </div>
  </section>

</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <span>© <?php echo $anoAtual; ?> <?php echo $nomeSite; ?>. Feito com carinho por quem ama bicho.</span>
  </div>
</footer>

<script src="script.js"></script>
</body>
</html>
