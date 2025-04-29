<?php
if (!isset($_GET["id"])) {
    echo json_encode(["mensagem" => "ID da fatura não informado."]);
    exit;
}

$id = $_GET["id"];
$conn = new mysqli("localhost", "root", "", "petplus");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Construir a consulta SQL
$sql = "DELETE FROM faturas WHERE id = " . intval($id);

// Executar a consulta
if ($conn->query($sql) === TRUE) {
    echo json_encode(["mensagem" => "Fatura excluída com sucesso!"]);
} else {
    echo json_encode(["mensagem" => "Erro ao excluir fatura: " . $conn->error]);
}

$conn->close();
?>
