<?php
// Arquivo para buscar pets de um tutor específico
require_once('../conecta_db.php');

// Verificar se o ID do tutor foi fornecido
if (!isset($_GET['id_tutor']) || empty($_GET['id_tutor'])) {
    echo json_encode([]);
    exit;
}

$id_tutor = (int)$_GET['id_tutor'];
$conn = conecta_db();

// Buscar pets do tutor
$sql = "SELECT id_pet, nome FROM Pets WHERE id_tutor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_tutor);
$stmt->execute();
$result = $stmt->get_result();

$pets = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
}

// Retornar como JSON
header('Content-Type: application/json');
echo json_encode($pets);

$conn->close();
?>
