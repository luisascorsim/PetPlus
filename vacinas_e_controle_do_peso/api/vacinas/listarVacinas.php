<?php
// Inicia a sessão
session_start();

// Verifica se o pet_id foi selecionado na sessão
if (!isset($_SESSION['pet_id'])) {
  // Retorna uma resposta vazia em JSON caso o pet não tenha sido selecionado
  echo json_encode([]);
  exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Obtém o ID do pet da sessão
$pet_id = (int)$_SESSION['pet_id']; // Garante que o pet_id seja um número inteiro

// Monta a query SQL para buscar as vacinas com base no ID do pet
$sql = "SELECT * FROM vacinas WHERE pet_id = $pet_id ORDER BY data DESC";

// Executa a query
$result = $conn->query($sql);

// Cria um array para armazenar as vacinas
$vacinas = [];

// Itera sobre os resultados e os adiciona ao array
while ($row = $result->fetch_assoc()) {
  $vacinas[] = $row;
}

// Retorna as vacinas em formato JSON
echo json_encode($vacinas);

// Fecha a conexão com o banco de dados
$conn->close();
?>
