<?php

require_once "conexao.php";

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");
$logado   = isset($_SESSION["id_cliente"]);

$idAnimal = isset($_GET["id_animal"]) ? (int) $_GET["id_animal"] : 0;

// -------- perguntas de múltipla escolha (campo => alternativas) --------
$opSN   = array("Sim", "Não");
$radios = array(
    "tipo_residencia"       => array("Casa", "Apartamento", "Outro"),
    "imovel"                => array("Próprio", "Alugado"),
    "espaco_adequado"       => $opSN,
    "cercado"               => array("Sim", "Não", "Parcialmente"),
    "condicoes_financeiras" => $opSN,
    "despesas_inesperadas"  => $opSN,
    "responsavel_cuidados"  => array("Eu", "Outra pessoa", "Dividiremos os cuidados"),
    "horas_sozinho"         => array("Menos de 2 horas", "2–4 horas", "4–8 horas", "Mais de 8 horas"),
    "tempo_disponivel"      => $opSN,
    "ja_teve_animais"       => $opSN,
    "possui_outros_animais" => $opSN,
    "moradores_de_acordo"   => $opSN
);

// desenha um grupo de alternativas
function grupoRadio($nome, $opcoes, $atual) {
    $html = '<div class="radio-group">';
    foreach ($opcoes as $op) {
        $checked = ($atual === $op) ? " checked" : "";
        $html .= '<label class="radio-pill"><input type="radio" name="' . $nome . '" value="' . htmlspecialchars($op) . '"' . $checked . '>'
               . '<span>' . htmlspecialchars($op) . '</span></label>';
    }
    return $html . '</div>';
}

// -------- animal solicitado (precisa existir e estar disponível) --------
$animal = null;
if ($idAnimal > 0) {
    $stmt = $pdo->prepare("SELECT * FROM animal WHERE id_animal = ? AND (adotado = 0 OR adotado IS NULL)");
    $stmt->execute(array($idAnimal));
    $animal = $stmt->fetch(PDO::FETCH_ASSOC);
}

// -------- valores do formulário (mantidos em caso de erro) --------
$v = array(
    "nome_completo" => "", "idade" => "", "telefone" => "", "email" => "",
    "cidade" => "", "profissao" => "", "quais_animais" => "",
    "motivo" => "", "plano_dificuldades" => ""
);
foreach ($radios as $campo => $ops) {
    $v[$campo] = "";
}

// pré-preenche com os dados da conta logada
if ($logado) {
    $stmt = $pdo->prepare("SELECT nome, email, telefone FROM cliente WHERE id_cliente = ?");
    $stmt->execute(array($_SESSION["id_cliente"]));
    $cli = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cli) {
        $v["nome_completo"] = $cli["nome"];
        $v["email"]         = $cli["email"];
        $v["telefone"]      = $cli["telefone"];
    }
}

$erro    = "";
$enviado = false;

