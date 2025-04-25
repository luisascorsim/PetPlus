<?php
$dados = json_decode(file_get_contents("php://input"), true);
$conn = new mysqli("localhost", "root", "", "petplus");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Construir a consulta SQL
$sql = "INSERT INTO faturas (nome_tutor, nome_pet, data_fatura, servico, valor, pagamento, observacoes, clinica, profissional) 
        VALUES ('" . $conn->real_escape_string($dados["nome_tutor"]) . "', 
                '" . $conn->real_escape_string($dados["nome_pet"]) . "', 
                '" . $conn->real_escape_string($dados["data_fatura"]) . "', 
                '" . $conn->real_escape_string($dados["servico"]) . "', 
                '" . $conn->real_escape_string($dados["valor"]) . "', 
                '" . $conn->real_escape_string($dados["pagamento"]) . "', 
                '" . $conn->real_escape_string($dados["observacoes"]) . "', 
                '" . $conn->real_escape_string($dados["clinica"]) . "', 
                '" . $conn->real_escape_string($dados["profissional"]) . "')";

// Executar a consulta
if ($conn->query($sql) === TRUE) {
    echo json_encode(["mensagem" => "Fatura cadastrada com sucesso!"]);
} else {
    echo json_encode(["mensagem" => "Erro ao cadastrar fatura: " . $conn->error]);
}

$conn->close();
?>
