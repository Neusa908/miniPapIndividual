<?php
session_start();
require 'conexao.php'; // inicia a sessão

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('É necessário estar logado para acessar o perfil. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sql = "SELECT nome, email, foto_perfil, telefone FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

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
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_tudo'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = "Erro de validação. Tente novamente.";
    } else {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $telefone = trim($_POST['telefone']);
        $senha_atual = trim($_POST['senha_atual'] ?? '');
        $nova_senha = trim($_POST['nova_senha'] ?? '');
        $foto_perfil = $_FILES['foto_perfil'] ?? null;

    
        $sql = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $usuario_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $mensagem = "Este email já está em uso por outro usuário!";
        } else {
            
            $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nome, $email, $telefone, $usuario_id);
            $stmt->execute();
            $_SESSION['usuario_nome'] = $nome; 

          
            if (!empty($senha_atual) && !empty($nova_senha)) {
                $sql = "SELECT senha FROM usuarios WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $dados = $result->fetch_assoc();

                if (password_verify($senha_atual, $dados['senha'])) {
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $nova_senha_hash, $usuario_id);
                    $stmt->execute();
                    $mensagem .= " Senha atualizada com sucesso!";
                } else {
                    $mensagem .= " A senha atual está incorreta.";
                }
            }

        
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
                $mensagem = "Alterações salvas com sucesso!";
            }

            $sql = "SELECT nome, email, foto_perfil, telefone FROM usuarios WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $usuario = $result->fetch_assoc();
            $_SESSION['foto_perfil'] = $usuario['foto_perfil']; 
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="registar">
    <div class="perfil-container">
        <h1>Meu Perfil</h1>
        <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>


        <?php if ($usuario['foto_perfil']): ?>
        <img src="<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" alt="Foto de Perfil"
            style="max-width: 100px;">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button type="submit" name="remover_foto">Remover Foto</button>
        </form>
        <?php else: ?>
        <p>Sem foto de perfil.</p>
        <?php endif; ?>
        <br>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">


            <label for="foto_perfil">Selecionar uma foto de Perfil:</label>
            <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*">


            <label for="nome">Nome:</label>
            <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>"
                placeholder="Digite o seu novo nome de utilizador" required>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>"
                placeholder="Digite o seu novo email" required>
            <label for="telefone">Número de Telefone:</label>
            <input type="text" name="telefone" id="telefone" placeholder="Digite o seu novo número"
                value="<?php echo htmlspecialchars($usuario['telefone'] ?? ''); ?>">


            <label for="senha_atual">Senha Atual:</label>
            <input type="password" name="senha_atual" id="senha_atual" placeholder="Digite a sua senha atual">
            <label for="nova_senha">Nova Senha:</label>
            <input type="password" name="nova_senha" id="nova_senha" placeholder="Digite a sua nova senha">


            <button type="submit" name="salvar_tudo">Salvar Alterações</button>
        </form>

        <p><a href="index.php" style="color: white;">Voltar ao Início</a></p>
    </div>
</body>

</html>