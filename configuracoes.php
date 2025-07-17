<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    echo "<script>alert('É necessário estar logado para acessar as configurações. Você será redirecionado para o login.'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$sql = "SELECT email, telefone, morada, cidade FROM utilizadores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$utilizador = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['salvar_dados'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensagem'] = "Erro de validação. Tente novamente.";
    } else {
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING));
        $morada = trim(filter_input(INPUT_POST, 'morada', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING));

        $erros = [];
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "Por favor, insira um email válido.";
        } else {
            $sql = "SELECT id FROM utilizadores WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $email, $utilizador_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $erros[] = "Este email já está em uso por outro utilizador.";
            }
        }
        if (empty($telefone) || !preg_match('/^[0-9\s+]+$/', $telefone)) {
            $erros[] = "O telefone deve conter apenas números, espaços ou o símbolo +.";
        }
        if (empty($morada)) {
            $erros[] = "A morada é obrigatória.";
        }
        if (empty($cidade)) {
            $erros[] = "A cidade é obrigatória.";
        }

        if (empty($erros)) {
            $sql = "UPDATE utilizadores SET email = ?, telefone = ?, morada = ?, cidade = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $email, $telefone, $morada, $cidade, $utilizador_id);
            if ($stmt->execute()) {
                $_SESSION['mensagem'] = "Dados pessoais atualizados com sucesso!";
                $_SESSION['utilizador_email'] = $email;
            } else {
                $_SESSION['mensagem'] = "Erro ao atualizar os dados.";
            }
            header("Location: configuracoes.php");
            exit();
        } else {
            $_SESSION['mensagem'] = implode("<br>", $erros);
            header("Location: configuracoes.php");
            exit();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['atualizar_endereco']) || isset($_POST['adicionar_endereco']))) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensagem'] = "Erro de validação. Tente novamente.";
    } else {
        $endereco_id = isset($_POST['endereco_id']) ? filter_input(INPUT_POST, 'endereco_id', FILTER_SANITIZE_NUMBER_INT) : null;
        $nome_endereco = trim(filter_input(INPUT_POST, 'nome_endereco', FILTER_SANITIZE_STRING));
        $rua = trim(filter_input(INPUT_POST, 'rua', FILTER_SANITIZE_STRING));
        $numero = trim(filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_STRING));
        $freguesia = trim(filter_input(INPUT_POST, 'freguesia', FILTER_SANITIZE_STRING));
        $cidade = trim(filter_input(INPUT_POST, 'cidade_endereco', FILTER_SANITIZE_STRING));
        $distrito = trim(filter_input(INPUT_POST, 'distrito', FILTER_SANITIZE_STRING));
        $codigo_postal = trim(filter_input(INPUT_POST, 'codigo_postal', FILTER_SANITIZE_STRING));
        $padrao = isset($_POST['padrao']) ? 1 : 0;

        $erros = [];
        if (empty($nome_endereco)) $erros[] = "O nome do endereço é obrigatório.";
        if (empty($rua)) $erros[] = "A rua é obrigatória.";
        if (!empty($numero) && !preg_match('/^[0-9\s]+$/', $numero)) $erros[] = "O número deve conter apenas números ou espaços.";
        if (empty($cidade)) $erros[] = "A cidade é obrigatória.";
        if (empty($distrito)) $erros[] = "O distrito é obrigatório.";
        if (empty($codigo_postal) || !preg_match('/^\d{4}-\d{3}$/', $codigo_postal)) $erros[] = "O Código Postal é obrigatório e deve estar no formato 1234-567.";

        if (empty($erros)) {
            if ($padrao) {
                $sql = "UPDATE enderecos SET padrao = 0 WHERE utilizador_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $utilizador_id);
                $stmt->execute();
            }
            if ($endereco_id) {
                $sql = "UPDATE enderecos SET nome_endereco = ?, rua = ?, numero = ?, freguesia = ?, cidade = ?, distrito = ?, codigo_postal = ?, padrao = ? WHERE id = ? AND utilizador_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssiiii", $nome_endereco, $rua, $numero, $freguesia, $cidade, $distrito, $codigo_postal, $padrao, $endereco_id, $utilizador_id);
                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Endereço atualizado com sucesso!";
                } else {
                    $_SESSION['mensagem'] = "Erro ao atualizar o endereço.";
                }
            } else {
                $sql = "INSERT INTO enderecos (utilizador_id, nome_endereco, rua, numero, freguesia, cidade, distrito, codigo_postal, padrao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssssi", $utilizador_id, $nome_endereco, $rua, $numero, $freguesia, $cidade, $distrito, $codigo_postal, $padrao);
                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Endereço adicionado com sucesso!";
                } else {
                    $_SESSION['mensagem'] = "Erro ao adicionar endereço.";
                }
            }
            header("Location: configuracoes.php");
            exit();
        } else {
            $_SESSION['mensagem'] = implode("<br>", $erros);
            header("Location: configuracoes.php");
            exit();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apagar_endereco'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['mensagem'] = "Erro de validação. Tente novamente.";
    } else {
        $endereco_id = filter_input(INPUT_POST, 'endereco_id', FILTER_SANITIZE_NUMBER_INT);
        $sql = "SELECT padrao FROM enderecos WHERE id = ? AND utilizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $endereco_id, $utilizador_id);
        $stmt->execute();
        $endereco = $stmt->get_result()->fetch_assoc();

        if ($endereco) {
            if ($endereco['padrao'] == 1) {
                $_SESSION['mensagem'] = "Não é possível eliminar o endereço padrão. Defina outro endereço como padrão primeiro.";
            } else {
                $sql = "DELETE FROM enderecos WHERE id = ? AND utilizador_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $endereco_id, $utilizador_id);
                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = "Endereço eliminado com sucesso!";
                } else {
                    $_SESSION['mensagem'] = "Erro ao eliminar endereço.";
                }
            }
        } else {
            $_SESSION['mensagem'] = "Endereço não encontrado.";
        }
        header("Location: configuracoes.php");
        exit();
    }
}

