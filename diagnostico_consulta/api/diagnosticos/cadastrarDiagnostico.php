<?php
// Obtém os dados enviados via JSON no corpo da requisição
$dados = json_decode(file_get_contents("php://input"), true);

// Verifica se a consulta_id foi enviada nos dados
if (!isset($dados['consulta_id'])) {
  // Verifica se a consulta foi selecionada na sessão
  if (!isset($_SESSION['consulta_id'])) {
    // Retorna um erro em JSON caso a consulta não tenha sido selecionada
    echo json_encode(["mensagem" => "Consulta não selecionada."]);
    exit(); // Interrompe a execução do script
  }
  $consulta_id = (int)$_SESSION['consulta_id'];
} else {
  $consulta_id = (int)$dados['consulta_id'];
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Prepara a consulta SQL para inserir os dados na tabela 'diagnosticos'
$stmt = $conn->prepare("INSERT INTO diagnosticos (consulta_id, sintomas, exames, prescricao) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $consulta_id, $dados['sintomas'], $dados['exames'], $dados['prescricao']);

// Executa a query
if ($stmt->execute()) {
  // Retorna uma mensagem de sucesso em JSON
  echo json_encode(["mensagem" => "Diagnóstico salvo com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao salvar
  echo json_encode(["mensagem" => "Erro ao salvar diagnóstico: " . $stmt->error]);
}

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
