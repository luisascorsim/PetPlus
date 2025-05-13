<?php
// Verifica se o ID foi passado na URL
if (!isset($_GET['id'])) {
  // Retorna um erro em JSON caso o ID não tenha sido informado
  echo json_encode(["mensagem" => "ID não informado."]);
  exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Obtém o ID diretamente da URL e garante que ele seja tratado como inteiro
$id = (int)$_GET['id']; // Garante que o ID seja um número inteiro

// Prepara a consulta SQL para buscar a consulta específica
$stmt = $conn->prepare("SELECT * FROM consultas WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou a consulta
if ($result->num_rows > 0) {
  // Retorna os dados da consulta em formato JSON
  echo json_encode($result->fetch_assoc());
} else {
  // Retorna uma mensagem de erro em JSON caso a consulta não seja encontrada
  echo json_encode(["mensagem" => "Consulta não encontrada."]);
}

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
