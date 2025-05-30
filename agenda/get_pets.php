<?php
// get_pets.php
require_once('../conecta_db.php');
$conn = conecta_db();

if (isset($_GET['id_tutor']) && is_numeric($_GET['id_tutor'])) {
    $id_tutor = $_GET['id_tutor'];
    
    $stmt = $conn->prepare("SELECT id_pet, nome FROM Pets WHERE id_tutor = ?");
    $stmt->bind_param("i", $id_tutor);
    $stmt->execute();
    $result = $stmt->get_result();

    $pets = [];
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }

    // Define o cabeçalho para indicar que a resposta é JSON
    header('Content-Type: application/json');
    echo json_encode($pets);
} else {
    // Retorna um array vazio se o id_tutor não for válido
    header('Content-Type: application/json');
    echo json_encode([]);
}
$conn->close(); // Fecha a conexão aqui também
?>