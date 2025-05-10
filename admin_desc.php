<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o usuário é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Obtém o ID do admin logado
$usuario_id = $_SESSION['usuario_id'];

// Busca os dados do admin logado
$sql_usuario = "SELECT nome, foto_perfil, descricao FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$admin = $result_usuario->fetch_assoc();
$stmt_usuario->close();

if (!$admin) {
    echo "<script>alert('Administrador não encontrado.'); window.location.href='admin_panel.php';</script>";
    exit();
}

// Se foto_perfil for nulo, usa uma imagem padrão
$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';
$admin['descricao'] = $admin['descricao'] ?? 'Nenhuma descrição fornecida.';

// Processa a atualização da descrição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_descricao = trim($_POST['descricao']);
    $sql_update = "UPDATE usuarios SET descricao = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $nova_descricao, $usuario_id);
    if ($stmt_update->execute()) {
        echo "<script>alert('Descrição atualizada com sucesso!'); window.location.href='admin_verPerfil.php?id=$usuario_id';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar descrição.'); window.location.href='admin_desc.php';</script>";
    }
    $stmt_update->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Descrição - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/admin_desc.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <!-- Barra Lateral -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span> Voltar ao Painel</a>
            </nav>
        </div>

        <!-- Conteúdo Principal -->
        <div class="main-content">
            <header class="admin-header">
                <h1>Editar Descrição</h1>
                <div class="admin-profile">
                    <div class="profile-pic">
                        <img src="<?php echo htmlspecialchars($admin['foto_perfil']); ?>" alt="Foto de perfil">
                        <div class="profile-dropdown">
                            <a href="admin_perfil.php">Configurações</a>
                            <a href="admin_verPerfil.php?id=<?php echo $usuario_id; ?>">Ver Perfil</a>
                            <a href="admin_lista.php">Lista de Administradores</a>
                            <a href="logout.php">Sair</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="desc-container">
                <form method="POST" class="desc-form">
                    <h2>Editar Descrição do Perfil</h2>
                    <label for="descricao">Descrição:</label>
                    <textarea name="descricao" id="descricao"
                        rows="5"><?php echo htmlspecialchars($admin['descricao']); ?></textarea>
                    <button type="submit">Salvar Alterações</button>
                    <a href="admin_verPerfil.php?id=<?php echo $usuario_id; ?>" class="desc-cancel-button">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>