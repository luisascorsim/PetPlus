<?php
$dados = json_decode(file_get_contents("php://input"), true);
$conn = new mysqli("localhost", "root", "", "petplus");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Construir a consulta SQL
$sql = "UPDATE faturas SET 
            nome_tutor = '" . $conn->real_escape_string($dados["nome_tutor"]) . "', 
            nome_pet = '" . $conn->real_escape_string($dados["nome_pet"]) . "', 
            data_fatura = '" . $conn->real_escape_string($dados["data_fatura"]) . "', 
            servico = '" . $conn->real_escape_string($dados["servico"]) . "', 
            valor = '" . $conn->real_escape_string($dados["valor"]) . "', 
            pagamento = '" . $conn->real_escape_string($dados["pagamento"]) . "', 
            observacoes = '" . $conn->real_escape_string($dados["observacoes"]) . "', 
            clinica = '" . $conn->real_escape_string($dados["clinica"]) . "', 
            profissional = '" . $conn->real_escape_string($dados["profissional"]) . "' 
        WHERE id = " . intval($dados["id"]);

// Executar a consulta
if ($conn->query($sql) === TRUE) {
    echo json_encode(["mensagem" => "Fatura atualizada com sucesso!"]);
} else {
    echo json_encode(["mensagem" => "Erro ao atualizar fatura: " . $conn->error]);
}

$conn->close();
?>
