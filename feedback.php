<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo "<script>alert('Faça login para acessar esta página.'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Processar envio de feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avaliacao = isset($_POST['avaliacao']) ? (int)$_POST['avaliacao'] : 0;
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    if ($avaliacao >= 1 && $avaliacao <= 5 && !empty($comentario)) {
        $stmt = $conn->prepare("INSERT INTO feedback_site (utilizador_id, avaliacao, comentario) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $utilizador_id, $avaliacao, $comentario);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Feedback enviado com sucesso!";
            header("Location: feedback.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Erro ao enviar feedback. Tente novamente.";
            header("Location: feedback.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Por favor, preencha a avaliação (1 a 5) e o comentário.";
        header("Location: feedback.php");
        exit();
    }
}

// Exibir mensagens da sessão e limpá-las
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Buscar feedback existente
$stmt = $conn->prepare("
    SELECT f.id, f.comentario, f.avaliacao, f.data_feedback, f.utilizador_id, u.nome 
    FROM feedback_site f 
    JOIN utilizadores u ON f.utilizador_id = u.id 
    ORDER BY f.data_feedback DESC
");
$stmt->execute();
$feedbacks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Buscar respostas para cada feedback
foreach ($feedbacks as &$feedback) {
    $stmt = $conn->prepare("
        SELECT r.resposta, r.data_resposta, u.nome AS admin_nome
        FROM respostas_feedback r
        JOIN utilizadores u ON r.admin_id = u.id
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
    <title>Feedback - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">

    <script>
    setTimeout(() => {
        const message = document.querySelector('.success-message, .error-message');
        if (message) message.style.display = 'none';
    }, 2000); // a mensagem desaparece após 2 segundos
    </script>

</head>

<body class="feedback-body">
    <div class="container-feedback">
        <header class="feedback-header">
            <h1 class="page-title-feedback">O que achou do nosso site?</h1>
            <a href="index.php" class="back-link-feedback">Voltar</a>
        </header>

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
            <button type="submit" class="submit-button-feedback">Enviar Feedback</button>
        </form>

        <!-- Lista de feedback -->
        <div class="feedback-list">
            <?php if (empty($feedbacks)): ?>
            <p class="no-feedback">Ainda não há feedback sobre o site.</p>
            <?php else: ?>
            <?php foreach ($feedbacks as $feedback): ?>
            <div class="feedback-item">
                <p class="feedback-author">
                    <a href="verPerfil.php?id=<?php echo $feedback['utilizador_id']; ?>" class="author-link">
                        <?php echo htmlspecialchars($feedback['nome']); ?>
                    </a>
                </p>
                <p class="feedback-rating">Avaliação: <?php echo htmlspecialchars($feedback['avaliacao']); ?>/5</p>
                <p class="feedback-text"><?php echo htmlspecialchars($feedback['comentario']); ?></p>
                <p class="feedback-date"><?php echo date('d/m/Y H:i', strtotime($feedback['data_feedback'])); ?></p>
                <!-- Exibir respostas -->
                <?php if (!empty($feedback['respostas'])): ?>
                <div class="responses-list">
                    <?php foreach ($feedback['respostas'] as $resposta): ?>
                    <div class="response-item">
                        <p class="response-author">Respondido por
                            <?php echo htmlspecialchars($resposta['admin_nome']); ?></p>
                        <p class="response-text"><?php echo htmlspecialchars($resposta['resposta']); ?></p>
                        <p class="response-date"><?php echo date('d/m/Y H:i', strtotime($resposta['data_resposta'])); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>