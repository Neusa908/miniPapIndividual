<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo "<script>alert('Faça login para acessar esta página.'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// Busca os dados do usuário logado
$sql_utilizador = "SELECT nome, foto_perfil, descricao FROM utilizadores WHERE id = ?";
$stmt_utilizador = $conn->prepare($sql_utilizador);
$stmt_utilizador->bind_param("i", $utilizador_id);
$stmt_utilizador->execute();
$result_utilizador = $stmt_utilizador->get_result();
$utilizador = $result_utilizador->fetch_assoc();
$stmt_utilizador->close();

if (!$utilizador) {
    echo "<script>alert('Utilizador não encontrado.'); window.location.href='index.php';</script>";
    exit();
}

// Se foto_perfil for nulo, usa uma imagem padrão
$utilizador['foto_perfil'] = $utilizador['foto_perfil'] ?? 'img/default-profile.jpg';
$utilizador['descricao'] = $utilizador['descricao'] ?? 'Nenhuma descrição fornecida.';

// Processa a atualização da descrição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_descricao = trim($_POST['descricao']);
    $sql_update = "UPDATE utilizadores SET descricao = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $nova_descricao, $utilizador_id);
    if ($stmt_update->execute()) {
        echo "<script>alert('Descrição atualizada com sucesso!'); window.location.href='verPerfil.php?id=$utilizador_id';</script>";
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
            <a href="verPerfil.php?id=<?php echo $utilizador_id; ?>" class="back-link">Voltar</a>
        </header>

        <div class="desc-container">
            <form method="POST" class="desc-form">
                <h2>Editar Descrição do Perfil</h2>
                <label for="descricao" class="form-label">Descrição:</label>
                <textarea name="descricao" id="descricao" class="form-textarea"
                    rows="5"><?php echo htmlspecialchars($utilizador['descricao']); ?></textarea>
                <button type="submit" class="submit-button">Salvar Alterações</button>
                <a href="verPerfil.php?id=<?php echo $utilizador_id; ?>" class="cancel-button">Cancelar</a>
            </form>
        </div>
    </div>
</body>

</html>