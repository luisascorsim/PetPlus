<?php
// Verifica se o ID foi passado na URL
if (!isset($_GET['id'])) {
  // Retorna um erro em JSON caso o ID não tenha sido informado
  echo json_encode(["mensagem" => "ID não informado."]);
  exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Obtém o ID diretamente da URL e garante que ele seja tratado como inteiro
$id = (int)$_GET['id']; // Garante que o ID seja um número inteiro

// Prepara a consulta SQL para excluir o registro de peso
$stmt = $conn->prepare("DELETE FROM pesos WHERE id = ?");
$stmt->bind_param("i", $id);

// Executa a query
if ($stmt->execute()) {
  // Retorna uma mensagem de sucesso em JSON caso a exclusão seja bem-sucedida
  echo json_encode(["mensagem" => "Registro de peso excluído com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao excluir
  echo json_encode(["mensagem" => "Erro ao excluir registro de peso: " . $stmt->error]);
}

// Fecha a conexão com o banco de dados
$stmt->close();
$conn->close();
?>
