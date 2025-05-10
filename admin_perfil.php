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
$sql_usuario = "SELECT nome, email, telefone, foto_perfil FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$admin = $result_usuario->fetch_assoc();
$stmt_usuario->close();

// Extrai a parte editável do email (antes de admin@mercadobompreco.com)
$email_parts = explode('@mercadobompreco.com', $admin['email']);
$email_prefix = $email_parts[0] ?? '';

// Se foto_perfil for nulo, usa uma imagem padrão
$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';

// Processa a edição do perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_perfil'])) {
    $novo_nome = trim($_POST['nome']);
    $email_prefix = trim($_POST['email_prefix']);
    $novo_telefone = trim($_POST['telefone']);
    $novo_email = $email_prefix . '@mercadobompreco.com';

    // Validações
    if (empty($novo_nome)) {
        echo "<script>alert('O nome não pode estar vazio!');</script>";
    } elseif (empty($email_prefix)) {
        echo "<script>alert('A parte inicial do email não pode estar vazia!');</script>";
    } elseif (!preg_match('/^[0-9\s+]+$/', $novo_telefone) && !empty($novo_telefone)) {
        echo "<script>alert('O telefone deve conter apenas números, espaços ou o símbolo +!');</script>";
    } else {
        // Verifica se o email já existe (exceto para o próprio usuário)
        $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $novo_email, $usuario_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Este email já está registrado por outro usuário!');</script>";
        } else {
            // Processa o upload da foto de perfil, se fornecida
            $foto_perfil = $admin['foto_perfil'];
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $file_type = $_FILES['foto_perfil']['type'];
                $file_size = $_FILES['foto_perfil']['size'];
                $file_tmp = $_FILES['foto_perfil']['tmp_name'];

                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    $file_name = 'perfil_' . $usuario_id . '_' . time() . '.' . pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                    $upload_dir = 'img/perfil/';
                    $upload_path = $upload_dir . $file_name;

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $foto_perfil = $upload_path;
                        // Remove a foto antiga, se não for a padrão
                        if ($admin['foto_perfil'] != 'img/default-profile.jpg' && file_exists($admin['foto_perfil'])) {
                            unlink($admin['foto_perfil']);
                        }
                    } else {
                        echo "<script>alert('Erro ao fazer upload da foto. Tente novamente.');</script>";
                    }
                } else {
                    echo "<script>alert('A foto deve ser um arquivo JPEG, PNG ou GIF e ter no máximo 2MB!');</script>";
                }
            }

            // Atualiza os dados no banco
            $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, foto_perfil = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $novo_nome, $novo_email, $novo_telefone, $foto_perfil, $usuario_id);
            if ($stmt->execute()) {
                $_SESSION['usuario_nome'] = $novo_nome; // Atualiza o nome na sessão
                echo "<script>alert('Perfil atualizado com sucesso!'); window.location.href='admin_perfil.php';</script>";
            } else {
                echo "<script>alert('Erro ao atualizar o perfil. Tente novamente.');</script>";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-perfil-body">
    <div class="admin-perfil-container">
        <h1>Editar Perfil</h1>
        <form method="POST" action="admin_perfil.php" enctype="multipart/form-data">
            <!-- Foto de Perfil -->
            <div class="foto-perfil">
                <img src="<?php echo htmlspecialchars($admin['foto_perfil']); ?>" alt="Foto do Admin">
                <label for="foto_perfil">Alterar Foto:</label>
                <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*">
            </div>

            <!-- Nome -->
            <label for="nome">Nome:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($admin['nome']); ?>" required>

            <!-- Email -->
            <label for="email_prefix">Email:</label>
            <div class="email-field">
                <input type="text" name="email_prefix" value="<?php echo htmlspecialchars($email_prefix); ?>" required>
                <span class="email-suffix">@mercadobompreco.com</span>
            </div>

            <!-- Telefone -->
            <label for="telefone">Número de Contato:</label>
            <input type="text" name="telefone" value="<?php echo htmlspecialchars($admin['telefone'] ?? ''); ?>"
                placeholder="Ex: +351 912 345 678">

            <!-- Botões -->
            <button type="submit" name="editar_perfil">Salvar Alterações</button>
            <a href="admin_panel.php" class="cancel-button">Voltar</a>
        </form>
    </div>
</body>

</html>

<?php
$conn->close();
?>