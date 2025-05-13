<?php
require_once '../includes/header.php';
require_once '../api/conexao.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obter parâmetros de filtro
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_veterinario = isset($_GET['veterinario']) ? $_GET['veterinario'] : '';
$filtro_cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';
$filtro_pet = isset($_GET['pet']) ? $_GET['pet'] : '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas - PetPlus</title>
    <link rel="stylesheet" href="../assets/css/consultas.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Gerenciamento de Consultas</h1>
        
        <div class="actions">
            <a href="agendar_consulta.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Consulta
            </a>
            <button id="btnFiltros" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filtros
            </button>
        </div>
        
        <div id="filtrosContainer" class="filtros-container" style="display: none;">
            <form id="formFiltros" method="GET">
                <div class="form-group">
                    <label for="data">Data:</label>
                    <input type="date" id="data" name="data" value="<?php echo $filtro_data; ?>">
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="agendada" <?php echo $filtro_status == 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                        <option value="confirmada" <?php echo $filtro_status == 'confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                        <option value="em_andamento" <?php echo $filtro_status == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="concluida" <?php echo $filtro_status == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                        <option value="cancelada" <?php echo $filtro_status == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="veterinario">Veterinário:</label>
                    <select id="veterinario" name="veterinario">
                        <option value="">Todos</option>
                        <?php
                        // Buscar veterinários
                        $sql_veterinarios = "SELECT id, nome FROM veterinarios ORDER BY nome";
                        $stmt_veterinarios = $conn->prepare($sql_veterinarios);
                        $stmt_veterinarios->execute();
                        
                        while ($veterinario = $stmt_veterinarios->fetch(PDO::FETCH_ASSOC)) {
                            $selected = $filtro_veterinario == $veterinario['id'] ? 'selected' : '';
                            echo "<option value='{$veterinario['id']}' $selected>{$veterinario['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cliente">Cliente:</label>
                    <select id="cliente" name="cliente">
                        <option value="">Todos</option>
                        <?php
                        // Buscar clientes
                        $sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome";
                        $stmt_clientes = $conn->prepare($sql_clientes);
                        $stmt_clientes->execute();
                        
                        while ($cliente = $stmt_clientes->fetch(PDO::FETCH_ASSOC)) {
                            $selected = $filtro_cliente == $cliente['id'] ? 'selected' : '';
                            echo "<option value='{$cliente['id']}' $selected>{$cliente['nome']}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pet">Pet:</label>
                    <select id="pet" name="pet">
                        <option value="">Todos</option>
                        <?php
                        // Buscar pets
                        $sql_pets = "SELECT p.id, p.nome, c.nome as dono FROM pets p 
                                     JOIN clientes c ON p.cliente_id = c.id 
                                     ORDER BY p.nome";
                        $stmt_pets = $conn->prepare($sql_pets);
                        $stmt_pets->execute();
                        
                        while ($pet = $stmt_pets->fetch(PDO::FETCH_ASSOC)) {
                            $selected = $filtro_pet == $pet['id'] ? 'selected' : '';
                            echo "<option value='{$pet['id']}' $selected>{$pet['nome']} ({$pet['dono']})</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    <button type="button" id="btnLimparFiltros" class="btn btn-secondary">Limpar Filtros</button>
                </div>
            </form>
        </div>
        
        <div class="consultas-container">
            <table class="consultas-table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Cliente</th>
                        <th>Pet</th>
                        <th>Veterinário</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="consultasTableBody">
                    <!-- Os dados das consultas serão carregados via JavaScript -->
                </tbody>
            </table>
            
            <div id="paginacao" class="paginacao">
                <!-- Paginação será gerada via JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div id="modalExcluir" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Confirmar Exclusão</h2>
            <p>Tem certeza que deseja excluir esta consulta?</p>
            <div class="modal-actions">
                <button id="btnConfirmarExclusao" class="btn btn-danger">Excluir</button>
                <button id="btnCancelarExclusao" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/consultas.js"></script>
</body>
</html>
