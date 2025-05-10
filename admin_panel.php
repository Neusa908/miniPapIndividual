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

// Busca os dados do admin logado
$usuario_id = $_SESSION['usuario_id'];
$sql_usuario = "SELECT nome, tipo, foto_perfil FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$admin = $result_usuario->fetch_assoc();
$stmt_usuario->close();

// Se foto_perfil for nulo, usa uma imagem padrão
$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <!-- Barra Lateral -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item"><span class="icon">⬅️</span> Voltar ao Site</a>
            </nav>
        </div>

        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Header -->
            <header class="admin-header">
                <h1>Bem-vindo, <?php echo htmlspecialchars($admin['nome']); ?>!</h1>
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

            <!-- Área Principal com Cards -->
            <div class="dashboard-cards">
                <a href="add_admin.php" class="card">
                    <span class="card-icon">👥</span>
                    <h3>Gestão de Administradores</h3>
                    <p>Adicione ou remova administradores do sistema.</p>
                </a>
                <a href="admin_produtos.php" class="card">
                    <span class="card-icon">🛒</span>
                    <h3>Gestão de Produtos</h3>
                    <p>Adicione, edite ou remova produtos da loja.</p>
                </a>
                <a href="admin_suporte.php" class="card">
                    <span class="card-icon">📞</span>
                    <h3>Gestão de Suporte</h3>
                    <p>Responda às mensagens do suporte dos clientes.</p>
                </a>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>