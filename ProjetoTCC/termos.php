<?php

$nomeSite = "Patas & Lar";
$anoAtual = date("Y");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Termos de Uso — <?php echo $nomeSite; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="termos.css">
</head>
<body>

<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <img src="imagens/logo.png.png" alt="Logo Patas & Lar" class="logo-img">
    </a>
    <nav class="main-nav">
      <a href="login.php">Entrar</a>
      <a href="cadastro.php">Cadastrar</a>
      <a href="animais.php">Ver animais</a>
      <a href="admin_login.php">Entrar admin</a>
    </nav>
  </div>
</header>

<main id="topo">

  <section class="termos-section">
    <div class="container termos-container">

      <p class="termos-eyebrow">Documento legal</p>
      <h1>Termos de Uso</h1>
      <p class="termos-atualizacao">Última atualização: <?php echo date("d/m/Y"); ?></p>

      <div class="termos-corpo">

        <p>
          Estes Termos de Uso regulam o acesso e a utilização da plataforma
          <?php echo $nomeSite; ?>, que tem como finalidade conectar pessoas
          interessadas em adotar animais a abrigos e responsáveis por animais
          disponíveis para adoção. Ao criar uma conta ou utilizar o site, você
          declara que leu, compreendeu e concorda com as condições abaixo.
        </p>

        <h2>1. Cadastro e responsabilidade da conta</h2>
        <p>
          Para utilizar determinadas funcionalidades da plataforma, é necessário
          realizar um cadastro fornecendo informações verdadeiras, completas e
          atualizadas, incluindo nome, telefone, e-mail e senha. Você é
          responsável por manter a confidencialidade dos seus dados de acesso e
          por todas as atividades realizadas em sua conta.
        </p>

        <h2>2. Uso da plataforma</h2>
        <p>
          O <?php echo $nomeSite; ?> deve ser utilizado exclusivamente para fins
          relacionados à adoção responsável de animais. É vedado o uso da
          plataforma para fins comerciais de venda de animais, para práticas de
          maus-tratos, abandono ou qualquer conduta que viole a legislação de
          proteção animal vigente.
        </p>

        <h2>3. Processo de adoção</h2>
        <p>
          O <?php echo $nomeSite; ?> atua como intermediário entre adotantes e
          abrigos ou responsáveis pelos animais anunciados. A plataforma não se
          responsabiliza por acordos, visitas ou compromissos firmados
          diretamente entre as partes, cabendo a cada usuário avaliar as
          condições da adoção antes de sua efetivação.
        </p>

        <h2>4. Privacidade e dados pessoais</h2>
        <p>
          As informações fornecidas no cadastro serão utilizadas apenas para
          viabilizar o funcionamento da plataforma e o contato entre adotantes
          e responsáveis pelos animais, não sendo compartilhadas com terceiros
          para fins diversos dos previstos nestes Termos, salvo obrigação legal.
        </p>

        <h2>5. Alterações nos Termos</h2>
        <p>
          O <?php echo $nomeSite; ?> pode atualizar estes Termos de Uso a
          qualquer momento, sendo recomendável a consulta periódica desta
          página. O uso continuado da plataforma após eventuais alterações
          implica a aceitação das novas condições.
        </p>

        <h2>6. Cancelamento de conta</h2>
        <p>
          O usuário pode solicitar o cancelamento de sua conta a qualquer
          momento. O <?php echo $nomeSite; ?> também se reserva o direito de
          suspender ou encerrar contas que descumpram estes Termos ou a
          legislação aplicável.
        </p>

        <h2>7. Contato</h2>
        <p>
          Em caso de dúvidas sobre estes Termos de Uso, entre em contato pelos
          canais disponíveis no site.
        </p>

      </div>

      <div class="termos-voltar">
        <a href="cadastro.php" class="link-forte">← Voltar para o cadastro</a>
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
</body>
</html>
