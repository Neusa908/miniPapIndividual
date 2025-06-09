<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}


if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Processar envio de feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avaliacao = isset($_POST['avaliacao']) ? (int)$_POST['avaliacao'] : 0;
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    if ($avaliacao >= 1 && $avaliacao <= 5 && !empty($comentario)) {
        $stmt = $conn->prepare("INSERT INTO feedback_site (usuario_id, avaliacao, comentario) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $usuario_id, $avaliacao, $comentario);
        if ($stmt->execute()) {
            $success_message = "Feedback enviado com sucesso!";
        } else {
            $error_message = "Erro ao enviar feedback. Tente novamente.";
        }
    } else {
        $error_message = "Por favor, preencha a avaliação (1 a 5) e o comentário.";
    }
}

// Buscar feedback existente
$stmt = $conn->prepare("
    SELECT f.comentario, f.avaliacao, f.data_feedback, u.nome 
    FROM feedback_site f 
    JOIN usuarios u ON f.usuario_id = u.id 
    ORDER BY f.data_feedback DESC
");
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <h1 class="page-title">O que achou do nosso site?</h1>

        <!-- Mensagens de feedback -->
        <?php if (isset($success_message)): ?>
        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php elseif (isset($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <!-- Formulário de feedback -->
        <form class="feedback-form" method="POST" action="">
            <div class="form-group">
                <label for="avaliacao" class="form-label">Avaliação (1 a 5):</label>
                <select id="avaliacao" name="avaliacao" class="form-select" required>
                    <option value="">Selecione</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>
            <div class="form-group">
                <label for="comentario" class="form-label">Comentário:</label>
                <textarea id="comentario" name="comentario" class="form-textarea" required></textarea>
            </div>
            <button type="submit" class="submit-button">Enviar Feedback</button>
        </form>

        <!-- Lista de feedback -->
        <div class="feedback-list">
            <?php if (empty($feedbacks)): ?>
            <p class="no-feedback">Ainda não há feedback sobre o site.</p>
            <?php else: ?>
            <?php foreach ($feedbacks as $feedback): ?>
            <div class="feedback-item">
                <p class="feedback-author"><?php echo htmlspecialchars($feedback['nome']); ?></p>
                <p class="feedback-rating">Avaliação: <?php echo htmlspecialchars($feedback['avaliacao']); ?>/5</p>
                <p class="feedback-text"><?php echo htmlspecialchars($feedback['comentario']); ?></p>
                <p class="feedback-date"><?php echo date('d/m/Y H:i', strtotime($feedback['data_feedback'])); ?></p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>