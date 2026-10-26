<?php

require_once "conexao.php";
require_once "doacao_config.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");
$logado   = isset($_SESSION["id_cliente"]) && !isset($_SESSION["id_admin"]);

function esc($t) {
    return htmlspecialchars($t);
}

// token simples contra requisições forjadas
if (!isset($_SESSION["csrf_minhas_doacoes"])) {
    $_SESSION["csrf_minhas_doacoes"] = md5(uniqid(mt_rand(), true));
}

// -------- cancelar uma doação que ainda aguarda entrega --------
if ($logado && $_SERVER["REQUEST_METHOD"] === "POST") {

    $msg       = "invalida";
    $tokenOk   = isset($_POST["csrf"]) && $_POST["csrf"] === $_SESSION["csrf_minhas_doacoes"];
    $idDoacao  = isset($_POST["id_doacao"]) ? (int) $_POST["id_doacao"] : 0;
    $acao      = isset($_POST["acao"]) ? $_POST["acao"] : "";

    if ($tokenOk && $idDoacao > 0 && $acao === "cancelar") {
        try {
            $stmt = $pdo->prepare(
                "UPDATE doacao SET status = 'Cancelada', data_status = ?
                 WHERE id_doacao = ? AND id_cliente = ? AND status = 'Aguardando entrega'"
            );
            $stmt->execute(array(date("Y-m-d H:i:s"), $idDoacao, $_SESSION["id_cliente"]));
            if ($stmt->rowCount() > 0) {
                $msg = "cancelada";
            }
        } catch (PDOException $e) {
            $msg = "invalida";
        }
    }

    header("Location: minhas_doacoes.php?msg=" . $msg);
    exit;
}

// -------- dados --------
$doacoes      = array();
$porStatus    = array("Aguardando entrega" => 0, "Recebida" => 0, "Cancelada" => 0);
$faltaTabela  = false;

if ($logado) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM doacao WHERE id_cliente = ? ORDER BY data_doacao DESC, id_doacao DESC");
        $stmt->execute(array($_SESSION["id_cliente"]));
        $doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($doacoes as $d) {
            if (isset($porStatus[$d["status"]])) {
                $porStatus[$d["status"]]++;
            }
        }
    } catch (PDOException $e) {
        $faltaTabela = true;
    }
}

