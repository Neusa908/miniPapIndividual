<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Foto de perfil
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $_SESSION['utilizador_id']);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg';
$stmt_foto->close();

// Vendas
$sql_vendas = "SELECT SUM(total) as total_vendas, COUNT(*) as total_pedidos 
               FROM pedidos 
               WHERE status IN ('pago', 'concluido')";
$result_vendas = $conn->query($sql_vendas);
$vendas = $result_vendas->fetch_assoc();

// Visitas totais
$sql_visitas = "SELECT COUNT(*) as total_visitas FROM visitas";
$result_visitas = $conn->query($sql_visitas);
$visitas = $result_visitas->fetch_assoc();

// Utilizadores novos
$sql_utilizadores_novos = "SELECT COUNT(*) as total_utilizadores_novos FROM utilizadores WHERE tipo = 'cliente' AND data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result_utilizadores_novos = $conn->query($sql_utilizadores_novos);
$utilizadores_novos = $result_utilizadores_novos->fetch_assoc();

// Total de clientes para comparação
$sql_total_utilizadores = "SELECT COUNT(*) as total FROM utilizadores WHERE tipo = 'cliente'";
$result_total_utilizadores = $conn->query($sql_total_utilizadores);
$total_utilizadores = $result_total_utilizadores->fetch_assoc();

// Pedidos recentes
$sql_pedidos = "SELECT p.id, p.data_pedido, p.total, p.status, u.nome 
                FROM pedidos p 
                JOIN utilizadores u ON p.utilizador_id = u.id 
                ORDER BY p.data_pedido DESC 
                LIMIT 10";
$result_pedidos = $conn->query($sql_pedidos);

// Visitas recentes
$sql_visitas_recentes = "SELECT v.pagina, v.data_visita, u.nome 
                         FROM visitas v 
                         LEFT JOIN utilizadores u ON v.utilizador_id = u.id 
                         ORDER BY v.data_visita DESC 
                         LIMIT 10";
$result_visitas_recentes = $conn->query($sql_visitas_recentes);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-relatorios"><span class="icon">⬅️</span> Voltar ao Painel</a>
            </nav>
        </div>
        <div class="main-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="usuario-foto-container">
                    <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
                </div>
            </header>

            <div class="reports-container">

                <div class="report-section">
                    <h2>Resumo de Vendas</h2>
                    <div class="chart-container">
                        <canvas id="vendasChart"></canvas>
                    </div>
                </div>

                <div class="report-section">
                    <h2>Resumo de Visitas</h2>
                    <div class="chart-container">
                        <canvas id="visitasChart"></canvas>
                    </div>
                </div>

                <div class="report-section">
                    <h2>Utilizadores Novos</h2>
                    <div class="chart-container">
                        <canvas id="utilizadoresChart"></canvas>
                    </div>
                </div>


                <div class="report-section">
                    <h2>Pedidos Recentes</h2>
                    <?php if ($result_pedidos->num_rows > 0): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Número do Pedido</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Total (€)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_pedidos->fetch_assoc()): ?>
                            <tr class="report-row">
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['nome']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['data_pedido'])) ?></td>
                                <td><?= number_format($row['total'], 2) ?></td>
                                <td><?= htmlspecialchars($row['status']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="no-reports">Nenhuma venda recente encontrada.</p>
                    <?php endif; ?>
                </div>

                <div class="report-section">
                    <h2>Visitas Recentes em Páginas</h2>
                    <?php if ($result_visitas_recentes->num_rows > 0): ?>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Utilizador</th>
                                <th>Página</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_visitas_recentes->fetch_assoc()): ?>
                            <tr class="report-row">
                                <td><?= htmlspecialchars($row['nome'] ?? 'Visitante') ?></td>
                                <td><?= htmlspecialchars($row['pagina']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['data_visita'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="no-reports">Nenhuma visita recente encontrada.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script>
    const vendasChart = new Chart(document.getElementById('vendasChart'), {
        type: 'bar',
        data: {
            labels: ['Total Vendas (€)', 'Total Pedidos'],
            datasets: [{
                label: 'Vendas e Pedidos',
                data: [
                    <?= number_format($vendas['total_vendas'] ?? 0, 2, '.', '') ?>,
                    <?= $vendas['total_pedidos'] ?? 0 ?>
                ],
                backgroundColor: ['#4CAF50', '#2196F3']
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    const visitasChart = new Chart(document.getElementById('visitasChart'), {
        type: 'doughnut',
        data: {
            labels: ['Visitas Totais'],
            datasets: [{
                label: 'Visitas',
                data: [<?= $visitas['total_visitas'] ?? 0 ?>],
                backgroundColor: ['#FF9800']
            }]
        },
        options: {
            responsive: true
        }
    });

    const utilizadoresChart = new Chart(document.getElementById('utilizadoresChart'), {
        type: 'pie',
        data: {
            labels: ['Utilizadores Novos', 'Utilizadores Existentes'],
            datasets: [{
                data: [
                    <?= $utilizadores_novos['total_utilizadores_novos'] ?? 0 ?>,
                    <?= max(0, ($total_utilizadores['total'] ?? 0) - ($utilizadores_novos['total_utilizadores_novos'] ?? 0)) ?>
                ],
                backgroundColor: ['#E91E63', '#9E9E9E']
            }]
        },
        options: {
            responsive: true
        }
    });
    </script>
</body>

</html>

<?php $conn->close(); ?>