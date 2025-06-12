<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('É necessário estar logado para acessar as configurações. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtém dados do usuário
$sql = "SELECT email, telefone, morada, cidade FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();


// Obtém endereços do usuário
$sql = "SELECT id, nome_endereco, rua, numero, bairro, cidade, estado, codigo_postal, padrao FROM enderecos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$enderecos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Processa atualização de dados pessoais
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_dados'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING));
        $morada = trim(filter_input(INPUT_POST, 'morada', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));

        // Validações
        $erros = [];
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Por favor, insira um email válido.";
        } else {
            $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $email, $usuario_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $erros[] = "Este email já está em uso por outro usuário.";
            }
        }
        if (empty($telefone) || !preg_match('/^[0-9\s+]+$/', $telefone)) {
            $erros[] = "O telefone deve conter apenas números, espaços ou o símbolo +.";
        }
        if (empty($morada)) {
            $erros[] = "A morada é obrigatória.";
        }
        if (empty($cidade)) {
            $erros[] = "A cidade é obrigatória.";
        }

        if (empty($erros)) {
            $sql = "UPDATE usuarios SET email = ?, telefone = ?, morada = ?, cidade = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $email, $telefone, $morada, $cidade, $usuario_id);
            if ($stmt->execute()) {
                $mensagem = "Dados pessoais atualizados com sucesso!";
                $_SESSION['usuario_email'] = $email;
                $usuario = ['email' => $email, 'telefone' => $telefone, 'morada' => $morada, 'cidade' => $cidade];
            } else {
                $mensagem = "Erro ao atualizar os dados.";
            }
        } else {
            $mensagem = implode("<br>", $erros);
        }
    }
}

// Processa adição de endereço
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_endereco'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $nome_endereco = trim(filter_input(INPUT_POST, 'nome_endereco', FILTER_SANITIZE_STRING));
        $rua = trim(filter_input(INPUT_POST, 'rua', FILTER_SANITIZE_STRING));
        $numero = trim(filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING));
        $bairro = trim(filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade_endereco', FILTER_SANITIZE_STRING));
        $estado = trim(filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING));
        $cep = trim(filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING));
        $padrao = isset($_POST['padrao']) ? 1 : 0;

        // Validações
        $erros = [];
        if (empty($nome_endereco)) {
            $erros[] = "O nome do endereço é obrigatório.";
        }
        if (empty($rua)) {
            $erros[] = "A rua é obrigatória.";
        }
        if (empty($cidade)) {
            $erros[] = "A cidade é obrigatória.";
        }
        if (empty($estado)) {
            $erros[] = "O estado é obrigatório.";
        }
        if (empty($cep)) {
            $erros[] = "O CEP é obrigatório.";
        }

        if (empty($erros)) {
            if ($padrao) {
                $sql = "UPDATE enderecos SET padrao = 0 WHERE usuario_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
            }

            $sql = "INSERT INTO enderecos (usuario_id, nome_endereco, rua, numero, bairro, cidade, estado, cep, padrao) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssssi", $usuario_id, $nome_endereco, $rua, $numero, $bairro, $cidade, $estado, $cep, $padrao);
            if ($stmt->execute()) {
                $mensagem = "Endereço adicionado com sucesso!";
                header("Location: configuracoes.php");
                exit;
            } else {
                $mensagem = "Erro ao adicionar endereço.";
            }
        } else {
            $mensagem = implode("<br>", $erros);
        }
    }
}

