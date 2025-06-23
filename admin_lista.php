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

// Busca os dados do admin logado
$utilizador_id = $_SESSION['utilizador_id'];
$sql_usuario = "SELECT nome, tipo, foto_perfil FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $utilizador_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$admin = $result_usuario->fetch_assoc();
$stmt_usuario->close();

// Se foto_perfil for nulo, usa uma imagem padrão
$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';

// Busca todos os administradores
$sql_admins = "SELECT id, nome, foto_perfil FROM utilizadores WHERE tipo = 'admin'";
$result_admins = $conn->query($sql_admins);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Administradores - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/admin_lista.css">
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
                <h1>Lista de Administradores</h1>
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

            <div class="dashboard-cards">
                <?php while ($row = $result_admins->fetch_assoc()): ?>
                <a href="admin_verPerfil.php?id=<?php echo $row['id']; ?>" class="card">
                    <div class="lista-card-content">
                        <div class="lista-foto">
                            <img src="<?php echo htmlspecialchars($row['foto_perfil'] ?? 'img/default-profile.jpg'); ?>"
                                alt="Foto de perfil">
                        </div>
                        <h3><?php echo htmlspecialchars($row['nome']); ?></h3>
                        <p>Ver perfil</p>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>

</html>