<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

error_log("Cliente logado com ID: $utilizador_id");

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM notificacoes WHERE id = ? AND usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $utilizador_id);
    if ($stmt->execute()) {
        header("Location: notificacoes.php");
        exit;
    } else {
        error_log("Erro ao excluir notificação: " . $conn->error);
    }
    $stmt->close();
}

// Consultar notificações para o cliente
$sql_notificacoes = "SELECT id, mensagem, data_criacao, lida FROM notificacoes WHERE utilizador_id = ? ORDER BY data_criacao DESC";
$stmt_notificacoes = $conn->prepare($sql_notificacoes);
$stmt_notificacoes->bind_param("i", $utilizador_id);
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
                <p class="notification-message">
                    <?php
                    $mensagem = htmlspecialchars($notificacao['mensagem']);
                    $mensagem = preg_replace('/\(ID \d+\)/', '', $mensagem);
                    echo trim($mensagem);
                    ?>
                </p>
                <span
                    class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></span>
                <?php if (!$notificacao['lida']): ?>
                <a href="marcar_lida.php?id=<?php echo $notificacao['id']; ?>" class="notification-action">Marcar como
                    lida</a>
                <a href="resposta_suporte.php" class="notificacao-ver">Ver</a>
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