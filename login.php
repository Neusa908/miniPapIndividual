<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $username = filter_var($username, FILTER_SANITIZE_STRING);

    $sql = "SELECT id, nome, apelido, senha, tipo, foto_perfil FROM utilizadores WHERE (email = ? OR apelido = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $utilizador = $result->fetch_assoc();

        if (password_verify($password, $utilizador['senha'])) {
            $_SESSION['utilizador_id'] = $utilizador['id'];
            $_SESSION['utilizador_nome'] = $utilizador['nome'];
            $_SESSION['utilizador_apelido'] = $utilizador['apelido'] ?? '';
            $_SESSION['tipo'] = $utilizador['tipo'];
            $_SESSION['foto_perfil'] = $utilizador['foto_perfil'] ?? null;

            // Define o nome visível para o header
            if ($utilizador['tipo'] === 'admin') {
                $_SESSION['utilizador_nome_visivel'] = $utilizador['nome'];
            } else {
                $_SESSION['utilizador_nome_visivel'] = !empty($utilizador['apelido']) ? $utilizador['apelido'] : $utilizador['nome'];
            }

            $_SESSION['mensagem'] = "Login bem-sucedido! Bem-vindo(a), " . htmlspecialchars($_SESSION['utilizador_nome_visivel']) . "!";
            $_SESSION['mensagem_sucesso'] = true;

            $redirect = $utilizador['tipo'] === 'admin' ? 'admin_panel.php' : 'index.php';
            header("Location: $redirect");
            exit();
        } else {
            $_SESSION['mensagem'] = "Palavra-passe incorreta!";
        }
    } else {
        $_SESSION['mensagem'] = "Nome de utilizador ou email não registado!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="login">
    <div class="box-login">
        <h1>LOGIN</h1>

        <?php if (isset($_SESSION['mensagem'])): ?>
        <div id="mensagem"
            class="<?php echo isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem'; ?>">
            <?php echo htmlspecialchars($_SESSION['mensagem']); ?>
        </div>
        <script>
        setTimeout(() => {
            document.getElementById('mensagem').style.display = 'none';
        }, 2000);
        </script>
        <?php unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']); ?>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Nome de utilizador ou email" required>
            <br>
            <input type="password" name="password" placeholder="Palavra-passe" required>
            <br>
            <button type="submit" class="button-login">Entrar</button>
        </form>

        <div>
            <br>Não tem uma conta? <a href="registar.php" class="link-login"> Crie uma agora!</a>
            <br>Voltar para a página <a href="index.php" class="link-login"> principal!</a>
        </div>
    </div>
</body>

</html>

<?php $conn->close(); ?>