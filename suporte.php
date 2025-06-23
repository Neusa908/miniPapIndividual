<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['mensagem'] = "É necessário estar registado para enviar um suporte.";
    header("Location: login.php");
    exit();
}

$email_utilizador = '';
$nome_utilizador = '';
if (isset($_SESSION['utilizador_id'])) {
    $utilizador_id = $_SESSION['utilizador_id'];
    $sql = "SELECT email, nome FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $utilizador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $email_utilizador = $user_data['email'];
        $nome_utilizador = $user_data['nome'];
    }
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $mensagem = trim($_POST['mensagem']);
    $utilizador_id = $_SESSION['utilizador_id'];

    if (empty($nome) || empty($email) || empty($mensagem)) {
        echo "<script>alert('Por favor, preencha todos os campos!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Por favor, insira um email válido!');</script>";
    } elseif ($email !== $email_utilizador || $nome !== $nome_utilizador) {
        echo "<script>alert('O nome ou email não correspondem à sua conta. Use os dados associados à sua conta.');</script>";
    } else {
        $sql = "INSERT INTO suporte (utilizador_id, email, mensagem, data_envio, status) VALUES (?, ?, ?, NOW(), 'pendente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $utilizador_id, $email, $mensagem);

        if ($stmt->execute()) {
            // Inserir notificação para o cliente sem ID
            $mensagem_notif_cliente = "Sua mensagem de suporte foi enviada em " . date('d/m/Y H:i');
            $stmt_notif_cliente = $conn->prepare("INSERT INTO notificacoes (mensagem, usuario_id) VALUES (?, ?)");
            $stmt_notif_cliente->bind_param("si", $mensagem_notif_cliente, $utilizador_id);
            $stmt_notif_cliente->execute();
            $stmt_notif_cliente->close();

            // Inserir notificação para todos os administradores
            $mensagem_notif_admin = "Nova mensagem de suporte de $nome (ID $utilizador_id) recebida em " . date('d/m/Y H:i');
            $sql_admins = "SELECT id FROM usuarios WHERE tipo = 'admin'";
            $result_admins = $conn->query($sql_admins);
            
            if ($result_admins->num_rows > 0) {
                $stmt_notif_admin = $conn->prepare("INSERT INTO notificacoes (mensagem, admin_id) VALUES (?, ?)");
                while ($admin = $result_admins->fetch_assoc()) {
                    $admin_id = $admin['id'];
                    $stmt_notif_admin->bind_param("si", $mensagem_notif_admin, $admin_id);
                    if (!$stmt_notif_admin->execute()) {
                        error_log("Erro ao criar notificação para admin_id $admin_id: " . $conn->error);
                    }
                }
                $stmt_notif_admin->close();
            } else {
                error_log("Nenhum administrador encontrado para notificar.");
            }

            echo "<script>alert('Mensagem enviada com sucesso! Entraremos em contato em breve.'); window.location.href='index.php';</script>";
        } else {
            echo "<script>alert('Erro ao enviar a mensagem. Tente novamente.');</script>";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Suporte</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="support-body" style="background: url('img/frutas.jpg') no-repeat center center; background-size: cover;">
    <div class="support-form-container">
        <h2>Suporte</h2>
        <form class="support-form" method="POST" action="suporte.php">
            <label for="nome">NOME</label>
            <input type="text" id="nome" name="nome" placeholder="Digite o seu nome de utilizador"
                value="<?php echo isset($_SESSION['usuario_nome']) ? htmlspecialchars($_SESSION['usuario_nome']) : htmlspecialchars($nome_usuario); ?>"
                required>

            <label for="email">EMAIL</label>
            <input type="email" id="email" name="email" placeholder="Selecione um email válido"
                value="<?php echo htmlspecialchars($email_usuario); ?>" required>

            <label for="mensagem">MENSAGEM</label>
            <textarea id="mensagem" name="mensagem" placeholder="Digite a sua mensagem" required></textarea>

            <button type="submit" class="support-button">Enviar</button>
        </form>
        <div>
            <br>Voltar para a página <a href="index.php"> principal!</a>
        </div>
    </div>
</body>

</html>