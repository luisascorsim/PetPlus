<?php
// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Verifica se o consulta_id foi passado via GET
if (isset($_GET['consulta_id'])) {
  $consulta_id = (int)$_GET['consulta_id']; // Garante que o consulta_id seja um número inteiro
} 
// Se não foi passado via GET, verifica se está na sessão
else if (isset($_SESSION['consulta_id'])) {
  $consulta_id = (int)$_SESSION['consulta_id']; // Garante que o consulta_id seja um número inteiro
} 
// Se não estiver em nenhum lugar, retorna um erro
else {
  // Retorna uma resposta vazia em JSON caso a consulta não tenha sido selecionada
  echo json_encode([]);
  exit(); // Interrompe a execução do script
}

// Prepara a consulta SQL para buscar os diagnósticos da consulta
$stmt = $conn->prepare("SELECT * FROM diagnosticos WHERE consulta_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $consulta_id);
$stmt->execute();
$result = $stmt->get_result();

// Cria um array para armazenar os diagnósticos
$diagnosticos = [];

// Itera sobre os resultados e os adiciona ao array
while ($row = $result->fetch_assoc()) {
  $diagnosticos[] = $row;
}

// Retorna os diagnósticos em formato JSON
echo json_encode($diagnosticos);

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
