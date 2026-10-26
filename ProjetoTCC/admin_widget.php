<?php
// -----------------------------------------
// Patas & Lar — widget de login do administrador
// Incluído no cabeçalho de todas as páginas
// -----------------------------------------
require_once "conexao.php";

$adminLogado = isset($_SESSION["id_admin"]);

if (!isset($_SESSION["csrf_admin_login"])) {
    $_SESSION["csrf_admin_login"] = md5(uniqid(mt_rand(), true));
}

$paginaAtual = basename($_SERVER["PHP_SELF"]);
?>
<div class="admin-widget">
  <?php if ($adminLogado): ?>

    <a href="solicitacoes.php" class="admin-chip">
      🛡️ <?php echo htmlspecialchars($_SESSION["admin_nome"]); ?>
    </a>
    <a href="admin_logout.php" class="admin-sair">Sair</a>

  <?php else: ?>

    <button type="button" class="admin-toggle" id="adminToggle">🛡️ Admin</button>

    <div class="admin-drop" id="adminDrop">
      <form action="admin_login.php" method="post" class="admin-form">
        <p class="admin-titulo">Login do administrador</p>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($paginaAtual); ?>">
        <input type="hidden" name="csrf" value="<?php echo $_SESSION["csrf_admin_login"]; ?>">
        <label>
          <span>E-mail</span>
          <input type="email" name="email" placeholder="admin@patasalar.com" required>
        </label>
        <label>
          <span>Senha</span>
          <input type="password" name="senha" placeholder="Senha" required>
        </label>
        <button type="submit">Entrar</button>
        <?php if (isset($_GET["admin_erro"])): ?>
          <p class="admin-erro">E-mail ou senha incorretos.</p>
        <?php endif; ?>
      </form>
    </div>

  <?php endif; ?>
</div>
<script>
(function () {
  var btn  = document.getElementById("adminToggle");
  var drop = document.getElementById("adminDrop");
  if (!btn || !drop) return;

  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    drop.classList.toggle("aberto");
  });

  document.addEventListener("click", function (e) {
    if (drop.classList.contains("aberto") && !drop.contains(e.target) && e.target !== btn) {
      drop.classList.remove("aberto");
    }
  });
})();
</script>
