<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['codigo'])) {
    header('Location: admin_cupao.php');
    exit();
}

$codigo = $_GET['codigo'];
$mensagem = '';

// Buscar o cupão atual
$stmt = $conn->prepare("SELECT * FROM promocoes WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();
$cupao = $result->fetch_assoc();
$stmt->close();

if (!$cupao) {
    $mensagem = "Cupão não encontrado.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novo_codigo = strtoupper(trim($_POST['codigo']));
    $desconto = floatval($_POST['desconto']);
    $valor_minimo = floatval($_POST['valor_minimo']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $ativa = isset($_POST['ativa']) ? 1 : 0;

    if (empty($novo_codigo) || $desconto <= 0 || $valor_minimo < 0 || empty($data_fim)) {
        $mensagem = "Preencha todos os campos corretamente.";
    } elseif ($ativa === 1 && strtotime($data_fim) < time()) {
        $mensagem = "Não é possível ativar um cupão com data de validade expirada.";
    } else {
        $stmt = $conn->prepare("UPDATE promocoes 
            SET codigo = ?, desconto = ?, valor_minimo = ?, data_inicio = ?, data_fim = ?, ativa = ? 
            WHERE id = ?");
        $stmt->bind_param("sddssii", $novo_codigo, $desconto, $valor_minimo, $data_inicio, $data_fim, $ativa, $cupao['id']);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: admin_cupao.php");
            exit();
        } else {
            $mensagem = "Erro ao atualizar cupão.";
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Editar Cupão</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-cupao-body">
    <div class="admin-cupao-container">
        <h1 class="admin-cupao-title">Editar Cupão</h1>

        <?php if ($mensagem): ?>
        <p class="admin-cupao-msg"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <?php if ($cupao): ?>
        <form method="POST" class="admin-cupao-form">
            <label for="codigo">Código do Cupão:</label>
            <input type="text" name="codigo" id="codigo" value="<?= htmlspecialchars($cupao['codigo']); ?>" required>

            <label for="desconto">Desconto (€):</label>
            <input type="number" step="0.01" name="desconto" id="desconto"
                value="<?= htmlspecialchars($cupao['desconto']); ?>" required>

            <label for="valor_minimo">Valor Mínimo de Compra (€):</label>
            <input type="number" step="0.01" name="valor_minimo" id="valor_minimo"
                value="<?= htmlspecialchars($cupao['valor_minimo']); ?>" required>

            <label for="data_inicio">Data de Início:</label>
            <input type="datetime-local" name="data_inicio" id="data_inicio"
                value="<?= str_replace(' ', 'T', $cupao['data_inicio']); ?>" required>

            <label for="data_fim">Data de Validade:</label>
            <input type="datetime-local" name="data_fim" id="data_fim"
                value="<?= str_replace(' ', 'T', $cupao['data_fim']); ?>" required>

            <label for="ativa">
                <input type="checkbox" name="ativa" id="ativa" <?= $cupao['ativa'] ? 'checked' : ''; ?>> Ativo
            </label>

            <button type="submit" class="admin-cupao-btn">Salvar Alterações</button>
        </form>
        <?php endif; ?>

        <a href="admin_cupao.php" class="admin-cupao-voltar">Voltar</a>
    </div>
</body>

</html>