<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // conexão com o banco de dados

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$sql_usuario = "SELECT nome, apelido, tipo, foto_perfil FROM usuarios WHERE id = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario_id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();
$admin = $result_usuario->fetch_assoc();
$stmt_usuario->close();

$admin['foto_perfil'] = $admin['foto_perfil'] ?? 'img/default-profile.jpg';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_admin'])) {
    $nome = trim($_POST['nome']);
    $apelido = trim($_POST['apelido']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $telefone = trim($_POST['telefone']);
    $tipo = 'admin';

    // Debug para verificar os valores
    error_log("Debug: nome=$nome, apelido=$apelido, email=$email, telefone=$telefone, tipo=$tipo");

    if (empty($nome) || empty($email) || empty($senha) || empty($telefone) || empty($apelido)) {
        echo "<script>alert('Por favor, preencha todos os campos obrigatórios!');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Por favor, insira um email válido!');</script>";
    } elseif ($nome === $apelido) {
        echo "<script>alert('O apelido não pode ser igual ao nome. Insira um apelido diferente!');</script>";
    } else {
        $email = strtolower(trim($email));
        $domain = '@mercadobompreco.com';

        if (substr_count($email, '@') > 1) {
            echo "<script>alert('O email contém mais de um símbolo @, o que não é permitido!');</script>";
        } elseif (!preg_match('/@mercadobompreco\.com$/', $email)) {
            echo "<script>alert('O email deve terminar com @mercadobompreco.com!');</script>";
        } else {
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                echo "<script>alert('Este email já está registrado!');</script>";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "INSERT INTO usuarios (nome, apelido, email, senha, telefone, tipo) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $nome, $apelido, $email, $senha_hash, $telefone, $tipo);
                if ($stmt->execute()) {
                    echo "<script>alert('Administrador adicionado com sucesso!'); window.location.href='add_admin.php';</script>";
                } else {
                    echo "<script>alert('Erro ao adicionar administrador. Tente novamente.');</script>";
                }
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['delete_admin'])) {
    $admin_id = $_GET['delete_admin'];

    if ($admin_id == $usuario_id) {
        echo "<script>alert('Você não pode excluir sua própria conta!'); window.location.href='add_admin.php';</script>";
    } else {
        // Deletar notificações associadas ao administrador
        $sql_notificacoes = "DELETE FROM notificacoes WHERE admin_id = ?";
        $stmt_notificacoes = $conn->prepare($sql_notificacoes);
        $stmt_notificacoes->bind_param("i", $admin_id);
        $stmt_notificacoes->execute();
        $stmt_notificacoes->close();

        // Deletar o administrador
        $sql = "DELETE FROM usuarios WHERE id = ? AND tipo = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        if ($stmt->execute()) {
            echo "<script>alert('Administrador excluído com sucesso!'); window.location.href='add_admin.php';</script>";
        } else {
            echo "<script>alert('Erro ao excluir administrador. Tente novamente.');</script>";
        }
        $stmt->close();
    }
}

$sql_admins = "SELECT id, nome, apelido, email FROM usuarios WHERE tipo = 'admin' ORDER BY nome";
$result_admins = $conn->query($sql_admins);

?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Administradores - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/add_admin.css">
</head>


<!--Menu na foto de perfil-->

<body class="support-body">
    <div class="add-admin-container">
        <div class="add-admin-profile">
            <div class="profile-pic">
                <img src="<?php echo htmlspecialchars($admin['foto_perfil']); ?>" alt="Foto do Admin">
                <div class="profile-dropdown">
                    <a href="admin_perfil.php">Configurações</a>
                    <a href="admin_verPerfil.php?id=<?php echo $usuario_id; ?>">Ver Perfil</a>
                    <a href="admin_lista.php">Lista de Administradores</a>
                    <a href="logout.php">Sair</a>
                </div>
            </div>
        </div>

        <div class="add-admin-title">
            <h1>Gestão de Administradores</h1>
        </div>

        <div class="add-admin-content">
            <div class="add-admin-section">
                <h4>Adicionar Novo Administrador</h4>
                <form class="add-admin-form" method="POST" action="add_admin.php">
                    <label for="nome">Nome do Administrador:</label>
                    <input type="text" name="nome" id="nome" placeholder="Nome do Administrador" required>
                    <label for="apelido">Apelido do Administrador:</label>
                    <input type="text" name="apelido" id="apelido" placeholder="Apelido do Administrador" required>
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" placeholder="nome@mercadobompreco.com" required>
                    <label for="senha">Senha (mínimo 8 caracteres):</label>
                    <input type="password" name="senha" id="senha" placeholder="Senha" required>
                    <label for="telefone">Telefone:</label>
                    <input type="text" name="telefone" id="telefone" placeholder="Telefone" required>
                    <button type="submit" name="adicionar_admin">Adicionar Administrador</button>
                </form>
            </div>

            <div class="add-admin-section">
                <h4>Lista de Administradores</h4>
                <div class="add-admin-list">
                    <?php if ($result_admins->num_rows > 0): ?>
                    <?php while ($row = $result_admins->fetch_assoc()): ?>
                    <div class="add-admin-item">
                        <div class="add-admin-info">
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></p>
                            <p><strong>Apelido:</strong> <?php echo htmlspecialchars($row['apelido']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                        </div>
                        <div class="add-admin-actions">
                            <a href="editar_admin.php?id=<?php echo $row['id']; ?>" class="add-admin-edit">Editar</a>
                            <a href="add_admin.php?delete_admin=<?php echo $row['id']; ?>" class="add-admin-delete"
                                onclick="return confirm('Tem certeza que deseja excluir este administrador?');">Deletar</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <p class="add-admin-empty">Nenhum administrador encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <a href="admin_panel.php" class="add-admin-back-link">Voltar para o Painel Administrativo</a>
    </div>
</body>

</html>
<?php
$conn->close();
?>