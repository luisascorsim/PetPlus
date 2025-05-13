<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Obter informações do usuário
$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$usuario_tipo = $_SESSION['usuario_tipo'];

// Obter estatísticas básicas
try {
    // Total de pets
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pets");
    $stmt->execute();
    $total_pets = $stmt->fetch()['total'];
    
    // Total de clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clientes");
    $stmt->execute();
    $total_clientes = $stmt->fetch()['total'];
    
    // Total de consultas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM consultas");
    $stmt->execute();
    $total_consultas = $stmt->fetch()['total'];
    
    // Consultas recentes
    $stmt = $pdo->prepare("
        SELECT c.id, c.data_hora, c.status, p.nome as pet_nome, cl.nome as cliente_nome
        FROM consultas c
        JOIN pets p ON c.pet_id = p.id
        JOIN clientes cl ON p.cliente_id = cl.id
        ORDER BY c.data_hora DESC
        LIMIT 5
    ");
    $stmt->execute();
    $consultas_recentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PetPlus</title>
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <header class="dashboard-header">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Pesquisar...">
                </div>
                <div class="header-user">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($usuario_nome); ?></span>
                        <img src="assets/images/user.png" alt="Usuário">
                    </div>
                </div>
            </header>
            
            <div class="dashboard-welcome">
                <h1>Bem-vindo, <?php echo htmlspecialchars($usuario_nome); ?>!</h1>
                <p>Confira as estatísticas e atividades recentes do sistema.</p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Pets</h3>
                        <p><?php echo $total_pets ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Clientes</h3>
                        <p><?php echo $total_clientes ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Consultas</h3>
                        <p><?php echo $total_consultas ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Vacinas Aplicadas</h3>
                        <p>87</p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-recent">
                <div class="recent-appointments">
                    <div class="section-header">
                        <h2>Consultas Recentes</h2>
                        <a href="historico_consultas/historico-consultas.php" class="view-all">Ver todas</a>
                    </div>
                    <div class="appointments-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pet</th>
                                    <th>Cliente</th>
                                    <th>Data/Hora</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($consultas_recentes) && !empty($consultas_recentes)): ?>
                                    <?php foreach ($consultas_recentes as $consulta): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($consulta['pet_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($consulta['cliente_nome']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($consulta['data_hora'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($consulta['status']); ?>">
                                                    <?php echo htmlspecialchars($consulta['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="no-data">Nenhuma consulta recente encontrada.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <div class="section-header">
                        <h2>Ações Rápidas</h2>
                    </div>
                    <div class="actions-grid">
                        <a href="cadastrar_pets.php" class="action-card">
                            <i class="fas fa-paw"></i>
                            <span>Cadastrar Pet</span>
                        </a>
                        <a href="agenda/agenda.php" class="action-card">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Agendar Consulta</span>
                        </a>
                        <a href="clientes/clientes.php" class="action-card">
                            <i class="fas fa-user-plus"></i>
                            <span>Novo Cliente</span>
                        </a>
                        <a href="vacinas_e_controle_do_peso/vacinas-peso.php" class="action-card">
                            <i class="fas fa-syringe"></i>
                            <span>Registrar Vacina</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>
