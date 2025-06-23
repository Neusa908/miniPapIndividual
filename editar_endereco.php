<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo "<script>alert('É necessário estar logado para editar endereços. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];
$mensagem = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$endereco_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$endereco_id) {
    header("Location: configuracoes.php");
    exit;
}

$sql = "SELECT id, nome_endereco, rua, numero, freguesia, cidade, distrito, codigo_postal, padrao FROM enderecos WHERE id = ? AND utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $endereco_id, $utilizador_id);
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
        $freguesia = trim(filter_input(INPUT_POST, 'freguesia', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));
        $distrito = trim(filter_input(INPUT_POST, 'distrito', FILTER_SANITIZE_STRING));
        $codigo_postal = trim(filter_input(INPUT_POST, 'codigo_postal', FILTER_SANITIZE_STRING));
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
        if (empty($distrito)) {
            $erros[] = "O distrito é obrigatório.";
        }
        if (empty($codigo_postal)) {
            $erros[] = "O código postal é obrigatório.";
        }

        if (empty($erros)) {
            if ($padrao) {
                $sql = "UPDATE enderecos SET padrao = 0 WHERE utilizador_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $utilizador_id);
                $stmt->execute();
            }

            $sql = "UPDATE enderecos SET nome_endereco = ?, rua = ?, numero = ?, freguesia = ?, cidade = ?, distrito = ?, codigo_postal = ?, padrao = ? 
                    WHERE id = ? AND utilizador_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssiii", $nome_endereco, $rua, $numero, $freguesia, $cidade, $distrito, $codigo_postal, $padrao, $endereco_id, $utilizador_id);
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
            <label for="freguesia">Freguesia:</label>
            <input type="text" name="freguesia" id="freguesia"
                value="<?php echo htmlspecialchars($endereco['freguesia'] ?? ''); ?>" placeholder="Digite a freguesia">
            <label for="cidade">Cidade:</label>
            <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($endereco['cidade']); ?>"
                placeholder="Digite a cidade" required>
            <label for="distrito">Distrito:</label>
            <input type="text" name="distrito" id="distrito"
                value="<?php echo htmlspecialchars($endereco['distrito']); ?>" placeholder="Digite o distrito" required>
            <label for="codigo_postal">Código Postal:</label>
            <input type="text" name="codigo_postal" id="codigo_postal"
                value="<?php echo htmlspecialchars($endereco['codigo_postal']); ?>" placeholder="Digite o Código Postal"
                required>
            <label><input type="checkbox" name="padrao" <?php echo $endereco['padrao'] ? 'checked' : ''; ?>> Definir
                como padrão</label>
            <button type="submit" name="salvar_endereco">Salvar Endereço</button>
        </form>

        <p><a href="configuracoes.php" style="color: white;">Voltar às Configurações</a></p>
    </div>
</body>

</html>