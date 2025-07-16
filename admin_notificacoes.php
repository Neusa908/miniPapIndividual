<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Buscar foto de perfil do administrador
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $utilizador_id);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg';
$stmt_foto->close();

// Deletar notificação
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $sql = "DELETE FROM notificacoes WHERE id = ? AND admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_id, $utilizador_id);
    if ($stmt->execute()) {
        header("Location: admin_notificacoes.php");
        exit;
    }
    $stmt->close();
}

// Responder suporte
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['suporte_id']) && isset($_POST['resposta'])) {
    $suporte_id = (int)$_POST['suporte_id'];
    $resposta = trim($_POST['resposta']);

    $sql_utilizador = "SELECT utilizador_id FROM suporte WHERE id = ?";
    $stmt_utilizador = $conn->prepare($sql_utilizador);
    $stmt_utilizador->bind_param("i", $suporte_id);
    $stmt_utilizador->execute();
    $result_utilizador = $stmt_utilizador->get_result();
    $utilizador_id_cliente = $result_utilizador->fetch_assoc()['utilizador_id'];
    $stmt_utilizador->close();

    if ($utilizador_id_cliente) {
        $sql_update = "UPDATE suporte SET resposta = ?, status = 'resolvido', data_resposta = NOW() WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $resposta, $suporte_id);

        if ($stmt_update->execute()) {
            $mensagem_notif = "Sua mensagem de suporte foi respondida em " . date('d/m/Y H:i');
            $stmt_notif = $conn->prepare("INSERT INTO notificacoes (utilizador_id, mensagem, data_criacao, lida, suporte_id) VALUES (?, ?, NOW(), 0, ?)");
            $stmt_notif->bind_param("isi", $utilizador_id_cliente, $mensagem_notif, $suporte_id);
            $stmt_notif->execute();
            $stmt_notif->close();

            echo "<script>alert('Resposta enviada com sucesso!'); window.location.href='admin_notificacoes.php';</script>";
        } else {
            echo "<script>alert('Erro ao enviar a resposta.');</script>";
        }
        $stmt_update->close();
    } else {
        echo "<script>alert('Utilizador não encontrado.');</script>";
    }
}

// Buscar notificações
$sql_notificacoes = "SELECT id, mensagem, data_criacao, lida, suporte_id FROM notificacoes WHERE admin_id = ? ORDER BY data_criacao DESC";
$stmt_notificacoes = $conn->prepare($sql_notificacoes);
$stmt_notificacoes->bind_param("i", $utilizador_id);
$stmt_notificacoes->execute();
$result_notificacoes = $stmt_notificacoes->get_result();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <script>
    function verificarNotificacoes() {
        fetch('verificar_notificacoes.php?admin_id=<?php echo $utilizador_id; ?>')
            .then(response => response.json())
            .then(data => {
                if (data.novas) {
                    location.reload();
                }
            })
            .catch(error => console.error('Erro ao verificar notificações:', error));
    }
    setInterval(verificarNotificacoes, 30000);
    </script>
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item">Voltar</a>
            </nav>
        </div>
        <div class="main-content">
            <header class="admin-header">
                <h1>Notificações</h1>
                <div class="usuario-foto-container">
                    <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
                </div>
            </header>
            <div class="notifications-container">
                <?php if ($result_notificacoes->num_rows > 0): ?>
                <ul class="notifications-list">
                    <?php while ($notificacao = $result_notificacoes->fetch_assoc()): ?>
                    <li
                        class="notification-item <?= $notificacao['lida'] ? 'notification-read' : 'notification-unread'; ?>">
                        <p class="notification-message"><?= htmlspecialchars($notificacao['mensagem']); ?></p>
                        <span
                            class="notification-date"><?= date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></span>
                        <?php if (!$notificacao['lida']): ?>
                        <?php if (!empty($notificacao['suporte_id'])): ?>
                        <a href="admin_suporte.php?suporte_id=<?= $notificacao['suporte_id']; ?>"
                            class="notificacao-ver">Ver</a>
                        <?php endif; ?>
                        <a href="admin_marcarLida.php?id=<?= $notificacao['id']; ?>" class="notification-action">Marcar
                            como lida</a>
                        <?php endif; ?>
                        <a href="?delete_id=<?= $notificacao['id']; ?>" class="notification-action delete"
                            onclick="return confirm('Tem certeza que deseja apagar esta notificação?');">Apagar</a>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="no-notifications">Nenhuma notificação encontrada.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$stmt_notificacoes->close();
$conn->close();
?>