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

$utilizador_id = $_SESSION['utilizador_id'];
$sql_utilizador = "SELECT nome, apelido, email, telefone, foto_perfil FROM usuarios WHERE id = ?";
$stmt_utilizador = $conn->prepare($sql_utilizador);
$stmt_utilizador->bind_param("i", $utilizador_id);
$stmt_utilizador->execute();
$result_utilizador = $stmt_utilizador->get_result();
$admin = $result_utilizador->fetch_assoc();
$stmt_utilizador->close();

if (!$admin) {
    echo "<script>alert('Utilizador não encontrado!'); window.location.href='index.php';</script>";
    exit();
}

$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';

function sanitizeEmailPrefix($text) {
    $text = strtolower(trim($text));
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('/[^a-z0-9]/', '', $text);
    return $text;
}

function getFullName($nome, $apelido) {
    $full_name = trim($nome);
    if (!empty($apelido)) {
        $full_name .= ' ' . trim($apelido);
    }
    return $full_name;
}

// Processa a edição do perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_perfil'])) {
    $novo_nome = trim($_POST['nome']);
    $novo_apelido = trim($_POST['apelido']);
    $novo_telefone = trim($_POST['telefone']);

    // Gera o novo prefixo de email usando apenas o nome
    $email_prefix = sanitizeEmailPrefix($novo_nome);
    $novo_email = $email_prefix . '@mercadobompreco.com';

    // Validações
    if (empty($novo_nome)) {
        echo "<script>alert('O nome não pode estar vazio!');</script>";
    } elseif (empty($email_prefix)) {
        echo "<script>alert('O nome fornecido não pode gerar um email válido! Use letras e números.'); window.location.href='admin_perfil.php';</script>";
    } elseif (!empty($novo_telefone) && !preg_match('/^[0-9\s+]+$/', $novo_telefone)) {
        echo "<script>alert('O telefone deve conter apenas números, espaços ou o símbolo +!');</script>";
    } else {
        // Verifica se o email já existe (exceto para o próprio utilizador)
        $sql = "SELECT id FROM utilizadores WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $novo_email, $utilizador_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Este email já está registrado por outro utilizador! Tente um nome diferente.'); window.location.href='admin_perfil.php';</script>";
        } else {

            // Upload da foto de perfil, se fornecida
            $foto_perfil = $admin['foto_perfil'];
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $file_type = $_FILES['foto_perfil']['type'];
                $file_size = $_FILES['foto_perfil']['size'];
                $file_tmp = $_FILES['foto_perfil']['tmp_name'];

                if (in_array($file_type, $allowed_types) && $file_size <= $max_size) {
                    $file_name = 'perfil_' . $utilizador_id . '_' . substr(md5(time()), 0, 8) . '.' . pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
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
                        echo "<script>alert('Erro ao fazer upload da foto. Tente novamente.'); window.location.href='admin_perfil.php';</script>";
                    }
                } else {
                    echo "<script>alert('A foto deve ser um arquivo JPEG, PNG ou GIF e ter no máximo 2MB!'); window.location.href='admin_perfil.php';</script>";
                }
            }

            // Atualiza os dados no banco
            $sql = "UPDATE utilizadores SET nome = ?, apelido = ?, email = ?, telefone = ?, foto_perfil = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $telefone_final = $novo_telefone ?: $admin['telefone'];
            $stmt->bind_param("sssssi", $novo_nome, $novo_apelido, $novo_email, $telefone_final, $foto_perfil, $utilizador_id);

            // Debug: Log variables to check their values
            error_log("Debug: nome=$novo_nome, apelido=$novo_apelido, email=$novo_email, telefone=$telefone_final, foto_perfil=$foto_perfil, utilizador_id=$utilizador_id");

            if ($stmt->execute()) {
                // Atualiza a sessão com o nome completo
                $_SESSION['utilizador_nome'] = getFullName($novo_nome, $novo_apelido);
                echo "<script>alert('Perfil atualizado com sucesso!'); window.location.href='admin_perfil.php';</script>";
            } else {
                echo "<script>alert('Erro ao atualizar o perfil: " . addslashes($stmt->error) . "'); window.location.href='admin_perfil.php';</script>";
            }
            $stmt->close();
        }
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

            <div class="foto-perfil">
                <img src="<?php echo htmlspecialchars($admin['foto_perfil']); ?>" alt="Foto do Admin">
                <label for="foto_perfil">Alterar Foto:</label>
                <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*">
            </div>

            <label for="nome">Nome:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($admin['nome']); ?>" required>

            <label for="apelido">Apelido:</label>
            <input type="text" name="apelido" value="<?php echo htmlspecialchars($admin['apelido'] ?? ''); ?>">

            <label for="email">Email:</label>
            <input type="text" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>

            <label for="telefone">Número de Contato:</label>
            <input type="text" name="telefone" value="<?php echo htmlspecialchars($admin['telefone'] ?? ''); ?>"
                placeholder="Ex: +351 912 345 678">

            <button type="submit" name="editar_perfil">Salvar Alterações</button>
            <a href="admin_panel.php" class="cancel-button-admin-perfil">Voltar</a>
        </form>
    </div>
</body>

</html>

<?php
$conn->close();
?>