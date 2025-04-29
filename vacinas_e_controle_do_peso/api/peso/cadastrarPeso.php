<?php
// Inicia a sessão
session_start();

// Verifica se o pet_id foi selecionado na sessão
if (!isset($_SESSION['pet_id'])) {
  // Retorna um erro em JSON caso o pet não tenha sido selecionado
  echo json_encode(["mensagem" => "Pet não selecionado."]);
  exit(); // Interrompe a execução do script
}

// Obtém os dados enviados via JSON no corpo da requisição
$dados = json_decode(file_get_contents("php://input"), true);

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Monta a query SQL para inserir o peso do pet
$sql = "INSERT INTO pesos (pet_id, data, peso) 
        VALUES (" . (int)$_SESSION['pet_id'] . ", 
                '" . $conn->real_escape_string($dados['data']) . "', 
                " . (float)$dados['peso'] . ")"; // Converte o peso para float

// Executa a query
if ($conn->query($sql)) {
  // Retorna uma mensagem de sucesso em JSON caso o peso seja registrado com sucesso
  echo json_encode(["mensagem" => "Peso registrado com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao registrar o peso
  echo json_encode(["mensagem" => "Erro ao registrar peso."]);
}

// Fecha a conexão com o banco de dados
$conn->close();
?>
