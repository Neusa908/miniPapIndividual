<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">
    <title>Registo</title>
</head>

<body class="registar">
    <div class="form-container">
        <form action="login.php" method="POST">
            <h1>Criar Conta</h1>

            <label for="username">Nome de Utilizador</label>
            <input type="text" id="username" name="nome" placeholder="Digite o seu nome de utilizador" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Digite o seu email" required>

            <label for="password">Palavra-passe</label>
            <input type="password" id="password" name="password" placeholder="Escolha uma palavra-passe forte" required>

            <label for="confirm-password">Confirmação de Palavra-passe</label>
            <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirme a palavra-passe"
                required>

            <label for="telefone">Número de telefone</label>
            <input type="tel" id="telefone" name="telefone" placeholder="Coloque o seu número" required>

            <label for="morada">Morada</label>
            <input type="text" id="morada" name="morada" placeholder="Coloque a sua morada" required><br>

            <br><button type="submit " class="registar">Criar Conta</button>
            <div class="link">
                <br>Já tem uma conta? <a href="login.php">Inicie sessão agora!<p>
            </div>
        </form>
    </div>
</body>


</html>