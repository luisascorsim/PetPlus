<?php
include 'C:\xampp\htdocs\PetPlus\conecta_db.php';
if (isset($_POST['nome']) && isset($_POST['email']) && isset($_POST['senha'])) {
    $oMysql = conecta_db();
    
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Sanitização das entradas para evitar ataques
    $nome = mysqli_real_escape_string($oMysql, $nome);
    $email = mysqli_real_escape_string($oMysql, $email);
    $senha = mysqli_real_escape_string($oMysql, $senha);

    // Criptografando a senha antes de armazenar
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $query = "INSERT INTO Cliente (nome, email, senha) 
              VALUES ('$nome', '$email', '$senhaHash')";
    
    if (mysqli_query($oMysql, $query)) {
        header('Location: index.php');
        exit();
    } else {
        echo "Erro: " . mysqli_error($oMysql);
    }

    mysqli_close($oMysql);
}
?>
