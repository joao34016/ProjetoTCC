<?php

require_once "conexao.php";
require_once "doacao_config.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");

function esc($t) {
    return htmlspecialchars($t);
}

// só o administrador (login em admin_login.php) acessa este painel
$ehAdmin = isset($_SESSION["id_admin"]);

$filtro = (isset($_GET["status"]) && in_array($_GET["status"], $statusDoacao, true)) ? $_GET["status"] : "";

// token simples contra requisições forjadas (mesmo usado em solicitacoes.php)
if (!isset($_SESSION["csrf_admin"])) {
    $_SESSION["csrf_admin"] = md5(uniqid(mt_rand(), true));
}

// -------- marcar como recebida / cancelar --------
if ($ehAdmin && $_SERVER["REQUEST_METHOD"] === "POST") {

    $msg      = "invalida";
    $tokenOk  = isset($_POST["csrf"]) && $_POST["csrf"] === $_SESSION["csrf_admin"];
    $idDoacao = isset($_POST["id_doacao"]) ? (int) $_POST["id_doacao"] : 0;
    $acao     = isset($_POST["acao"]) ? $_POST["acao"] : "";

    if ($tokenOk && $idDoacao > 0 && ($acao === "receber" || $acao === "cancelar")) {
        try {
            $novo = ($acao === "receber") ? "Recebida" : "Cancelada";
            // só muda se ainda estiver aguardando entrega
            $stmt = $pdo->prepare("UPDATE doacao SET status = ?, data_status = ? WHERE id_doacao = ? AND status = 'Aguardando entrega'");
            $stmt->execute(array($novo, date("Y-m-d H:i:s"), $idDoacao));
            if ($stmt->rowCount() > 0) {
                $msg = ($acao === "receber") ? "recebida" : "cancelada";
            }
        } catch (PDOException $e) {
            $msg = "invalida";
        }
    }

    header("Location: doacoes_admin.php?" . ($filtro !== "" ? "status=" . urlencode($filtro) . "&" : "") . "msg=" . $msg);
    exit;
}

// -------- dados do painel --------
$porStatus   = array("Aguardando entrega" => 0, "Recebida" => 0, "Cancelada" => 0);
$doacoes     = array();
$faltaTabela = false;

if ($ehAdmin) {
    try {
        $res = $pdo->query("SELECT status, COUNT(*) AS qtd FROM doacao GROUP BY status");
        foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            if (isset($porStatus[$linha["status"]])) {
                $porStatus[$linha["status"]] = (int) $linha["qtd"];
            }
        }

        $sql = "SELECT d.*, c.nome AS doador_nome, c.telefone AS doador_telefone, c.email AS doador_email
                FROM doacao d
                LEFT JOIN cliente c ON c.id_cliente = d.id_cliente";

        if ($filtro !== "") {
            $stmt = $pdo->prepare($sql . " WHERE d.status = ? ORDER BY d.data_doacao DESC, d.id_doacao DESC");
            $stmt->execute(array($filtro));
        } else {
            $stmt = $pdo->query($sql . " ORDER BY d.data_doacao DESC, d.id_doacao DESC");
        }
        $doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $faltaTabela = true;
    }
}

$totalDoacoes = array_sum($porStatus);

