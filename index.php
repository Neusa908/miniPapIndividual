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
            <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
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
                    <a href="perfil.php" class="menu-item home"><span class="icon">👤</span> Perfil</a>
                    <a href="resposta_suporte.php" class="menu-item portfolio"><span class="icon">📜</span> Histórico
                        de Suporte</a>
                    <?php if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin'): ?>
                    <a href="admin_panel.php" class="menu-item admin"><span class="icon">🔧</span> Painel Admin</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <a href="#"
                        onclick="alert('É necessário estar logado para acessar o perfil. Você será redirecionado para o login.'); window.location.href='login.php';"
                        class="menu-item home"><span class="icon">👤</span> Perfil</a>
                    <a href="#"
                        onclick="alert('É necessário estar logado para acessar o histórico de suporte. Você será redirecionado para o login.'); window.location.href='login.php';"
                        class="menu-item portfolio"><span class="icon">📜</span> Histórico de Suporte</a>
                    <?php endif; ?>
                    <a href="suporte.php" class="menu-item blog"><span class="icon">📖</span> Suporte</a>
                </div>
            </div>
        </div>
        <a href="produtos.php">Produtos</a>
        <a href="sobre.php">Sobre</a>
        <a href="registar.php">Criar Conta</a>
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