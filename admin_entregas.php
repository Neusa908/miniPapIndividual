<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso restrito.'); window.location.href='index.php';</script>";
    exit();
}

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entrega_id'], $_POST['status_entrega'])) {
    $entrega_id = intval($_POST['entrega_id']);
    $novo_status = $_POST['status_entrega'];
    $minutos = isset($_POST['tempo_min']) ? intval($_POST['tempo_min']) : 5; // Valor padrão de 5 minutos se não for fornecido

    $hora_estimada = (new DateTime())->modify("+$minutos minutes")->format('Y-m-d H:i:s');

    $sql = "UPDATE entregas SET status_entrega = ?, hora_estimada_entrega = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $novo_status, $hora_estimada, $entrega_id);

    if ($stmt->execute()) {
        $mensagem = "Entrega atualizada com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar a entrega.";
    }

    $stmt->close();
}

// Buscar todas as entregas
$sql = "SELECT e.id AS entrega_id, e.pedido_id, e.status_entrega, e.hora_estimada_entrega, 
               p.utilizador_id, u.nome 
        FROM entregas e
        JOIN pedidos p ON e.pedido_id = p.id
        JOIN utilizadores u ON p.utilizador_id = u.id
        ORDER BY e.hora_estimada_entrega DESC";
$result = $conn->query($sql);
$entregas = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Admin - Gestão de Entregas</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="admin-entregas-body">
    <div class="admin-entregas-container">
        <h1 class="admin-entregas-titulo">Gestão de Entregas</h1>

        <?php if ($mensagem): ?>
        <div class="admin-entregas-msg"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php foreach ($entregas as $entrega): ?>
        <form class="admin-entrega-form" method="POST">
            <div class="admin-entrega-info">
                <p><strong>Pedido #<?= $entrega['pedido_id'] ?></strong> - Cliente:
                    <?= htmlspecialchars($entrega['nome']) ?></p>
                <p>Status atual: <strong><?= ucfirst($entrega['status_entrega']) ?></strong></p>
                <p>Hora estimada:
                    <?= $entrega['hora_estimada_entrega'] ? (new DateTime($entrega['hora_estimada_entrega']))->format('H:i') : 'Não definida' ?>
                </p>
            </div>

            <input type="hidden" name="entrega_id" value="<?= $entrega['entrega_id'] ?>">

            <label>Alterar estado:</label>
            <select name="status_entrega" required>
                <option value="a preparar" <?= $entrega['status_entrega'] === 'a preparar' ? 'selected' : '' ?>>A
                    preparar</option>
                <option value="entregue" <?= $entrega['status_entrega'] === 'entregue' ? 'selected' : '' ?>>Entregue
                </option>
            </select>

            <label>Tempo estimado (minutos):</label>
            <input type="number" name="tempo_min" min="1" value="10" required>

            <button type="submit" class="admin-entrega-btn">Atualizar Entrega</button>
        </form>
        <hr>
        <?php endforeach; ?>
        <a href="admin_panel.php" class="add-admin-back-link">Voltar para o Painel Administrativo</a>

    </div>
</body>

</html>