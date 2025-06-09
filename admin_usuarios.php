<?php
   // Inicia a sessão apenas se ainda não estiver ativa
   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   require 'conexao.php';

   // Verifica se o usuário é administrador
   if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
       echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
       exit();
   }

   // Busca clientes
   $sql_usuarios = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente'";
   $result_usuarios = $conn->query($sql_usuarios);
   ?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-panel-body">
    <div class="admin-panel-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Mercado Bom Preço</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_panel.php" class="nav-item"><span class="icon">⬅️</span> Voltar ao Painel</a>
            </nav>
        </div>

        <div class="main-content">
            <header class="admin-header">
                <h1>Gestão de Usuários</h1>
            </header>
            <div class="users-container">
                <?php if ($result_usuarios->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($usuario = $result_usuarios->fetch_assoc()): ?>
                        <tr class="user-row">
                            <td><?php echo $usuario['id']; ?></td>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <a href="admin_editarUsuarios.php?id=<?php echo $usuario['id']; ?>"
                                    class="user-action edit">Editar</a>
                                <a href="admin_excluirUsuarios.php?id=<?php echo $usuario['id']; ?>"
                                    class="user-action delete"
                                    onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-users">Nenhum usuário encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php
   $conn->close();
   ?>