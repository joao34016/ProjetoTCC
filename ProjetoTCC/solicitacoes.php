<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");

function esc($t) {
    return htmlspecialchars($t);
}

// só o administrador (login em admin_login.php) acessa este painel
$ehAdmin = isset($_SESSION["id_admin"]);

$statusValidos = array("Pendente", "Aprovada", "Recusada");
$filtro = (isset($_GET["status"]) && in_array($_GET["status"], $statusValidos, true)) ? $_GET["status"] : "";

// token simples contra requisições forjadas
if (!isset($_SESSION["csrf_admin"])) {
    $_SESSION["csrf_admin"] = md5(uniqid(mt_rand(), true));
}

// -------- processa aceitar / recusar --------
// (por enquanto só muda o status da solicitação; o que acontece
//  depois de aceitar ou recusar será definido na próxima etapa)
if ($ehAdmin && $_SERVER["REQUEST_METHOD"] === "POST") {

    $msg      = "invalida";
    $tokenOk  = isset($_POST["csrf"]) && $_POST["csrf"] === $_SESSION["csrf_admin"];
    $idAdocao = isset($_POST["id_adocao"]) ? (int) $_POST["id_adocao"] : 0;
    $acao     = isset($_POST["acao"]) ? $_POST["acao"] : "";

    if ($tokenOk && $idAdocao > 0 && ($acao === "aceitar" || $acao === "recusar")) {

        $stmt = $pdo->prepare("SELECT status FROM adocao WHERE id_adocao = ?");
        $stmt->execute(array($idAdocao));
        $statusAtual = $stmt->fetchColumn();

        if ($statusAtual === "Pendente") {
            $novo = ($acao === "aceitar") ? "Aprovada" : "Recusada";
            $pdo->prepare("UPDATE adocao SET status = ? WHERE id_adocao = ?")->execute(array($novo, $idAdocao));
            $msg = ($acao === "aceitar") ? "aceita" : "recusada";
        }
    }

    header("Location: solicitacoes.php?" . ($filtro !== "" ? "status=" . urlencode($filtro) . "&" : "") . "msg=" . $msg);
    exit;
}

// -------- dados do painel --------
$porStatus    = array("Pendente" => 0, "Aprovada" => 0, "Recusada" => 0);
$solicitacoes = array();

if ($ehAdmin) {

    $res = $pdo->query("SELECT status, COUNT(*) AS qtd FROM adocao GROUP BY status");
    foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if (isset($porStatus[$linha["status"]])) {
            $porStatus[$linha["status"]] = (int) $linha["qtd"];
        }
    }

    $sql = "SELECT a.*, an.nome AS animal_nome, an.especie AS animal_especie, an.raca AS animal_raca,
                   an.idade AS animal_idade, an.imagem AS animal_imagem
            FROM adocao a
            LEFT JOIN animal an ON an.id_animal = a.id_animal";

    if ($filtro !== "") {
        $stmt = $pdo->prepare($sql . " WHERE a.status = ? ORDER BY a.data_solicitacao DESC, a.id_adocao DESC");
        $stmt->execute(array($filtro));
    } else {
        $stmt = $pdo->query($sql . " ORDER BY a.data_solicitacao DESC, a.id_adocao DESC");
    }
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalSolicitacoes = array_sum($porStatus);

$mensagens = array(
    "aceita"   => array("ok",    "Solicitação aceita."),
    "recusada" => array("ok",    "Solicitação recusada."),
    "invalida" => array("aviso", "Não foi possível concluir a ação. A solicitação pode já ter sido finalizada.")
);
$msgChave = isset($_GET["msg"]) ? $_GET["msg"] : "";

