<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensagem'] = "É necessário estar registado para acessar esta página.";
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT nome, email, telefone, saldo FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sql = "SELECT id, nome_endereco, rua, numero, bairro, cidade, estado, cep, padrao FROM enderecos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$enderecos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_endereco'])) {
    $nome_endereco = $_POST['nome_endereco'] ?? '';
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $padrao = isset($_POST['padrao']) ? 1 : 0;

    if ($padrao) {
        $sql = "UPDATE enderecos SET padrao = 0 WHERE usuario_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $stmt->close();
    }

    $sql = "INSERT INTO enderecos (usuario_id, nome_endereco, rua, numero, bairro, cidade, estado, cep, padrao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssi", $usuario_id, $nome_endereco, $rua, $numero, $bairro, $cidade, $estado, $cep, $padrao);
    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Endereço adicionado com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao adicionar endereço.";
    }
    $stmt->close();
    header("Location: minha_conta.php#enderecos");
    exit();
}

$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
unset($_SESSION['mensagem']);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="finalizar-compra-container">
        <h1>Minha Conta</h1>
        <?php if ($mensagem): ?>
        <div class="mensagem"><?php echo htmlspecialchars($mensagem); ?></div>
        <script>
        setTimeout(() => {
            document.querySelector('.mensagem').style.display = 'none';
        }, 2000);
        </script>
        <?php endif; ?>
        <h2>Dados Pessoais</h2>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($usuario['nome']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p>
        <p><strong>Telefone:</strong> <?php echo htmlspecialchars($usuario['telefone']); ?></p>
        <p><strong>Saldo:</strong> €<?php echo number_format($usuario['saldo'], 2, ',', '.'); ?></p>

        <h2 id="enderecos">Endereços</h2>
        <?php if (!empty($enderecos)): ?>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Morada</th>
                    <th>Cidade</th>
                    <th>Padrão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enderecos as $endereco): ?>
                <tr>
                    <td><?php echo htmlspecialchars($endereco['nome_endereco']); ?></td>
                    <td><?php echo htmlspecialchars($endereco['rua'] . ', ' . ($endereco['numero'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($endereco['cidade']); ?></td>
                    <td><?php echo $endereco['padrao'] ? 'Sim' : 'Não'; ?></td>
                    <td><a href="editar_endereco.php?id=<?php echo $endereco['id']; ?>" class="btn">Editar</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p>Você não tem endereços cadastrados.</p>
        <?php endif; ?>
        <h3>Adicionar Novo Endereço</h3>
        <form method="POST">
            <label>Nome do Endereço:</label>
            <input type="text" name="nome_endereco" required>
            <label>Morada:</label>
            <input type="text" name="rua" required>
            <label>Número:</label>
            <input type="text" name="numero">
            <label>Bairro:</label>
            <input type="text" name="bairro">
            <label>Cidade:</label>
            <input type="text" name="cidade" required>
            <label>Estado:</label>
            <input type="text" name="estado">
            <label>CEP:</label>
            <input type="text" name="cep">
            <label><input type="checkbox" name="padrao"> Definir como padrão</label>
            <button type="submit" name="adicionar_endereco" class="btn">Adicionar</button>
        </form>
        <a href="index.php" class="btn">Voltar</a>
        <p><a href="rastrear_pedido.php">Rastrear Pedidos</a></p>
    </div>
    <footer>
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>
<?php $conn->close(); ?>