<?php
$conn = new mysqli("localhost", "root", "", "petplus");

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Construir a consulta SQL
$sql = "SELECT * FROM faturas";

// Executar a consulta
$resultado = $conn->query($sql);

// Verificar se há resultados
if ($resultado->num_rows > 0) {
    $faturas = [];
    // Buscar os dados de cada linha e adicionar ao array
    while ($linha = $resultado->fetch_assoc()) {
        $faturas[] = $linha;
    }
    // Retornar as faturas como JSON
    echo json_encode($faturas);
} else {
    echo json_encode(["mensagem" => "Nenhuma fatura encontrada."]);
}

$conn->close();
?>
