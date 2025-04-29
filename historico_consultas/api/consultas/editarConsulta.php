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

// Monta a query SQL para atualizar os dados da consulta
$sql = "UPDATE consultas 
        SET data = '" . $conn->real_escape_string($dados['data']) . "', 
            descricao = '" . $conn->real_escape_string($dados['descricao']) . "', 
            status = '" . $conn->real_escape_string($dados['status']) . "' 
        WHERE id = " . (int)$dados['id'];

// Executa a query
if ($conn->query($sql)) {
  // Retorna uma mensagem de sucesso em JSON caso a atualização seja bem-sucedida
  echo json_encode(["mensagem" => "Consulta atualizada com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao atualizar a consulta
  echo json_encode(["mensagem" => "Erro ao atualizar consulta."]);
}

// Fecha a conexão com o banco de dados
$conn->close();
?>

