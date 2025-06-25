<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'conexao.php';

// Verifica se o utilizador é administrador
if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Atualizar produto
$mensagem = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar_produto'])) {
    $produto_id = intval($_POST['produto_id']);
    $nome = trim($_POST['nome']);
    $preco = floatval($_POST['preco']);
    $quantidade_estoque = intval($_POST['quantidade_estoque']);
    $estoque = intval($_POST['estoque']);
    $categoria_id = intval($_POST['categoria_id']);

    // Verifica se imagem nova foi enviada
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $target_dir = "img/produtos/";
        $target_file = $target_dir . basename($_FILES['imagem']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (getimagesize($_FILES['imagem']['tmp_name'])) {
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $target_file)) {
                $imagem = $target_file;
                $sql = "UPDATE produtos SET nome=?, preco=?, quantidade_estoque=?, estoque=?, categoria_id=?, imagem=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdiiisi", $nome, $preco, $quantidade_estoque, $estoque, $categoria_id, $imagem, $produto_id);
            } else {
                $mensagem = "Erro ao fazer upload da imagem.";
            }
        } else {
            $mensagem = "O arquivo enviado não é uma imagem válida.";
        }
    } else {
        // Atualiza sem imagem
        $sql = "UPDATE produtos SET nome=?, preco=?, quantidade_estoque=?, estoque=?, categoria_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdiiii", $nome, $preco, $quantidade_estoque, $estoque, $categoria_id, $produto_id);
    }

    if ($stmt->execute()) {
        $mensagem = "Produto atualizado com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar produto.";
    }

    $stmt->close();
}

// Carrega lista de produtos
$produtos = $conn->query("SELECT id, nome FROM produtos ORDER BY nome");

// Carrega lista de categorias
$categorias = $conn->query("SELECT id, nome FROM categorias ORDER BY nome");

// Carrega dados de produto selecionado
$produto_selecionado = null;
if (isset($_GET['editar_id'])) {
    $editar_id = intval($_GET['editar_id']);
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $editar_id);
    $stmt->execute();
    $produto_selecionado = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Editar Produto - Admin</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-editar-body">
    <div class="admin-editar-container">
        <h1 class="admin-editar-title">Editar Produto</h1>

        <?php if ($mensagem): ?>
        <p class="admin-editar-mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <!-- Seleção de Produto -->
        <form method="GET" class="admin-editar-selecao">
            <label for="editar_id">Escolha o Produto:</label>
            <select name="editar_id" id="editar_id" onchange="this.form.submit()" required>
                <option value="">-- Selecione --</option>
                <?php while ($p = $produtos->fetch_assoc()): ?>
                <option value="<?php echo $p['id']; ?>"
                    <?php echo (isset($produto_selecionado) && $produto_selecionado['id'] == $p['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['nome']); ?>
                </option>
                <?php endwhile; ?>
            </select>
        </form>

        <?php if ($produto_selecionado): ?>
        <!-- Formulário de Edição -->
        <form method="POST" enctype="multipart/form-data" class="admin-editar-form">
            <input type="hidden" name="produto_id" value="<?php echo $produto_selecionado['id']; ?>">

            <label>Nome:</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($produto_selecionado['nome']); ?>"
                required>

            <label>Preço (€):</label>
            <input type="number" step="0.01" name="preco" value="<?php echo $produto_selecionado['preco']; ?>" required>

            <label>Quantidade em Estoque:</label>
            <input type="number" name="quantidade_estoque"
                value="<?php echo $produto_selecionado['quantidade_estoque']; ?>" required>

            <label>Estoque:</label>
            <input type="number" name="estoque" value="<?php echo $produto_selecionado['estoque']; ?>" required>

            <label>Categoria:</label>
            <select name="categoria_id" required>
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                <option value="<?php echo $cat['id']; ?>"
                    <?php echo ($cat['id'] == $produto_selecionado['categoria_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['nome']); ?>
                </option>
                <?php endwhile; ?>
            </select>

            <label>Imagem (opcional):</label>
            <input type="file" name="imagem" accept="image/*">

            <button type="submit" name="atualizar_produto" class="admin-editar-btn">Atualizar Produto</button>
        </form>
        <?php endif; ?>

        <div class="admin-editar-links">
            <a href="admin_lista_produtos.php" class="admin-editar-link">Ver Lista de Produtos</a>
            <a href="admin_panel.php" class="admin-editar-link">Voltar ao Painel</a>
        </div>
    </div>
</body>

</html>