<?php
// Obtém os dados enviados via JSON no corpo da requisição
$dados = json_decode(file_get_contents("php://input"), true);

// Verifica se o pet_id foi enviado nos dados
if (!isset($dados['pet_id'])) {
  // Verifica se o pet foi selecionado na sessão
  if (!isset($_SESSION['pet_id'])) {
    // Retorna um erro em JSON caso o pet não tenha sido selecionado
    echo json_encode(["mensagem" => "Pet não selecionado."]);
    exit(); // Interrompe a execução do script
  }
  $pet_id = (int)$_SESSION['pet_id'];
} else {
  $pet_id = (int)$dados['pet_id'];
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Prepara a consulta SQL para inserir a nova consulta
$stmt = $conn->prepare("INSERT INTO consultas (pet_id, data, descricao, status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $pet_id, $dados['data'], $dados['descricao'], $dados['status']);

// Executa a query
if ($stmt->execute()) {
  // Retorna uma mensagem de sucesso em JSON caso a consulta seja cadastrada com sucesso
  echo json_encode(["mensagem" => "Consulta cadastrada com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao cadastrar a consulta
  echo json_encode(["mensagem" => "Erro ao cadastrar consulta: " . $stmt->error]);
}

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
