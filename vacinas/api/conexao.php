<?php
// Arquivo de conexão com o banco de dados para as APIs
$host = "localhost";
$usuario = "root";
$senha = "PUC@1234";
$banco = "PetPlus";

$conn = new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
