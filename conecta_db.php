<?php
/**
 * Função para conectar ao banco de dados
 * 
 * @return mysqli Conexão com o banco de dados
 */
function conecta_db() {
    $db_name = "petplus";
    $user = "root";
    $pass = "";
    $server = "localhost";
    
    $conexao = new mysqli($server, $user, $pass, $db_name);
    
    // Verifica erros de conexão
    if ($conexao->connect_error) {
        die("Erro de conexão: " . $conexao->connect_error);
    }
    
    // Define o charset para utf8
    $conexao->set_charset("utf8");
    
    return $conexao; 
}

