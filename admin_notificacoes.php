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

error_log("Admin logado com ID: $usuario_id");

if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM notificacoes WHERE id = ? AND admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $usuario_id);
    if ($stmt->execute()) {
        header("Location: admin_notificacoes.php");
        exit;
    } else {
        error_log("Erro ao excluir notificação: " . $conn->error);
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['suporte_id']) && isset($_POST['resposta'])) {
    $suporte_id = (int)$_POST['suporte_id'];
    $resposta = trim($_POST['resposta']);

    // Obter o usuario_id associado ao suporte
    $sql_usuario = "SELECT usuario_id FROM suporte WHERE id = ?";
    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $suporte_id);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    $usuario_id_cliente = $result_usuario->fetch_assoc()['usuario_id'];
    $stmt_usuario->close();

    if ($usuario_id_cliente) {
        // Atualizar o suporte com a resposta
        $sql_update = "UPDATE suporte SET resposta = ?, status = 'resolvido', data_resposta = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $resposta, $suporte_id);

        if ($stmt_update->execute()) {
            // Criar notificação para o cliente
            $mensagem_notif = "Sua mensagem de suporte (ID $suporte_id) foi respondida em " . date('d/m/Y H:i');
            $stmt_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, mensagem, data_criacao, lida) VALUES (?, ?, NOW(), 0)");
            $stmt_notif->bind_param("is", $usuario_id_cliente, $mensagem_notif);
            $stmt_notif->execute();
            $stmt_notif->close();

            echo "<script>alert('Resposta enviada com sucesso!'); window.location.href='admin_notificacoes.php';</script>";
        } else {
            echo "<script>alert('Erro ao enviar a resposta. Tente novamente.');</script>";
        }
        $stmt_update->close();
    } else {
        echo "<script>alert('Usuário não encontrado para este suporte.');</script>";
    }
}

// Consultar notificações para o admin
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
    <script>
    function verificarNotificacoes() {
        fetch('verificar_notificacoes.php?admin_id=<?php echo $usuario_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.novas) {
                    location.reload();
                }
            })
            .catch(error => console.error('Erro ao verificar notificações:', error));
    }
    setInterval(verificarNotificacoes, 30000);
    </script>
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span> Voltar</a>
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
                        <?php
                        preg_match('/\(ID (\d+)\)/', $notificacao['mensagem'], $matches);
                        $suporte_id = $matches[1] ?? null;
                        if ($suporte_id): ?>
                        <a href="admin_suporte.php?suporte_id=<?php echo $suporte_id; ?>"
                            class="notificacao-ver">Ver</a>
                        <?php endif; ?>
                        <a href="admin_marcarLida.php?id=<?php echo $notificacao['id']; ?>"
                            class="notification-action">Marcar como lida</a>
                        <?php endif; ?>
                        <a href="?delete_id=<?php echo $notificacao['id']; ?>" class="notification-action delete"
                            onclick="return confirm('Tem certeza que deseja excluir esta notificação?');">Excluir</a>
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