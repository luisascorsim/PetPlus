<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../conecta_db.php'); // Ajuste o caminho se necessário
$conn = conecta_db();

$response = ['error' => 'Agendamento não encontrado ou ID não fornecido.'];
http_response_code(404); // Default para não encontrado

if (isset($_GET['id_agendamento'])) {
    $id_agendamento = (int)$_GET['id_agendamento'];
    
    $stmt = $conn->prepare("SELECT id_pet, id_tutor, id_servico, data_hora, observacoes FROM Agendamentos WHERE id_agendamento = ?");
    $stmt->bind_param("i", $id_agendamento);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($agendamento = $result->fetch_assoc()) {
        $dateTime = new DateTime($agendamento['data_hora']);
        // Retorna os dados necessários para preencher o formulário
        $response = [
            'id_pet' => $agendamento['id_pet'],
            'id_tutor' => $agendamento['id_tutor'],
            'id_servico' => $agendamento['id_servico'],
            'data' => $dateTime->format('Y-m-d'),
            'hora' => $dateTime->format('H:i'),
            'observacoes' => $agendamento['observacoes']
        ];
        http_response_code(200); // OK
    } else {
        $response = ['error' => 'Detalhes do agendamento não encontrados para o ID: ' . $id_agendamento];
        http_response_code(404);
    }
    $stmt->close();
} else {
    http_response_code(400); // Bad request se id_agendamento não estiver presente
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>