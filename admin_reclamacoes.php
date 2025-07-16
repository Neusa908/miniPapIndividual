<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso restrito.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Buscar todas as reclamações
$sql = "SELECT r.id, r.pedido_id, r.utilizador_id, r.descricao, r.data_reclamacao, r.status, u.apelido 
        FROM reclamacoes r 
        JOIN utilizadores u ON r.utilizador_id = u.id 
        ORDER BY r.data_reclamacao DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$reclamacoes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Atualizar status da reclamação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $reclamacao_id = (int)$_POST['reclamacao_id'];
    $novo_status = $_POST['status'];

    $sql = "UPDATE reclamacoes SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $novo_status, $reclamacao_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Status atualizado com sucesso!'); window.location.href='admin_reclamacoes.php';</script>";
    exit();
}

// Buscar foto de perfil do administrador
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
    <title>Gerenciar Reclamações - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="admin-reclamacao-body">
    <div class="admin-reclamacao-container">
        <div class="usuario-foto-container">
            <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
        </div>
        <h1 class="admin-reclamacao-titulo">Reclamações</h1>

        <?php if ($reclamacoes): ?>
        <?php foreach ($reclamacoes as $reclamacao): ?>
        <div class="admin-reclamacao-item">
            <p><strong>Reclamação:</strong> <?= htmlspecialchars($reclamacao['id']) ?></p>
            <p><strong>Número do Pedido:</strong> <?= htmlspecialchars($reclamacao['pedido_id']) ?></p>
            <p><strong>Usuário:</strong> <?= htmlspecialchars($reclamacao['apelido']) ?>
            <p><strong>Descrição:</strong> <?= htmlspecialchars($reclamacao['descricao']) ?></p>
            <p><strong>Data:</strong> <?= htmlspecialchars($reclamacao['data_reclamacao']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($reclamacao['status']) ?></p>

            <form method="POST" class="admin-reclamacao-form">
                <input type="hidden" name="reclamacao_id" value="<?= htmlspecialchars($reclamacao['id']) ?>">
                <select name="status" class="admin-reclamacao-select">
                    <option value="pendente" <?= $reclamacao['status'] === 'pendente' ? 'selected' : '' ?>>Pendente
                    </option>
                    <option value="resolvido" <?= $reclamacao['status'] === 'resolvido' ? 'selected' : '' ?>>Resolvido
                    </option>
                </select>
                <button type="submit" name="atualizar_status" class="admin-reclamacao-submit">Atualizar Status</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="admin-reclamacao-no-data">Nenhuma reclamação registrada.</p>
        <?php endif; ?>

        <a href="admin_panel.php" class="admin-reclamacao-voltar">Voltar ao Painel</a>
    </div>
</body>

<footer class="index-footer">
    <p>© 2024-2025 Mercado Bom Preço. Todos os direitos reservados.</p>
</footer>

</html>