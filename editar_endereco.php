<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('É necessário estar logado para editar endereços. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$endereco_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$endereco_id) {
    header("Location: configuracoes.php");
    exit;
}

$sql = "SELECT id, nome_endereco, rua, numero, bairro, cidade, estado, cep, padrao FROM enderecos WHERE id = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $endereco_id, $usuario_id);
$stmt->execute();
$endereco = $stmt->get_result()->fetch_assoc();

if (!$endereco) {
    header("Location: configuracoes.php");
    exit;
}

// Processa edição do endereço
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_endereco'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $nome_endereco = trim(filter_input(INPUT_POST, 'nome_endereco', FILTER_SANITIZE_STRING));
        $rua = trim(filter_input(INPUT_POST, 'rua', FILTER_SANITIZE_STRING));
        $numero = trim(filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING));
        $bairro = trim(filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));
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

            $sql = "UPDATE enderecos SET nome_endereco = ?, rua = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, cep = ?, padrao = ? 
                    WHERE id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssiii", $nome_endereco, $rua, $numero, $bairro, $cidade, $estado, $cep, $padrao, $endereco_id, $usuario_id);
            if ($stmt->execute()) {
                $mensagem = "Endereço atualizado com sucesso!";
                header("Location: configuracoes.php");
                exit;
            } else {
                $mensagem = "Erro ao atualizar endereço.";
            }
        } else {
            $mensagem = implode("<br>", $erros);
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
    <title>Editar Endereço - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="registar">
    <div class="perfil-container">
        <h1>Editar Endereço</h1>
        <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nome_endereco">Nome do Endereço:</label>
            <input type="text" name="nome_endereco" id="nome_endereco"
                value="<?php echo htmlspecialchars($endereco['nome_endereco']); ?>" placeholder="Ex.: Casa, Trabalho"
                required>
            <label for="rua">Rua:</label>
            <input type="text" name="rua" id="rua" value="<?php echo htmlspecialchars($endereco['rua']); ?>"
                placeholder="Digite a rua" required>
            <label for="numero">Número:</label>
            <input type="text" name="numero" id="numero"
                value="<?php echo htmlspecialchars($endereco['numero'] ?? ''); ?>" placeholder="Digite o número">
            <label for="bairro">Bairro:</label>
            <input type="text" name="bairro" id="bairro"
                value="<?php echo htmlspecialchars($endereco['bairro'] ?? ''); ?>" placeholder="Digite o bairro">
            <label for="cidade">Cidade:</label>
            <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($endereco['cidade']); ?>"
                placeholder="Digite a cidade" required>
            <label for="estado">Estado:</label>
            <input type="text" name="estado" id="estado" value="<?php echo htmlspecialchars($endereco['estado']); ?>"
                placeholder="Digite o estado" required>
            <label for="cep">CEP:</label>
            <input type="text" name="cep" id="cep" value="<?php echo htmlspecialchars($endereco['cep']); ?>"
                placeholder="Digite o CEP" required>
            <label><input type="checkbox" name="padrao" <?php echo $endereco['padrao'] ? 'checked' : ''; ?>> Definir
                como padrão</label>
            <button type="submit" name="salvar_endereco">Salvar Endereço</button>
        </form>

        <p><a href="configuracoes.php" style="color: white;">Voltar às Configurações</a></p>
    </div>
</body>

</html>