$classeStatus = array("Pendente" => "pendente", "Aprovada" => "aprovada", "Recusada" => "recusada");
$rotuloStatus = array("Pendente" => "Pendente", "Aprovada" => "Aceita", "Recusada" => "Recusada");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitações de adoção — <?php echo $nomeSite; ?></title>
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
      <?php if ($ehAdmin): ?>
        <a href="solicitacoes.php" class="active-link">Solicitações</a>
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

    <?php if (!$ehAdmin): ?>

      <div class="painel-vazio">
        <div class="icone">🔒</div>
        <h2>Acesso restrito</h2>
        <p>Entre como administrador para ver as solicitações de adoção.</p>
        <a href="admin_login.php" class="paw-btn primary" style="text-decoration:none; display:inline-flex;">
          <span class="paw-btn-icon">🔑</span>
          <span class="paw-btn-text"><strong>Entrar admin</strong><small>Fazer login</small></span>
        </a>
      </div>

    <?php else: ?>

      <div class="painel-topo">
        <p class="eyebrow">Painel do administrador</p>
        <h1>Solicitações de adoção</h1>
        <p>Olá, <?php echo esc(isset($_SESSION["nome_admin"]) ? $_SESSION["nome_admin"] : "admin"); ?>. Abra uma solicitação para ver as respostas e escolher se aceita ou recusa.</p>
      </div>

      <div class="contadores">
        <div class="contador destaque">
          <span class="num"><?php echo $totalSolicitacoes; ?></span>
          <span class="rot">Solicitações no total</span>
        </div>
        <div class="contador alerta">
          <span class="num"><?php echo $porStatus["Pendente"]; ?></span>
          <span class="rot">Pendentes</span>
        </div>
        <div class="contador">
          <span class="num"><?php echo $porStatus["Aprovada"]; ?></span>
          <span class="rot">Aceitas</span>
        </div>
        <div class="contador">
          <span class="num"><?php echo $porStatus["Recusada"]; ?></span>
          <span class="rot">Recusadas</span>
        </div>
      </div>

      <?php if ($msgChave !== "" && isset($mensagens[$msgChave])): ?>
        <p class="msg-painel <?php echo ($mensagens[$msgChave][0] === "aviso" ? "aviso" : ""); ?>">
          <?php echo esc($mensagens[$msgChave][1]); ?>
        </p>
      <?php endif; ?>

      <div class="painel-filtros">
        <a href="solicitacoes.php" class="filtro-chip <?php echo ($filtro === "" ? "ativo" : ""); ?>">Todas <span class="qtd"><?php echo $totalSolicitacoes; ?></span></a>
        <?php foreach ($statusValidos as $st): ?>
          <a href="solicitacoes.php?status=<?php echo urlencode($st); ?>" class="filtro-chip <?php echo ($filtro === $st ? "ativo" : ""); ?>">
            <?php echo esc($rotuloStatus[$st]); ?> <span class="qtd"><?php echo $porStatus[$st]; ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (count($solicitacoes) === 0): ?>

        <div class="painel-vazio">
          <div class="icone">📭</div>
          <h2>Nenhuma solicitação<?php echo ($filtro !== "" ? " com esse status" : " ainda"); ?></h2>
          <p>Quando alguém preencher o formulário de adoção, a solicitação aparece aqui.</p>
        </div>

      <?php else: ?>

        <div class="sol-lista">
        <?php foreach ($solicitacoes as $s): ?>
          <?php
            $pendente   = ($s["status"] === "Pendente");
            $nomeAnimal = ($s["animal_nome"] !== null) ? $s["animal_nome"] : "Animal removido";
            $cls        = isset($classeStatus[$s["status"]]) ? $classeStatus[$s["status"]] : "pendente";
            $rot        = isset($rotuloStatus[$s["status"]]) ? $rotuloStatus[$s["status"]] : $s["status"];

            $grupos = array(
              "Informações pessoais" => array(
                "Nome completo"      => $s["nome_completo"],
                "Idade"              => $s["idade"] . " anos",
                "Telefone"           => $s["telefone"],
                "E-mail"             => $s["email"],
                "Cidade"             => $s["cidade"],
                "Profissão/ocupação" => $s["profissao"]
              ),
              "Sobre a residência" => array(
                "Mora em"                       => $s["tipo_residencia"],
                "O imóvel é"                    => $s["imovel"],
                "Espaço adequado para o animal" => $s["espaco_adequado"],
                "Muro/tela/portão contra fugas" => $s["cercado"]
              ),
              "Condições para cuidar" => array(
                "Condições financeiras (alimentação, vacinas, veterinário)" => $s["condicoes_financeiras"],
                "Preparado para despesas inesperadas de saúde"              => $s["despesas_inesperadas"],
                "Principal responsável pelos cuidados"                      => $s["responsavel_cuidados"]
              ),
              "Rotina e disponibilidade" => array(
                "Tempo que o animal ficará sozinho por dia"           => $s["horas_sozinho"],
                "Tempo para alimentação, higiene, passeios e atenção" => $s["tempo_disponivel"]
              ),
              "Experiência com animais" => array(
                "Já teve animais"                  => $s["ja_teve_animais"],
                "Possui outros animais atualmente" => $s["possui_outros_animais"],
                "Quais outros animais"             => ($s["possui_outros_animais"] === "Sim" && $s["quais_animais"] !== null && $s["quais_animais"] !== "") ? $s["quais_animais"] : "—",
                "Moradores de acordo com a adoção" => $s["moradores_de_acordo"]
              )
            );
          ?>
          <details class="sol">
            <summary class="sol-resumo">
              <div class="sol-foto">
                <?php if (!empty($s["animal_imagem"])): ?>
                  <img src="imagens/animais/<?php echo esc($s["animal_imagem"]); ?>" alt="Foto de <?php echo esc($nomeAnimal); ?>">
                <?php else: ?>
                  <?php echo ($s["animal_especie"] === "Gato" ? "🐈" : ($s["animal_especie"] === "Cachorro" ? "🐕" : "🐾")); ?>
                <?php endif; ?>
              </div>
              <div class="sol-titulo">
                <strong><?php echo esc($nomeAnimal); ?></strong>
                <span>Solicitado por <?php echo esc($s["nome_completo"]); ?> · <?php echo date("d/m/Y H:i", strtotime($s["data_solicitacao"])); ?></span>
              </div>
              <span class="badge <?php echo $cls; ?>"><?php echo esc($rot); ?></span>
              <span class="seta" aria-hidden="true"></span>
            </summary>

            <div class="sol-corpo">

              <?php if ($s["animal_nome"] !== null): ?>
                <p class="sol-animal-info">
                  Animal: <?php echo esc($s["animal_especie"]); ?><?php echo (!empty($s["animal_raca"]) ? " · " . esc($s["animal_raca"]) : ""); ?>
                  · <?php echo (int) $s["animal_idade"]; ?> <?php echo ((int) $s["animal_idade"] === 1 ? "ano" : "anos"); ?>
                </p>
              <?php endif; ?>

              <div class="info-grid">
                <?php foreach ($grupos as $titulo => $campos): ?>
                  <div class="info-bloco">
                    <h3><?php echo esc($titulo); ?></h3>
                    <dl>
                      <?php foreach ($campos as $rotulo => $valor): ?>
                        <div>
                          <dt><?php echo esc($rotulo); ?></dt>
                          <dd><?php echo esc($valor); ?></dd>
                        </div>
                      <?php endforeach; ?>
                    </dl>
                  </div>
                <?php endforeach; ?>

                <div class="info-bloco info-texto">
                  <h3>Motivação</h3>
                  <p>
                    <span class="pergunta-txt">Por que deseja adotar este animal?</span>
                    <?php echo esc($s["motivo"]); ?>
                  </p>
                  <p>
                    <span class="pergunta-txt">O que pretende fazer caso tenha dificuldades para cuidar do animal?</span>
                    <?php echo esc($s["plano_dificuldades"]); ?>
                  </p>
                </div>
              </div>

              <?php if ($pendente): ?>
                <form class="acoes" action="" method="post">
                  <input type="hidden" name="csrf" value="<?php echo esc($_SESSION["csrf_admin"]); ?>">
                  <input type="hidden" name="id_adocao" value="<?php echo (int) $s["id_adocao"]; ?>">

                  <button type="submit" name="acao" value="aceitar" class="acao-btn aprovar"
                          onclick="return confirm('Aceitar a solicitação de <?php echo esc(addslashes($s["nome_completo"])); ?>?');">
                    ✅ Aceitar solicitação
                  </button>

                  <button type="submit" name="acao" value="recusar" class="acao-btn recusar"
                          onclick="return confirm('Recusar esta solicitação de adoção?');">
                    ✖ Recusar
                  </button>
                </form>
              <?php else: ?>
                <p class="acao-final">Esta solicitação já foi finalizada (<?php echo esc(strtolower($rot)); ?>).</p>
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
