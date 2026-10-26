<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");
$logado   = isset($_SESSION["id_cliente"]) && !isset($_SESSION["id_admin"]);

function esc($t) {
    return htmlspecialchars($t);
}

// token simples contra requisições forjadas
if (!isset($_SESSION["csrf_notif"])) {
    $_SESSION["csrf_notif"] = md5(uniqid(mt_rand(), true));
}

// -------- aceitar / recusar adoção --------
if ($logado && $_SERVER["REQUEST_METHOD"] === "POST") {

    $msg      = "invalida";
    $tokenOk  = isset($_POST["csrf"]) && $_POST["csrf"] === $_SESSION["csrf_notif"];
    $idAdocao = isset($_POST["id_adocao"]) ? (int) $_POST["id_adocao"] : 0;
    $acao     = isset($_POST["acao"]) ? $_POST["acao"] : "";

    if ($tokenOk && $idAdocao > 0 && ($acao === "aceitar" || $acao === "recusar")) {

        // só vale se o animal for deste usuário e o pedido ainda estiver sem resposta
        $stmt = $pdo->prepare(
            "SELECT a.id_adocao, a.id_animal, an.imagem
             FROM adocao a INNER JOIN animal an ON an.id_animal = a.id_animal
             WHERE a.id_adocao = ? AND an.id_cliente = ? AND a.status_dono = 'Pendente'"
        );
        $stmt->execute(array($idAdocao, $_SESSION["id_cliente"]));
        $sol = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sol) {

            if ($acao === "recusar") {
                // o animal continua normalmente disponível
                $pdo->prepare("UPDATE adocao SET status_dono = 'Recusada' WHERE id_adocao = ?")->execute(array($idAdocao));
                $msg = "recusada";

            } else {
                // aceitar = animal entregue: ele é excluído do banco
                try {
                    $pdo->beginTransaction();

                    $pdo->prepare("UPDATE adocao SET status_dono = 'Aceita' WHERE id_adocao = ?")->execute(array($idAdocao));

                    // os outros pedidos para o mesmo animal perdem o sentido
                    $pdo->prepare("UPDATE adocao SET status_dono = 'Recusada' WHERE id_animal = ? AND id_adocao <> ? AND status_dono = 'Pendente'")
                        ->execute(array($sol["id_animal"], $idAdocao));

                    $pdo->prepare("DELETE FROM animal WHERE id_animal = ? AND id_cliente = ?")
                        ->execute(array($sol["id_animal"], $_SESSION["id_cliente"]));

                    $pdo->commit();

                    // apaga também a foto do animal
                    if (!empty($sol["imagem"])) {
                        $arquivo = "imagens/animais/" . basename($sol["imagem"]);
                        if (is_file($arquivo)) {
                            @unlink($arquivo);
                        }
                    }
                    $msg = "aceita";

                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $msg = "erro_exclusao";
                }
            }
        }
    }

    header("Location: notificacoes.php?msg=" . $msg);
    exit;
}

// -------- notificações (pedidos sem resposta para os animais do usuário) --------
$notificacoes = array();
$faltaColuna  = false;

if ($logado) {
    try {
        $stmt = $pdo->prepare(
            "SELECT a.id_adocao, a.data_solicitacao, a.nome_completo, a.idade, a.telefone, a.email, a.cidade, a.profissao,
                    an.nome AS animal_nome, an.especie AS animal_especie, an.imagem AS animal_imagem
             FROM adocao a INNER JOIN animal an ON an.id_animal = a.id_animal
             WHERE an.id_cliente = ? AND a.status_dono = 'Pendente'
             ORDER BY a.data_solicitacao DESC, a.id_adocao DESC"
        );
        $stmt->execute(array($_SESSION["id_cliente"]));
        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $faltaColuna = true;
    }
}

