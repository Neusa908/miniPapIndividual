<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    header('Location: login.php');
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];
$produto_id = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : 0;

if ($produto_id <= 0) {
    echo "<p class='error-message'>Produto inválido.</p>";
    exit();
}

// Busca informações do produto
$stmt = $conn->prepare("SELECT nome FROM produtos WHERE id = ?");
$stmt->bind_param("i", $produto_id);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();
if (!$produto) {
    echo "<p class='error-message'>Produto não encontrado.</p>";
    exit();
}

// Processa envio de comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avaliacao = isset($_POST['avaliacao']) ? (int)$_POST['avaliacao'] : 0;
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

    if ($avaliacao >= 1 && $avaliacao <= 5 && !empty($comentario)) {
        $stmt = $conn->prepare("INSERT INTO avaliacoes (utilizador_id, produto_id, avaliacao, comentario) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $utilizador_id, $produto_id, $avaliacao, $comentario);
        if ($stmt->execute()) {
            $success_message = "Comentário enviado com sucesso!";
        } else {
            $error_message = "Erro ao enviar comentário. Tente novamente.";
        }
    } else {
        $error_message = "Por favor, preencha a avaliação (1 a 5) e o comentário.";
    }
}

// Busca os comentários existentes
$stmt = $conn->prepare("
    SELECT a.comentario, a.avaliacao, a.data_avaliacao, u.nome 
    FROM avaliacoes a 
    JOIN utilizadores u ON a.utilizador_id = u.id 
    WHERE a.produto_id = ? 
    ORDER BY a.data_avaliacao DESC
");
$stmt->bind_param("i", $produto_id);
$stmt->execute();
$comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentários - <?php echo htmlspecialchars($produto['nome']); ?></title>
    <link rel="stylesheet" href="./css/style.css">

</head>

<body>
    <div class="container">
        <h1 class="page-title">Comentários sobre <?php echo htmlspecialchars($produto['nome']); ?></h1>

        <!-- Mensagens de feedback -->
        <?php if (isset($success_message)): ?>
        <p class="success-message"><?php echo htmlspecialchars($success_message); ?></p>
        <?php elseif (isset($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <!-- Formulário de comentário -->
        <form class="comment-form" method="POST" action="">
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
            <button type="submit" class="submit-button">Enviar Comentário</button>
        </form>

        <!-- Lista de comentários -->
        <div class="comments-list">
            <?php if (empty($comentarios)): ?>
            <p class="no-comments">Ainda não há comentários para este produto.</p>
            <?php else: ?>
            <?php foreach ($comentarios as $comentario): ?>
            <div class="comment-item">
                <p class="comment-author"><?php echo htmlspecialchars($comentario['nome']); ?></p>
                <p class="comment-rating">Avaliação: <?php echo htmlspecialchars($comentario['avaliacao']); ?>/5</p>
                <p class="comment-text"><?php echo htmlspecialchars($comentario['comentario']); ?></p>
                <p class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comentario['data_avaliacao'])); ?></p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>