<?php
session_start();
require 'conexao.php';

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem_erro = '';
$dados_form = [
    'nome' => '',
    'email' => '',
    'telefone' => '',
    'morada' => '',
    'cidade' => ''
];

// Verificar se é um pedido AJAX para verificar telefone
if (isset($_GET['check_telefone']) && !empty($_GET['check_telefone'])) {
    $telefone_check = trim(filter_var($_GET['check_telefone'], FILTER_SANITIZE_STRING));
    $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE telefone = ?");
    $stmt->bind_param("s", $telefone_check);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0 ? 'true' : 'false';
    $stmt->close();
    echo $existe;
    exit;
}

//POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem_erro = "Erro de validação. Tente novamente.";
    } else {
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $senha = $_POST["password"];
        $confirmar_senha = $_POST["confirm-password"];
        $telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING));
        $morada = trim(filter_input(INPUT_POST, 'morada', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));

        $dados_form['nome'] = $nome;
        $dados_form['email'] = $email;
        $dados_form['telefone'] = $telefone;
        $dados_form['morada'] = $morada;
        $dados_form['cidade'] = $cidade;

        $erros = [];

        //NOME
        if (empty($nome)) {
            $erros[] = "O nome completo é obrigatório.";
        }

        //EMAIL
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Por favor, insira um email válido.";
        } else {
            $sql = "SELECT id FROM utilizadores WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $erros[] = "Este email já está registado.";
            }
            $stmt->close();
        }

        //SENHA
        if (empty($senha)) {
            $erros[] = "A palavra-passe é obrigatória.";
        } else {
            if (strlen($senha) < 8) {
                $erros[] = "A palavra-passe deve ter pelo menos 8 caracteres.";
            }
            if (!preg_match("/[A-Z]/", $senha)) {
                $erros[] = "A palavra-passe deve conter pelo menos uma letra maiúscula.";
            }
            if (!preg_match("/[a-z]/", $senha)) {
                $erros[] = "A palavra-passe deve conter pelo menos uma letra minúscula.";
            }
            if (!preg_match("/[0-9]/", $senha)) {
                $erros[] = "A palavra-passe deve conter pelo menos um número.";
            }
            if (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $senha)) {
                $erros[] = "A palavra-passe deve conter pelo menos um caractere especial (ex.: !@#$%).";
            }
            if ($senha !== $confirmar_senha) {
                $erros[] = "As palavras-passe não coincidem.";
            }
        }

        //TELEFONE
        if (empty($telefone)) {
            $erros[] = "O número de telefone é obrigatório.";
        } elseif (!preg_match('/^[0-9\s+]+$/', $telefone)) {
            $erros[] = "O número de telefone deve conter apenas números, espaços ou o símbolo +.";
        } else {
            // Verificar se telefone já existe
            $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE telefone = ?");
            $stmt->bind_param("s", $telefone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $erros[] = "Este número de telefone já está registado.";
            }
            $stmt->close();
        }

        //MORADA
        if (empty($morada)) {
            $erros[] = "A morada é obrigatória.";
        }

        //CIDADE
        if (empty($cidade)) {
            $erros[] = "A cidade é obrigatória.";
        }

        // Termos e Condições
        if (!isset($_POST['termos'])) {
            $erros[] = "É necessário aceitar os Termos e Condições para prosseguir.";
        }

        if (empty($erros)) {
            $conn->begin_transaction();
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $apelido_base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $nome)[0]));
                $apelido = $apelido_base;
                $suffix = 1;

                $stmt_check = $conn->prepare("SELECT id FROM utilizadores WHERE apelido = ?");
                $stmt_check->bind_param("s", $apelido);
                $stmt_check->execute();
                $stmt_check->store_result();

                while ($stmt_check->num_rows > 0) {
                    $apelido = $apelido_base . $suffix;
                    $stmt_check->bind_param("s", $apelido);
                    $stmt_check->execute();
                    $stmt_check->store_result();
                    $suffix++;
                }
                $stmt_check->close();

                $sql = "INSERT INTO utilizadores (nome, apelido, email, senha, telefone, morada, cidade, tipo, saldo) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'cliente', 300.00)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssss", $nome, $apelido, $email, $senha_hash, $telefone, $morada, $cidade);
                $stmt->execute();
                $novo_utilizador_id = $conn->insert_id;

                $nome_endereco = 'Principal';
                $rua = $morada;
                $distrito = '';
                $codigo_postal = '';
                $padrao = 1;

                $sql = "INSERT INTO enderecos (utilizador_id, nome_endereco, rua, cidade, distrito, codigo_postal, padrao) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssii", $novo_utilizador_id, $nome_endereco, $rua, $cidade, $distrito, $codigo_postal, $padrao);
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao inserir endereço: " . $conn->error);
                }

                $acao = "Novo utilizador registrado";
                $detalhes = "Utilizador $apelido ($email) foi registrado.";
                $sql_log = "INSERT INTO logs (utilizador_id, acao, detalhes, data_log) VALUES (?, ?, ?, NOW())";
                $stmt_log = $conn->prepare($sql_log);
                $stmt_log->bind_param("iss", $novo_utilizador_id, $acao, $detalhes);
                $stmt_log->execute();
                $stmt_log->close();

                // REMOVIDA a parte que cria notificações para novos cadastros

                $conn->commit();
                echo "<script>alert('Registo concluído com sucesso!'); window.location.href='login.php';</script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $mensagem_erro = "Erro ao registar: " . $e->getMessage();
            }
        } else {
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
    <title>Registo</title>
    <link rel="stylesheet" href="./css/style.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const telefoneInput = document.getElementById('telefone');
        telefoneInput.addEventListener('blur', function() {
            const telefone = telefoneInput.value.trim();
            if (telefone.length === 0) return;

            fetch('registar.php?check_telefone=' + encodeURIComponent(telefone))
                .then(response => response.text())
                .then(data => {
                    if (data === 'true') {
                        alert('Este número de telefone já está registado.');
                        telefoneInput.focus();
                    }
                })
                .catch(err => {
                    console.error('Erro na verificação do telefone:', err);
                });
        });
    });
    </script>
