<?php
///print_r($_REQUEST);
if(isset($_POST['submit']) && !empty($_POST['email']) && !empty($_POST['senha']))
{
//acessa
include_once('conexao.php');
$email = $_POST['email'];
$senha = $_POST['senha'];

//print_r('email: ' . $email);
//print_r('<br>');
//print_r('senha: ' . $senha);

$sql = "SELECT * FROM utilizadores WHERE email = '$email' and senha = '$senha'";

$result = $conexao->query($sql);

print_r($result);

}
else
{
    //nao acessa
    header('Location: login.php');
}
?>