<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");

$logado = isset($_SESSION["id_cliente"]);

$erro = "";
$cadastroOk = false;

// valores para manter preenchido em caso de erro
$valNome    = "";
$valEspecie = "";
$valRaca    = "";
$valIdade   = "";
$valSexo    = "";
$valPorte   = "";

if ($logado && $_SERVER["REQUEST_METHOD"] === "POST") {

    $valNome    = isset($_POST["nome"]) ? trim($_POST["nome"]) : "";
    $valEspecie = isset($_POST["especie"]) ? trim($_POST["especie"]) : "";
    $valRaca    = isset($_POST["raca"]) ? trim($_POST["raca"]) : "";
    $valIdade   = isset($_POST["idade"]) ? trim($_POST["idade"]) : "";
    $valSexo    = isset($_POST["sexo"]) ? trim($_POST["sexo"]) : "";
    $valPorte   = isset($_POST["porte"]) ? trim($_POST["porte"]) : "";

    $temImagem = isset($_FILES["imagem"]) && $_FILES["imagem"]["error"] !== UPLOAD_ERR_NO_FILE;

    if ($valNome === "" || $valEspecie === "" || $valIdade === "" || $valSexo === "" || $valPorte === "") {
        $erro = "Preencha todos os campos obrigatórios para continuar.";
    } elseif (!ctype_digit($valIdade)) {
        $erro = "Informe uma idade válida (somente números).";
    } elseif ($temImagem && $_FILES["imagem"]["error"] !== UPLOAD_ERR_OK) {
        $erro = "Houve um problema ao enviar a imagem. Tente novamente.";
    } else {

        $nomeImagem = null;

        if ($temImagem) {

            $extensoesPermitidas = array("jpg", "jpeg", "png", "gif", "webp");
            $extensao = strtolower(pathinfo($_FILES["imagem"]["name"], PATHINFO_EXTENSION));

            if (!in_array($extensao, $extensoesPermitidas)) {
                $erro = "Envie uma imagem em JPG, PNG, GIF ou WEBP.";
            } else {

                $pastaDestino = "imagens/animais";
                if (!is_dir($pastaDestino)) {
                    mkdir($pastaDestino, 0755, true);
                }

                $nomeImagem  = uniqid("animal_") . "." . $extensao;
                $caminhoFull = $pastaDestino . "/" . $nomeImagem;

                if (!move_uploaded_file($_FILES["imagem"]["tmp_name"], $caminhoFull)) {
                    $erro = "Não foi possível salvar a imagem enviada.";
                }
            }
        }

        if ($erro === "") {
            $stmt = $pdo->prepare(
                "INSERT INTO animal (nome, especie, raca, idade, sexo, porte, imagem, id_cliente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute(array(
                $valNome,
                $valEspecie,
                ($valRaca !== "" ? $valRaca : null),
                (int) $valIdade,
                $valSexo,
                $valPorte,
                $nomeImagem,
                $_SESSION["id_cliente"]
            ));

            $cadastroOk = true;

            // limpa os campos após salvar com sucesso
            $valNome = $valEspecie = $valRaca = $valIdade = $valSexo = $valPorte = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cadastrar animal — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="login.css">
<link rel="stylesheet" href="cadastro_animal.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <img src="imagens/logo.png.png" alt="Logo Patas & Lar" class="logo-img">
    </a>
    <nav class="main-nav">
      <?php if ($logado): ?>
        <a href="doacoes.php">Faça doações</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="cadastro_animal.php" class="active-link">Cadastrar animal</a>
        <a href="animais.php">Ver animais</a>
        <a href="logout.php">Sair</a>
        <?php echo sinoNotificacoes($notifPendentes); ?>
      <?php elseif ($logadoAdmin): ?>
        <a href="solicitacoes.php">Solicitações</a>
        <a href="doacoes_admin.php">Doações</a>
        <a href="animais.php">Ver animais</a>
        <a href="logout.php">Sair</a>
      <?php else: ?>
        <a href="login.php">Entrar</a>
        <a href="cadastro.php">Cadastrar</a>
        <a href="doacoes.php">Faça doações</a>
        <a href="cadastro_animal.php" class="active-link">Cadastrar animal</a>
        <a href="animais.php">Ver animais</a>
        <a href="admin_login.php">Entrar admin</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main id="topo">

  <section class="animal-section">
    <div class="container animal-container">

      <div class="animal-card">

        <p class="eyebrow">Anuncie um bichinho</p>
        <h1>Cadastrar animal</h1>

        <?php if (!$logado): ?>

          <p class="animal-sub">Você precisa estar logado pra cadastrar um animal.</p>

          <p style="background:var(--amarelo-100); color:var(--verde-900); border:1px solid var(--amarelo-500); border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; font-weight:700; margin:0 0 24px;">
            Faça login pra continuar com o cadastro do bichinho.
          </p>

          <a href="login.php" class="paw-btn primary animal-submit" style="text-decoration:none; display:flex;">
            <span class="paw-btn-icon">🐾</span>
            <span class="paw-btn-text">
              <strong>Entrar na conta</strong>
              <small>Faça login pra continuar</small>
            </span>
          </a>

        <?php else: ?>

        <p class="animal-sub">Preencha os dados do bichinho pra colocar ele na lista de adoção.</p>

        <?php if ($erro !== ""): ?>
          <p style="background:var(--amarelo-100); color:var(--verde-900); border:1px solid var(--amarelo-500); border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; font-weight:700; margin:0 0 18px;">
            <?php echo htmlspecialchars($erro); ?>
          </p>
        <?php endif; ?>

        <?php if ($cadastroOk): ?>
          <p style="background:var(--verde-100); color:var(--verde-900); border:1px solid var(--verde-600); border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; font-weight:700; margin:0 0 18px;">
            Animal cadastrado com sucesso!
          </p>
        <?php endif; ?>

        <form class="animal-form" action="" method="post" enctype="multipart/form-data" novalidate>

          <div class="animal-form-grid">

            <label class="form-field full">
              <span>Nome do animal</span>
              <input type="text" name="nome" placeholder="Ex: Bidu" value="<?php echo htmlspecialchars($valNome); ?>" required>
            </label>

            <label class="form-field">
              <span>Espécie</span>
              <select name="especie" required>
                <option value="" disabled <?php echo ($valEspecie === "" ? "selected" : ""); ?>>Selecione</option>
                <option value="Cachorro" <?php echo ($valEspecie === "Cachorro" ? "selected" : ""); ?>>Cachorro</option>
                <option value="Gato" <?php echo ($valEspecie === "Gato" ? "selected" : ""); ?>>Gato</option>
                <option value="Outro" <?php echo ($valEspecie === "Outro" ? "selected" : ""); ?>>Outro</option>
              </select>
            </label>

            <label class="form-field">
              <span>Raça</span>
              <input type="text" name="raca" placeholder="Ex: SRD, Labrador..." value="<?php echo htmlspecialchars($valRaca); ?>">
            </label>

            <label class="form-field">
              <span>Idade (anos)</span>
              <input type="number" name="idade" min="0" max="40" placeholder="Ex: 2" value="<?php echo htmlspecialchars($valIdade); ?>" required>
            </label>

            <label class="form-field">
              <span>Sexo</span>
              <select name="sexo" required>
                <option value="" disabled <?php echo ($valSexo === "" ? "selected" : ""); ?>>Selecione</option>
                <option value="Macho" <?php echo ($valSexo === "Macho" ? "selected" : ""); ?>>Macho</option>
                <option value="Fêmea" <?php echo ($valSexo === "Fêmea" ? "selected" : ""); ?>>Fêmea</option>
              </select>
            </label>

            <label class="form-field full">
              <span>Porte</span>
              <select name="porte" required>
                <option value="" disabled <?php echo ($valPorte === "" ? "selected" : ""); ?>>Selecione</option>
                <option value="Pequeno" <?php echo ($valPorte === "Pequeno" ? "selected" : ""); ?>>Pequeno</option>
                <option value="Médio" <?php echo ($valPorte === "Médio" ? "selected" : ""); ?>>Médio</option>
                <option value="Grande" <?php echo ($valPorte === "Grande" ? "selected" : ""); ?>>Grande</option>
              </select>
            </label>

          </div>

          <label class="form-field-file">
            <span>Foto do animal</span>
            <div class="file-drop" id="fileDrop">
              <div class="file-drop-icon">📷</div>
              <div class="file-drop-text">
                <strong id="fileLabel">Clique pra escolher uma imagem</strong>
                <small>JPG, PNG, GIF ou WEBP</small>
              </div>
              <input type="file" name="imagem" id="inputImagem" accept="image/*">
            </div>
            <div class="animal-preview" id="previewWrapper">
              <img id="previewImg" src="" alt="Pré-visualização">
              <span id="previewNome" style="font-size:13.5px; color:var(--texto-suave); font-weight:700;"></span>
            </div>
          </label>

          <button type="submit" class="paw-btn primary animal-submit">
            <span class="paw-btn-icon">🐾</span>
            <span class="paw-btn-text">
              <strong>Salvar</strong>
              <small>Cadastrar animal pra adoção</small>
            </span>
          </button>

        </form>

        <?php endif; ?>

        <div class="animal-voltar">
          <a href="index.php" class="link-forte">← Voltar para o início</a>
        </div>

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
<script>
  var inputImagem   = document.getElementById("inputImagem");
  var fileLabel     = document.getElementById("fileLabel");
  var previewWrapper = document.getElementById("previewWrapper");
  var previewImg    = document.getElementById("previewImg");
  var previewNome   = document.getElementById("previewNome");

  if (inputImagem) {
    inputImagem.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        var arquivo = this.files[0];
        fileLabel.textContent = "Imagem selecionada";
        previewNome.textContent = arquivo.name;
        previewWrapper.style.display = "flex";

        var leitor = new FileReader();
        leitor.onload = function (e) {
          previewImg.src = e.target.result;
        };
        leitor.readAsDataURL(arquivo);
      } else {
        fileLabel.textContent = "Clique pra escolher uma imagem";
        previewWrapper.style.display = "none";
      }
    });
  }
</script>

<?php if ($cadastroOk): ?>
<script>
  alert("Animal cadastrado com sucesso!");
</script>
<?php endif; ?>

</body>
</html>
