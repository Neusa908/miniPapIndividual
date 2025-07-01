<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o usuário está logado e é um cliente
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Busca as mensagens de suporte do cliente logado
$utilizador_id = $_SESSION['utilizador_id'];
$sql = "SELECT id, email, mensagem, data_envio, status, resposta, data_resposta 
        FROM suporte 
        WHERE utilizador_id = ? AND status = 'resolvido'
        ORDER BY data_envio DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Mensagens de Suporte - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="support-history">
    <div class="support-history-container">
        <h2>Minhas Mensagens de Suporte</h2>
        <div class="support-items-container">
            <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="support-item">
                <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                <p><strong>Mensagem:</strong> <?php echo htmlspecialchars($row['mensagem']); ?></p>
                <p><strong>Data de Envio:</strong> <?php echo htmlspecialchars($row['data_envio']); ?></p>
                <p><strong>Status:</strong> <span
                        class="status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                </p>
                <?php if ($row['resposta']): ?>
                <p><strong>Resposta:</strong> <?php echo htmlspecialchars($row['resposta']); ?></p>
                <p><strong>Data da Resposta:</strong> <?php echo htmlspecialchars($row['data_resposta']); ?></p>
                <?php else: ?>
                <p><strong>Resposta:</strong> Ainda não respondida.</p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <div class="no-messages-box">
                <p>Você ainda não enviou nenhuma mensagem de suporte ou a mensagem solicitada não foi encontrada.</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="support-links">
            <a href="notificacoes.php"><b>Ir para as notificações</b></a>

            <a href="index.php"><b>Ir para a Página Principal</b></a>
            <a href="suporte.php"><b>Nova Mensagem de Suporte</b></a>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>