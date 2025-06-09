<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$sql_notificacoes = "SELECT id, mensagem, data_criacao, lida FROM notificacoes WHERE admin_id = ? ORDER BY data_criacao DESC";
$stmt_notificacoes = $conn->prepare($sql_notificacoes);
$stmt_notificacoes->bind_param("i", $usuario_id);
$stmt_notificacoes->execute();
$result_notificacoes = $stmt_notificacoes->get_result();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Mercado Bom Preço</title>
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
                <h1>Notificações</h1>
            </header>
            <div class="notifications-container">
                <?php if ($result_notificacoes->num_rows > 0): ?>
                <ul class="notifications-list">
                    <?php while ($notificacao = $result_notificacoes->fetch_assoc()): ?>
                    <li
                        class="notification-item <?php echo $notificacao['lida'] ? 'notification-read' : 'notification-unread'; ?>">
                        <p class="notification-message"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                        <span
                            class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></span>
                        <?php if (!$notificacao['lida']): ?>
                        <a href="marcar_lida.php?id=<?php echo $notificacao['id']; ?>"
                            class="notification-action">Marcar como lida</a>
                        <?php endif; ?>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="no-notifications">Nenhuma notificação encontrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$stmt_notificacoes->close();
$conn->close();
?>