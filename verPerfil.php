<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo "<script>alert('Faça login para acessar esta página.'); window.location.href='login.php';</script>";
    exit();
}


$perfil_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['utilizador_id'];
$utilizador_id = $_SESSION['utilizador_id'];

// Busca os dados do usuário a ser visualizado
$sql_utilizador = "SELECT nome, tipo, foto_perfil, descricao FROM utilizadores WHERE id = ?";
$stmt_utilizador = $conn->prepare($sql_utilizador);
$stmt_utilizador->bind_param("i", $perfil_id);
$stmt_utilizador->execute();
$result_utilizador = $stmt_utilizador->get_result();
$utilizador = $result_utilizador->fetch_assoc();
$stmt_utilizador->close();

if (!$utilizador) {
    echo "<script>alert('Usuário não encontrado.'); window.location.href='index.php';</script>";
    exit();
}

// Se foto_perfil for nulo, usa uma imagem padrão
$utilizador['foto_perfil'] = $utilizador['foto_perfil'] ?? 'img/default-profile.jpg';
$utilizador['descricao'] = $utilizador['descricao'] ?? 'Nenhuma descrição fornecida.';

// Verifica se o usuário logado é o dono do perfil
$is_own_profile = ($perfil_id === $utilizador_id);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($utilizador['nome']); ?> - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="vp-body">
    <header class="vp-header">
        <img src="img/logo.png" alt="Logo do Mercado Bom Preço" class="vp-logo">
        <h1>Perfil de <?php echo htmlspecialchars($utilizador['nome']); ?></h1>

        <nav class="vp-nav">
            <a href="index.php" class="vp-link">Página Inicial</a>
            <a href="sobre.php" class="vp-link">Sobre</a>
            <a href="suporte.php" class="vp-link">Suporte</a>
        </nav>
    </header>

    <main class="vp-container">
        <section class="vp-card">
            <div class="vp-left">
                <img src="<?php echo htmlspecialchars($utilizador['foto_perfil']); ?>" alt="Foto de perfil"
                    class="vp-avatar">
                <h2 class="vp-name"><?php echo htmlspecialchars($utilizador['nome']); ?></h2>
                <p class="vp-role"><?php echo $utilizador['tipo'] === 'admin' ? 'Administrador' : 'Cliente'; ?></p>
            </div>
            <div class="vp-right">
                <h3 class="vp-desc-title">Descrição:</h3>
                <p class="vp-desc-text"><?php echo htmlspecialchars($utilizador['descricao']); ?></p>
                <?php if ($is_own_profile): ?>
                <a href="desc.php" class="vp-edit-btn">Editar Descrição</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="footer-index">
        <p>© 2024-2025 Mercado Bom Preço. Todos os direitos reservados.</p>
    </footer>
</body>