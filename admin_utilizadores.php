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

// Buscar foto de perfil do administrador
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $_SESSION['utilizador_id']);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg'; // Fallback
$stmt_foto->close();

// Busca clientes
$sql_utilizadores = "SELECT nome, email FROM utilizadores WHERE tipo = 'cliente'";
$result_utilizadores = $conn->query($sql_utilizadores);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores - Mercado Bom Preço</title>
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
            </nav>
        </div>

        <div class="main-content">
            <header class="admin-header">
                <h1>Gestão de Utilizadores</h1>
                <div class="usuario-foto-container">
                    <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
                </div>
            </header>
            <div class="users-container">
                <?php if ($result_utilizadores->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($utilizador = $result_utilizadores->fetch_assoc()): ?>
                        <tr class="user-row">
                            <td><?php echo htmlspecialchars($utilizador['nome']); ?></td>
                            <td><?php echo htmlspecialchars($utilizador['email']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-users">Nenhum utilizador encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>