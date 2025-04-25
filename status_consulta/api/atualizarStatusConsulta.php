<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

$dadosRecebidos = json_decode(file_get_contents("php://input"), true);

if (!isset($dadosRecebidos['consulta_id']) || !isset($dadosRecebidos['status'])) {
  echo json_encode(["sucesso" => false, "mensagem" => "Dados incompletos."]);
  exit();
}

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "petplus";

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
  echo json_encode(["sucesso" => false, "mensagem" => "Erro na conexão com o banco de dados."]);
  exit();
}

$consulta_id = (int)$dadosRecebidos['consulta_id'];
$status = mysqli_real_escape_string($conn, $dadosRecebidos['status']);

$sql = "UPDATE consultas SET status = '$status' WHERE id = $consulta_id";

if (mysqli_query($conn, $sql)) {
  echo json_encode(["sucesso" => true]);
} else {
  echo json_encode([
    "sucesso" => false,
    "mensagem" => "Erro ao atualizar: " . mysqli_error($conn)
  ]);
}

mysqli_close($conn);
?>
