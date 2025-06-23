<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'conexao.php';

// Verifica se o utilizador é administrador
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Excluir pedido, se solicitado
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM pedidos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('Pedido excluído com sucesso!'); window.location.href='admin_vendas.php';</script>";
}

// Resumo de vendas
$sql_resumo = "SELECT COUNT(*) as total_pedidos, SUM(total) as valor_total FROM pedidos";
$result_resumo = $conn->query($sql_resumo);
$resumo = $result_resumo->fetch_assoc();

// Lista de todos os pedidos
$sql_pedidos = "SELECT p.id, p.data_pedido, p.total, p.status, u.nome 
                FROM pedidos p 
                JOIN utilizadores u ON p.utilizador_id = u.id 
                ORDER BY p.data_pedido DESC";
$result_pedidos = $conn->query($sql_pedidos);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Vendas - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span> Voltar ao Painel</a>
            </nav>
        </div>
        <div class="main-content">
            <header class="admin-header">
                <h1>Gerenciar Vendas</h1>
            </header>
            <div class="report-container">
                <h2>Resumo de Vendas</h2>
                <p>Total de Pedidos: <?php echo $resumo['total_pedidos'] ?? 0; ?></p>
                <p>Valor Total: €<?php echo number_format($resumo['valor_total'] ?? 0, 2); ?></p>

                <h2>Lista de Pedidos</h2>
                <?php if ($result_pedidos->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Total (€)</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pedido = $result_pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $pedido['id']; ?></td>
                            <td><?php echo htmlspecialchars($pedido['nome']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                            <td><?php echo number_format($pedido['total'], 2); ?></td>
                            <td><?php echo htmlspecialchars($pedido['status']); ?></td>
                            <td>
                                <a href="admin_vendas.php?excluir=<?php echo $pedido['id']; ?>"
                                    class="user-action delete"
                                    onclick="return confirm('Tem certeza que deseja excluir este pedido?')">Excluir</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Nenhum pedido encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>