<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('É necessário estar logado para ver o histórico de suporte. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$suporte_id = isset($_GET['suporte_id']) ? (int)$_GET['suporte_id'] : 0;

// Busca a mensagem de suporte específica do usuário logado
$sql = "SELECT id, email, mensagem, data_envio, status, resposta, data_resposta FROM suporte WHERE id = ? AND usuario_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $suporte_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Mensagem não encontrada ou acesso negado.'); window.location.href='notificacoes.php';</script>";
    exit();
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Suporte - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">

</head>

<body class="support-body" style="background: url('img/frutas.jpg') no-repeat center center; background-size: cover;">
    <div class="historico-container">
        <h2>Histórico de Suporte (ID: <?php echo htmlspecialchars($suporte_id); ?>)</h2>
        <div class="mensagem-item">
            <p><strong>Mensagem:</strong> <?php echo htmlspecialchars($row['mensagem']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
            <p><strong>Data de Envio:</strong> <?php echo htmlspecialchars($row['data_envio']); ?></p>
            <p><strong>Status:</strong> <span
                    class="status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
            </p>
            <?php if ($row['resposta']): ?>
            <p><strong>Resposta:</strong> <?php echo htmlspecialchars($row['resposta']); ?></p>
            <p><strong>Data da Resposta:</strong> <?php echo htmlspecialchars($row['data_resposta']); ?></p>
            <?php endif; ?>
        </div>
        <div>
            <br><a href="suporte.php">Enviar Nova Mensagem</a> | <a href="notificacoes.php">Voltar para Notificações</a>
        </div>
    </div>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>