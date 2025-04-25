<?php
// Inicia a sessão
session_start();

// Verifica se a consulta foi selecionada na sessão
if (!isset($_SESSION['consulta_id'])) {
  // Retorna uma resposta vazia em JSON caso a consulta não tenha sido selecionada
  echo json_encode([]);
  exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Obtém o ID da consulta da sessão
$consulta_id = (int)$_SESSION['consulta_id']; // Garante que o ID seja um inteiro

// Monta a query SQL para buscar os diagnósticos com base no ID da consulta
$sql = "SELECT * FROM diagnosticos WHERE consulta_id = $consulta_id ORDER BY id DESC";

// Executa a query
$result = $conn->query($sql);

// Cria um array para armazenar os diagnósticos
$diagnosticos = [];

// Itera sobre os resultados e os adiciona ao array
while ($row = $result->fetch_assoc()) {
  $diagnosticos[] = $row;
}

// Retorna os diagnósticos em formato JSON
echo json_encode($diagnosticos);

// Fecha a conexão com o banco de dados
$conn->close();
?>

