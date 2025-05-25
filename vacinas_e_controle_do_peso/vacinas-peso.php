<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o header e a barra lateral
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Busca todos os pets para o dropdown
$query = "SELECT id_pet, nome FROM Pets ORDER BY nome";
$result = $conn->query($query);
$pets = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
}

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formulário de vacina
    $pet_id = $_POST['pet_id'];
    $nome = $_POST['nome'];
    $data_aplicacao = $_POST['data_vacina'];
    $lote = $_POST['lote'];
    $data_proxima = $_POST['reforco'];
    
    // Validações
    if (empty($pet_id) || empty($nome) || empty($data_aplicacao)) {
        $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
        $tipo_mensagem = "erro";
    } else {
        // Inserir na tabela vacinas
        $sql = "INSERT INTO Vacinas (pet_id, nome, data_aplicacao, lote, data_proxima) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $pet_id, $nome, $data_aplicacao, $lote, $data_proxima);
        
        if ($stmt->execute()) {
            $mensagem = "Vacina registrada com sucesso!";
            $tipo_mensagem = "sucesso";
        } else {
            $mensagem = "Erro ao registrar vacina: " . $stmt->error;
            $tipo_mensagem = "erro";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Controle de Vacinas - PetPlus</title>
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            margin-left: 180px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .card {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto 30px;
        }

        h1, h2 {
            color: #003b66;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #003b66;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        .btn-submit {
            background-color: #003b66;
            color: white;
            padding: 12px;
            border: none;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }

        .btn-submit:hover {
            background-color: #002b4d;
        }

        .mensagem {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }

        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensagem-erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f2f2f2;
            color: #003b66;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                margin-left: 60px;
                padding: 15px;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Controle de Vacinas</h1>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="selectPet">Selecione o Pet:</label>
                <select id="selectPet" onchange="selecionarPet(this.value)">
                    <option value="">-- Selecione um Pet --</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id_pet']; ?>"><?php echo htmlspecialchars($pet['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <form action="vacinas-peso.php" method="POST">
                <input type="hidden" id="vacina_pet_id" name="pet_id" value="">
                
                <div class="form-group">
                    <label for="nome_vacina">Nome da Vacina:</label>
                    <input type="text" id="nome_vacina" name="nome_vacina" required />
                </div>
                
                <div class="form-group">
                    <label for="data_vacina">Data da Aplicação:</label>
                    <input type="date" id="data_vacina" name="data_vacina" required />
                </div>
                
                <div class="form-group">
                    <label for="lote">Lote:</label>
                    <input type="text" id="lote" name="lote" />
                </div>
                
                <div class="form-group">
                    <label for="reforco">Data do Reforço:</label>
                    <input type="date" id="reforco" name="reforco" />
                </div>
                
                <button type="submit" class="btn-submit">Registrar Vacina</button>
            </form>
            
            <div id="historico-vacina" style="display: none; margin-top: 30px;">
                <h3>Histórico de Vacinas</h3>
                <table id="tabela-vacina">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Data</th>
                            <th>Lote</th>
                            <th>Reforço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="lista-vacinas">
                        <!-- Será preenchido via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        let petSelecionado = null;
        
        // Função para selecionar um pet
        function selecionarPet(petId) {
            petSelecionado = petId;
            
            // Atualiza o campo hidden do formulário
            document.getElementById('vacina_pet_id').value = petId;
            
            if (petId) {
                // Carrega os dados do pet selecionado
                carregarHistoricoVacinas(petId);
                
                // Mostra o histórico
                document.getElementById('historico-vacina').style.display = 'block';
            } else {
                // Esconde o histórico se nenhum pet estiver selecionado
                document.getElementById('historico-vacina').style.display = 'none';
            }
        }
        
        // Função para carregar o histórico de vacinas
        function carregarHistoricoVacinas(petId) {
            fetch(`api/vacinas/listarVacinas.php?pet_id=${petId}`)
                .then(response => response.json())
                .then(data => {
                    renderizarTabelaVacinas(data);
                })
                .catch(error => {
                    console.error('Erro ao carregar histórico de vacinas:', error);
                    // Dados de exemplo para teste quando a API falha
                    const vacinasExemplo = [
                        { id: 1, nome: 'Antirrábica', data: '2023-05-15', lote: 'ABC123', reforco: '2024-05-15' },
                        { id: 2, nome: 'V10', data: '2023-06-20', lote: 'XYZ789', reforco: '2024-06-20' }
                    ];
                    renderizarTabelaVacinas(vacinasExemplo);
                });
        }
        
        // Função para renderizar a tabela de vacinas
        function renderizarTabelaVacinas(vacinas) {
            const tbody = document.getElementById('lista-vacinas');
            tbody.innerHTML = '';
            
            if (!Array.isArray(vacinas) || vacinas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5">Nenhum registro de vacina encontrado</td></tr>';
                return;
            }
            
            vacinas.forEach(vacina => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${vacina.nome}</td>
                    <td>${formatarData(vacina.data)}</td>
                    <td>${vacina.lote || '-'}</td>
                    <td>${vacina.reforco ? formatarData(vacina.data_proxima) : '-'}</td>
                    <td>
                        <button class="btn-danger" onclick="excluirVacina(${vacina.id})">Excluir</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // Função para excluir um registro de vacina
        function excluirVacina(id) {
            if (!confirm('Tem certeza que deseja excluir este registro de vacina?')) return;
            
            fetch(`api/vacinas/excluirVacina.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    alert(data.mensagem || 'Registro excluído com sucesso!');
                    carregarHistoricoVacinas(petSelecionado);
                })
                .catch(error => {
                    console.error('Erro ao excluir registro de vacina:', error);
                    alert('Erro ao excluir registro. Verifique o console para mais detalhes.');
                });
        }
        
        // Função para formatar a data
        function formatarData(dataString) {
            if (!dataString) return '';
            const data = new Date(dataString);
            return data.toLocaleDateString('pt-BR');
        }
    </script>
</body>
</html>
