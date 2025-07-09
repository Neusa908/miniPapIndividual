<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['mensagem'] = "Faça login para ver seus favoritos.";
    header("Location: login.php");
    exit;
}

// Verifica se há mensagem para exibir
$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$mensagem_classe = isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);

// Obtém os produtos favoritados
$utilizador_id = $_SESSION['utilizador_id'];
$sql = "SELECT p.id, p.nome, p.preco, p.descricao, p.quantidade_estoque, p.imagem
        FROM produtos p
        INNER JOIN favoritos f ON p.id = f.produto_id
        WHERE f.utilizador_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $utilizador_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoritos - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="favoritos">
    <header class="header-produtos">
        <div class="header-content">
            <div class="title-section">
                <h1 class="h1-favoritos">Meus Favoritos</h1>

            </div>
            <a href="produtos.php" class="produtos-link">Voltar aos Produtos</a>
            <button class="login-button" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </header>
    <div class="box-container">
        <?php if ($mensagem): ?>
        <div id="mensagem" class="<?php echo $mensagem_classe; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <script>
        setTimeout(() => {
            document.getElementById('mensagem').style.display = 'none';
        }, 2000);
        </script>
        <?php endif; ?>

        <div class="produto-lista">
            <?php if ($result->num_rows > 0): ?>
            <?php while ($produto = $result->fetch_assoc()): ?>
            <div class="produto-item">
                <img src="<?php echo htmlspecialchars($produto['imagem'] ?? 'img/default-product.jpg'); ?>"
                    alt="<?php echo htmlspecialchars($produto['nome']); ?>" class="produto-imagem">
                <div class="produto-detalhes">
                    <h3 class="produto-nome"><?php echo htmlspecialchars($produto['nome']); ?></h3>
                    <p class="produto-descricao"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                    <span class="preco">€<?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                    <p class="produto-estoque">Em estoque: <?php echo $produto['quantidade_estoque']; ?> Unidades</p>
                    <?php if ($produto['quantidade_estoque'] > 0): ?>
                    <form method="POST" action="carrinho.php" class="produto-form">
                        <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                        <div class="quantidade-container">
                            <label for="quantidade_<?php echo $produto['id']; ?>"
                                class="quantidade-label">Quantidade:</label>
                            <input type="number" name="quantidade" id="quantidade_<?php echo $produto['id']; ?>" min="1"
                                max="<?php echo $produto['quantidade_estoque']; ?>" value="1"
                                class="produto-quantidade">
                        </div>
                        <button class="produto-button" type="submit" name="adicionar_carrinho">Adicionar ao
                            Carrinho</button>
                    </form>
                    <?php else: ?>
                    <p class="erro">Produto fora de estoque.</p>
                    <?php endif; ?>
                    <form method="POST" action="favoritos_adicionar.php" class="favorito-form">
                        <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                        <button class="favorito-button" type="submit" name="adicionar_favorito">⭐</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <p class="sem-produtos">Nenhum produto favoritado.</p>
            <?php endif; ?>
        </div>
    </div>
    <footer>
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>
<?php $stmt->close(); $conn->close(); ?>