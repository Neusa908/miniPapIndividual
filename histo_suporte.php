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

// Busca as mensagens de suporte do usuário logado
$sql = "SELECT id, email, mensagem, data_envio, status, resposta, data_resposta FROM suporte WHERE usuario_id = ? ORDER BY data_envio DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Suporte - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <style>
    .historico-container {
        max-width: 800px;
        margin: 20px auto;
        padding: 20px;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .mensagem-item {
        border-bottom: 1px solid #ccc;
        padding: 10px 0;
        margin-bottom: 10px;
    }

    .mensagem-item:last-child {
        border-bottom: none;
    }

    .mensagem-item p {
        margin: 5px 0;
    }

    .mensagem-item .status-pendente {
        color: #e74c3c;
        font-weight: bold;
    }

    .mensagem-item .status-resolvido {
        color: #2ecc71;
        font-weight: bold;
    }
    </style>
</head>

<body class="support-body" style="background: url('img/frutas.jpg') no-repeat center center; background-size: cover;">
    <div class="historico-container">
        <h2>Histórico de Suporte</h2>
        <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
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
        <?php endwhile; ?>
        <?php else: ?>
        <p>Você ainda não enviou nenhuma mensagem de suporte.</p>
        <?php endif; ?>
        <div>
            <br><a href="suporte.php">Enviar Nova Mensagem</a> | <a href="index.php">Voltar para a Página Principal</a>
        </div>
    </div>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>