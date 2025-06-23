<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o utilizador é administrador
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Processa a resposta, se enviada
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['responder'])) {
    $suporte_id = $_POST['suporte_id'];
    $resposta = trim($_POST['resposta']);

    if (!empty($resposta)) {
        $sql = "UPDATE suporte SET resposta = ?, data_resposta = NOW(), status = 'resolvido' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $resposta, $suporte_id);
        if ($stmt->execute()) {
            // Obter o utilizador_id associado ao suporte
            $sql_utilizador = "SELECT utilizador_id FROM suporte WHERE id = ?";
            $stmt_utilizador = $conn->prepare($sql_utilizador);
            $stmt_utilizador->bind_param("i", $suporte_id);
            $stmt_utilizador->execute();
            $result_utilizador = $stmt_utilizador->get_result();
            $utilizador_id_cliente = $result_utilizador->fetch_assoc()['utilizador_id'];
            $stmt_utilizador->close();

            if ($utilizador_id_cliente) {
                // Criar notificação para o cliente sem o ID
                $mensagem_notif = "Sua mensagem de suporte foi respondida em " . date('d/m/Y H:i');
                $stmt_notif = $conn->prepare("INSERT INTO notificacoes (utilizador_id, mensagem, data_criacao, lida) VALUES (?, ?, NOW(), 0)");
                $stmt_notif->bind_param("is", $utilizador_id_cliente, $mensagem_notif);
                $stmt_notif->execute();
                $stmt_notif->close();
            }

            echo "<script>alert('Resposta enviada com sucesso!'); window.location.href='admin_suporte.php';</script>";
        } else {
            echo "<script>alert('Erro ao enviar a resposta. Tente novamente.');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('A resposta não pode estar vazia!');</script>";
    }
}

// Processa a exclusão de uma mensagem de suporte
if (isset($_GET['delete_suporte'])) {
    $suporte_id = $_GET['delete_suporte'];
    $sql = "DELETE FROM suporte WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $suporte_id);
    if ($stmt->execute()) {
        echo "<script>alert('Mensagem de suporte excluída com sucesso!'); window.location.href='admin_suporte.php';</script>";
    } else {
        echo "<script>alert('Erro ao excluir a mensagem de suporte. Tente novamente.');</script>";
    }
    $stmt->close();
}

// Busca todas as mensagens de suporte
$sql = "SELECT s.id, s.utilizador_id, s.email, s.mensagem, s.data_envio, s.status, s.resposta, s.data_resposta, u.nome 
        FROM suporte s 
        LEFT JOIN utilizadores u ON s.utilizador_id = u.id 
        ORDER BY s.data_envio DESC";
$result = $conn->query($sql);

// Verifica se a consulta foi executada com sucesso
if ($result === false) {
    echo "<p>Erro na consulta SQL: " . htmlspecialchars($conn->error) . "</p>";
    $result = null;
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão do Suporte - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/admin_suporte.css">
</head>

<body class="support-body">
    <div class="suporte-container">
        <h2>Gestão de Suporte</h2>
        <div class="suporte-items">
            <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="suporte-item">
                <p><a
                        href="verPerfil.php?id=<?php echo htmlspecialchars($row['utilizador_id']); ?>"><?php echo htmlspecialchars($row['nome'] ?? 'Anônimo'); ?></a>
                </p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                <p><strong>Mensagem:</strong> <?php echo htmlspecialchars($row['mensagem']); ?></p>
                <p><strong>Data de Envio:</strong> <?php echo htmlspecialchars($row['data_envio']); ?></p>
                <p><strong>Status:</strong> <span
                        class="suporte-status suporte-status-<?php echo strtolower($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                </p>
                <?php if ($row['resposta']): ?>
                <p><strong>Resposta:</strong> <?php echo htmlspecialchars($row['resposta']); ?></p>
                <p><strong>Data da Resposta:</strong> <?php echo htmlspecialchars($row['data_resposta']); ?></p>
                <?php else: ?>
                <form class="suporte-resposta-form" method="POST" action="admin_suporte.php">
                    <input type="hidden" name="suporte_id" value="<?php echo $row['id']; ?>">
                    <textarea name="resposta" placeholder="Digite a sua resposta aqui" required></textarea>
                    <button type="submit" name="responder">Enviar Resposta</button>
                </form>
                <?php endif; ?>
                <div class="suporte-actions">
                    <a href="admin_suporte.php?delete_suporte=<?php echo $row['id']; ?>"
                        onclick="return confirm('Tem certeza que deseja excluir esta mensagem?');">Excluir</a>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <p class="suporte-empty">Nenhuma mensagem de suporte encontrada.</p>
            <?php endif; ?>
        </div>
        <a href="admin_panel.php" class="suporte-back-link">Voltar para o Painel Administrativo</a>
    </div>
</body>

</html>

<?php
$conn->close();
?>