<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso restrito.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Buscar foto de perfil do utilizador
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $utilizador_id);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg';
$stmt_foto->close();

// Buscar todos os pedidos do utilizador
$sql = "SELECT e.status_entrega, e.hora_estimada_entrega, e.pedido_id, p.total, p.data_pedido 
        FROM entregas e 
        JOIN pedidos p ON e.pedido_id = p.id 
        WHERE p.utilizador_id = ? 
        ORDER BY p.data_pedido DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$result = $stmt->get_result();
$entregas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Estado da Entrega - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="cliente-entrega-body">
    <div class="cliente-entrega-container">
        <div class="usuario-foto-container">
            <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
        </div>
        <h1 class="cliente-entrega-titulo">Estado da Entrega</h1>

        <?php if ($entregas): ?>
        <?php foreach ($entregas as $entrega): ?>
        <div class="cliente-entrega-item">
            <p><strong>Número do Pedido:</strong> <?= htmlspecialchars($entrega['pedido_id']) ?></p>
            <p><strong>Total:</strong> €<?= number_format($entrega['total'], 2, ',', '.') ?></p>

            <?php
                    $agora = new DateTime();
                    $estimada = new DateTime($entrega['hora_estimada_entrega']);
                    $estado = $entrega['status_entrega'];

                    if ($estado === 'entregue') {
                        echo "<p class='cliente-entrega-status entregue'>Status: Entregue </p>";
                    } elseif ($agora >= $estimada) {
                        $sql = "UPDATE entregas SET status_entrega = 'entregue' WHERE pedido_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $entrega['pedido_id']);
                        $stmt->execute();
                        $stmt->close();

                        echo "<p class='cliente-entrega-status entregue'>Status: Entregue </p>";
                    } else {
                        $intervalo = $agora->diff($estimada);
                        $min_restantes = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;
                        echo "<p class='cliente-entrega-status preparando'>Status: A ser preparado </p>";
                        echo "<p class='cliente-entrega-tempo'>Entrega estimada em aproximadamente <strong>$min_restantes minutos</strong>.</p>";
                    }
                    ?>
            <a href="reclamacoes.php?pedido_id=<?= htmlspecialchars($entrega['pedido_id']) ?>"
                class="reclamacao-botao">Reclamar Pedido</a>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>Não há pedidos em andamento.</p>
        <?php endif; ?>

        <a href="index.php" class="cliente-entrega-voltar">Voltar à loja</a>
    </div>
</body>

<footer class="index-footer">
    <p>© 2024-2025 Mercado Bom Preço. Todos os direitos reservados.</p>
</footer>

</html>