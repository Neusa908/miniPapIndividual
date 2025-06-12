<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
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
    $usuario_id = $result_usuario->fetch_assoc()['usuario_id'];
    $stmt_usuario->close();

    if ($usuario_id) {
        // Atualizar o suporte com a resposta
        $sql_update = "UPDATE suporte SET resposta = ?, status = 'resolvido', data_resposta = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $resposta, $suporte_id);

        if ($stmt_update->execute()) {
            // Criar notificação para o cliente
            $mensagem_notif = "Sua mensagem de suporte (ID $suporte_id) foi respondida em " . date('d/m/Y H:i');
            $stmt_notif = $conn->prepare("INSERT INTO notificacoes (mensagem, usuario_id) VALUES (?, ?)");
            $stmt_notif->bind_param("si", $mensagem_notif, $usuario_id);
            $stmt_notif->execute();
            $stmt_notif->close();

            echo "<script>alert('Resposta enviada com sucesso!'); window.location.href='admin_suporte.php';</script>";
        } else {
            echo "<script>alert('Erro ao enviar a resposta. Tente novamente.');</script>";
        }
        $stmt_update->close();
    } else {
        echo "<script>alert('Usuário não encontrado para este suporte.');</script>";
    }
}

// Consultar mensagens de suporte pendentes
$sql_suporte = "SELECT id, email, mensagem, data_envio, status FROM suporte WHERE status = 'pendente' ORDER BY data_envio DESC";
$result_suporte = $conn->query($sql_suporte);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - Admin</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="main-content">
            <header class="admin-header">
                <h1>Gerenciar Suporte</h1>
            </header>
            <?php if ($result_suporte->num_rows > 0): ?>
            <?php while ($suporte = $result_suporte->fetch_assoc()): ?>
            <div class="suporte-item">
                <p><strong>ID:</strong> <?php echo $suporte['id']; ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($suporte['email']); ?></p>
                <p><strong>Mensagem:</strong> <?php echo htmlspecialchars($suporte['mensagem']); ?></p>
                <p><strong>Data de Envio:</strong> <?php echo htmlspecialchars($suporte['data_envio']); ?></p>
                <form method="POST" action="admin_suporte.php" class="suporte-form">
                    <input type="hidden" name="suporte_id" value="<?php echo $suporte['id']; ?>">
                    <textarea name="resposta" placeholder="Digite a resposta" required></textarea>
                    <button type="submit">Enviar Resposta</button>
                </form>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <p>Nenhuma mensagem de suporte pendente.</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>