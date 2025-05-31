<?php
// get_pets.php
require_once('../conecta_db.php'); // Certifique-se que o caminho está correto

// Defina o tipo de conteúdo como JSON desde o início
header('Content-Type: application/json');

$conn = conecta_db();
$response = []; // Inicializa a resposta

if (!$conn) {
    http_response_code(500); // Erro interno do servidor
    $response = ['error' => 'Falha na conexão com o banco de dados.'];
    echo json_encode($response);
    exit;
}

if (isset($_GET['id_tutor']) && is_numeric($_GET['id_tutor'])) {
    $id_tutor = (int)$_GET['id_tutor'];

    $stmt = $conn->prepare("SELECT id_pet, nome FROM Pets WHERE id_tutor = ? ORDER BY nome");

    if ($stmt) {
        $stmt->bind_param("i", $id_tutor);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $pets_data = [];
            while ($row = $result->fetch_assoc()) {
                $pets_data[] = $row;
            }
            $response = $pets_data; 
        } else {
            http_response_code(500);
            error_log("Erro ao executar statement em get_pets.php: " . $stmt->error); // Log do erro no servidor
            $response = ['error' => 'Erro ao buscar pets no banco de dados.'];
        }
        $stmt->close();
    } else {
        http_response_code(500);
        error_log("Falha ao preparar statement em get_pets.php: " . $conn->error); // Log do erro no servidor
        $response = ['error' => 'Erro ao preparar a consulta de pets.'];
    }
} else {
    http_response_code(400); // Requisição inválida
    $response = ['error' => 'ID do tutor inválido ou não fornecido.'];
}

echo json_encode($response);
if ($conn) { 
    $conn->close();
}
?>