// Processa exclusão de endereço
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['excluir_endereco'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $endereco_id = filter_input(INPUT_POST, 'endereco_id', FILTER_SANITIZE_NUMBER_INT);
        $sql = "SELECT padrao FROM enderecos WHERE id = ? AND usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $endereco_id, $usuario_id);
        $stmt->execute();
        $endereco = $stmt->get_result()->fetch_assoc();

        if ($endereco) {
            if ($endereco['padrao'] == 1) {
                $mensagem = "Não é possível eliminar o endereço padrão. Defina outro endereço como padrão primeiro.";
            } else {
                $sql = "DELETE FROM enderecos WHERE id = ? AND usuario_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $endereco_id, $usuario_id);
                if ($stmt->execute()) {
                    $mensagem = "Endereço eliminado com sucesso!";
                    header("Location: configuracoes.php");
                    exit;
                } else {
                    $mensagem = "Erro ao eliminar endereço.";
                }
            }
        } else {
            $mensagem = "Endereço não encontrado.";
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="registar">
    <div class="perfil-container">
        <h1>Configurações da Conta</h1>
        <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <h2>Dados Pessoais</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>"
                placeholder="Digite o seu email" required>
            <label for="telefone">Telefone:</label>
            <input type="tel" name="telefone" id="telefone"
                value="<?php echo htmlspecialchars($usuario['telefone']); ?>" placeholder="Digite o seu número"
                required>
            <label for="morada">Morada:</label>
            <input type="text" name="morada" id="morada" value="<?php echo htmlspecialchars($usuario['morada']); ?>"
                placeholder="Digite a sua morada" required>
            <label for="cidade">Cidade:</label>
            <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($usuario['cidade']); ?>"
                placeholder="Digite a sua cidade" required>
            <button type="submit" name="salvar_dados">Salvar Dados Pessoais</button>
        </form>

        <h2>Endereços de Entrega</h2>
        <?php if (!empty($enderecos)): ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Morada</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>CP</th>
                    <th>Padrão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enderecos as $endereco): ?>
                <tr>
                    <td><?php echo htmlspecialchars($endereco['nome_endereco']); ?></td>
                    <td><?php echo htmlspecialchars($endereco['rua'] . ($endereco['numero'] ? ', ' . $endereco['numero'] : '') . ($endereco['bairro'] ? ', ' . $endereco['bairro'] : '')); ?>
                    </td>
                    <td><?php echo htmlspecialchars($endereco['cidade']); ?></td>
                    <td><?php echo htmlspecialchars($endereco['estado']); ?></td>
                    <td><?php echo htmlspecialchars($endereco['cep']); ?></td>
                    <td><?php echo $endereco['padrao'] ? 'Sim' : 'Não'; ?></td>
                    <td>
                        <a href="editar_endereco.php?id=<?php echo $endereco['id']; ?>" class="btn">Editar</a>
                        <?php if (!$endereco['padrao']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <input type="hidden" name="endereco_id" value="<?php echo $endereco['id']; ?>">
                            <button type="submit" name="excluir_endereco"
                                onclick="return confirm('Tem certeza que deseja excluir este endereço?')">Excluir</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Você não tem endereços cadastrados.</p>
        <?php endif; ?>


        <h3>Adicionar Novo Endereço</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nome_endereco">Nome do Endereço:</label>
            <input type="text" name="nome_endereco" id="nome_endereco" placeholder="Ex.: Casa, Trabalho" required>
            <label for="rua">Rua:</label>
            <input type="text" name="rua" id="rua" placeholder="Digite a rua" required>
            <label for="numero">Número:</label>
            <input type="text" name="numero" id="numero" placeholder="Digite o número">
            <label for="bairro">Bairro:</label>
            <input type="text" name="bairro" id="bairro" placeholder="Digite o bairro">
            <label for="cidade_endereco">Cidade:</label>
            <input type="text" name="cidade_endereco" id="cidade_endereco" placeholder="Digite a cidade" required>
            <label for="estado">Estado:</label>
            <input type="text" name="estado" id="estado" placeholder="Digite o estado" required>
            <label for="cep">CEP:</label>
            <input type="text" name="cep" id="cep" placeholder="Digite o CEP" required>
            <label><input type="checkbox" name="padrao"> Definir como padrão</label>
            <button type="submit" name="adicionar_endereco">Adicionar Endereço</button>
        </form>

        <p><a href="perfil.php" style="color: white;">Voltar ao Perfil</a></p>
        <p><a href="index.php" style="color: white;">Voltar ao Início</a></p>
    </div>
</body>

</html>