$mensagens = array(
    "cancelada" => array("ok",    "Doação cancelada."),
    "invalida"  => array("aviso", "Não foi possível concluir a ação. A doação pode já ter sido finalizada.")
);
$msgChave = isset($_GET["msg"]) ? $_GET["msg"] : "";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minhas doações — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="login.css">
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
        <a href="doacoes.php">Faça doações</a>
        <a href="minhas_doacoes.php" class="active-link">Minhas doações</a>
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
  <section class="painel-section">
    <div class="container">

    <?php if (!$logado): ?>

      <div class="painel-vazio">
        <div class="icone">🔒</div>
        <h2>Entre para ver suas doações</h2>
        <p>Aqui você acompanha o status das doações feitas com a sua conta.</p>
        <a href="login.php" class="paw-btn primary" style="text-decoration:none; display:inline-flex;">
          <span class="paw-btn-icon">🐾</span>
          <span class="paw-btn-text"><strong>Entrar na conta</strong><small>Fazer login</small></span>
        </a>
      </div>

    <?php elseif ($faltaTabela): ?>

      <div class="painel-vazio">
        <div class="icone">🛠️</div>
        <h2>Falta atualizar o banco</h2>
        <p>Rode o arquivo <code>banco_doacoes.sql</code> no banco <code>patas_lar</code> para ativar as doações.</p>
      </div>

    <?php else: ?>

      <div class="painel-topo">
        <p class="eyebrow">Seu histórico</p>
        <h1>Minhas doações</h1>
        <p>Acompanhe o status de cada doação que você fez.</p>
      </div>

      <div class="contadores">
        <div class="contador destaque">
          <span class="num"><?php echo count($doacoes); ?></span>
          <span class="rot">Doações no total</span>
        </div>
        <div class="contador alerta">
          <span class="num"><?php echo $porStatus["Aguardando entrega"]; ?></span>
          <span class="rot">Aguardando entrega</span>
        </div>
        <div class="contador">
          <span class="num"><?php echo $porStatus["Recebida"]; ?></span>
          <span class="rot">Recebidas</span>
        </div>
        <div class="contador">
          <span class="num"><?php echo $porStatus["Cancelada"]; ?></span>
          <span class="rot">Canceladas</span>
        </div>
      </div>

      <?php if ($msgChave !== "" && isset($mensagens[$msgChave])): ?>
        <p class="msg-painel <?php echo ($mensagens[$msgChave][0] === "aviso" ? "aviso" : ""); ?>">
          <?php echo esc($mensagens[$msgChave][1]); ?>
        </p>
      <?php endif; ?>

      <?php if (count($doacoes) === 0): ?>

        <div class="painel-vazio">
          <div class="icone">🎁</div>
          <h2>Você ainda não fez nenhuma doação</h2>
          <p>Toda ajuda faz a diferença. Que tal começar agora?</p>
          <a href="doacoes.php" class="paw-btn primary" style="text-decoration:none; display:inline-flex;">
            <span class="paw-btn-icon">💛</span>
            <span class="paw-btn-text"><strong>Fazer uma doação</strong><small>Escolher o que doar</small></span>
          </a>
        </div>

      <?php else: ?>

        <div class="sol-lista">
        <?php foreach ($doacoes as $d): ?>
          <?php
            $aguardando = ($d["status"] === "Aguardando entrega");
            $cls        = isset($classeStatusDoacao[$d["status"]]) ? $classeStatusDoacao[$d["status"]] : "pendente";
            $icone      = isset($tiposDoacao[$d["tipo"]]) ? $tiposDoacao[$d["tipo"]] : "🎁";
          ?>
          <details class="sol">
            <summary class="sol-resumo">
              <div class="sol-foto"><?php echo $icone; ?></div>
              <div class="sol-titulo">
                <strong><?php echo esc($d["item"]); ?></strong>
                <span><?php echo esc($d["tipo"]); ?> · <?php echo date("d/m/Y H:i", strtotime($d["data_doacao"])); ?></span>
              </div>
              <span class="badge <?php echo $cls; ?>"><?php echo esc($d["status"]); ?></span>
              <span class="seta" aria-hidden="true"></span>
            </summary>

            <div class="sol-corpo">

              <div class="info-grid">
                <div class="info-bloco info-texto">
                  <h3>Detalhes da doação</h3>
                  <dl style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px 24px; margin-top:10px;">
                    <div><dt>Número</dt><dd>#<?php echo (int) $d["id_doacao"]; ?></dd></div>
                    <div><dt>Tipo</dt><dd><?php echo esc($d["tipo"]); ?></dd></div>
                    <div><dt>O que foi doado</dt><dd><?php echo esc($d["item"]); ?></dd></div>
                    <div><dt>Registrada em</dt><dd><?php echo date("d/m/Y H:i", strtotime($d["data_doacao"])); ?></dd></div>
                    <?php if (!empty($d["data_status"])): ?>
                      <div><dt>Status alterado em</dt><dd><?php echo date("d/m/Y H:i", strtotime($d["data_status"])); ?></dd></div>
                    <?php endif; ?>
                  </dl>
                </div>
              </div>

              <?php if ($aguardando): ?>

                <?php
                  $msgWhats = "Olá! Fiz uma doação pelo " . $nomeSite . " (nº " . (int) $d["id_doacao"] . "): "
                            . $d["tipo"] . " - " . $d["item"] . ". Gostaria de combinar a entrega.";
                  echo cartaoPontoColeta($msgWhats);
                ?>

                <form class="acoes" action="" method="post">
                  <input type="hidden" name="csrf" value="<?php echo esc($_SESSION["csrf_minhas_doacoes"]); ?>">
                  <input type="hidden" name="id_doacao" value="<?php echo (int) $d["id_doacao"]; ?>">
                  <button type="submit" name="acao" value="cancelar" class="acao-btn recusar"
                          onclick="return confirm('Cancelar esta doação?');">
                    ✖ Cancelar doação
                  </button>
                </form>

              <?php elseif ($d["status"] === "Recebida"): ?>
                <p class="acao-final">Sua doação foi recebida. Muito obrigado por ajudar! 💛</p>
              <?php else: ?>
                <p class="acao-final">Esta doação foi cancelada.</p>
              <?php endif; ?>

            </div>
          </details>
        <?php endforeach; ?>
        </div>

      <?php endif; ?>

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
