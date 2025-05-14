<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = 'fatura';
$page_title = 'Fatura Detalhada';
include_once('../includes/header.php');

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
}

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Buscar tutores para o dropdown
$tutores = [];
$sql_tutores = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
$result_tutores = $conn->query($sql_tutores);
if ($result_tutores && $result_tutores->num_rows > 0) {
    while ($row = $result_tutores->fetch_assoc()) {
        $tutores[] = $row;
    }
}

// Buscar serviços para o dropdown
$servicos = [];
// Primeiro, vamos verificar qual é o nome da coluna de ID
$check_columns = $conn->query("SHOW COLUMNS FROM servicos");
$id_column = null;

if ($check_columns) {
    while ($column = $check_columns->fetch_assoc()) {
        if ($column['Key'] == 'PRI') {
            $id_column = $column['Field'];
            break;
        }
    }
}

// Se encontramos a coluna de ID, usamos ela; caso contrário, tentamos algumas opções comuns
if ($id_column) {
    $sql_servicos = "SELECT {$id_column} as id, nome, preco FROM servicos ORDER BY nome";
} else {
    // Tentamos algumas opções comuns para o nome da coluna de ID
    $sql_servicos = "SELECT COALESCE(id_servico, servico_id, codigo_servico, id) as id, nome, preco FROM servicos ORDER BY nome";
}
$result_servicos = $conn->query($sql_servicos);
if ($result_servicos && $result_servicos->num_rows > 0) {
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos[] = $row;
    }
} else {
    // Dados de exemplo se a tabela não existir
    $servicos = [
        ['id' => 1, 'nome' => 'Consulta de Rotina', 'preco' => 120.00],
        ['id' => 2, 'nome' => 'Vacinação', 'preco' => 80.00],
        ['id' => 3, 'nome' => 'Banho e Tosa', 'preco' => 70.00],
        ['id' => 4, 'nome' => 'Exames Laboratoriais', 'preco' => 150.00],
        ['id' => 5, 'nome' => 'Cirurgia de Castração', 'preco' => 350.00]
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Detalhada - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    
    <style>
        /* Estilos específicos da página */
        .fatura-container {
            margin-left: 250px;
            padding: 20px;
            background-color: #f6f9fc;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            color: #0b3556;
            font-size: 24px;
            margin: 0;
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            color: #0b3556;
            font-size: 18px;
            margin: 0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: #0b3556;
            box-shadow: 0 0 0 3px rgba(11, 53, 86, 0.1);
            outline: none;
        }
        
        .form-control:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-primary {
            background-color: #0b3556;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0d4371;
            transform: translateY(-2px);
        }
        
        .btn-icon {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-icon i {
            font-size: 16px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th, 
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            color: #0b3556;
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background-color: #f6f9fc;
        }
        
        .data-table .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .btn-edit {
            color: #4a90e2;
        }
        
        .btn-delete {
            color: #e25c5c;
        }
        
        .btn-view {
            color: #50c878;
        }
        
        .btn-action:hover {
            opacity: 0.8;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background-color: rgba(80, 200, 120, 0.1);
            color: #50c878;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #777;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .fatura-container {
                margin-left: 0;
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        /* Garantir compatibilidade com o sidebar */
        .sidebar {
            width: 220px !important;
            background-color: #003b66 !important;
            position: fixed !important;
            top: 60px !important;
            bottom: 0 !important;
            left: 0 !important;
            z-index: 900 !important;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 60px !important;
            }
            
            .sidebar.expanded {
                width: 220px !important;
            }
            
            .fatura-container {
                margin-left: 60px;
            }
        }
    </style>
</head>
<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="fatura-container">
        <div class="page-header">
            <h1 class="page-title">Fatura Detalhada</h1>
            <div class="header-actions">
                <button class="btn btn-primary btn-icon" onclick="exportarFaturas()">
                    <i class="fas fa-file-export"></i> Exportar
                </button>
            </div>
        </div>

        <!-- Formulário da Fatura -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Nova Fatura</h2>
            </div>
            
            <form id="formularioFatura">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tutor_id">Tutor</label>
                        <select id="tutor_id" class="form-control" onchange="carregarPets(this.value)" required>
                            <option value="">Selecione um tutor</option>
                            <?php foreach ($tutores as $tutor): ?>
                                <option value="<?php echo $tutor['id_tutor']; ?>"><?php echo htmlspecialchars($tutor['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="pet_id">Pet</label>
                        <select id="pet_id" class="form-control" required disabled>
                            <option value="">Selecione um tutor primeiro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dataFatura">Data da Fatura</label>
                        <input type="date" id="dataFatura" class="form-control" required value="<?php echo date('Y-m-d'); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="servico_id">Serviço Realizado</label>
                        <select id="servico_id" class="form-control" onchange="atualizarValor()" required>
                            <option value="">Selecione um serviço</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?php echo $servico['id']; ?>" data-preco="<?php echo $servico['preco']; ?>">
                                    <?php echo htmlspecialchars($servico['nome']); ?> - R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="valor">Valor do Serviço (R$)</label>
                        <input type="number" id="valor" class="form-control" step="0.01" required readonly />
                    </div>

                    <div class="form-group">
                        <label for="pagamento">Forma de Pagamento</label>
                        <select id="pagamento" class="form-control" required>
                            <option value="">Selecione</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="Pix">Pix</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="clinica">Nome da Clínica</label>
                        <input type="text" id="clinica" class="form-control" required value="PetPlus Clínica Veterinária" />
                    </div>

                    <div class="form-group">
                        <label for="profissional">Profissional Responsável</label>
                        <input type="text" id="profissional" class="form-control" required />
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-icon">
                        <i class="fas fa-save"></i> Cadastrar Fatura
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabela de Faturas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Faturas Registradas</h2>
                <div class="card-actions">
                    <input type="text" id="pesquisaFatura" class="form-control" placeholder="Pesquisar faturas..." style="width: 250px;">
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tutor</th>
                            <th>Pet</th>
                            <th>Data</th>
                            <th>Serviço</th>
                            <th>Valor (R$)</th>
                            <th>Pagamento</th>
                            <th>Profissional</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="listaFaturas">
                        <!-- Dados de exemplo -->
                        <tr>
                            <td>1</td>
                            <td>João Silva</td>
                            <td>Rex</td>
                            <td>01/06/2023</td>
                            <td>Consulta de Rotina</td>
                            <td>R$ 120,00</td>
                            <td>Cartão de Crédito</td>
                            <td>Dr. Carlos</td>
                            <td class="actions">
                                <button class="btn-action btn-view" title="Visualizar"><i class="fas fa-eye"></i></button>
                                <button class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-action btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Maria Oliveira</td>
                            <td>Luna</td>
                            <td>02/06/2023</td>
                            <td>Vacinação</td>
                            <td>R$ 80,00</td>
                            <td>Dinheiro</td>
                            <td>Dra. Ana</td>
                            <td class="actions">
                                <button class="btn-action btn-view" title="Visualizar"><i class="fas fa-eye"></i></button>
                                <button class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-action btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Pedro Santos</td>
                            <td>Thor</td>
                            <td>03/06/2023</td>
                            <td>Banho e Tosa</td>
                            <td>R$ 70,00</td>
                            <td>Pix</td>
                            <td>Juliana</td>
                            <td class="actions">
                                <button class="btn-action btn-view" title="Visualizar"><i class="fas fa-eye"></i></button>
                                <button class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></button>
                                <button class="btn-action btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Função para carregar os pets do tutor selecionado
        function carregarPets(tutorId) {
            const petSelect = document.getElementById('pet_id');
            
            // Limpar o select de pets
            petSelect.innerHTML = '<option value="">Selecione um pet</option>';
            
            if (!tutorId) {
                petSelect.disabled = true;
                return;
            }
            
            // Habilitar o select de pets
            petSelect.disabled = false;
            
            // Fazer uma requisição para buscar os pets do tutor
            fetch(`../api/pets/listar_pets.php?tutor_id=${tutorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.pets && data.pets.length > 0) {
                        data.pets.forEach(pet => {
                            const option = document.createElement('option');
                            option.value = pet.id;
                            option.textContent = pet.nome;
                            petSelect.appendChild(option);
                        });
                    } else {
                        // Dados de exemplo se a API falhar
                        const petsExemplo = [
                            { id: 1, nome: 'Rex' },
                            { id: 2, nome: 'Luna' },
                            { id: 3, nome: 'Mel' }
                        ];
                        
                        petsExemplo.forEach(pet => {
                            const option = document.createElement('option');
                            option.value = pet.id;
                            option.textContent = pet.nome;
                            petSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar pets:', error);
                    
                    // Dados de exemplo em caso de erro
                    const petsExemplo = [
                        { id: 1, nome: 'Rex' },
                        { id: 2, nome: 'Luna' },
                        { id: 3, nome: 'Mel' }
                    ];
                    
                    petsExemplo.forEach(pet => {
                        const option = document.createElement('option');
                        option.value = pet.id;
                        option.textContent = pet.nome;
                        petSelect.appendChild(option);
                    });
                });
        }
        
        // Função para atualizar o valor com base no serviço selecionado
        function atualizarValor() {
            const servicoSelect = document.getElementById('servico_id');
            const valorInput = document.getElementById('valor');
            
            if (servicoSelect.value) {
                const option = servicoSelect.options[servicoSelect.selectedIndex];
                const preco = option.getAttribute('data-preco');
                valorInput.value = preco;
            } else {
                valorInput.value = '';
            }
        }
        
        // Função para exportar faturas
        function exportarFaturas() {
            alert('Funcionalidade de exportação será implementada em breve!');
        }
        
        // Inicializar o formulário
        document.getElementById('formularioFatura').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar formulário
            const tutor = document.getElementById('tutor_id').options[document.getElementById('tutor_id').selectedIndex].text;
            const pet = document.getElementById('pet_id').options[document.getElementById('pet_id').selectedIndex].text;
            const data = new Date(document.getElementById('dataFatura').value).toLocaleDateString('pt-BR');
            const servico = document.getElementById('servico_id').options[document.getElementById('servico_id').selectedIndex].text.split(' - ')[0];
            const valor = parseFloat(document.getElementById('valor').value).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
            const pagamento = document.getElementById('pagamento').value;
            const profissional = document.getElementById('profissional').value;
            
            // Adicionar à tabela (simulação)
            const tbody = document.getElementById('listaFaturas');
            const novaLinha = document.createElement('tr');
            
            // Gerar ID aleatório para simulação
            const id = Math.floor(Math.random() * 1000) + 4;
            
            novaLinha.innerHTML = `
                <td>${id}</td>
                <td>${tutor}</td>
                <td>${pet}</td>
                <td>${data}</td>
                <td>${servico}</td>
                <td>${valor}</td>
                <td>${pagamento}</td>
                <td>${profissional}</td>
                <td class="actions">
                    <button class="btn-action btn-view" title="Visualizar"><i class="fas fa-eye"></i></button>
                    <button class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-action btn-delete" title="Excluir"><i class="fas fa-trash"></i></button>
                </td>
            `;
            
            // Adicionar com animação
            novaLinha.style.opacity = '0';
            novaLinha.style.transform = 'translateY(10px)';
            tbody.insertBefore(novaLinha, tbody.firstChild);
            
            setTimeout(() => {
                novaLinha.style.transition = 'opacity 0.5s, transform 0.5s';
                novaLinha.style.opacity = '1';
                novaLinha.style.transform = 'translateY(0)';
            }, 10);
            
            // Mostrar mensagem de sucesso
            const mensagem = document.createElement('div');
            mensagem.style.position = 'fixed';
            mensagem.style.top = '20px';
            mensagem.style.right = '20px';
            mensagem.style.padding = '15px 20px';
            mensagem.style.background = '#50c878';
            mensagem.style.color = 'white';
            mensagem.style.borderRadius = '5px';
            mensagem.style.boxShadow = '0 3px 10px rgba(0,0,0,0.2)';
            mensagem.style.zIndex = '9999';
            mensagem.style.transform = 'translateY(-20px)';
            mensagem.style.opacity = '0';
            mensagem.style.transition = 'transform 0.3s, opacity 0.3s';
            mensagem.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i> Fatura cadastrada com sucesso!';
            
            document.body.appendChild(mensagem);
            
            setTimeout(() => {
                mensagem.style.transform = 'translateY(0)';
                mensagem.style.opacity = '1';
            }, 10);
            
            setTimeout(() => {
                mensagem.style.transform = 'translateY(-20px)';
                mensagem.style.opacity = '0';
                
                setTimeout(() => {
                    document.body.removeChild(mensagem);
                }, 300);
            }, 3000);
            
            // Limpar o formulário
            this.reset();
            document.getElementById('pet_id').disabled = true;
            document.getElementById('pet_id').innerHTML = '<option value="">Selecione um tutor primeiro</option>';
        });
        
        // Pesquisa de faturas
        document.getElementById('pesquisaFatura').addEventListener('input', function() {
            const termo = this.value.toLowerCase();
            const linhas = document.querySelectorAll('#listaFaturas tr');
            
            linhas.forEach(linha => {
                const conteudo = linha.textContent.toLowerCase();
                if (conteudo.includes(termo)) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
        });
        
        // Adicionar eventos aos botões de ação
        document.querySelectorAll('.btn-action').forEach(btn => {
            btn.addEventListener('click', function() {
                const linha = this.closest('tr');
                const id = linha.cells[0].textContent;
                
                if (this.classList.contains('btn-view')) {
                    alert(`Visualizando fatura #${id}`);
                } else if (this.classList.contains('btn-edit')) {
                    alert(`Editando fatura #${id}`);
                } else if (this.classList.contains('btn-delete')) {
                    if (confirm(`Deseja realmente excluir a fatura #${id}?`)) {
                        linha.style.transition = 'opacity 0.5s, transform 0.5s';
                        linha.style.opacity = '0';
                        linha.style.transform = 'translateY(10px)';
                        
                        setTimeout(() => {
                            linha.remove();
                        }, 500);
                    }
                }
            });
        });
    </script>
    
    <script src="../includes/header.js"></script>
</body>
</html>
