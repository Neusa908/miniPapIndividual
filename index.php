<?php

include('protect.php')

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
        <h1>Mercado Bom Preço</h1>
        <p>Onde o preço é bom!</p>
        <!-- Modificado para usar a classe 'login-button' -->
        <button class="login-button" onclick="window.location.href='login.php'">Login</button>
    </header>


    <nav class="nav-index">
        <a href="produtos.php">Produtos</a>
        <a href="sobre.php">Sobre</a>
        <a href="contato.php">Contato</a>
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
        <p>&copy; 2024-2025 Mercado Bom Preço. Todos os direitos reservados.</p>
    </footer>
</body>