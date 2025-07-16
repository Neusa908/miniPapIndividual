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

// busca a foto de perfil do administrador
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $_SESSION['utilizador_id']);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg';
$stmt_foto->close();



//POST
// Processa o adicionar de um novo produto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_produto'])) {
    $nome = trim($_POST['nome']);
    $preco = floatval($_POST['preco']);
    $quantidade_estoque = intval($_POST['quantidade_estoque']);
    $categoria_id = intval($_POST['categoria_id']);
    $descricao = trim($_POST['descricao']);

    

    // Lida com o upload da imagem
    $imagem = '';
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $target_dir = "img/produtos/"; // Diretório onde as imagens serão salvas
        $target_file = $target_dir . basename($_FILES['imagem']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Verifica se o ficheiro é uma imagem
        $check = getimagesize($_FILES['imagem']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $target_file)) {
                $imagem = $target_file;
            } else {
                echo "<script>alert('Erro ao fazer upload da imagem.');</script>";
            }
        } else {
            echo "<script>alert('O arquivo não é uma imagem.');</script>";
        }
    }

$sql = "INSERT INTO produtos (nome, preco, quantidade_estoque, categoria_id, imagem, descricao) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sdiiiss", $nome, $preco, $quantidade_estoque, $categoria_id, $imagem, $descricao);

    if ($stmt->execute()) {
        echo "<script>alert('Produto adicionado com sucesso!'); window.location.href='admin_produtos.php';</script>";
    } else {
        echo "<script>alert('Erro ao adicionar o produto. Tente novamente.');</script>";
    }
    $stmt->close();
}

// Busca todas as categorias para o formulário
$sql_categorias = "SELECT id, nome FROM categorias ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Produto - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="admin-container">
        <h2 class="admin-title">Adicionar Produto</h2>
        <div class="usuario-foto-container">
            <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
        </div>

        <!-- Formulário para adicionar o produto -->
        <div class="section add-product-section">

            <form class="product-form" method="POST" action="admin_produtos.php" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" name="nome" class="form-input" placeholder="Nome do Produto" required>
                </div>

                <!-- Preço -->
                <div class="form-group">
                    <input type="number" name="preco" class="form-input" placeholder="Preço (€)" step="0.01" required>
                </div>

                <!-- Quantidade em estoque -->
                <div class="form-group">
                    <input type="number" name="quantidade_estoque" class="form-input"
                        placeholder="Quantidade em Estoque" required>
                </div>

                <!-- Descrição -->
                <div class="form-group">
                    <textarea name="descricao" class="form-textarea-desc" rows="4" placeholder="Descrição do Produto"
                        required></textarea>
                </div>

                <!-- Categoria -->
                <div class="form-group">
                    <select name="categoria_id" class="form-select" required>
                        <option value="">Selecione uma Categoria</option>
                        <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Imagem -->
                <div class="form-group">
                    <input type="file" name="imagem" class="form-input" accept="image/*" required>
                </div>

                <!-- Botão para Adicionar Produto -->
                <div class="form-group">
                    <button type="submit" name="adicionar_produto" class="form-button">Adicionar Produto</button>
                </div>
            </form>
        </div>

        <!-- Links -->
        <div class="nav-links">
            <a href="admin_lista_produtos.php" class="nav-link">Ver Lista de Produtos</a>
            <a href="admin_panel.php" class="nav-link">Voltar para o Painel Administrativo</a>
        </div>
    </div>


</body>

</html>

<?php
$conn->close();
?>