if ($logado && $animal && $_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($v as $campo => $valor) {
        $v[$campo] = isset($_POST[$campo]) ? trim($_POST[$campo]) : "";
    }
    $declaracao = isset($_POST["declaracao"]);

    // alguma pergunta de múltipla escolha sem resposta (ou com valor inválido)?
    $faltouRadio = false;
    foreach ($radios as $campo => $ops) {
        if (!in_array($v[$campo], $ops, true)) {
            $faltouRadio = true;
        }
    }

    if ($animal["id_cliente"] !== null && (int) $animal["id_cliente"] === (int) $_SESSION["id_cliente"]) {
        $erro = "Você não pode solicitar a adoção de um animal que você mesmo cadastrou.";
    } elseif ($v["nome_completo"] === "" || $v["idade"] === "" || $v["telefone"] === "" ||
        $v["email"] === "" || $v["cidade"] === "" || $v["profissao"] === "") {
        $erro = "Preencha todas as suas informações pessoais.";
    } elseif (!ctype_digit($v["idade"]) || (int) $v["idade"] < 18 || (int) $v["idade"] > 120) {
        $erro = "Informe uma idade válida. A adoção é permitida apenas para maiores de 18 anos.";
    } elseif (!filter_var($v["email"], FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } elseif ($faltouRadio) {
        $erro = "Responda todas as perguntas de múltipla escolha.";
    } elseif ($v["possui_outros_animais"] === "Sim" && $v["quais_animais"] === "") {
        $erro = "Você informou que possui outros animais. Diga quais são.";
    } elseif (strlen($v["motivo"]) < 10 || strlen($v["plano_dificuldades"]) < 10) {
        $erro = "Responda as duas perguntas abertas com um pouco mais de detalhes.";
    } elseif (!$declaracao) {
        $erro = "Você precisa aceitar a declaração de compromisso.";
    } else {

        // evita pedido duplicado do mesmo cliente para o mesmo animal
        $stmt = $pdo->prepare("SELECT id_adocao FROM adocao WHERE id_cliente = ? AND id_animal = ? AND status IN ('Pendente', 'Em análise')");
        $stmt->execute(array($_SESSION["id_cliente"], $idAnimal));

        if ($stmt->rowCount() > 0) {
            $erro = "Você já enviou uma solicitação para este animal. Aguarde a análise.";
        } else {

            $dados = array(
                "id_cliente"            => $_SESSION["id_cliente"],
                "id_animal"             => $idAnimal,
                "nome_completo"         => $v["nome_completo"],
                "idade"                 => (int) $v["idade"],
                "telefone"              => $v["telefone"],
                "email"                 => $v["email"],
                "cidade"                => $v["cidade"],
                "profissao"             => $v["profissao"],
                "tipo_residencia"       => $v["tipo_residencia"],
                "imovel"                => $v["imovel"],
                "espaco_adequado"       => $v["espaco_adequado"],
                "cercado"               => $v["cercado"],
                "condicoes_financeiras" => $v["condicoes_financeiras"],
                "despesas_inesperadas"  => $v["despesas_inesperadas"],
                "responsavel_cuidados"  => $v["responsavel_cuidados"],
                "horas_sozinho"         => $v["horas_sozinho"],
                "tempo_disponivel"      => $v["tempo_disponivel"],
                "ja_teve_animais"       => $v["ja_teve_animais"],
                "possui_outros_animais" => $v["possui_outros_animais"],
                "quais_animais"         => ($v["possui_outros_animais"] === "Sim" ? $v["quais_animais"] : null),
                "moradores_de_acordo"   => $v["moradores_de_acordo"],
                "motivo"                => $v["motivo"],
                "plano_dificuldades"    => $v["plano_dificuldades"]
            );

            $colunas       = implode(", ", array_keys($dados));
            $interrogacoes = implode(", ", array_fill(0, count($dados), "?"));

            $stmt = $pdo->prepare(
                "INSERT INTO adocao (" . $colunas . ", data_solicitacao, status) VALUES (" . $interrogacoes . ", NOW(), 'Pendente')"
            );
            $stmt->execute(array_values($dados));

            $enviado = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Interesse em adotar — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="login.css">
<link rel="stylesheet" href="adocao.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <img src="imagens/logo.png.png" alt="Logo Patas & Lar" class="logo-img">
    </a>
    <nav class="main-nav">
      <?php if ($logado): ?>
        <a href="cadastro_animal.php">Cadastrar animal</a>
        <a href="animais.php" class="active-link">Ver animais</a>
        <a href="logout.php">Sair</a>
        <?php echo sinoNotificacoes($notifPendentes); ?>
      <?php else: ?>
        <a href="login.php">Entrar</a>
        <a href="cadastro.php">Cadastrar</a>
        <a href="animais.php" class="active-link">Ver animais</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main id="topo">

  <section class="adocao-section">
    <div class="container adocao-container">

      <div class="adocao-card">

        <p class="eyebrow">Formulário de adoção</p>
        <h1>🐾 Interesse em adotar</h1>

        <?php if (!$logado): ?>

          <p class="adocao-sub">Você precisa estar logado para solicitar uma adoção.</p>
          <p class="msg-erro">Faça login para continuar com o formulário.</p>
          <a href="login.php" class="paw-btn primary adocao-submit" style="text-decoration:none;">
            <span class="paw-btn-icon">🐾</span>
            <span class="paw-btn-text">
              <strong>Entrar na conta</strong>
              <small>Faça login pra continuar</small>
            </span>
          </a>

        <?php elseif (!$animal): ?>

          <p class="adocao-sub">Não encontramos esse animal.</p>
          <p class="msg-erro">Ele pode já ter sido adotado ou o link está incorreto.</p>
          <a href="animais.php" class="paw-btn secondary adocao-submit" style="text-decoration:none;">
            <span class="paw-btn-icon">📋</span>
            <span class="paw-btn-text">
              <strong>Ver animais</strong>
              <small>Voltar para a lista</small>
            </span>
          </a>

        <?php else: ?>

        <p class="adocao-sub">
          Para garantir que nossos animais encontrem um lar seguro e responsável, responda algumas
          perguntas sobre você e sobre o ambiente onde o animal irá viver.
        </p>

        <div class="adocao-animal">
          <div class="adocao-animal-foto">
            <?php if (!empty($animal["imagem"])): ?>
              <img src="imagens/animais/<?php echo htmlspecialchars($animal["imagem"]); ?>" alt="Foto de <?php echo htmlspecialchars($animal["nome"]); ?>">
            <?php else: ?>
              <?php echo ($animal["especie"] === "Gato" ? "🐈" : ($animal["especie"] === "Cachorro" ? "🐕" : "🐾")); ?>
            <?php endif; ?>
          </div>
          <div>
            <small>Você quer adotar</small>
            <strong><?php echo htmlspecialchars($animal["nome"]); ?></strong>
            <p>
              <?php echo htmlspecialchars($animal["especie"]); ?><?php echo (!empty($animal["raca"]) ? " · " . htmlspecialchars($animal["raca"]) : ""); ?>
              · <?php echo (int) $animal["idade"]; ?> <?php echo ((int) $animal["idade"] === 1 ? "ano" : "anos"); ?>
            </p>
          </div>
        </div>

        <?php if ($erro !== ""): ?>
          <p class="msg-erro"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <form class="adocao-form" action="" method="post" novalidate>

          <!-- 1. informações pessoais -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">1</span> Informações pessoais</h2>
            <div class="adocao-grid">
              <label class="form-field full">
                <span>Nome completo</span>
                <input type="text" name="nome_completo" value="<?php echo htmlspecialchars($v["nome_completo"]); ?>" required>
              </label>
              <label class="form-field">
                <span>Idade</span>
                <input type="number" name="idade" min="18" max="120" placeholder="Ex: 28" value="<?php echo htmlspecialchars($v["idade"]); ?>" required>
              </label>
              <label class="form-field">
                <span>Telefone</span>
                <input type="tel" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($v["telefone"]); ?>" required>
              </label>
              <label class="form-field full">
                <span>E-mail</span>
                <input type="email" name="email" value="<?php echo htmlspecialchars($v["email"]); ?>" required>
              </label>
              <label class="form-field">
                <span>Cidade</span>
                <input type="text" name="cidade" placeholder="Onde você mora" value="<?php echo htmlspecialchars($v["cidade"]); ?>" required>
              </label>
              <label class="form-field">
                <span>Profissão/ocupação</span>
                <input type="text" name="profissao" placeholder="Ex: Estudante" value="<?php echo htmlspecialchars($v["profissao"]); ?>" required>
              </label>
            </div>
          </section>

          <!-- 2. residência -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">2</span> Sobre a residência 🏠</h2>
            <fieldset class="pergunta">
              <legend>Você mora em:</legend>
              <?php echo grupoRadio("tipo_residencia", $radios["tipo_residencia"], $v["tipo_residencia"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>O imóvel é:</legend>
              <?php echo grupoRadio("imovel", $radios["imovel"], $v["imovel"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>O local possui espaço adequado para o animal?</legend>
              <?php echo grupoRadio("espaco_adequado", $radios["espaco_adequado"], $v["espaco_adequado"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>O imóvel possui muro/tela/portão que impeça o animal de fugir?</legend>
              <?php echo grupoRadio("cercado", $radios["cercado"], $v["cercado"]); ?>
            </fieldset>
          </section>

          <!-- 3. condições -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">3</span> Condições para cuidar do animal 💰</h2>
            <fieldset class="pergunta">
              <legend>Você possui condições financeiras para alimentação, vacinação e cuidados veterinários?</legend>
              <?php echo grupoRadio("condicoes_financeiras", $radios["condicoes_financeiras"], $v["condicoes_financeiras"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>Você está preparado para arcar com despesas inesperadas relacionadas à saúde do animal?</legend>
              <?php echo grupoRadio("despesas_inesperadas", $radios["despesas_inesperadas"], $v["despesas_inesperadas"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>Quem será responsável principalmente pelos cuidados?</legend>
              <?php echo grupoRadio("responsavel_cuidados", $radios["responsavel_cuidados"], $v["responsavel_cuidados"]); ?>
            </fieldset>
          </section>

          <!-- 4. rotina -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">4</span> Rotina e disponibilidade ⏰</h2>
            <fieldset class="pergunta">
              <legend>Quantas horas por dia o animal ficará sozinho?</legend>
              <?php echo grupoRadio("horas_sozinho", $radios["horas_sozinho"], $v["horas_sozinho"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>Você possui tempo disponível para alimentação, higiene, passeios e atenção?</legend>
              <?php echo grupoRadio("tempo_disponivel", $radios["tempo_disponivel"], $v["tempo_disponivel"]); ?>
            </fieldset>
          </section>

          <!-- 5. experiência -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">5</span> Experiência com animais 🐶🐱</h2>
            <fieldset class="pergunta">
              <legend>Você já teve animais anteriormente?</legend>
              <?php echo grupoRadio("ja_teve_animais", $radios["ja_teve_animais"], $v["ja_teve_animais"]); ?>
            </fieldset>
            <fieldset class="pergunta">
              <legend>Possui outros animais atualmente?</legend>
              <?php echo grupoRadio("possui_outros_animais", $radios["possui_outros_animais"], $v["possui_outros_animais"]); ?>
            </fieldset>
            <label class="form-field" id="campoQuais" style="display:none;">
              <span>Se sim, quais?</span>
              <input type="text" name="quais_animais" placeholder="Ex: 1 cachorro e 2 gatos" value="<?php echo htmlspecialchars($v["quais_animais"]); ?>">
            </label>
            <fieldset class="pergunta">
              <legend>Todos os moradores da residência estão de acordo com a adoção?</legend>
              <?php echo grupoRadio("moradores_de_acordo", $radios["moradores_de_acordo"], $v["moradores_de_acordo"]); ?>
            </fieldset>
          </section>

          <!-- 6. motivação -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">6</span> Motivação ❤️</h2>
            <label class="form-field">
              <span>Por que você deseja adotar este animal?</span>
              <textarea name="motivo" required><?php echo htmlspecialchars($v["motivo"]); ?></textarea>
            </label>
            <label class="form-field">
              <span>O que você pretende fazer caso tenha dificuldades para cuidar do animal?</span>
              <textarea name="plano_dificuldades" required><?php echo htmlspecialchars($v["plano_dificuldades"]); ?></textarea>
            </label>
          </section>

          <!-- 7. compromisso -->
          <section class="adocao-bloco">
            <h2><span class="bloco-num">7</span> Compromisso</h2>
            <label class="checkbox-field declaracao">
              <input type="checkbox" name="declaracao" required>
              <span>
                Declaro que as informações fornecidas são verdadeiras e que estou ciente de que a adoção
                exige responsabilidade, cuidados, alimentação, vacinação, acompanhamento veterinário e
                dedicação ao longo da vida do animal.
              </span>
            </label>

            <button type="submit" class="paw-btn primary adocao-submit">
              <span class="paw-btn-icon">🐾</span>
              <span class="paw-btn-text">
                <strong>Enviar solicitação de adoção</strong>
                <small>Sua solicitação será analisada</small>
              </span>
            </button>
          </section>

        </form>

        <?php endif; ?>

        <div class="adocao-voltar">
          <a href="animais.php" class="link-forte">← Voltar para os animais</a>
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
  // mostra o campo "quais animais" só quando a resposta for "Sim"
  (function () {
    var campo  = document.getElementById("campoQuais");
    var radios = document.getElementsByName("possui_outros_animais");
    if (!campo) return;

    function atualizar() {
      var mostrar = false;
      for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked && radios[i].value === "Sim") mostrar = true;
      }
      campo.style.display = mostrar ? "flex" : "none";
    }

    for (var i = 0; i < radios.length; i++) {
      radios[i].addEventListener("change", atualizar);
    }
    atualizar();
  })();
</script>

<?php if ($enviado): ?>
<script>
  alert("Solicitação enviada! Nossa equipe vai analisar suas respostas e entrar em contato.");
  window.location.href = "animais.php";
</script>
<?php endif; ?>

</body>
</html>
