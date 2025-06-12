<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mercado Bom Preço - Uma loja com produtos de alta qualidade e preços acessíveis.">
    <title>Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="index">
    <?php include_once 'header.php'; ?>

    <main class="main-index"
        style="background: url('img/verduras.png') no-repeat center center; background-size: cover;">
        <section id="inicio">
            <h2>Bem-vindo ao Mercado Bom Preço</h2>
            <p>Explore uma ampla gama de produtos de alta qualidade com preços acessíveis.<br>
                Navegue pelas categorias para encontrar exatamente o que procura!</p>
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