<?php
include('logar.php');

if(isset($_POST['email']) || isset($_POST['senha'])) {
    if(strlen($_POST['email']) == 0) {
        echo "Preencha seu e-mail";
    } else if(strlen($_POST['senha']) == 0) {
        echo "Preencha sua senha";
    } else {
        $email = $mysqli->real_escape_string($_POST['email']);
        $senha = $mysqli->real_escape_string($_POST['senha']);

        $sql_code = "SELECT * FROM usuarios WHERE email = '$email' AND senha = '$senha'";
        $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);

        $quantidade = $sql_query->num_rows;

        if($quantidade == 1) {
            $usuario = $sql_query->fetch_assoc();

            if(!isset($_SESSION)){
                session_start();
            }

        $_SESSION['id'] = $usuario['id'];
        $_SESSION['nome'] = $usuario['nome'];

        header("Location: index.php");

        } else {
            echo "Falha ao logar! E-mail ou senha incorretos";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">
    <title>Login</title>
</head>

<body class="login">
    <div class="box-login">
        <img class="img-login" src="img/user.png" alt="">
        <h1 class="title-login">LOGIN</h1>
        <form action="index.php" method="POST">
            <!-- o formulário -->
            <input type="text" name="username" placeholder="Nome de utilizador ou email" required>
            <br>
            <input type="password" name="password" placeholder="Palavra-passe" required>
            <br>
            <button type="submit" class="button-login">Entrar</button>
        </form>
        <div class="link">
            <br>Não tem uma conta? <a href="registar.php"> Crie uma agora!</a>
        </div>
    </div>
</body>

</html>