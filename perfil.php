<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo "<script>alert('É necessário estar logado para acessar o perfil. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];
$mensagem = "";

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtém dados do utilizador
$sql = "SELECT nome, apelido, foto_perfil FROM utilizadores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$utilizador = $stmt->get_result()->fetch_assoc();

$apelido = $utilizador['apelido'] ?? '';
$nome = $utilizador['nome'] ?? '';

// Remoção da foto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remover_foto'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        if ($utilizador['foto_perfil'] && file_exists($utilizador['foto_perfil'])) {
            unlink($utilizador['foto_perfil']);
        }
        $sql = "UPDATE utilizadores SET foto_perfil = NULL WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $utilizador_id);
        $stmt->execute();
        $_SESSION['foto_perfil'] = null;
        $mensagem = "Foto de perfil removida com sucesso!";
        $utilizador['foto_perfil'] = null;
        $_SESSION['utilizador_apelido'] = $apelido;
    }
}

// Atualização do perfil
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_perfil'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $apelido = trim(filter_input(INPUT_POST, 'apelido', FILTER_SANITIZE_STRING));
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
        $foto_perfil = $_FILES['foto_perfil'] ?? null;

        $erros = [];
        if (empty($apelido)) {
            $erros[] = "O nome de utilizador é obrigatório.";
        } else {
            $sql = "SELECT id FROM utilizadores WHERE apelido = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $apelido, $utilizador_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $erros[] = "Este nome de utilizador já está em uso.";
            }
        }

        if (empty($nome)) {
            $erros[] = "O nome completo é obrigatório.";
        }

        if (empty($erros)) {
            // Atualiza nome e apelido
            $sql = "UPDATE utilizadores SET nome = ?, apelido = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nome, $apelido, $utilizador_id);
            $stmt->execute();

            // Upload da foto
            if ($foto_perfil && $foto_perfil['error'] == UPLOAD_ERR_OK) {
                $target_dir = "img/perfil/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $imageFileType = strtolower(pathinfo($foto_perfil["name"], PATHINFO_EXTENSION));
                $target_file = $target_dir . "perfil_" . $utilizador_id . "_" . time() . "." . $imageFileType;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($imageFileType, $allowed_types)) {
                    if (move_uploaded_file($foto_perfil["tmp_name"], $target_file)) {
                        if ($utilizador['foto_perfil'] && file_exists($utilizador['foto_perfil'])) {
                            unlink($utilizador['foto_perfil']);
                        }
                        $sql = "UPDATE utilizadores SET foto_perfil = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $target_file, $utilizador_id);
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

            // Atualiza dados locais e sessão
            $sql = "SELECT nome, apelido, foto_perfil FROM utilizadores WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $utilizador_id);
            $stmt->execute();
            $utilizador = $stmt->get_result()->fetch_assoc();

            $_SESSION['utilizador_apelido'] = $utilizador['apelido'] ?? '';
            $_SESSION['utilizador_nome'] = $utilizador['nome'] ?? '';
$_SESSION['utilizador_nome_visivel'] = !empty($utilizador['apelido']) ? $utilizador['apelido'] : $utilizador['nome'];

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
    <title>Configurações - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/perfil.css">
</head>

<body class="client-perfil-body">
    <div class="client-perfil-container">
        <h1 class="client-perfil-title">Configurações</h1>

        <?php if ($mensagem): ?>
        <p class="client-perfil-message"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <!-- Foto de perfil -->
        <div class="client-perfil-photo-section">
            <?php if ($utilizador['foto_perfil']): ?>
            <img src="<?php echo htmlspecialchars($utilizador['foto_perfil']); ?>" alt="Foto de Perfil"
                class="client-perfil-photo">
            <form method="POST" class="client-perfil-photo-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" name="remover_foto" class="client-perfil-photo-remove">Remover Foto</button>
            </form>
            <?php else: ?>
            <p class="client-perfil-no-photo">Sem foto de perfil</p>
            <?php endif; ?>
        </div>

        <p class="client-perfil-name"><b>Nome:</b> <?php echo htmlspecialchars($utilizador['nome'] ?? ''); ?></p>
        <p class="client-perfil-username"><b>Nome de utilizador:</b>
            <?php echo htmlspecialchars($utilizador['apelido'] ?? ''); ?></p>

        <form method="POST" enctype="multipart/form-data" class="client-perfil-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="foto_perfil" class="client-perfil-label">Selecionar uma Foto de Perfil:</label>
            <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*" class="client-perfil-input">


            <label for="nome" class="client-perfil-label">Nome Completo:</label>
            <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($utilizador['nome'] ?? ''); ?>"
                placeholder="Digite o seu nome completo" required class="client-perfil-input">

            <label for="apelido" class="client-perfil-label">Nome de Utilizador:</label>
            <input type="text" name="apelido" id="apelido"
                value="<?php echo htmlspecialchars($utilizador['apelido'] ?? ''); ?>"
                placeholder="Digite o seu nome de utilizador" required class="client-perfil-input">

            <button type="submit" name="salvar_perfil" class="client-perfil-submit">Salvar Alterações</button>
        </form>

        <a href="configuracoes.php" class="client-perfil-link">Editar Endereços</a>
        <a href="index.php" class="client-perfil-link">Voltar ao Início</a>
    </div>
</body>

</html>