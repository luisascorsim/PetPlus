<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

try {
    // Total de Pets
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pets");
    $totalPets = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de Clientes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $totalClients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Consultas para hoje
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM consultas WHERE DATE(data_consulta) = :today");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $appointmentsToday = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Faturamento mensal
    $firstDayOfMonth = date('Y-m-01');
    $lastDayOfMonth = date('Y-m-t');
    
    $stmt = $pdo->prepare("SELECT SUM(valor) as total FROM faturas WHERE data_fatura BETWEEN :first AND :last");
    $stmt->bindParam(':first', $firstDayOfMonth);
    $stmt->bindParam(':last', $lastDayOfMonth);
    $stmt->execute();
    $monthlyRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Próximas consultas
    $stmt = $pdo->prepare("
        SELECT c.id, p.nome as pet_name, cl.nome as client_name, 
               DATE_FORMAT(c.data_consulta, '%d/%m/%Y') as date, 
               DATE_FORMAT(c.hora_consulta, '%H:%i') as time,
               c.status
        FROM consultas c
        JOIN pets p ON c.pet_id = p.id
        JOIN clientes cl ON p.cliente_id = cl.id
        WHERE c.data_consulta >= :today
        ORDER BY c.data_consulta, c.hora_consulta
        LIMIT 5
    ");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $upcomingAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pets recentes
    $stmt = $pdo->prepare("
        SELECT p.id, p.nome as name, p.tipo as type, p.raca as breed, 
               p.foto as image, cl.nome as owner_name
        FROM pets p
        JOIN clientes cl ON p.cliente_id = cl.id
        ORDER BY p.data_cadastro DESC
        LIMIT 4
    ");
    $stmt->execute();
    $recentPets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar dados
    echo json_encode([
        'totalPets' => $totalPets,
        'totalClients' => $totalClients,
        'appointmentsToday' => $appointmentsToday,
        'monthlyRevenue' => $monthlyRevenue,
        'upcomingAppointments' => $upcomingAppointments,
        'recentPets' => $recentPets
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
?>
