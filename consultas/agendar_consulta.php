<?php
require_once '../config/database.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obter lista de veterinários
$sql_veterinarios = "SELECT id, nome FROM veterinarios ORDER BY nome";
$stmt_veterinarios = $conn->prepare($sql_veterinarios);
$stmt_veterinarios->execute();
$veterinarios = $stmt_veterinarios->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de clientes
$sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome";
$stmt_clientes = $conn->prepare($sql_clientes);
$stmt_clientes->execute();
$clientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de pets
$sql_pets = "SELECT id, nome, cliente_id FROM pets ORDER BY nome";
$stmt_pets = $conn->prepare($sql_pets);
$stmt_pets->execute();
$pets = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);

// Obter lista de serviços
$sql_servicos = "SELECT id, nome, preco FROM servicos ORDER BY nome";
$stmt_servicos = $conn->prepare($sql_servicos);
$stmt_servicos->execute();
$servicos = $stmt_servicos->fetchAll(PDO::FETCH_ASSOC);

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = $_POST['cliente_id'];
    $pet_id = $_POST['pet_id'];
    $veterinario_id = $_POST['veterinario_id'];
    $servico_id = $_POST['servico_id'];
    $data_consulta = $_POST['data_consulta'];
    $hora_consulta = $_POST['hora_consulta'];
    $observacoes = $_POST['observacoes'];
    $status = 'Agendada';
    
    try {
        $sql = "INSERT INTO consultas (cliente_id, pet_id, veterinario_id, servico_id, data_consulta, hora_consulta, observacoes, status, data_criacao) 
                VALUES (:cliente_id, :pet_id, :veterinario_id, :servico_id, :data_consulta, :hora_consulta, :observacoes, :status, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->bindParam(':pet_id', $pet_id);
        $stmt->bindParam(':veterinario_id', $veterinario_id);
        $stmt->bindParam(':servico_id', $servico_id);
        $stmt->bindParam(':data_consulta', $data_consulta);
        $stmt->bindParam(':hora_consulta', $hora_consulta);
        $stmt->bindParam(':observacoes', $observacoes);
        $stmt->bindParam(':status', $status);
        
        $stmt->execute();
        
        $mensagem = "Consulta agendada com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (PDOException $e) {
        $mensagem = "Erro ao agendar consulta: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

$titulo_pagina = "Agendar Consulta";
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Agendar Nova Consulta</h1>
            </div>
            
            <?php if (isset($mensagem)): ?>
                <div class="alert alert-<?php echo $tipo_mensagem === 'sucesso' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label">Cliente</label>
                                <select class="form-select" id="cliente_id" name="cliente_id" required>
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="pet_id" class="form-label">Pet</label>
                                <select class="form-select" id="pet_id" name="pet_id" required>
                                    <option value="">Selecione um pet</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="veterinario_id" class="form-label">Veterinário</label>
                                <select class="form-select" id="veterinario_id" name="veterinario_id" required>
                                    <option value="">Selecione um veterinário</option>
                                    <?php foreach ($veterinarios as $veterinario): ?>
                                        <option value="<?php echo $veterinario['id']; ?>"><?php echo $veterinario['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="servico_id" class="form-label">Serviço</label>
                                <select class="form-select" id="servico_id" name="servico_id" required>
                                    <option value="">Selecione um serviço</option>
                                    <?php foreach ($servicos as $servico): ?>
                                        <option value="<?php echo $servico['id']; ?>"><?php echo $servico['nome']; ?> - R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="data_consulta" class="form-label">Data da Consulta</label>
                                <input type="date" class="form-control" id="data_consulta" name="data_consulta" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="hora_consulta" class="form-label">Hora da Consulta</label>
                                <input type="time" class="form-control" id="hora_consulta" name="hora_consulta" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="consultas.php" class="btn btn-secondary me-md-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Agendar Consulta</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clienteSelect = document.getElementById('cliente_id');
    const petSelect = document.getElementById('pet_id');
    const petsData = <?php echo json_encode($pets); ?>;
    
    // Função para filtrar pets por cliente
    function filtrarPetsPorCliente(clienteId) {
        petSelect.innerHTML = '<option value="">Selecione um pet</option>';
        
        const petsFiltrados = petsData.filter(pet => pet.cliente_id == clienteId);
        
        petsFiltrados.forEach(pet => {
            const option = document.createElement('option');
            option.value = pet.id;
            option.textContent = pet.nome;
            petSelect.appendChild(option);
        });
    }
    
    // Evento de mudança no select de cliente
    clienteSelect.addEventListener('change', function() {
        const clienteId = this.value;
        if (clienteId) {
            filtrarPetsPorCliente(clienteId);
        } else {
            petSelect.innerHTML = '<option value="">Selecione um pet</option>';
        }
    });
});
</script>

<?php include_once '../includes/footer.php'; ?>
