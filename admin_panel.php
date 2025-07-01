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
$sql_utilizador = "SELECT nome, apelido, tipo, foto_perfil FROM utilizadores WHERE id = ?";
$stmt_utilizador = $conn->prepare($sql_utilizador);
$stmt_utilizador->bind_param("i", $utilizador_id);
$stmt_utilizador->execute();
$result_utilizador = $stmt_utilizador->get_result();
$admin = $result_utilizador->fetch_assoc();
$stmt_utilizador->close();

$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Administração - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav-adminPanel">
                <a href="index.php" class="nav-item"><span class="icon">⬅️</span> Voltar</a>
            </nav>
        </div>

        <div class="main-content">
            <header class="admin-header">
                <h1>Bem-vindo, <?php echo htmlspecialchars($admin['nome']); ?>!</h1>
                <div class="admin-profile">
                    <div class="profile-pic">
                        <img src="<?php echo htmlspecialchars($admin['foto_perfil']); ?>" alt="Foto de perfil">
                        <div class="profile-dropdown">
                            <a href="admin_perfil.php">Configurações de Perfil</a>
                            <a href="admin_verPerfil.php?id=<?php echo $utilizador_id; ?>">Ver Perfil</a>
                            <a href="admin_lista.php">Lista de Administradores</a>
                            <a href="logout.php">Sair</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-cards">
                <a href="add_admin.php" class="card">
                    <h3>Gestão de Administradores</h3>
                    <p>Adicione ou remova administradores do sistema.</p>
                </a>

                <a href="admin_produtos.php" class="card">
                    <h3>Gestão de Produtos</h3>
                    <p>Adicione, edite ou remova produtos da loja.</p>
                </a>

                <a href="admin_suporte.php" class="card">
                    <h3>Gestão de Suporte</h3>
                    <p>Responda às mensagens do suporte dos clientes.</p>
                </a>

                <a href="admin_dashboard.php" class="card">
                    <h3>Dashboard</h3>
                    <p>Veja as estatísticas do sistema.</p>
                </a>

                <a href="admin_utilizadores.php" class="card">
                    <h3>Gestão de Utilizadores</h3>
                    <p>Edite e gerencie a lista de clientes.</p>
                </a>

                <a href="admin_notificacoes.php" class="card">
                    <h3>Notificações</h3>
                    <p>Veja alertas de atividades.</p>
                </a>

                <a href="admin_cupao.php" class="card">
                    <h3>Gestão de Cupões</h3>
                    <p>Crie e gerencie cupões promocionais.</p>
                </a>

                <a href="admin_vendas.php" class="card">
                    <h3>Relatórios de Vendas</h3>
                    <p>Veja os relatórios detalhados de vendas.</p>
                </a>
                <a href="admin_feedback.php" class="card">
                    <h3>Feedback dos Clientes</h3>
                    <p>Veja e gerencie o feedback dos clientes.</p>
                </a>
                <a href="admin_entregas.php" class="card">
                    <h3>Gestão de Entregas</h3>
                    <p>Veja e gerencie as entregas dos pedidos.</p>
                </a>
            </div>
        </div>
    </div>

</body>

</html>

<?php
$conn->close();
?>