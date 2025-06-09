<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('Faça login para acessar esta página.'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Busca os dados do usuário logado
$sql_usuario = "SELECT nome, foto_perfil, descricao FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
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

// Processa a atualização da descrição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_descricao = trim($_POST['descricao']);
    $sql_update = "UPDATE usuarios SET descricao = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $nova_descricao, $usuario_id);
    if ($stmt_update->execute()) {
        echo "<script>alert('Descrição atualizada com sucesso!'); window.location.href='verPerfil.php?id=$usuario_id';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar descrição.'); window.location.href='desc.php';</script>";
    }
    $stmt_update->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Descrição - Mercado Bom Preço</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="profile-body">
    <div class="container">
        <header class="profile-header">
            <h1>Editar Descrição</h1>
            <a href="verPerfil.php?id=<?php echo $usuario_id; ?>" class="back-link">Voltar</a>
        </header>

        <div class="desc-container">
            <form method="POST" class="desc-form">
                <h2>Editar Descrição do Perfil</h2>
                <label for="descricao" class="form-label">Descrição:</label>
                <textarea name="descricao" id="descricao" class="form-textarea"
                    rows="5"><?php echo htmlspecialchars($usuario['descricao']); ?></textarea>
                <button type="submit" class="submit-button">Salvar Alterações</button>
                <a href="verPerfil.php?id=<?php echo $usuario_id; ?>" class="cancel-button">Cancelar</a>
            </form>
        </div>
    </div>
</body>

</html>