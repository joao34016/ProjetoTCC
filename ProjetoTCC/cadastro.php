<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");

$erro = "";
$cadastroOk = false;

// valores para manter preenchido em caso de erro
$valNome = "";
$valTelefone = "";
$valEmail = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $valNome     = isset($_POST["nome"]) ? trim($_POST["nome"]) : "";
    $valTelefone = isset($_POST["telefone"]) ? trim($_POST["telefone"]) : "";
    $valEmail    = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $senha       = isset($_POST["senha"]) ? $_POST["senha"] : "";
    $termos      = isset($_POST["termos"]);

    if ($valNome === "" || $valTelefone === "" || $valEmail === "" || $senha === "") {
        $erro = "Preencha todos os campos para continuar.";
    } elseif (!filter_var($valEmail, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif (!$termos) {
        $erro = "Você precisa aceitar os termos de uso.";
    } else {

        // verifica se o e-mail já está cadastrado
        $stmt = $pdo->prepare("SELECT id_cliente FROM cliente WHERE email = ?");
        $stmt->execute(array($valEmail));

        if ($stmt->rowCount() > 0) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO cliente (nome, email, telefone, senha) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute(array($valNome, $valEmail, $valTelefone, gerarHashSenha($senha)));

            $cadastroOk = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastrar — <?php echo $nomeSite; ?></title>
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
      <a href="cadastro.php" class="active-link">Cadastrar</a>
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
            <text x="100"  y="150"  class="paw-mark" transform="rotate(-12 20 60)">🐾</text>
            <text x="330" y="80" class="paw-mark" transform="rotate(15 330 120)">🐾</text>
            <text x="25"  y="330" class="paw-mark" transform="rotate(8 60 330)">🐾</text>
            <text x="330" y="330" class="paw-mark" transform="rotate(-10 300 300)">🐾</text>
        </svg>
    </div>

    <div class="login-art-copy">
        <p class="eyebrow">Comece por aqui</p>
        <h2>Faça parte de quem <span class="highlight">muda uma história</span> de patinhas.</h2>
        <p class="login-art-sub">
            Crie sua conta pra adotar, acompanhar processos e ficar de olho nos bichinhos disponíveis.
        </p>
    </div>

</div>


      

      <!-- card de cadastro -->
      <div class="login-card">
        
        <h1>Criar conta</h1>
        <p class="login-sub">Preencha seus dados pra começar a fazer parte dessa rede.</p>

        <?php if ($erro !== ""): ?>
          <p style="background:var(--amarelo-100); color:var(--verde-900); border:1px solid var(--amarelo-500); border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; font-weight:700; margin:0 0 18px;">
            <?php echo htmlspecialchars($erro); ?>
          </p>
        <?php endif; ?>

        <form class="login-form" action="" method="post" novalidate>
          <label class="form-field">
            <span>Nome completo</span>
            <input type="text" name="nome" placeholder="Digite seu nome completo" value="<?php echo htmlspecialchars($valNome); ?>" required>
          </label>

          <label class="form-field">
            <span>Telefone</span>
            <input type="tel" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($valTelefone); ?>" required>
          </label>

          <label class="form-field">
            <span>E-mail</span>
            <input type="email" name="email" placeholder="seuemail@exemplo.com" value="<?php echo htmlspecialchars($valEmail); ?>" required>
          </label>

          <label class="form-field">
            <span>Senha</span>
            <input type="password" name="senha" placeholder="Crie uma senha" required>
          </label>

          <p class="termos-link-linha">
            <a href="termos.php" target="_blank" class="link-suave">Leia os termos de uso</a>
          </p>

          <div class="form-row">
            <label class="checkbox-field">
              <input type="checkbox" name="termos" required>
              <span>Aceito os termos de uso</span>
            </label>
          </div>

          <button type="submit" class="paw-btn primary login-submit">
            <span class="paw-btn-icon">🐾</span>
            <span class="paw-btn-text">
              <strong>Cadastrar</strong>
              <small>Criar minha conta</small>
            </span>
          </button>
        </form>

        <div class="login-divider"><span>ou</span></div>

        <p class="login-footer-text">
          Já tem uma conta?
          <a href="login.php" class="link-forte">Entrar</a>
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

<?php if ($cadastroOk): ?>
<script>
  alert("Cadastro concluido com sucesso!");
  window.location.href = "login.php";
</script>
<?php endif; ?>

</body>
</html>
