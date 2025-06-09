<?php
session_start(); // Inicia a sessão
?>
<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mercado Bom Preço - Uma loja com produtos de alta qualidade e preços acessíveis.">
    <title>Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="index">
    <header class="header-index">
        <div class="header-content">
            <div class="title-section">
                <h1>Mercado Bom Preço</h1>
                <p>Onde o preço é bom!</p>
            </div>
            <?php if (isset($_SESSION['usuario_id'])): ?>
            <div class="user-section" style="display: flex; align-items: center; gap: 10px;">
                <?php if (isset($_SESSION['foto_perfil']) && !empty($_SESSION['foto_perfil'])): ?>
                <img src="<?php echo htmlspecialchars($_SESSION['foto_perfil']); ?>" alt="Foto de Perfil"
                    style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #fff;">
                <?php else: ?>
                <img src="img/default-profile.jpg" alt="Foto Padrão"
                    style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #fff;">
                <?php endif; ?>
                <span><b>Bem-vindo,</b> <b><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>! </b></span>
            </div>
            <button class="login-button" onclick="window.location.href='logout.php'">Logout</button>
            <?php else: ?>
            <button class="login-button" onclick="window.location.href='login.php'">Login</button>
            <?php endif; ?>
        </div>
    </header>

    <nav class="nav-index">
        <div class="nav-content">
            <div class="dropdown" id="dropdownMenu">
                <button class="dropdown-btn" onclick="toggleDropdown()"></button>
                <div class="dropdown-content">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente'): ?>
                    <a href="perfil.php" class="menu-item home"><span class="icon">⚙️</span> Editar Perfil</a>
                    <a href="verPerfil.php" class="menu-item home"><span class="icon">👤</span> Perfil</a>
                    <a href="resposta_suporte.php" class="menu-item portfolio"><span class="icon">📜</span> Histórico de
                        Suporte</a>
                    <a href="suporte.php" class="menu-item blog"><span class="icon">📖</span> Suporte</a>
                    <a href="cupao.php" class="menu-item blog"><span class="icon">🏷️</span> Meus Cupões</a>
                    <a href="carteira.php" class="menu-item blog"><span class="icon">👛</span> Minha carteira</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin'): ?>
                    <a href="admin_verPerfil.php" class="menu-home"><span class="icon">👤</span> Perfil</a>
                    <a href="admin_panel.php" class="menu-admin"><span class="icon">🔧</span> Painel Admin</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <a href="#"
                        onclick="alert('É necessário estar logado para acessar o perfil. Você será redirecionado para o login.'); window.location.href='login.php';"
                        class="menu-item home"><span class="icon">👤</span> Editar Perfil</a>
                    <a href="#"
                        onclick="alert('É necessário estar logado para acessar o histórico de suporte. Você será redirecionado para o login.'); window.location.href='login.php';"
                        class="menu-item portfolio"><span class="icon">📜</span> Histórico de Suporte</a>
                    <a href="suporte.php" class="menu-item blog"><span class="icon">📖</span> Suporte</a>
                    <a href="#"
                        onclick="alert('É necessário estar logado para acessar os cupões. Você será redirecionado para o login.'); window.location.href='login.php';"
                        class="menu-item blog"><span class="icon">🏷️</span> Meus Cupões</a>
                    <a href="#"
                        onclick="alert('É necessário estar logado para acessar a carteira. Você será redirecionado para o login.'); window.location.href='login.php';"
                        class="menu-item blog"><span class="icon">👛</span> Minha carteira</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!isset($_SESSION['usuario_id']) || (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'cliente')): ?>
            <a href="comentarios.php">Comentários</a>
            <a href="registar.php">Criar Conta</a>
            <a href="feedback.php">Feedback</a>
            <a href="carrinho.php">Meu carrinho de compras</a>
            <a href="produtos.php">Produtos</a>
            <?php endif; ?>
            <a href="sobre.php">Sobre</a>

        </div>
    </nav>

    <main class="main-index"
        style="background: url('img/verduras.png') no-repeat center center; background-size: cover;">
        <section id="inicio">
            <h2>Bem-vindo ao Mercado Bom Preço</h2>
            <p>Explore uma ampla variedade de produtos de alta qualidade com preços acessíveis.<br>
                Navegue pelas categorias para encontrar exatamente o que procura!
            </p>
        </section>
    </main>

    <footer class="footer-index">
        <p>© 2024-2025 Mercado Bom Preço. Todos os direitos reservados.</p>
    </footer>

    <script>
    function toggleDropdown() {
        const dropdown = document.getElementById('dropdownMenu');
        const content = dropdown.querySelector('.dropdown-content');
        content.style.display = content.style.display === 'block' ? 'none' : 'block';
        dropdown.classList.toggle('active');
    }

    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('dropdownMenu');
        const content = dropdown.querySelector('.dropdown-content');
        if (!dropdown.contains(event.target)) {
            content.style.display = 'none';
            dropdown.classList.remove('active');
        }
    });
    </script>
</body>

</html>