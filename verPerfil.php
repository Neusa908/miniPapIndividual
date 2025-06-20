<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('Faça login para acessar esta página.'); window.location.href='login.php';</script>";
    exit();
}


$perfil_id = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['usuario_id'];
$usuario_id = $_SESSION['usuario_id'];

// Busca os dados do usuário a ser visualizado
$sql_usuario = "SELECT nome, tipo, foto_perfil, descricao FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $perfil_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();
$stmt_usuario->close();

if (!$usuario) {
    echo "<script>alert('Usuário não encontrado.'); window.location.href='index.php';</script>";
    exit();
}

// Se foto_perfil for nulo, usa uma imagem padrão
$usuario['foto_perfil'] = $usuario['foto_perfil'] ?? 'img/default-profile.jpg';
$usuario['descricao'] = $usuario['descricao'] ?? 'Nenhuma descrição fornecida.';

// Verifica se o usuário logado é o dono do perfil
$is_own_profile = ($perfil_id === $usuario_id);

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($usuario['nome']); ?> - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/verPerfil.css">
</head>

<body class="profile-body">
    <div class="container">
        <header class="profile-header">
            <h1>Perfil de <?php echo htmlspecialchars($usuario['nome']); ?></h1>
            <a href="index.php" class="back-link">Voltar</a>
        </header>

        <div class="profile-container">
            <div class="profile-photo">
                <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de perfil">
            </div>
            <h2><?php echo htmlspecialchars($usuario['nome']); ?></h2>
            <p class="profile-type"><?php echo $usuario['tipo'] === 'admin' ? 'Administrador' : 'Cliente'; ?></p>
            <div class="profile-description">
                <label>Descrição:</label>
                <p><?php echo htmlspecialchars($usuario['descricao']); ?></p>
            </div>

            <?php if ($is_own_profile): ?>
            <a href="desc.php" class="profile-edit-button">Editar Descrição</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>