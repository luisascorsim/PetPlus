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
$id = (int)$_GET['id'];

// Prepara a query SQL para excluir o diagnóstico com base no ID
$sql = "DELETE FROM diagnosticos WHERE id = ?";

// Prepara a declaração
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["mensagem" => "Erro ao preparar a consulta: " . $conn->error]);
    exit();
}

// Vincula o parâmetro
$stmt->bind_param("i", $id);

// Executa a query
if ($stmt->execute()) {
    // Retorna uma mensagem de sucesso em JSON se a exclusão for bem-sucedida
    echo json_encode(["mensagem" => "Diagnóstico excluído com sucesso!"]);
} else {
    // Retorna uma mensagem de erro em JSON caso falhe ao excluir
    echo json_encode(["mensagem" => "Erro ao excluir diagnóstico: " . $stmt->error]);
}

// Fecha a declaração e a conexão
$stmt->close();
$conn->close();
?>