$mensagens = array(
    "recebida"  => array("ok",    "Doação marcada como recebida."),
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
<title>Doações — <?php echo $nomeSite; ?></title>
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
      <?php if ($ehAdmin): ?>
        <a href="solicitacoes.php">Solicitações</a>
        <a href="doacoes_admin.php" class="active-link">Doações</a>
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

    <?php if (!$ehAdmin): ?>

      <div class="painel-vazio">
        <div class="icone">🔒</div>
        <h2>Acesso restrito</h2>
        <p>Entre como administrador para ver as doações.</p>
        <a href="admin_login.php" class="paw-btn primary" style="text-decoration:none; display:inline-flex;">
          <span class="paw-btn-icon">🔑</span>
          <span class="paw-btn-text"><strong>Entrar admin</strong><small>Fazer login</small></span>
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
        <p class="eyebrow">Painel do administrador</p>
        <h1>Doações recebidas</h1>
        <p>Olá, <?php echo esc(isset($_SESSION["nome_admin"]) ? $_SESSION["nome_admin"] : "admin"); ?>. Quando o item chegar ao ponto de coleta, abra a doação e marque como recebida.</p>
      </div>

      <div class="contadores">
        <div class="contador destaque">
          <span class="num"><?php echo $totalDoacoes; ?></span>
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

      <div class="painel-filtros">
        <a href="doacoes_admin.php" class="filtro-chip <?php echo ($filtro === "" ? "ativo" : ""); ?>">Todas <span class="qtd"><?php echo $totalDoacoes; ?></span></a>
        <?php foreach ($statusDoacao as $st): ?>
          <a href="doacoes_admin.php?status=<?php echo urlencode($st); ?>" class="filtro-chip <?php echo ($filtro === $st ? "ativo" : ""); ?>">
            <?php echo esc($st); ?> <span class="qtd"><?php echo $porStatus[$st]; ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (count($doacoes) === 0): ?>

        <div class="painel-vazio">
          <div class="icone">📭</div>
          <h2>Nenhuma doação<?php echo ($filtro !== "" ? " com esse status" : " ainda"); ?></h2>
          <p>Quando alguém preencher o formulário de doação, ela aparece aqui.</p>
        </div>

      <?php else: ?>

        <div class="sol-lista">
        <?php foreach ($doacoes as $d): ?>
          <?php
            $aguardando = ($d["status"] === "Aguardando entrega");
            $cls        = isset($classeStatusDoacao[$d["status"]]) ? $classeStatusDoacao[$d["status"]] : "pendente";
            $icone      = isset($tiposDoacao[$d["tipo"]]) ? $tiposDoacao[$d["tipo"]] : "🎁";
            $doador     = ($d["doador_nome"] !== null) ? $d["doador_nome"] : "Visitante (sem cadastro)";
          ?>
          <details class="sol">
            <summary class="sol-resumo">
              <div class="sol-foto"><?php echo $icone; ?></div>
              <div class="sol-titulo">
                <strong><?php echo esc($d["item"]); ?></strong>
                <span><?php echo esc($d["tipo"]); ?> · Doado por <?php echo esc($doador); ?> · <?php echo date("d/m/Y H:i", strtotime($d["data_doacao"])); ?></span>
              </div>
              <span class="badge <?php echo $cls; ?>"><?php echo esc($d["status"]); ?></span>
              <span class="seta" aria-hidden="true"></span>
            </summary>

            <div class="sol-corpo">

              <div class="info-grid">
                <div class="info-bloco">
                  <h3>Doação</h3>
                  <dl>
                    <div><dt>Número</dt><dd>#<?php echo (int) $d["id_doacao"]; ?></dd></div>
                    <div><dt>Tipo</dt><dd><?php echo esc($d["tipo"]); ?></dd></div>
                    <div><dt>O que será doado</dt><dd><?php echo esc($d["item"]); ?></dd></div>
                    <div><dt>Registrada em</dt><dd><?php echo date("d/m/Y H:i", strtotime($d["data_doacao"])); ?></dd></div>
                    <?php if (!empty($d["data_status"])): ?>
                      <div><dt>Status alterado em</dt><dd><?php echo date("d/m/Y H:i", strtotime($d["data_status"])); ?></dd></div>
                    <?php endif; ?>
                  </dl>
                </div>

                <div class="info-bloco">
                  <h3>Doador</h3>
                  <dl>
                    <div><dt>Nome</dt><dd><?php echo esc($doador); ?></dd></div>
                    <div><dt>Telefone</dt><dd><?php echo ($d["doador_telefone"] !== null && $d["doador_telefone"] !== "") ? esc($d["doador_telefone"]) : "—"; ?></dd></div>
                    <div><dt>E-mail</dt><dd><?php echo ($d["doador_email"] !== null && $d["doador_email"] !== "") ? esc($d["doador_email"]) : "—"; ?></dd></div>
                  </dl>
                </div>
              </div>

              <?php if ($aguardando): ?>
                <form class="acoes" action="" method="post">
                  <input type="hidden" name="csrf" value="<?php echo esc($_SESSION["csrf_admin"]); ?>">
                  <input type="hidden" name="id_doacao" value="<?php echo (int) $d["id_doacao"]; ?>">

                  <button type="submit" name="acao" value="receber" class="acao-btn aprovar"
                          onclick="return confirm('Confirmar que esta doação foi recebida?');">
                    ✅ Marcar como recebida
                  </button>

                  <button type="submit" name="acao" value="cancelar" class="acao-btn recusar"
                          onclick="return confirm('Cancelar esta doação?');">
                    ✖ Cancelar
                  </button>
                </form>
              <?php else: ?>
                <p class="acao-final">Esta doação já foi finalizada (<?php echo esc(strtolower($d["status"])); ?>).</p>
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
