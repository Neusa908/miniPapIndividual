<?php
session_start(); // Inicia a sessão para usar tokens CSRF e mensagens de erro
require 'conexao.php';

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa variáveis para mensagens de erro e dados do formulário
$mensagem_erro = '';
$dados_form = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'morada' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem_erro = "Erro de validação. Tente novamente.";
    } else {
        // Sanitiza e valida os dados de entrada
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $senha = $_POST["password"];
        $confirmar_senha = $_POST["confirm-password"];
        $telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING));
        $morada = trim(filter_input(INPUT_POST, 'morada', FILTER_SANITIZE_STRING));

        // Preserva os dados do formulário em caso de erro
        $dados_form['nome'] = $nome;
        $dados_form['email'] = $email;
        $dados_form['telefone'] = $telefone;
        $dados_form['morada'] = $morada;

        // Validações
        $erros = [];

        // Validação do Nome
        if (empty($nome)) {
            $erros[] = "O nome é obrigatório.";
        } else {
            // Verifica se o nome já existe
            $sql = "SELECT id FROM usuarios WHERE nome = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $nome);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $erros[] = "Este nome de utilizador já está registado.";
            }
            $stmt->close();
        }

        // Validação do Email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Por favor, insira um email válido.";
        } else {
            // Verifica se o email já existe
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $erros[] = "Este email já está registado.";
            }
            $stmt->close();
        }

        // Validação da Senha
        if (empty($senha)) {
            $erros[] = "A senha é obrigatória.";
        } elseif (strlen($senha) < 8) {
            $erros[] = "A senha deve ter pelo menos 8 caracteres.";
        } elseif ($senha !== $confirmar_senha) {
            $erros[] = "As senhas não coincidem.";
        }

        // Validação do Telefone
        if (empty($telefone)) {
            $erros[] = "O telefone é obrigatório.";
        } elseif (!preg_match('/^[0-9\s+]+$/', $telefone)) {
            $erros[] = "O telefone deve conter apenas números, espaços ou o símbolo +.";
        }

        // Validação da Morada
        if (empty($morada)) {
            $erros[] = "A morada é obrigatória.";
        }

        // Se não houver erros, prossegue com o registro
        if (empty($erros)) {
            // Hash da senha para segurança
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o novo utilizador
            $sql = "INSERT INTO usuarios (nome, email, senha, telefone, morada, tipo) VALUES (?, ?, ?, ?, ?, 'cliente')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nome, $email, $senha_hash, $telefone, $morada);

            if ($stmt->execute()) {
                // Registra a ação na tabela logs
                $usuario_id = $stmt->insert_id;
                $acao = "Novo usuário registrado";
                $detalhes = "Usuário $nome ($email) foi registrado.";
                $sql_log = "INSERT INTO logs (usuario_id, acao, detalhes, data_log) VALUES (?, ?, ?, NOW())";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("iss", $usuario_id, $acao, $detalhes);
                $stmt_log->execute();
                $stmt_log->close();

                // Redireciona para o login com mensagem de sucesso
                echo "<script>alert('Registo concluído com sucesso!'); window.location.href='login.php';</script>";
                exit;
            } else {
                $mensagem_erro = "Erro ao registar. Tente novamente.";
            }
            $stmt->close();
        } else {
            // Exibe os erros
            $mensagem_erro = implode("<br>", $erros);
        }
    }
}

$conn->close();
?>

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
        <?php if ($mensagem_erro): ?>
        <p class="error-message"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>
        <form action="registar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <h1>Criar Conta</h1>
            <label for="username">Nome de Utilizador</label>
            <input type="text" id="username" name="nome" placeholder="Digite o seu nome de utilizador"
                value="<?php echo htmlspecialchars($dados_form['nome']); ?>" required>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Digite o seu email"
                value="<?php echo htmlspecialchars($dados_form['email']); ?>" required>
            <label for="password">Palavra-passe</label>
            <input type="password" id="password" name="password" placeholder="Escolha uma palavra-passe forte" required>
            <label for="confirm-password">Confirmação de Palavra-passe</label>
            <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirme a palavra-passe"
                required>
            <label for="telefone">Número de telefone</label>
            <input type="tel" id="telefone" name="telefone" placeholder="Coloque o seu número"
                value="<?php echo htmlspecialchars($dados_form['telefone']); ?>" required>
            <label for="morada">Morada</label>
            <input type="text" id="morada" name="morada" placeholder="Coloque a sua morada"
                value="<?php echo htmlspecialchars($dados_form['morada']); ?>" required>
            <button type="submit" class="registar"><b>Criar Conta</b></button>
            <div class="link">
                Já tem uma conta? <a href="login.php">Inicie sessão agora!</a>
            </div>
        </form>
    </div>
</body>

</html>