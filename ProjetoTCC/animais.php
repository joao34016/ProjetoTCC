<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");
$logado   = isset($_SESSION["id_cliente"]);

// -------- filtros (vindos da URL, via GET) --------
$especiesValidas = array("Cachorro", "Gato", "Outro");
$portesValidos   = array("Pequeno", "Médio", "Grande");
$sexosValidos    = array("Macho", "Fêmea");

$filtroEspecie = (isset($_GET["especie"]) && in_array($_GET["especie"], $especiesValidas, true)) ? $_GET["especie"] : "";
$filtroPorte   = (isset($_GET["porte"])   && in_array($_GET["porte"],   $portesValidos,   true)) ? $_GET["porte"]   : "";
$filtroSexo    = (isset($_GET["sexo"])    && in_array($_GET["sexo"],    $sexosValidos,    true)) ? $_GET["sexo"]    : "";
$filtroRaca    = isset($_GET["raca"]) ? trim($_GET["raca"]) : "";
$filtroIdade   = (isset($_GET["idade"]) && ctype_digit(trim($_GET["idade"]))) ? trim($_GET["idade"]) : "";

// só lista os animais que ainda estão disponíveis (não adotados)
$where  = array("(adotado = 0 OR adotado IS NULL)");
$params = array();

if ($filtroEspecie !== "") { $where[] = "especie = ?"; $params[] = $filtroEspecie; }
if ($filtroPorte   !== "") { $where[] = "porte = ?";   $params[] = $filtroPorte; }
if ($filtroSexo    !== "") { $where[] = "sexo = ?";    $params[] = $filtroSexo; }
if ($filtroIdade   !== "") { $where[] = "idade = ?";   $params[] = (int) $filtroIdade; }
if ($filtroRaca    !== "") {
    // busca parcial: "poodle" encontra "Poodle", "Poodle toy", etc.
    $where[] = "raca LIKE ?";
    $params[] = "%" . str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $filtroRaca) . "%";
}

$temFiltro = (count($params) > 0);

$stmt = $pdo->prepare("SELECT * FROM animal WHERE " . implode(" AND ", $where) . " ORDER BY id_animal DESC");
$stmt->execute($params);

