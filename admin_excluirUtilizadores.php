<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

// Verifica se o utilizador é administrador
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Verifica se o ID foi passado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID de utilizador inválido.'); window.location.href='admin_usuarios.php';</script>";
    exit();
}

$id = $_GET['id'];

// Exclui o utilizador
if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $sql = "DELETE FROM utilizadores WHERE id = ? AND tipo = 'cliente'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Utilizador excluído com sucesso!'); window.location.href='admin_utilizadores.php';</script>";
    } else {
        echo "<script>alert('Erro ao excluir utilizador.'); window.location.href='admin_utilizadores.php';</script>";
    }
    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deletar Usuário - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span> Voltar ao Painel</a>
                <a href="admin_usuarios.php" class="nav-item"><span class="icon">⬅️</span>Utilizadores</a>
            </nav>

        </div>
        <div class="main-content">
            <header class="admin-header">
                <h1>Eliminar Utilizador</h1>
            </header>
            <div class="delete-user-container">
                <p>Tem certeza que deseja eliminar este utilizador? Esta ação não pode ser desfeita.</p>
                <a href="admin_excluirUtilizadores.php?id=<?php echo $id; ?>&confirm=yes" class="delete-btn-excluir">
                    Eliminar</a>
                <p></p> <a href="admin_utilizadores.php" class="cancel-btn-excluir">Cancelar</a>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>