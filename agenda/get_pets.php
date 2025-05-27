<?php
require_once('../conecta_db.php');
$conn = conecta_db();

$id_tutor = intval($_GET['id_tutor']);
$sql = "SELECT id_pet, nome FROM Pets WHERE id_tutor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_tutor);
$stmt->execute();
$result = $stmt->get_result();

$pets = [];
while ($row = $result->fetch_assoc()) {
    $pets[] = $row;
}

echo json_encode($pets);