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

// Verifica se o ID do administrador a ser editado foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID de administrador inválido!'); window.location.href='add_admin.php';</script>";
    exit();
}

$admin_id = $_GET['id'];
$utilizador_id = $_SESSION['utilizador_id'];

// Busca os dados do administrador a ser editado
$sql = "SELECT nome, apelido, email, telefone FROM utilizadores WHERE id = ? AND tipo = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_edit = $result->fetch_assoc();
$stmt->close();

// Verifica se o administrador existe
if (!$admin_edit) {
    echo "<script>alert('Administrador não encontrado!'); window.location.href='add_admin.php';</script>";
    exit();
}

// Extrai a parte editável do email (antes de @mercadobompreco.com)
$email_parts = explode('@mercadobompreco.com', $admin_edit['email']);
$email_prefix = $email_parts[0] ?? '';

// Processa a edição do administrador
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_admin'])) {
    $novo_nome = trim($_POST['nome']);
    $novo_apelido = trim($_POST['apelido']);
    $email_prefix = trim($_POST['email_prefix']);
    $novo_telefone = trim($_POST['telefone']);
    $nova_senha = $_POST['senha'];
    $novo_email = $email_prefix . '@mercadobompreco.com';

    // Validações
    if (empty($novo_nome)) {
        echo "<script>alert('O nome não pode estar vazio!');</script>";
    } elseif (empty($email_prefix)) {
        echo "<script>alert('A parte inicial do email não pode estar vazia!');</script>";
    } elseif (!preg_match('/^[0-9\s+]+$/', $novo_telefone)) {
        echo "<script>alert('O telefone deve conter apenas números, espaços ou o símbolo +!');</script>";
    } elseif (!empty($nova_senha) && strlen($nova_senha) < 8) {
        echo "<script>alert('A nova senha deve ter pelo menos 8 caracteres!');</script>";
    } else {
        // Verifica se o email já existe (exceto para o próprio administrador)
        $sql = "SELECT id FROM utilizadores WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $novo_email, $admin_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Este email já está registrado por outro usuário!');</script>";
        } else {
            // Atualiza os dados no banco
            if (!empty($nova_senha)) {
                // Se uma nova senha foi fornecida, faz o hash
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $sql = "UPDATE utilizadores SET nome = ?, apelido = ?, email = ?, telefone = ?, senha = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $novo_nome, $novo_apelido, $novo_email, $novo_telefone, $senha_hash, $admin_id);
            } else {
                // Se não houver nova senha, atualiza apenas os outros campos
                $sql = "UPDATE utilizadores SET nome = ?, apelido = ?, email = ?, telefone = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $novo_nome, $novo_apelido, $novo_email, $novo_telefone, $admin_id);
            }

            if ($stmt->execute()) {
                echo "<script>alert('Administrador atualizado com sucesso!'); window.location.href='add_admin.php';</script>";
            } else {
                echo "<script>alert('Erro ao atualizar administrador. Tente novamente.');</script>";
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
    <title>Editar Administrador - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="adEditar">
    <div class="adEditar-container">
        <h1 class="adEditar-title">Editar Administrador</h1>
        <form method="POST" action="editar_admin.php?id=<?php echo $admin_id; ?>" class="adEditar-form">
            <!-- Nome -->
            <label for="nome" class="adEditar-label">Nome:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($admin_edit['nome']); ?>" required
                class="adEditar-input">

            <!-- Apelido -->
            <label for="apelido" class="adEditar-label">Apelido:</label>
            <input type="text" name="apelido" value="<?php echo htmlspecialchars($admin_edit['apelido'] ?? ''); ?>"
                class="adEditar-input" placeholder="Opcional">

            <!-- Email -->
            <label for="email_prefix" class="adEditar-label">Email:</label>
            <div class="adEditar-email-field">
                <input type="text" name="email_prefix" value="<?php echo htmlspecialchars($email_prefix); ?>" required
                    class="adEditar-input">
                <span class="adEditar-email-suffix">@mercadobompreco.com</span>
            </div>

            <!-- Telefone -->
            <label for="telefone" class="adEditar-label">Número de Contato:</label>
            <input type="text" name="telefone" value="<?php echo htmlspecialchars($admin_edit['telefone']); ?>" required
                class="adEditar-input" placeholder="Ex: +351 912 345 678">

            <!-- Senha -->
            <label for="senha" class="adEditar-label">Nova Senha (opcional):</label>
            <input type="password" name="senha" placeholder="Deixe em branco para não alterar" class="adEditar-input">

            <!-- Botões -->
            <button type="submit" name="editar_admin" class="adEditar-submit">Salvar Alterações</button>
            <button type="reset" class="adEditar-reset">Cancelar</button>
            <a href="add_admin.php" class="adEditar-cancel">Voltar</a>
        </form>
    </div>
</body>

</html>