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

// Monta a query SQL para inserir a vacina
$sql = "INSERT INTO vacinas (pet_id, nome, data, lote, reforco) 
        VALUES (" . (int)$_SESSION['pet_id'] . ", 
                '" . $conn->real_escape_string($dados['nome']) . "', 
                '" . $conn->real_escape_string($dados['data']) . "', 
                '" . $conn->real_escape_string($dados['lote']) . "', 
                '" . $conn->real_escape_string($dados['reforco']) . "')";

// Executa a query
if ($conn->query($sql)) {
  // Retorna uma mensagem de sucesso em JSON caso a vacina seja cadastrada com sucesso
  echo json_encode(["mensagem" => "Vacina cadastrada com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao cadastrar a vacina
  echo json_encode(["mensagem" => "Erro ao cadastrar vacina."]);
}

// Fecha a conexão com o banco de dados
$conn->close();
?>
