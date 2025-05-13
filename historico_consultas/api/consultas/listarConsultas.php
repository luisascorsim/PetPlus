<?php
// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Verifica se o pet_id foi passado via GET
if (isset($_GET['pet_id'])) {
  $pet_id = (int)$_GET['pet_id']; // Garante que o pet_id seja um número inteiro
} 
// Se não foi passado via GET, verifica se está na sessão
else if (isset($_SESSION['pet_id'])) {
  $pet_id = (int)$_SESSION['pet_id']; // Garante que o pet_id seja um número inteiro
} 
// Se não estiver em nenhum lugar, retorna um erro
else {
  // Retorna uma resposta vazia em JSON caso o pet não tenha sido selecionado
  echo json_encode([]);
  exit(); // Interrompe a execução do script
}

// Monta a query SQL para buscar as consultas com base no ID do pet
$sql = "SELECT * FROM consultas WHERE pet_id = ? ORDER BY data DESC";

// Prepara e executa a consulta
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pet_id);
$stmt->execute();
$result = $stmt->get_result();

// Cria um array para armazenar as consultas
$consultas = [];

// Itera sobre os resultados e os adiciona ao array
while ($row = $result->fetch_assoc()) {
  $consultas[] = $row;
}

// Retorna as consultas em formato JSON
echo json_encode($consultas);

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
