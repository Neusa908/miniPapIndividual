<?php
session_start();
require 'conexao.php';

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicializa variáveis para mensagens de erro e dados do formulário
$mensagem_erro = '';
$dados_form = [
    'nome' => '',
    'apelido' => '',
    'email' => '',
    'telefone' => '',
    'morada' => '',
    'cidade' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem_erro = "Erro de validação. Tente novamente.";
    } else {
        // Sanitiza e valida os dados de entrada
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $apelido = trim(filter_input(INPUT_POST, 'apelido', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $senha = $_POST["password"];
        $confirmar_senha = $_POST["confirm-password"];
        $telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING));
        $morada = trim(filter_input(INPUT_POST, 'morada', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));

        // Preserva os dados do formulário em caso de erro
        $dados_form['nome'] = $nome;
        $dados_form['apelido'] = $apelido;
        $dados_form['email'] = $email;
        $dados_form['telefone'] = $telefone;
        $dados_form['morada'] = $morada;
        $dados_form['cidade'] = $cidade;

        // Validações
        $erros = [];

        // Validação do Nome
        if (empty($nome)) {
            $erros[] = "O nome completo é obrigatório.";
        }

// Validação do Apelido
if (!empty($apelido)) {
    $sql = "SELECT id FROM usuarios WHERE apelido = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $apelido_param = $apelido;
    $usuario_id_param = $usuario_id ?? 0;
    $stmt->bind_param("si", $apelido_param, $usuario_id_param); // Usa 0 se $usuario_id não estiver definido
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $erros[] = "Este nome de usuário já está registado.";
    }
    $stmt->close();
} elseif (empty($apelido)) {
    // Usa o nome completo como apelido padrão se não fornecido
    $apelido = $nome; // Pode ajustar para pegar só primeira palavra ou primeira + última
}

        // Validação do Email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Por favor, insira um email válido.";
        } else {
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
        } else {
            if (strlen($senha) < 8) {
                $erros[] = "A senha deve ter pelo menos 8 caracteres.";
            }
            if (!preg_match("/[A-Z]/", $senha)) {
                $erros[] = "A senha deve conter pelo menos uma letra maiúscula.";
            }
            if (!preg_match("/[a-z]/", $senha)) {
                $erros[] = "A senha deve conter pelo menos uma letra minúscula.";
            }
            if (!preg_match("/[0-9]/", $senha)) {
                $erros[] = "A senha deve conter pelo menos um número.";
            }
            if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $senha)) {
                $erros[] = "A senha deve conter pelo menos um caractere especial (ex.: !@#$%).";
            }
            if ($senha !== $confirmar_senha) {
                $erros[] = "As senhas não coincidem.";
            }
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

        // Validação da Cidade
        if (empty($cidade)) {
            $erros[] = "A cidade é obrigatória.";
        }

        if (empty($erros)) {
            // Inicia transação para garantir consistência
            $conn->begin_transaction();

            try {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Insere o novo usuário
$sql = "INSERT INTO usuarios (nome, apelido, email, senha, telefone, morada, cidade, tipo, saldo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente', 300.00)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $nome, $apelido, $email, $senha_hash, $telefone, $morada, $cidade);
$stmt->execute();
                $novo_usuario_id = $conn->insert_id;



$sql = "INSERT INTO enderecos (usuario_id, nome_endereco, rua, cidade, distrito, codigo_postal, padrao) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issssii", $novo_usuario_id, $nome_endereco, $rua, $cidade, $distrito, $codigo_postal, $padrao);
if (!$stmt->execute()) {
    throw new Exception("Erro ao inserir endereço: " . $conn->error);
}

                // Registra a ação na tabela logs
                $acao = "Novo usuário registrado";
                $detalhes = "Usuário $apelido ($email) foi registrado.";
                $sql_log = "INSERT INTO logs (usuario_id, acao, detalhes, data_log) VALUES (?, ?, ?, NOW())";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("iss", $novo_usuario_id, $acao, $detalhes);
                $stmt_log->execute();
                $stmt_log->close();

                // Registra notificação para TODOS os administradores
                $mensagem_notif = "Novo cadastro: Usuário $apelido (ID $novo_usuario_id) registrado em " . date('d/m/Y H:i');
                
                // Busca todos os administradores
                $sql_admins = "SELECT id FROM usuarios WHERE tipo = 'admin'";
                $result_admins = $conn->query($sql_admins);
                
                if ($result_admins->num_rows > 0) {
                    $stmt_notif = $conn->prepare("INSERT INTO notificacoes (mensagem, admin_id) VALUES (?, ?)");
                    while ($admin = $result_admins->fetch_assoc()) {
                        $admin_id = $admin['id'];
                        $stmt_notif->bind_param("si", $mensagem_notif, $admin_id);
                        if ($stmt_notif->execute()) {
                            error_log("Notificação criada com sucesso para admin_id $admin_id: $mensagem_notif");
                        } else {
                            error_log("Erro ao criar notificação para admin_id $admin_id: " . $conn->error);
                        }
                    }
                    $stmt_notif->close();
                } else {
                    error_log("Nenhum administrador encontrado para notificar.");
                }

                // Confirma transação
                $conn->commit();

                // Redireciona para o login com mensagem de sucesso
                echo "<script>alert('Registo concluído com sucesso!'); window.location.href='login.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $mensagem_erro = "Erro ao registar: " . $e->getMessage();
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
        <p class="error-message">
            <?php echo $mensagem_erro; ?>
        </p>
        <?php endif; ?>
        <form action="registar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <h1>Criar Conta</h1>
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" placeholder="Digite o seu nome completo"
                value="<?php echo htmlspecialchars($dados_form['nome']); ?>" required>
            <label for="apelido">Nome de Usuário</label>
            <input type="text" id="apelido" name="apelido" placeholder="Digite o seu nome de usuário"
                value="<?php echo htmlspecialchars($dados_form['apelido']); ?>">
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
            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" placeholder="Digite a sua cidade"
                value="<?php echo htmlspecialchars($dados_form['cidade']); ?>" required>

            <button type="submit" class="registar"><b>Criar Conta</b></button>
            <div class="link">
                Já tem uma conta? <a href="login.php">Inicie sessão agora!</a>
            </div>
        </form>
    </div>
</body>

</html>