$sql = "SELECT id, nome_endereco, rua, numero, freguesia, cidade, distrito, codigo_postal, padrao FROM enderecos WHERE utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$enderecos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$stmt->close();
$conn->close();
?>



<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endereços - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">

</head>

<body class="configuracoes-body">

    <div class="configuracoes-container">
        <?php if ($mensagem): ?>
        <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>



        <h2>Dados Pessoais</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($utilizador['email']); ?>"
                placeholder="Digite o seu email" required>
            <label for="telefone">Telefone:</label>
            <input type="tel" name="telefone" id="telefone"
                value="<?php echo htmlspecialchars($utilizador['telefone']); ?>" placeholder="Digite o seu número"
                required>
            <label for="morada">Morada:</label>
            <input type="text" name="morada" id="morada" value="<?php echo htmlspecialchars($utilizador['morada']); ?>"
                placeholder="Digite a sua morada" required>
            <label for="cidade">Cidade:</label>
            <input type="text" name="cidade" id="cidade" value="<?php echo htmlspecialchars($utilizador['cidade']); ?>"
                placeholder="Digite a sua cidade" required>
            <button type="submit" name="salvar_dados">Salvar Dados Pessoais</button>
        </form>


        <?php if (!empty($enderecos)): ?>
        <h2>Os Seus Endereços</h2>
        <?php foreach ($enderecos as $endereco): ?>
        <div class="endereco-card">
            <strong><?php echo htmlspecialchars($endereco['nome_endereco']); ?></strong><br>
            <?php echo htmlspecialchars($endereco['rua'] . ', ' . $endereco['numero']); ?><br>
            <?php echo htmlspecialchars($endereco['freguesia'] . ', ' . $endereco['cidade'] . ', ' . $endereco['distrito']); ?><br>
            <?php echo htmlspecialchars($endereco['codigo_postal']); ?><br>
            <?php if ($endereco['padrao']): ?>
            <span class="endereco-padrao"><b>Endereço Padrão</b></span>
            <?php endif; ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="endereco_id" value="<?php echo $endereco['id']; ?>">
                <button type="submit" name="apagar_endereco" class="btn-cancelar"
                    onclick="return confirm('Tem certeza que deseja apagar este endereço?');">Apagar</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p>Você não tem endereços registados.</p>
        <?php endif; ?>

        <h3>Adicionar Novo Endereço</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <label for="nome_endereco">Nome do Endereço:</label>
            <input type="text" name="nome_endereco" id="nome_endereco" placeholder="Ex.: Casa, Trabalho" required>
            <label for="rua">Rua:</label>
            <input type="text" name="rua" id="rua" placeholder="Digite a rua" required>
            <label for="numero">Número da Porta:</label>
            <input type="text" name="numero" id="numero" placeholder="Digite o número da porta" required>
            <label for="freguesia">Freguesia:</label>
            <input type="text" name="freguesia" id="freguesia" placeholder="Digite a freguesia">
            <label for="cidade_endereco">Cidade:</label>
            <input type="text" name="cidade_endereco" id="cidade_endereco" placeholder="Digite a cidade" required>
            <label for="distrito">Distrito:</label>
            <input type="text" name="distrito" id="distrito" placeholder="Digite o distrito" required>
            <label for="codigo_postal">Código Postal:</label>
            <input type="text" name="codigo_postal" id="codigo_postal" placeholder="Digite o Código postal" required>
            <label><input type="checkbox" name="padrao"> Definir como padrão</label>
            <button type="submit" name="adicionar_endereco">Adicionar Endereço</button>
            <button type="reset" class="btn-cancelar">Cancelar</button>
        </form>

        <a href="perfil.php" class="btn-voltar">Ir para as configurações da conta</a>
        <a href="index.php" class="btn-voltar">Ir para o início</a>
    </div>
</body>

</html>