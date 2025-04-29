<?php
// Obtém os dados enviados via JSON no corpo da requisição
$dados = json_decode(file_get_contents("php://input"), true);

// Verifica se o ID do diagnóstico foi informado
if (!isset($dados['id'])) {
  // Retorna um erro em JSON caso o ID do diagnóstico não tenha sido informado
  echo json_encode(["mensagem" => "ID do diagnóstico não informado."]);
  exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include '../conexao.php';

// Monta a query SQL para atualizar o diagnóstico
$sql = "UPDATE diagnosticos 
        SET sintomas = '" . $conn->real_escape_string($dados['sintomas']) . "', 
            exames = '" . $conn->real_escape_string($dados['exames']) . "', 
            prescricao = '" . $conn->real_escape_string($dados['prescricao']) . "' 
        WHERE id = " . (int)$dados['id'];

// Executa a query
if ($conn->query($sql)) {
  // Retorna uma mensagem de sucesso em JSON se a atualização for bem-sucedida
  echo json_encode(["mensagem" => "Diagnóstico atualizado com sucesso!"]);
} else {
  // Retorna uma mensagem de erro em JSON caso falhe ao atualizar
  echo json_encode(["mensagem" => "Erro ao atualizar diagnóstico."]);
}

// Fecha a conexão com o banco de dados
$conn->close();
?>

