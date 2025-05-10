<php? 
if(issets($_POST['email']) && !empty($_POST['email']) && issets($_POST['senha']) && !empty($_POST['senha'])){

    require 'conexao.php';

     $login=addslashes($_POST['email']); 
     $senha=addslashes($_POST['senha']); 
    
    }else{

    header("Location: login.php"); 
} 

?>