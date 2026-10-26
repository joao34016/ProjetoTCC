<?php

require_once "conexao.php";
require_once "doacao_config.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");
$logado   = isset($_SESSION["id_cliente"]) && !isset($_SESSION["id_admin"]);

function esc($t) {
    return htmlspecialchars($t);
}

$erro    = "";
$valTipo = "";
$valItem = "";

// -------- envia a doação --------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $valTipo = isset($_POST["tipo"]) ? trim($_POST["tipo"]) : "";
    $valItem = isset($_POST["item"]) ? trim($_POST["item"]) : "";

    if (!isset($tiposDoacao[$valTipo])) {
        $erro = "Escolha o tipo de doação.";
    } elseif ($valItem === "") {
        $erro = "Informe o que você vai doar.";
    } elseif (strlen($valItem) > 150) {
        $erro = "Descreva o item em até 150 caracteres.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO doacao (id_cliente, tipo, item, status, data_doacao) VALUES (?, ?, ?, 'Aguardando entrega', ?)"
            );
            $stmt->execute(array(
                ($logado ? (int) $_SESSION["id_cliente"] : null),
                $valTipo,
                $valItem,
                date("Y-m-d H:i:s")
            ));

            // guarda qual foi a doação para mostrar a confirmação
            $_SESSION["ultima_doacao"] = (int) $pdo->lastInsertId();

            // redireciona: atualizar a página não repete a doação
            header("Location: doacoes.php?enviada=1");
            exit;

        } catch (PDOException $e) {
            $erro = "Não foi possível registrar a doação. Rode o arquivo banco_doacoes.sql no banco e tente de novo.";
        }
    }
}

// -------- tela de confirmação --------
$confirmacao = null;
if (isset($_GET["enviada"]) && isset($_SESSION["ultima_doacao"])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM doacao WHERE id_doacao = ?");
        $stmt->execute(array((int) $_SESSION["ultima_doacao"]));
        $linha = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($linha) {
            $confirmacao = $linha;
        }
    } catch (PDOException $e) {
        $confirmacao = null;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Faça doações — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="solicitacoes.css">
<link rel="stylesheet" href="doacoes.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <img src="imagens/logo.png.png" alt="Logo Patas & Lar" class="logo-img">
    </a>
    <nav class="main-nav">
      <?php if ($logado): ?>
        <a href="doacoes.php" class="active-link">Faça doações</a>
        <a href="minhas_doacoes.php">Minhas doações</a>
        <a href="cadastro_animal.php">Cadastrar animal</a>
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
        <a href="animais.php">Ver animais</a>
        <a href="admin_login.php">Entrar admin</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main id="topo">

<?php if ($confirmacao !== null): ?>

  <!-- ===== confirmação + próximos passos ===== -->
  <section class="doacoes-section conf-section">
    <div class="container doacoes-container">

      <div class="doacoes-card conf-card">

        <div class="conf-icone" aria-hidden="true">💛</div>
        <p class="eyebrow">Doação registrada</p>
        <h1>Obrigado pela sua doação!</h1>
        <p class="conf-sub">Agora é só levar o item até o nosso ponto de coleta. Veja abaixo como fazer isso.</p>

        <div class="conf-resumo">
          <div class="conf-resumo-icone"><?php echo isset($tiposDoacao[$confirmacao["tipo"]]) ? $tiposDoacao[$confirmacao["tipo"]] : "🎁"; ?></div>
          <div class="conf-resumo-info">
            <small>Doação nº <?php echo (int) $confirmacao["id_doacao"]; ?> · <?php echo esc($confirmacao["tipo"]); ?></small>
            <strong><?php echo esc($confirmacao["item"]); ?></strong>
            <span><?php echo date("d/m/Y H:i", strtotime($confirmacao["data_doacao"])); ?></span>
          </div>
          <span class="badge pendente"><?php echo esc($confirmacao["status"]); ?></span>
        </div>

        <?php
          $msgWhats = "Olá! Fiz uma doação pelo " . $nomeSite . " (nº " . (int) $confirmacao["id_doacao"] . "): "
                    . $confirmacao["tipo"] . " - " . $confirmacao["item"] . ". Gostaria de combinar a entrega.";
          echo cartaoPontoColeta($msgWhats);
        ?>

        <?php if ($logado): ?>
          <p class="conf-nota">Você pode acompanhar o status desta doação em <a href="minhas_doacoes.php" class="link-forte">Minhas doações</a>.</p>
        <?php else: ?>
          <p class="conf-nota"><a href="login.php" class="link-forte">Entre na sua conta</a> nas próximas doações para acompanhar o status de cada uma.</p>
        <?php endif; ?>

        <div class="conf-acoes">
          <a href="doacoes.php" class="paw-btn primary conf-btn">
            <span class="paw-btn-icon">🎁</span>
            <span class="paw-btn-text"><strong>Fazer outra doação</strong><small>Voltar ao formulário</small></span>
          </a>
          <a href="animais.php" class="paw-btn secondary conf-btn">
            <span class="paw-btn-icon">🐾</span>
            <span class="paw-btn-text"><strong>Ver animais</strong><small>Conheça quem espera por um lar</small></span>
          </a>
        </div>

      </div>
    </div>
  </section>

<?php else: ?>

  <!-- ===== introdução ===== -->
  <section class="doacoes-hero">
    <div class="container doacoes-container">
      <p class="eyebrow">Doações</p>
      <h1>Toda ajuda <span class="highlight">faz a diferença</span></h1>
      <p class="doacoes-hero-sub">
        Você pode contribuir com ração, medicamentos, brinquedos, caminhas e outros itens que ajudam a melhorar a vida dos animais.
      </p>
    </div>
  </section>

  <!-- ===== formulário de doação ===== -->
  <section class="doacoes-section">
    <div class="container doacoes-container">
      <div class="doacoes-card">

        <h2>O que você quer doar?</h2>

        <?php if ($erro !== ""): ?>
          <p class="doacoes-erro"><?php echo esc($erro); ?></p>
        <?php endif; ?>

        <form class="doacoes-form" action="" method="post" novalidate>

          <fieldset class="doacoes-tipo">
            <legend>Tipo de doação</legend>
            <div class="tipo-grid">
              <?php foreach ($tiposDoacao as $tipo => $icone): ?>
                <label class="tipo-opcao">
                  <input type="radio" name="tipo" value="<?php echo esc($tipo); ?>" <?php echo ($valTipo === $tipo ? "checked" : ""); ?>>
                  <span>
                    <em><?php echo $icone; ?></em>
                    <?php echo esc($tipo); ?>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </fieldset>

          <label class="doacoes-campo">
            <span>O que será doado?</span>
            <input type="text" name="item" maxlength="150" placeholder="Ex: 2 caminhas, 1 pacote de tapete higiênico..." value="<?php echo esc($valItem); ?>" required>
          </label>

          <button type="submit" class="paw-btn primary doacoes-submit">
            <span class="paw-btn-icon">💛</span>
            <span class="paw-btn-text">
              <strong>Doar</strong>
              <small>Enviar minha doação</small>
            </span>
          </button>

        </form>

      </div>
    </div>
  </section>

<?php endif; ?>

</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <span>© <?php echo $anoAtual; ?> <?php echo $nomeSite; ?>. Feito com carinho por quem ama bicho.</span>
  </div>
</footer>

<script src="script.js"></script>
</body>
</html>
