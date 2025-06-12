<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('É necessário estar logado para acessar o perfil. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtém dados do usuário
$sql = "SELECT nome, apelido, foto_perfil FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// Processa remoção da foto de perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remover_foto'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        if ($usuario['foto_perfil'] && file_exists($usuario['foto_perfil'])) {
            unlink($usuario['foto_perfil']);
        }
        $sql = "UPDATE usuarios SET foto_perfil = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $_SESSION['foto_perfil'] = null;
        $mensagem = "Foto de perfil removida com sucesso!";
        $usuario['foto_perfil'] = null;
    }
}

// Processa atualização de apelido e foto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_perfil'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $apelido = trim(filter_input(INPUT_POST, 'apelido', FILTER_SANITIZE_STRING));
        $foto_perfil = $_FILES['foto_perfil'] ?? null;

        // Validações
        $erros = [];
        if (empty($apelido)) {
            $erros[] = "O nome de usuário é obrigatório.";
        } else {
            $sql = "SELECT id FROM usuarios WHERE apelido = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $apelido, $usuario_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $erros[] = "Este nome de usuário já está em uso.";
            }
        }

        if (empty($erros)) {
            // Atualiza apelido
            $sql = "UPDATE usuarios SET apelido = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $apelido, $usuario_id);
            $stmt->execute();

            // Processa upload da foto
            if ($foto_perfil && $foto_perfil['error'] == UPLOAD_ERR_OK) {
                $target_dir = "img/perfil/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $imageFileType = strtolower(pathinfo($foto_perfil["name"], PATHINFO_EXTENSION));
                $target_file = $target_dir . "perfil_" . $usuario_id . "_" . time() . "." . $imageFileType;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($imageFileType, $allowed_types)) {
                    if (move_uploaded_file($foto_perfil["tmp_name"], $target_file)) {
                        if ($usuario['foto_perfil'] && file_exists($usuario['foto_perfil'])) {
                            unlink($usuario['foto_perfil']);
                        }
                        $sql = "UPDATE usuarios SET foto_perfil = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $target_file, $usuario_id);
                        $stmt->execute();
                        $_SESSION['foto_perfil'] = $target_file;
                        $mensagem .= " Foto de perfil atualizada com sucesso!";
                    } else {
                        $mensagem .= " Erro ao fazer upload da foto.";
                    }
                } else {
                    $mensagem .= " Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.";
                }
            }

            if (empty($mensagem)) {
                $mensagem = "Perfil atualizado com sucesso!";
            }

            // Atualiza dados do usuário
            $sql = "SELECT nome, apelido, foto_perfil FROM usuarios WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $_SESSION['usuario_apelido'] = $apelido;
        } else {
            $mensagem = implode("<br>", $erros);
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="perfil">
    <div class="perfil-container">
        <h1>Meu Perfil</h1>
        <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <!-- Exibe foto de perfil -->
        <?php if ($usuario['foto_perfil']): ?>
        <div class="foto-perfil-container-PC">
            <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de Perfil">
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="remover_foto">Remover Foto</button>
        </form>
        <?php else: ?>
        <p>Sem foto de perfil</p>
        <?php endif; ?>
        <br>
        <p class="nome-perfil"><b>Nome:</b> <?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></p>
        <p class="apelido-perfil"><strong><b>Nome de usuário:</b></strong>
            <?php echo htmlspecialchars($usuario['apelido'] ?? ''); ?></p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="foto_perfil">Selecionar uma Foto de Perfil:</label>
            <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*">
            <label for="apelido">Nome de Usuário:</label>
            <input type="text" name="apelido" id="apelido"
                value="<?php echo htmlspecialchars($usuario['apelido'] ?? ''); ?>"
                placeholder="Digite o seu nome de usuário" required>
            <button type="submit" name="salvar_perfil">Salvar Alterações</button>
        </form>

        <p><a href="configuracoes.php" class="Econfig">Editar Configurações</a></p>
        <p><a href="index.php" class="Econfig">Voltar ao Início</a></p>
    </div>
</body>

</html>