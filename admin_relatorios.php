<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'conexao.php';

// Verifica se o usuário é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// vendas
$sql_vendas = "SELECT SUM(total) as total_vendas, COUNT(*) as total_pedidos FROM pedidos WHERE status = 'concluido'";
$result_vendas = $conn->query($sql_vendas);
$vendas = $result_vendas->fetch_assoc();

// vendas recentes
$sql_pedidos = "SELECT p.id, p.data_pedido, p.total, p.status, u.nome FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.data_pedido DESC LIMIT 10";
$result_pedidos = $conn->query($sql_pedidos);

// visitas
$sql_visitas = "SELECT COUNT(*) as total_visitas, COUNT(DISTINCT usuario_id) as usuarios_unicos FROM visitas WHERE data_visita >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result_visitas = $conn->query($sql_visitas);
$visitas = $result_visitas->fetch_assoc();

//  visitas recentes
$sql_visitas_recentes = "SELECT v.pagina, v.data_visita, u.nome FROM visitas v LEFT JOIN usuarios u ON v.usuario_id = u.id ORDER BY v.data_visita DESC LIMIT 10";
$result_visitas_recentes = $conn->query($sql_visitas_recentes);

// Usuários ativos
$sql_usuarios = "SELECT COUNT(*) as total_usuarios FROM usuarios WHERE tipo = 'cliente'";
$result_usuarios = $conn->query($sql_usuarios);
$usuarios = $result_usuarios->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios e Análises - Mercado Bom Preço</title>
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
                <h1>Relatórios e Análises</h1>
            </header>
            <div class="report-container">
                <h2>Resumo de Vendas</h2>
                <p class="users-table">Total de Vendas (Concluídas):
                    €<?php echo number_format($vendas['total_vendas'] ?? 0, 2); ?></p>
                <p class="users-table">Total de Pedidos Concluídos: <?php echo $vendas['total_pedidos'] ?? 0; ?></p>

                <h2>Vendas Recentes</h2>
                <?php if ($result_pedidos->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Total (€)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['data_pedido'])); ?></td>
                            <td><?php echo number_format($row['total'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="users-table">Nenhuma venda recente encontrada.</p>
                <?php endif; ?>

                <h2>Resumo de Visitas (Últimos 30 Dias)</h2>
                <p class="users-table">Total de Visitas: <?php echo $visitas['total_visitas'] ?? 0; ?></p>
                <h2>Visitas Recentes</h2>
                <?php if ($result_visitas_recentes->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Página</th>
                            <th>Usuário</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_visitas_recentes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['pagina']); ?></td>
                            <td><?php echo $row['nome'] ? htmlspecialchars($row['nome']) : 'Não registado'; ?></td>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($row['data_visita'])); ?></td>
                        </tr>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="users-table">Nenhuma visita recente encontrada.</p>
                <?php endif; ?>

                <h2>Usuários Ativos</h2>
                <p class="users-table">Total de Usuários: <?php echo $usuarios['total_usuarios'] ?? 0; ?></p>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>