</head>

<body class="registar">
    <div class="form-container">
        <?php if ($mensagem_erro): ?>
        <p class="error-message"><?php echo $mensagem_erro; ?></p>
        <?php endif; ?>
        <form action="registar.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <h1>Criar Conta</h1>

            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" required placeholder="Digite o seu nome completo"
                value="<?php echo htmlspecialchars($dados_form['nome']); ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="Digite o seu email"
                value="<?php echo htmlspecialchars($dados_form['email']); ?>">

            <label for="password">Palavra-passe</label>
            <input type="password" id="password" name="password" required placeholder="Escolha uma palavra-passe forte">

            <label for="confirm-password">Confirmação de Palavra-passe</label>
            <input type="password" id="confirm-password" name="confirm-password" required
                placeholder="Confirme a palavra-passe">

            <label for="telefone">Número de telefone</label>
            <input type="tel" id="telefone" name="telefone" required placeholder="Coloque o seu número"
                value="<?php echo htmlspecialchars($dados_form['telefone']); ?>">

            <label for="morada">Morada</label>
            <input type="text" id="morada" name="morada" placeholder="Coloque a sua morada"
                value="<?php echo htmlspecialchars($dados_form['morada']); ?>">

            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" placeholder="Digite a sua cidade"
                value="<?php echo htmlspecialchars($dados_form['cidade']); ?>">

            <div class="checkbox-termos">
                <input type="checkbox" id="termos" name="termos" required>
                <label for="termos">Li e aceito os <a href="termos.php" target="_blank">Termos e Condições</a>.</label>
            </div>

            <button type="submit" class="registar"><b>Criar Conta</b></button>

            <div class="link">
                Já tem uma conta? <a href="login.php">Inicie sessão agora!</a>
            </div>
        </form>
    </div>
</body>

</html>