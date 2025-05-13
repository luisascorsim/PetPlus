<?php
// Obtém os dados enviados via JSON no corpo da requisição
$dados = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID da consulta foi informado
if (!isset($dados['id'])) {
  // Retorna um erro em JSON caso o ID da consulta não tenha sido informado
  echo json_encode(["mensagem" => "ID da consulta não informado."]);
  exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Prepara a consulta SQL para atualizar os dados da consulta
$stmt = $conn->prepare("UPDATE consultas SET data = ?, descricao = ?, status = ? WHERE id = ?");
$stmt->bind_param("sssi", $dados['data'], $dados['descricao'], $dados['status'], $dados['id']);

// Executa a query
if ($stmt->execute()) {
  // Retorna uma mensagem de sucesso em JSON caso a atualização seja bem-sucedida
  echo json_encode(["mensagem" => "Consulta atualizada com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao atualizar a consulta
  echo json_encode(["mensagem" => "Erro ao atualizar consulta: " . $stmt->error]);
}

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
