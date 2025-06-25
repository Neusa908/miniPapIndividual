<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o usuário é administrador
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Obtém o ID do admin a ser visualizado (passado via GET)
$perfil_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['utilizador_id'];
$utilizador_id = $_SESSION['utilizador_id'];

// Busca os dados do admin a ser visualizado
$sql_utilizador = "SELECT nome, tipo, foto_perfil, descricao FROM utilizadores WHERE id = ?";
$stmt_utilizador = $conn->prepare($sql_utilizador);
$stmt_utilizador->bind_param("i", $perfil_id);
$stmt_utilizador->execute();
$result_utilizador = $stmt_utilizador->get_result();
$admin = $result_utilizador->fetch_assoc();
$stmt_utilizador->close();

if (!$admin) {
    echo "<script>alert('Administrador não encontrado.'); window.location.href='admin_panel.php';</script>";
    exit();
}

// Se foto_perfil for nulo, usa uma imagem padrão
$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';
$admin['descricao'] = $admin['descricao'] ?? 'Nenhuma descrição fornecida.';

// Verifica se o usuário logado é o dono do perfil
$is_own_profile = ($perfil_id === $utilizador_id);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Administrador - Mercado Bom Preço</title>
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
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span>Painel</a>
                <a href="admin_lista.php" class="nav-item"><span class="icon">⬅️</span>Lista de
                    Admins</a>
            </nav>
        </div>

        <!-- Conteúdo Principal -->
        <div class="main-content">
            <header class="admin-header">
                <h1>Perfil de <?php echo htmlspecialchars($admin['nome']); ?></h1>
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

            <div class="ver-perfil-container">
                <div class="ver-perfil-foto">
                    <img src="<?php echo htmlspecialchars($admin['foto_perfil']); ?>" alt="Foto de perfil">
                </div>
                <h2><?php echo htmlspecialchars($admin['nome']); ?></h2>
                <div class="ver-perfil-descricao">
                    <label>Descrição:</label>
                    <p><?php echo htmlspecialchars($admin['descricao']); ?></p>
                </div>

                <?php if ($is_own_profile): ?>
                <a href="admin_desc.php" class="ver-perfil-edit-button">Editar Descrição</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>