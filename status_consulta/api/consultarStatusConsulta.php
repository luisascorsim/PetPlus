<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: application/json");

$dadosRecebidos = json_decode(file_get_contents("php://input"), true);

if (!isset($dadosRecebidos['consulta_id'])) {
  echo json_encode(["sucesso" => false, "mensagem" => "ID da consulta não informado."]);
  exit();
}

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "petplus";

$conn = mysqli_connect($host, $usuario, $senha, $banco);

if (!$conn) {
  echo json_encode(["sucesso" => false, "mensagem" => "Erro ao conectar ao banco de dados."]);
  exit();
}

$consulta_id = (int)$dadosRecebidos['consulta_id'];

$sql = "SELECT status FROM consultas WHERE id = $consulta_id";
$resultado = mysqli_query($conn, $sql);

if ($resultado && mysqli_num_rows($resultado) > 0) {
  $linha = mysqli_fetch_assoc($resultado);
  echo json_encode(["sucesso" => true, "status" => $linha['status']]);
} else {
  echo json_encode(["sucesso" => false, "mensagem" => "Consulta não encontrada."]);
}

mysqli_close($conn);
?>
