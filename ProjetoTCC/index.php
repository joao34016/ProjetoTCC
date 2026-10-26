<?php

require_once "conexao.php";

$nomeSite      = "Patas & Lar";
$anoAtual      = date("Y");
$logado        = isset($_SESSION["id_cliente"]);

// contagens reais vindas do banco
$pessoasCadastradas  = 0;
$animaisCadastrados  = 0;
$adocoesMes          = 0;

try {
    $pessoasCadastradas = (int) $pdo->query("SELECT COUNT(*) FROM cliente")->fetchColumn();
} catch (PDOException $e) {
    $pessoasCadastradas = 0;
}

try {
    $animaisCadastrados = (int) $pdo->query("SELECT COUNT(*) FROM animal")->fetchColumn();
} catch (PDOException $e) {
    $animaisCadastrados = 0;
}

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM animal WHERE adotado = 1 AND MONTH(data_adocao) = MONTH(CURDATE()) AND YEAR(data_adocao) = YEAR(CURDATE())"
    );
    $stmt->execute();
    $adocoesMes = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    $adocoesMes = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $nomeSite; ?> — Adoção de animais</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php  " class="logo">
      <img src="imagens/logo.png.png" alt="Logo Patas & Lar" class="logo-img">
    </a>
    <nav class="main-nav">
      <?php if ($logado): ?>
        <a href="doacoes.php">Faça doações</a>
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

  <section class="hero">
    <div class="container hero-grid">
      <div class="hero-copy">
        <p class="eyebrow">Adoção responsável</p>
        <h1>Todo focinho <span class="highlight">merece um lar</span> pra chamar de seu.</h1>
        <p class="hero-sub">
          Cadastre um bichinho pra adoção ou conheça os que já estão a espera de um lar.
        </p>

        <div class="cta-grid" id="acoes">
          <button type="button" class="paw-btn primary" onclick="window.location.href='cadastro_animal.php'">
            <span class="paw-btn-icon">🐶</span>
            <span class="paw-btn-text">
              <strong>Cadastrar animal</strong>
              <small>Anuncie um bichinho pra adoção</small>
            </span>
          </button>

          <button type="button" class="paw-btn secondary" onclick="window.location.href='animais.php'">
            <span class="paw-btn-icon">📋</span>
            <span class="paw-btn-text">
              <strong>Ver animais</strong>
              <small>Lista completa pra adoção</small>
            </span>
          </button>
        </div>
      </div>

      <div class="hero-art" aria-hidden="true">
        <div class="art-blob"></div>
        <div class="art-card card-1">🐕</div>
        <div class="art-card card-2">🐈</div>
        
      </div>
    </div>
  </section>

  
  <section class="numeros" id="numeros">
    <div class="container numeros-grid">
      <div class="numero-card">
        <span class="numero"><?php echo $pessoasCadastradas; ?></span>
        <span class="numero-label">Pessoas Cadastradas</span>
      </div>
      <div class="numero-card">
        <span class="numero"><?php echo $adocoesMes; ?></span>
        <span class="numero-label">Adoções Neste Mês</span>
      </div>
      <div class="numero-card">
        <span class="numero"><?php echo $animaisCadastrados; ?></span>
        <span class="numero-label">Animais Cadastrados</span>
      </div>
    </div>
  </section>

  <!-- MISSÃO -->
  <section class="missao" id="missao">
    <div class="container missao-grid">
      <div class="missao-texto">
        <p class="eyebrow">Por que existimos</p>
        <h2>Conectamos quem tem amor a dar com quem precisa de um lar.</h2>
        <p>
          Cada patinha que passa por aqui tem um passado. Nosso trabalho
          é simples: aproximar essas histórias das pessoas certas, com um
          processo transparente do primeiro cadastro até o dia da adoção.
        </p>
      </div>
      <ul class="missao-lista">
        <li>
          <span class="bullet">1</span>
          <div>
            <strong>Cadastre-se</strong>
            <p>Crie sua conta como adotante ou como responsável por um abrigo.</p>
          </div>
        </li>
        <li>
          <span class="bullet">2</span>
          <div>
            <strong>Conheça os bichinhos</strong>
            <p>Navegue pela lista de animais disponíveis pra adoção perto de você.</p>
          </div>
        </li>
        <li>
          <span class="bullet">3</span>
          <div>
            <strong>Leve pra casa</strong>
            <p>Combine a visita e comece essa nova amizade com o pé direito.</p>
          </div>
        </li>
      </ul>
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