$animais = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Animais para adoção — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="animais.css">
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
        <a href="cadastro_animal.php">Cadastrar animal</a>
        <a href="animais.php" class="active-link">Ver animais</a>
        <a href="logout.php">Sair</a>
        <?php echo sinoNotificacoes($notifPendentes); ?>
      <?php elseif ($logadoAdmin): ?>
        <a href="solicitacoes.php">Solicitações</a>
        <a href="doacoes_admin.php">Doações</a>
        <a href="animais.php" class="active-link">Ver animais</a>
        <a href="logout.php">Sair</a>
      <?php else: ?>
        <a href="login.php">Entrar</a>
        <a href="cadastro.php">Cadastrar</a>
        <a href="animais.php" class="active-link">Ver animais</a>
        <a href="admin_login.php">Entrar admin</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main id="topo">

  <section class="animais-hero">
    <div class="container">
      <p class="eyebrow">Bichinhos disponíveis</p>
      <h1>Encontre <span class="highlight">seu novo melhor amigo</span></h1>
      <p class="animais-hero-sub">
        Esses são os animais cadastrados até agora esperando por um lar.
      </p>

      <form class="filtro-form" action="animais.php" method="get">
        <div class="filtro-grid">

          <label class="filtro-campo">
            <span>Espécie</span>
            <select name="especie">
              <option value="">Todas</option>
              <?php foreach ($especiesValidas as $op): ?>
                <option value="<?php echo $op; ?>" <?php echo ($filtroEspecie === $op ? "selected" : ""); ?>><?php echo $op; ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="filtro-campo">
            <span>Raça</span>
            <input type="text" name="raca" placeholder="Ex: Poodle, SRD..." value="<?php echo htmlspecialchars($filtroRaca); ?>">
          </label>

          <label class="filtro-campo">
            <span>Porte</span>
            <select name="porte">
              <option value="">Todos</option>
              <?php foreach ($portesValidos as $op): ?>
                <option value="<?php echo $op; ?>" <?php echo ($filtroPorte === $op ? "selected" : ""); ?>><?php echo $op; ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="filtro-campo">
            <span>Sexo</span>
            <select name="sexo">
              <option value="">Todos</option>
              <?php foreach ($sexosValidos as $op): ?>
                <option value="<?php echo $op; ?>" <?php echo ($filtroSexo === $op ? "selected" : ""); ?>><?php echo $op; ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="filtro-campo">
            <span>Idade (anos)</span>
            <input type="number" name="idade" min="0" max="40" placeholder="Qualquer" value="<?php echo htmlspecialchars($filtroIdade); ?>">
          </label>

        </div>

        <div class="filtro-acoes">
          <button type="submit" class="filtro-btn">🔍 Filtrar</button>
          <?php if ($temFiltro): ?>
            <a href="animais.php" class="filtro-limpar">Limpar filtros</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </section>

  <section class="animais-section">
    <div class="container">

      <?php if (count($animais) === 0): ?>

        <div class="animais-vazio">
          <span class="animais-vazio-icon">🐾</span>
          <h2>Nenhum animal encontrado</h2>
          <p><?php echo ($temFiltro ? "Nenhum bichinho corresponde aos filtros escolhidos. Tente mudar ou limpar os filtros." : "Ainda não há bichinhos cadastrados."); ?></p>
          <?php if ($logado): ?>
            <a href="cadastro_animal.php" class="paw-btn primary">
              <span class="paw-btn-icon">🐶</span>
              <span class="paw-btn-text">
                <strong>Cadastrar animal</strong>
                <small>Seja o primeiro a anunciar</small>
              </span>
            </a>
          <?php endif; ?>
        </div>

      <?php else: ?>

        <?php if ($temFiltro): ?>
          <p class="filtro-resultado"><?php echo count($animais); ?> <?php echo (count($animais) === 1 ? "animal encontrado" : "animais encontrados"); ?></p>
        <?php endif; ?>

        <div class="animais-grid">
          <?php foreach ($animais as $animal): ?>
            <div class="animal-item">

              <div class="animal-item-foto">
                <?php if (!empty($animal["imagem"])): ?>
                  <img src="imagens/animais/<?php echo htmlspecialchars($animal["imagem"]); ?>" alt="Foto de <?php echo htmlspecialchars($animal["nome"]); ?>">
                <?php else: ?>
                  <span class="animal-item-placeholder"><?php echo ($animal["especie"] === "Gato" ? "🐈" : ($animal["especie"] === "Cachorro" ? "🐕" : "🐾")); ?></span>
                <?php endif; ?>
                <span class="animal-item-porte"><?php echo htmlspecialchars($animal["porte"]); ?></span>
              </div>

              <div class="animal-item-info">
                <h3><?php echo htmlspecialchars($animal["nome"]); ?></h3>
                <p class="animal-item-raca">
                  <?php echo htmlspecialchars($animal["especie"]); ?><?php echo (!empty($animal["raca"]) ? " · " . htmlspecialchars($animal["raca"]) : ""); ?>
                </p>

                <div class="animal-item-tags">
                  <span class="tag"><?php echo (int) $animal["idade"]; ?> <?php echo ((int) $animal["idade"] === 1 ? "ano" : "anos"); ?></span>
                  <span class="tag"><?php echo htmlspecialchars($animal["sexo"]); ?></span>
                </div>
              </div>

              <div class="animal-item-adotar">
                <a href="formulario_adocao.php?id_animal=<?php echo (int) $animal["id_animal"]; ?>" class="paw-btn primary animal-item-adotar-btn" style="text-decoration:none;">
                  <span class="paw-btn-icon">🐾</span>
                  <span class="paw-btn-text">
                    <strong>Adotar</strong>
                    <small><?php echo htmlspecialchars($animal["nome"]); ?></small>
                  </span>
                </a>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

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
