<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");

$erro       = "";
$redefinido = false;
$valEmail   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $valEmail  = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $novaSenha = isset($_POST["nova_senha"]) ? $_POST["nova_senha"] : "";
    $confirma  = isset($_POST["confirmar_senha"]) ? $_POST["confirmar_senha"] : "";

    if ($valEmail === "" || $novaSenha === "" || $confirma === "") {
        $erro = "Preencha todos os campos para continuar.";
    } elseif (!filter_var($valEmail, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (strlen($novaSenha) < 6) {
        $erro = "A nova senha precisa ter pelo menos 6 caracteres.";
    } elseif ($novaSenha !== $confirma) {
        $erro = "As senhas não coincidem. Digite novamente.";
    } else {

        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE email = ?");
        $stmt->execute(array($valEmail));
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            $erro = "Não encontramos uma conta com esse e-mail.";
        } else {
            $stmt = $pdo->prepare("UPDATE cliente SET senha = ? WHERE id_cliente = ?");
            $stmt->execute(array(gerarHashSenha($novaSenha), $cliente["id_cliente"]));

            $redefinido = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar nova senha — <?php echo $nomeSite; ?></title>
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
      <a href="login.php" class="active-link">Entrar</a>
      <a href="cadastro.php">Cadastrar</a>
      <a href="animais.php">Ver animais</a>
      <a href="admin_login.php">Entrar admin</a>
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
          <p class="eyebrow">Sem problemas</p>
          <h2>
            Vamos te ajudar a <span class="highlight">voltar pra casa</span>.
          </h2>
          <p class="login-art-sub">
            Crie uma nova senha pra acessar sua conta e continuar ajudando quem mais precisa.
          </p>
        </div>

      </div>

      <!-- card de nova senha -->
      <div class="login-card">

        <h1>Criar nova senha</h1>
        <p class="login-sub">Digite e confirme a nova senha que você quer usar.</p>

        <?php if ($erro !== ""): ?>
          <p style="background:var(--amarelo-100); color:var(--verde-900); border:1px solid var(--amarelo-500); border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; font-weight:700; margin:0 0 18px;">
            <?php echo htmlspecialchars($erro); ?>
          </p>
        <?php endif; ?>

        <form class="login-form" action="" method="post" novalidate>
          <label class="form-field">
            <span>E-mail da conta</span>
            <input type="email" name="email" placeholder="seuemail@exemplo.com" value="<?php echo htmlspecialchars($valEmail); ?>" required>
          </label>

          <label class="form-field">
            <span>Nova senha</span>
            <input type="password" name="nova_senha" placeholder="Digite a nova senha" minlength="6" required>
          </label>

          <label class="form-field">
            <span>Confirmar senha</span>
            <input type="password" name="confirmar_senha" placeholder="Digite a senha novamente" minlength="6" required>
          </label>

          <button type="submit" class="paw-btn primary login-submit">
            <span class="paw-btn-icon">🔑</span>
            <span class="paw-btn-text">
              <strong>Salvar nova senha</strong>
              <small>Redefinir minha senha</small>
            </span>
          </button>
        </form>

        <div class="login-divider"><span>ou</span></div>

        <p class="login-footer-text">
          Lembrou da senha?
          <a href="login.php" class="link-forte">Voltar para o login</a>
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

<?php if ($redefinido): ?>
<script>
  alert("Senha alterada com sucesso! Faça login com a nova senha.");
  window.location.href = "login.php";
</script>
<?php endif; ?>

</body>
</html>
