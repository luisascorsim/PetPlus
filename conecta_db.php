<?php
function conecta_db() {
    $db_name = "PetPlus";
    $user = "root";
    $pass = "";
    $server = "localhost:3306";
    
    $conexao = new mysqli($server, $user, $pass, $db_name);
    
    // Verifica erros de conexão
    if ($conexao->connect_error) {
        die("Erro de conexão: " . $conexao->connect_error);
    }
    
    return $conexao; 
}
?>