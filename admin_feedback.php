<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$admin_id = $_SESSION['usuario_id'];

// Exclusão de feedback
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM feedback_site WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Feedback excluído com sucesso!";
    } else {
        $_SESSION['error_message'] = "Erro ao excluir feedback.";
    }
    header("Location: admin_feedback.php");
    exit();
}

// Envio de resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    $resposta = trim($_POST['resposta']);
    if (!empty($resposta)) {
        $stmt = $conn->prepare("INSERT INTO respostas_feedback (feedback_id, admin_id, resposta) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $feedback_id, $admin_id, $resposta);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Resposta enviada com sucesso!";
        } else {
            $_SESSION['error_message'] = "Erro ao enviar resposta.";
        }
    } else {
        $_SESSION['error_message'] = "A resposta não pode estar vazia.";
    }
    header("Location: admin_feedback.php");
    exit();
}

// Exibir mensagens da sessão e limpá-las
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Busca feedbacks e respostas
$stmt = $conn->prepare("
    SELECT f.id, f.comentario, f.avaliacao, f.data_feedback, u.nome AS usuario_nome
    FROM feedback_site f
    JOIN usuarios u ON f.usuario_id = u.id
    ORDER BY f.data_feedback DESC
");
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Busca respostas para cada feedback
foreach ($feedbacks as &$feedback) {
    $stmt = $conn->prepare("
        SELECT r.resposta, r.data_resposta, u.nome AS admin_nome
        FROM respostas_feedback r
        JOIN usuarios u ON r.admin_id = u.id
        WHERE r.feedback_id = ?
        ORDER BY r.data_resposta ASC
    ");
    $stmt->bind_param("i", $feedback['id']);
    $stmt->execute();
    $feedback['respostas'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Feedbacks - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_feedback.css">

    <script>
    setTimeout(() => {
        const message = document.querySelector('.success-message, .error-message');
        if (message) message.style.display = 'none';
    }, 2000); // Desaparece após 2 segundos
    </script>

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
                <h1>Feedbacks do Site</h1>
            </header>

            <div class="feedback-container">
                <!-- Mensagens de feedback -->
                <?php if (isset($success_message)): ?>
                <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
                <?php elseif (isset($error_message)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <!-- Lista dos feedbacks -->
                <div class="feedback-list">
                    <?php if (empty($feedbacks)): ?>
                    <p class="no-feedback">Nenhum feedback disponível.</p>
                    <?php else: ?>
                    <?php foreach ($feedbacks as $feedback): ?>
                    <div class="feedback-item">
                        <p class="feedback-author">Feedback de
                            <?php echo htmlspecialchars($feedback['usuario_nome']); ?></p>
                        <p class="feedback-rating">Avaliação: <?php echo htmlspecialchars($feedback['avaliacao']); ?>/5
                        </p>
                        <p class="feedback-text"><?php echo htmlspecialchars($feedback['comentario']); ?></p>
                        <p class="feedback-date"><?php echo date('d/m/Y H:i', strtotime($feedback['data_feedback'])); ?>
                        </p>
                        <div class="feedback-actions">
                            <a href="admin_feedback.php?delete_id=<?php echo $feedback['id']; ?>" class="delete-button"
                                onclick="return confirm('Tem certeza que deseja deletar este feedback?');">Deletar</a>
                            <form method="POST" class="response-form">
                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                                <textarea name="resposta" class="response-textarea"
                                    placeholder="Escreva a sua resposta..." required></textarea>
                                <button type="submit" class="submit-response-button">Responder</button>
                            </form>
                        </div>

                        <!-- Exibe as respostas -->
                        <?php if (!empty($feedback['respostas'])): ?>
                        <div class="responses-list">
                            <?php foreach ($feedback['respostas'] as $resposta): ?>
                            <div class="response-item">
                                <p class="response-author">Respondido por
                                    <?php echo htmlspecialchars($resposta['admin_nome']); ?></p>
                                <p class="response-text"><?php echo htmlspecialchars($resposta['resposta']); ?></p>
                                <p class="response-date">
                                    <?php echo date('d/m/Y H:i', strtotime($resposta['data_resposta'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>