<?php
// Inicia a sessão
session_start();

// Verifica se a consulta foi selecionada na sessão
if (!isset($_SESSION['consulta_id'])) {
  // Retorna um erro em JSON caso a consulta não tenha sido selecionada
  echo json_encode(["mensagem" => "Consulta não selecionada."]);
  exit(); // Interrompe a execução do script
}

// Obtém os dados enviados via JSON no corpo da requisição
$dados = json_decode(file_get_contents("php://input"), true);

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Prepara a query para inserir os dados na tabela 'diagnosticos'
$sql = "INSERT INTO diagnosticos (consulta_id, sintomas, exames, prescricao) 
        VALUES (" . (int)$_SESSION['consulta_id'] . ", '" . $conn->real_escape_string($dados['sintomas']) . "', 
        '" . $conn->real_escape_string($dados['exames']) . "', '" . $conn->real_escape_string($dados['prescricao']) . "')";

// Executa a query
if ($conn->query($sql)) {
  // Retorna uma mensagem de sucesso em JSON
  echo json_encode(["mensagem" => "Diagnóstico salvo com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao salvar
  echo json_encode(["mensagem" => "Erro ao salvar diagnóstico."]);
}

// Fecha a conexão com o banco de dados
$conn->close();
?>
