<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID de usuário inválido.'); window.location.href='admin_usuarios.php';</script>";
    exit();
}

$id = $_GET['id'];

$sql = "SELECT nome, email, telefone, morada FROM usuarios WHERE id = ? AND tipo = 'cliente'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Usuário não encontrado ou não é cliente.'); window.location.href='admin_usuarios.php';</script>";
    exit();
}

$usuario = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'] ?? $usuario['nome'];
    $email = $_POST['email'] ?? $usuario['email'];
    $telefone = $_POST['telefone'] ?? $usuario['telefone'];
    $morada = $_POST['morada'] ?? $usuario['morada'];

    $sql_update = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, morada = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssi", $nome, $email, $telefone, $morada, $id);

    if ($stmt_update->execute()) {
        echo "<script>alert('Usuário atualizado com sucesso!'); window.location.href='admin_usuarios.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar usuário.'); window.location.href='admin_usuarios.php';</script>";
    }
    $stmt_update->close();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span> Voltar</a>
                <a href="admin_usuarios.php" class="nav-item">⬅️ Usuários</a>
            </nav>
        </div>
        <div class="main-content">
            <header class="admin-header">
                <h1>Editar Usuário</h1>
            </header>
            <div class="edit-user-wrapper">
                <div class="edit-user-box">
                    <h2 class="edit-user-title">Editar Dados do Usuário</h2>
                    <form method="POST" action="" class="edit-user-form">
                        <div class="form-field">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" id="nome" name="nome" class="form-input"
                                value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input"
                                value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" id="telefone" name="telefone" class="form-input"
                                value="<?php echo htmlspecialchars($usuario['telefone']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="morada" class="form-label">Morada</label>
                            <input type="text" id="morada" name="morada" class="form-input"
                                value="<?php echo htmlspecialchars($usuario['morada']); ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-save">Salvar Alterações</button>
                            <a href="admin_usuarios.php" class="btn btn-cancel">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>