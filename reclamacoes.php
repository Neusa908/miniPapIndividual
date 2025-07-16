<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso restrito.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];
$pedido_id = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : 0;

if ($pedido_id) {
    // Verifica se o pedido pertence ao utilizador
    $sql = "SELECT p.id FROM pedidos p WHERE p.id = ? AND p.utilizador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $pedido_id, $utilizador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedido_valido = $result->fetch_assoc();
    $stmt->close();

    if (!$pedido_valido) {
        echo "<script>alert('Pedido inválido.'); window.location.href='entregas.php';</script>";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reclamacao'])) {
    $descricao = $_POST['descricao'] ?? '';
    $data_reclamacao = date('Y-m-d H:i:s');

    $sql = "INSERT INTO reclamacoes (pedido_id, utilizador_id, descricao, data_reclamacao, status) VALUES (?, ?, ?, ?, 'pendente')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $pedido_id, $utilizador_id, $descricao, $data_reclamacao);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Reclamação enviada com sucesso!'); window.location.href='entregas.php';</script>";
    exit();
}

// Buscar foto de perfil do utilizador
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $utilizador_id);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg'; // Fallback
$stmt_foto->close();
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Reclamação - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="reclamacao-body">
    <div class="reclamacao-container">
        <div class="usuario-foto-container">
            <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
        </div>
        <h1 class="reclamacao-titulo">Fazer Reclamação</h1>
        <?php if ($pedido_id): ?>
        <p class="reclamacao-info">Número do Pedido: <?= htmlspecialchars($pedido_id) ?></p>
        <form method="POST" class="reclamacao-form">
            <textarea name="descricao" class="reclamacao-textarea" placeholder="Escreva a sua reclamação..."
                required></textarea>
            <button type="submit" name="reclamacao" class="reclamacao-enviar">Enviar Reclamação</button>
        </form>
        <?php else: ?>
        <p class="reclamacao-erro">Nenhum pedido selecionado.</p>
        <?php endif; ?>
        <a href="entregas.php" class="reclamacao-voltar">Voltar</a>
    </div>
</body>

<footer class="index-footer">
    <p>© 2024-2025 Mercado Bom Preço. Todos os direitos reservados.</p>
</footer>

</html>