$mensagens = array(
    "aceita"        => array("ok",    "Adoção aceita! O animal foi entregue e removido da lista."),
    "recusada"      => array("ok",    "Adoção recusada. O animal continua disponível normalmente."),
    "erro_exclusao" => array("aviso", "Não foi possível remover o animal. Rode o arquivo banco_notificacoes.sql no banco e tente de novo."),
    "invalida"      => array("aviso", "Não foi possível concluir a ação. A solicitação pode já ter sido respondida.")
);
$msgChave = isset($_GET["msg"]) ? $_GET["msg"] : "";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notificações — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="login.css">
<link rel="stylesheet" href="solicitacoes.css">
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
        <a href="animais.php">Ver animais</a>
        <a href="logout.php">Sair</a>
        <?php echo sinoNotificacoes($notifPendentes, true); ?>
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
        <h2>Entre para ver suas notificações</h2>
        <p>As notificações aparecem para quem cadastrou animais e recebeu pedidos de adoção.</p>
        <a href="login.php" class="paw-btn primary" style="text-decoration:none; display:inline-flex;">
          <span class="paw-btn-icon">🐾</span>
          <span class="paw-btn-text"><strong>Entrar na conta</strong><small>Fazer login</small></span>
        </a>
      </div>

    <?php elseif ($faltaColuna): ?>

      <div class="painel-vazio">
        <div class="icone">🛠️</div>
        <h2>Falta atualizar o banco</h2>
        <p>Rode o arquivo <code>banco_notificacoes.sql</code> no banco <code>patas_lar</code> para ativar as notificações.</p>
      </div>

    <?php else: ?>

      <div class="painel-topo">
        <p class="eyebrow">Suas notificações</p>
        <h1>Pedidos de adoção</h1>
        <p>Pessoas que querem adotar os animais que você cadastrou. Entrem em contato para combinar a entrega e depois responda aqui.</p>
      </div>

      <div style="height:26px;"></div>

      <?php if ($msgChave !== "" && isset($mensagens[$msgChave])): ?>
        <p class="msg-painel <?php echo ($mensagens[$msgChave][0] === "aviso" ? "aviso" : ""); ?>">
          <?php echo esc($mensagens[$msgChave][1]); ?>
        </p>
      <?php endif; ?>

      <?php if (count($notificacoes) === 0): ?>

        <div class="painel-vazio">
          <div class="icone">🔔</div>
          <h2>Nenhuma notificação</h2>
          <p>Quando alguém pedir para adotar um animal que você cadastrou, o pedido aparece aqui.</p>
        </div>

      <?php else: ?>

        <div class="sol-lista">
        <?php foreach ($notificacoes as $n): ?>
          <details class="sol">
            <summary class="sol-resumo">
              <div class="sol-foto">
                <?php if (!empty($n["animal_imagem"])): ?>
                  <img src="imagens/animais/<?php echo esc($n["animal_imagem"]); ?>" alt="Foto de <?php echo esc($n["animal_nome"]); ?>">
                <?php else: ?>
                  <?php echo ($n["animal_especie"] === "Gato" ? "🐈" : ($n["animal_especie"] === "Cachorro" ? "🐕" : "🐾")); ?>
                <?php endif; ?>
              </div>
              <div class="sol-titulo">
                <strong><?php echo esc($n["animal_nome"]); ?></strong>
                <span><?php echo esc($n["nome_completo"]); ?> quer adotar · <?php echo date("d/m/Y H:i", strtotime($n["data_solicitacao"])); ?></span>
              </div>
              <span class="badge pendente">Novo</span>
              <span class="seta" aria-hidden="true"></span>
            </summary>

            <div class="sol-corpo">

              <div class="info-grid">
                <div class="info-bloco info-texto">
                  <h3>Informações pessoais</h3>
                  <dl style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px 24px; margin-top:10px;">
                    <div><dt>Nome completo</dt><dd><?php echo esc($n["nome_completo"]); ?></dd></div>
                    <div><dt>Idade</dt><dd><?php echo (int) $n["idade"]; ?> anos</dd></div>
                    <div><dt>Telefone</dt><dd><?php echo esc($n["telefone"]); ?></dd></div>
                    <div><dt>E-mail</dt><dd><?php echo esc($n["email"]); ?></dd></div>
                    <div><dt>Cidade</dt><dd><?php echo esc($n["cidade"]); ?></dd></div>
                    <div><dt>Profissão/ocupação</dt><dd><?php echo esc($n["profissao"]); ?></dd></div>
                  </dl>
                </div>
              </div>

              <form class="acoes" action="" method="post">
                <input type="hidden" name="csrf" value="<?php echo esc($_SESSION["csrf_notif"]); ?>">
                <input type="hidden" name="id_adocao" value="<?php echo (int) $n["id_adocao"]; ?>">

                <button type="submit" name="acao" value="aceitar" class="acao-btn aprovar"
                        onclick="return confirm('Aceitar a adoção de <?php echo esc(addslashes($n["animal_nome"])); ?>? Isso significa que o animal já foi entregue e ele será excluído da lista.');">
                  ✅ Aceitar adoção
                </button>

                <button type="submit" name="acao" value="recusar" class="acao-btn recusar"
                        onclick="return confirm('Recusar este pedido de adoção? O animal continuará disponível.');">
                  ✖ Recusar adoção
                </button>
              </form>

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
