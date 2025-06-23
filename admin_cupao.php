<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$mensagem = '';

// Atualiza cupões vencidos para inativos
$conn->query("UPDATE promocoes SET ativa = 0 WHERE data_fim < NOW()");

// Exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir']) && !empty($_POST['codigo_excluir'])) {
    $codigo_excluir = $_POST['codigo_excluir'];
    $stmt = $conn->prepare("DELETE FROM promocoes WHERE codigo = ?");
    $stmt->bind_param("s", $codigo_excluir);
    if ($stmt->execute()) {
        $mensagem = "Cupão '{$codigo_excluir}' excluído com sucesso!";
    } else {
        $mensagem = "Erro ao excluir cupão.";
    }
    $stmt->close();
}

// Criação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo']) && !isset($_POST['excluir'])) {
    $codigo = strtoupper(trim($_POST['codigo']));
    $desconto = floatval($_POST['desconto']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];

    if (empty($codigo) || $desconto <= 0 || empty($data_fim)) {
        $mensagem = "Preencha todos os campos corretamente.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM promocoes WHERE codigo = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $mensagem = "Já existe um cupão com esse código.";
        } else {
            $sql = "INSERT INTO promocoes (codigo, desconto, data_inicio, data_fim, ativa) 
                    VALUES (?, ?, ?, ?, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdss", $codigo, $desconto, $data_inicio, $data_fim);
            $stmt->execute();
            $mensagem = $stmt->affected_rows > 0 ? "Cupão criado com sucesso!" : "Erro ao criar cupão.";
        }

        $stmt->close();
    }
}

$result = $conn->query("SELECT * FROM promocoes ORDER BY data_fim DESC");
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Cupões - Admin</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-cupao-body">
    <div class="admin-cupao-container">
        <h1 class="admin-cupao-title">Gestão de Cupões</h1>

        <?php if ($mensagem): ?>
        <p class="admin-cupao-msg"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <form method="POST" class="admin-cupao-form">
            <label for="codigo">Código do Cupão:</label>
            <input type="text" name="codigo" id="codigo" maxlength="20" required>

            <label for="desconto">Desconto (€):</label>
            <input type="number" step="0.01" name="desconto" id="desconto" required>

            <label for="data_inicio">Data de Início:</label>
            <input type="datetime-local" name="data_inicio" id="data_inicio" required>

            <label for="data_fim">Data de Validade:</label>
            <input type="datetime-local" name="data_fim" id="data_fim" required>

            <button type="submit" class="admin-cupao-btn">Criar Cupão</button>
        </form>

        <hr class="admin-cupao-sep">

        <h2 class="admin-cupao-subtitle">Cupões Existentes</h2>
        <table class="admin-cupao-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Desconto (€)</th>
                    <th>Início</th>
                    <th>Validade</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cupao = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cupao['codigo']); ?></td>
                    <td>€<?php echo number_format($cupao['desconto'], 2, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($cupao['data_inicio']); ?></td>
                    <td><?php echo htmlspecialchars($cupao['data_fim']); ?></td>
                    <td><?php echo $cupao['ativa'] ? 'Sim' : 'Não'; ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este cupão?');">
                            <input type="hidden" name="codigo_excluir"
                                value="<?php echo htmlspecialchars($cupao['codigo']); ?>">
                            <button type="submit" name="excluir" class="admin-cupao-btn-excluir">Deletar</button>
                            <button type="button" class="admin-cupao-btn-editar"
                                onclick="window.location.href='admin_editarCupao.php?codigo=<?php echo urlencode($cupao['codigo']); ?>'">Editar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="admin_panel.php" class="admin-cupao-voltar">← Voltar ao Painel</a>
    </div>
</body>

</html>