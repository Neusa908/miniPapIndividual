<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

error_log("Cliente logado com ID: $usuario_id");

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $usuario_id);
    if ($stmt->execute()) {
        header("Location: notificacoes.php");
        exit;
    } else {
        error_log("Erro ao excluir notificação: " . $conn->error);
    }
    $stmt->close();
}

// Consultar notificações de mensagens respondidas
$sql_notificacoes = "SELECT n.id, n.mensagem, n.data_criacao, n.lida, 
                    SUBSTRING_INDEX(SUBSTRING_INDEX(n.mensagem, '(ID ', -1), ')', 1) AS suporte_id 
                    FROM notificacoes n 
                    WHERE n.usuario_id = ? 
                    AND n.mensagem LIKE '%foi respondida%' 
                    ORDER BY n.data_criacao DESC";
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

<body class="support-body">
    <div class="notifications-container">
        <h2>Minhas Notificações</h2>
        <?php if ($result_notificacoes->num_rows > 0): ?>
        <ul class="notifications-list">
            <?php while ($notificacao = $result_notificacoes->fetch_assoc()): ?>
            <li
                class="notification-item <?php echo $notificacao['lida'] ? 'notification-read' : 'notification-unread'; ?>">
                <p class="notification-message"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                <span
                    class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></span>
                <?php if (!$notificacao['lida']): ?>
                <a href="marcar_lida.php?id=<?php echo $notificacao['id']; ?>" class="notification-action">Marcar como
                    lida</a>
                <?php endif; ?>
                <?php if ($notificacao['suporte_id']): ?>
                <a href="histo_suporte.php?suporte_id=<?php echo htmlspecialchars($notificacao['suporte_id']); ?>"
                    class="notificacao-ver">Ver</a>
                <?php endif; ?>
                <a href="?delete_id=<?php echo $notificacao['id']; ?>" class="notification-action delete"
                    onclick="return confirm('Tem certeza que deseja excluir esta notificação?');">Excluir</a>
            </li>
            <?php endwhile; ?>
        </ul>
        <?php else: ?>
        <p class="no-notifications">Nenhuma notificação encontrada.</p>
        <?php endif; ?>
        <a href="index.php" class="suporte-back-link">Voltar para a Página Principal</a>
    </div>
</body>

</html>
<?php
$stmt_notificacoes->close();
$conn->close();
?>