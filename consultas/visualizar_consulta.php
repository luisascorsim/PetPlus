<?php
require_once '../config/database.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

// Verificar se o ID da consulta foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: consultas.php');
    exit;
}

$consulta_id = $_GET['id'];

try {
    // Obter detalhes da consulta
    $sql = "SELECT c.*, 
            cl.nome AS cliente_nome, 
            p.nome AS pet_nome, 
            p.especie AS pet_especie, 
            p.raca AS pet_raca,
            v.nome AS veterinario_nome,
            s.nome AS servico_nome,
            s.preco AS servico_preco
            FROM consultas c
            JOIN clientes cl ON c.cliente_id = cl.id
            JOIN pets p ON c.pet_id = p.id
            JOIN veterinarios v ON c.veterinario_id = v.id
            JOIN servicos s ON c.servico_id = s.id
            WHERE c.id = :id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $consulta_id);
    $stmt->execute();
    
    $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consulta) {
        header('Location: consultas.php');
        exit;
    }
    
    // Obter histórico de diagnósticos
    $sql_diagnosticos = "SELECT * FROM diagnosticos WHERE consulta_id = :consulta_id ORDER BY data_criacao DESC";
    $stmt_diagnosticos = $conn->prepare($sql_diagnosticos);
    $stmt_diagnosticos->bindParam(':consulta_id', $consulta_id);
    $stmt_diagnosticos->execute();
    $diagnosticos = $stmt_diagnosticos->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensagem = "Erro ao obter detalhes da consulta: " . $e->getMessage();
    $tipo_mensagem = "erro";
}

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $novo_status = $_POST['status'];
    
    try {
        $sql_update = "UPDATE consultas SET status = :status WHERE id = :id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':status', $novo_status);
        $stmt_update->bindParam(':id', $consulta_id);
        $stmt_update->execute();
        
        // Atualizar a consulta na variável
        $consulta['status'] = $novo_status;
        
        $mensagem = "Status da consulta atualizado com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (PDOException $e) {
        $mensagem = "Erro ao atualizar status: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Processar adição de diagnóstico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_diagnostico'])) {
    $descricao = $_POST['descricao'];
    $tratamento = $_POST['tratamento'];
    $medicamentos = $_POST['medicamentos'];
    
    try {
        $sql_diagnostico = "INSERT INTO diagnosticos (consulta_id, descricao, tratamento, medicamentos, data_criacao) 
                           VALUES (:consulta_id, :descricao, :tratamento, :medicamentos, NOW())";
        
        $stmt_diagnostico = $conn->prepare($sql_diagnostico);
        $stmt_diagnostico->bindParam(':consulta_id', $consulta_id);
        $stmt_diagnostico->bindParam(':descricao', $descricao);
        $stmt_diagnostico->bindParam(':tratamento', $tratamento);
        $stmt_diagnostico->bindParam(':medicamentos', $medicamentos);
        $stmt_diagnostico->execute();
        
        // Atualizar a lista de diagnósticos
        $stmt_diagnosticos->execute();
        $diagnosticos = $stmt_diagnosticos->fetchAll(PDO::FETCH_ASSOC);
        
        $mensagem = "Diagnóstico adicionado com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (PDOException $e) {
        $mensagem = "Erro ao adicionar diagnóstico: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

$titulo_pagina = "Detalhes da Consulta";
include_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Detalhes da Consulta</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="consultas.php" class="btn btn-sm btn-outline-secondary">Voltar</a>
                        <a href="editar_consulta.php?id=<?php echo $consulta_id; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($mensagem)): ?>
                <div class="alert alert-<?php echo $tipo_mensagem === 'sucesso' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações da Consulta</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span class="badge bg-<?php 
                                    switch($consulta['status']) {
                                        case 'Agendada': echo 'primary'; break;
                                        case 'Em Andamento': echo 'warning'; break;
                                        case 'Concluída': echo 'success'; break;
                                        case 'Cancelada': echo 'danger'; break;
                                        default: echo 'secondary';
                                    }
                                ?>"><?php echo $consulta['status']; ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Hora:</strong> <?php echo $consulta['hora_consulta']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Serviço:</strong> <?php echo $consulta['servico_nome']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Preço:</strong> R$ <?php echo number_format($consulta['servico_preco'], 2, ',', '.'); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Veterinário:</strong> <?php echo $consulta['veterinario_nome']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Observações:</strong>
                                <p><?php echo nl2br($consulta['observacoes'] ?: 'Nenhuma observação registrada.'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Atualizar Status</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status da Consulta</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Agendada" <?php echo $consulta['status'] === 'Agendada' ? 'selected' : ''; ?>>Agendada</option>
                                        <option value="Em Andamento" <?php echo $consulta['status'] === 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                        <option value="Concluída" <?php echo $consulta['status'] === 'Concluída' ? 'selected' : ''; ?>>Concluída</option>
                                        <option value="Cancelada" <?php echo $consulta['status'] === 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                </div>
                                <button type="submit" name="atualizar_status" class="btn btn-primary">Atualizar Status</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Cliente e Pet</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Cliente:</strong> <?php echo $consulta['cliente_nome']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Pet:</strong> <?php echo $consulta['pet_nome']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Espécie:</strong> <?php echo $consulta['pet_especie']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Raça:</strong> <?php echo $consulta['pet_raca']; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Diagnósticos</h5>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalDiagnostico">
                                Adicionar Diagnóstico
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($diagnosticos)): ?>
                                <p class="text-muted">Nenhum diagnóstico registrado.</p>
                            <?php else: ?>
                                <div class="accordion" id="accordionDiagnosticos">
                                    <?php foreach ($diagnosticos as $index => $diagnostico): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                                    Diagnóstico - <?php echo date('d/m/Y H:i', strtotime($diagnostico['data_criacao'])); ?>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#accordionDiagnosticos">
                                                <div class="accordion-body">
                                                    <div class="mb-3">
                                                        <strong>Descrição:</strong>
                                                        <p><?php echo nl2br($diagnostico['descricao']); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Tratamento:</strong>
                                                        <p><?php echo nl2br($diagnostico['tratamento']); ?></p>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Medicamentos:</strong>
                                                        <p><?php echo nl2br($diagnostico['medicamentos']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para adicionar diagnóstico -->
<div class="modal fade" id="modalDiagnostico" tabindex="-1" aria-labelledby="modalDiagnosticoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDiagnosticoLabel">Adicionar Diagnóstico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição do Diagnóstico</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="tratamento" class="form-label">Tratamento Recomendado</label>
                        <textarea class="form-control" id="tratamento" name="tratamento" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="medicamentos" class="form-label">Medicamentos</label>
                        <textarea class="form-control" id="medicamentos" name="medicamentos" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="adicionar_diagnostico" class="btn btn-primary">Salvar Diagnóstico</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
