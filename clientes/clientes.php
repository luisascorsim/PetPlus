<?php
// Vamos garantir que a página clientes.php esteja carregando corretamente
// Adicione um código de depuração no início do arquivo para verificar se está sendo carregado

// No início do arquivo, logo após a primeira linha <?php, adicione:
// Verificar se o arquivo está sendo carregado
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Também vamos garantir que a conexão com o banco de dados esteja funcionando
try {
    // Testar conexão
    if (!isset($conn) || $conn === null) {
        // Se $conn não estiver definido, tente criar uma nova conexão
        if (!file_exists('../config/database.php')) {
            die("Arquivo de configuração do banco de dados não encontrado.");
        }
    }
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage());
}
require_once '../config/database.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Definir uma sessão temporária para desenvolvimento
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
    
    // Comentado para evitar redirecionamento durante o desenvolvimento
    // header('Location: ../login.php');
    // exit;
}

// Configuração de paginação
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Busca
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$condicao_busca = '';
$params = [];

if (!empty($busca)) {
    $condicao_busca = "WHERE nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca";
    $params[':busca'] = "%{$busca}%";
}

try {
    // Contar total de registros
    $sql_count = "SELECT COUNT(*) FROM clientes {$condicao_busca}";
    $stmt_count = $conn->prepare($sql_count);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt_count->bindValue($key, $value);
        }
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    
    // Calcular total de páginas
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    
    // Consultar clientes
    $sql = "SELECT * FROM clientes {$condicao_busca} ORDER BY nome LIMIT :offset, :limit";
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->execute();
    
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $mensagem = "Erro ao listar clientes: " . $e->getMessage();
    $tipo_mensagem = "erro";
}

// Processar exclusão
if (isset($_POST['excluir_cliente'])) {
    $cliente_id = $_POST['cliente_id'];
    
    try {
        // Verificar se existem pets associados
        $sql_check = "SELECT COUNT(*) FROM pets WHERE cliente_id = :cliente_id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':cliente_id', $cliente_id);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            $mensagem = "Não é possível excluir o cliente pois existem pets associados a ele.";
            $tipo_mensagem = "erro";
        } else {
            // Excluir cliente
            $sql_delete = "DELETE FROM clientes WHERE id = :id";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bindParam(':id', $cliente_id);
            $stmt_delete->execute();
            
            $mensagem = "Cliente excluído com sucesso!";
            $tipo_mensagem = "sucesso";
            
            // Recarregar a página para atualizar a lista
            header("Location: clientes.php?pagina={$pagina_atual}" . (!empty($busca) ? "&busca={$busca}" : ""));
            exit;
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir cliente: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Define o caminho base para os recursos
$caminhoBase = '../';

// Inclui o header e sidebar
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Conexão com o banco de dados
//require_once('../conecta_db.php');
//$conn = conecta_db();

// Simulação de dados de clientes (em um sistema real, viria do banco de dados)
/*$clientes = [
    [
        'id' => 1,
        'nome' => 'Maria Silva',
        'email' => 'maria.silva@email.com',
        'telefone' => '(11) 98765-4321',
        'endereco' => 'Rua das Flores, 123 - São Paulo, SP',
        'pets' => [
            ['id' => 1, 'nome' => 'Rex', 'especie' => 'Cachorro', 'raca' => 'Labrador', 'idade' => 3],
            ['id' => 2, 'nome' => 'Nina', 'especie' => 'Gato', 'raca' => 'Persa', 'idade' => 2]
        ]
    ],
    [
        'id' => 2,
        'nome' => 'João Pereira',
        'email' => 'joao.pereira@email.com',
        'telefone' => '(11) 91234-5678',
        'endereco' => 'Av. Paulista, 1000 - São Paulo, SP',
        'pets' => [
            ['id' => 3, 'nome' => 'Luna', 'especie' => 'Cachorro', 'raca' => 'Poodle', 'idade' => 5]
        ]
    ],
    [
        'id' => 3,
        'nome' => 'Ana Costa',
        'email' => 'ana.costa@email.com',
        'telefone' => '(11) 99876-5432',
        'endereco' => 'Rua Augusta, 500 - São Paulo, SP',
        'pets' => [
            ['id' => 4, 'nome' => 'Mel', 'especie' => 'Cachorro', 'raca' => 'Golden Retriever', 'idade' => 2],
            ['id' => 5, 'nome' => 'Bob', 'especie' => 'Cachorro', 'raca' => 'Bulldog', 'idade' => 4]
        ]
    ],
    [
        'id' => 4,
        'nome' => 'Carlos Oliveira',
        'email' => 'carlos.oliveira@email.com',
        'telefone' => '(11) 97654-3210',
        'endereco' => 'Rua Oscar Freire, 300 - São Paulo, SP',
        'pets' => [
            ['id' => 6, 'nome' => 'Thor', 'especie' => 'Cachorro', 'raca' => 'Pastor Alemão', 'idade' => 3]
        ]
    ]
];*/

// Filtragem de clientes
//$termoBusca = isset($_GET['busca']) ? $_GET['busca'] : '';
//if (!empty($termoBusca)) {
//    $clientes = array_filter($clientes, function($cliente) use ($termoBusca) {
//        return (
//            stripos($cliente['nome'], $termoBusca) !== false ||
//            stripos($cliente['email'], $termoBusca) !== false ||
//            stripos($cliente['telefone'], $termoBusca) !== false
//        );
//    });
//}
?>

<div class="container">
    <div class="card">
        <div class="header-card">
            <h1>Clientes</h1>
            <button class="btn-novo" onclick="abrirModalCliente()">Novo Cliente</button>
        </div>
        
        <div class="filtro-container">
            <form action="" method="GET" class="form-busca">
                <input type="text" name="busca" placeholder="Buscar por nome, email ou telefone..." value="<?php echo htmlspecialchars($busca); ?>">
                <button type="submit" class="btn-buscar">Buscar</button>
                <?php if (!empty($busca)): ?>
                    <a href="clientes.php" class="btn-limpar">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="clientes-lista">
            <?php if (empty($clientes)): ?>
                <div class="mensagem-vazia">
                    <p>Nenhum cliente encontrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($clientes as $cliente): ?>
                    <div class="cliente-card">
                        <div class="cliente-info">
                            <h3><?php echo htmlspecialchars($cliente['nome']); ?></h3>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                            <p><strong>Endereço:</strong> <?php echo htmlspecialchars($cliente['endereco']); ?></p>
                        </div>
                        <div class="cliente-acoes">
                            <button class="btn-editar" onclick="abrirModalCliente(<?php echo $cliente['id']; ?>)">Editar</button>
                            <button class="btn-visualizar" onclick="visualizarCliente(<?php echo $cliente['id']; ?>)">Visualizar</button>
                            <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                                <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                <button type="submit" name="excluir_cliente" class="btn-excluir">Excluir</button>
                            </form>
                        </div>
                    </div>
                    
                    <div id="pets-<?php echo $cliente['id']; ?>" class="pets-container" style="display: none;">
                        <h4>Pets de <?php echo htmlspecialchars($cliente['nome']); ?></h4>
                        <div class="pets-lista">
                            <?php
                            // Consulta os pets do cliente atual
                            $sql_pets = "SELECT * FROM pets WHERE cliente_id = :cliente_id";
                            $stmt_pets = $conn->prepare($sql_pets);
                            $stmt_pets->bindParam(':cliente_id', $cliente['id']);
                            $stmt_pets->execute();
                            $pets = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <?php if (empty($pets)): ?>
                                <p>Este cliente não possui pets cadastrados.</p>
                            <?php else: ?>
                                <?php foreach ($pets as $pet): ?>
                                    <div class="pet-card">
                                        <h5><?php echo htmlspecialchars($pet['nome']); ?></h5>
                                        <p><strong>Espécie:</strong> <?php echo htmlspecialchars($pet['especie']); ?></p>
                                        <p><strong>Raça:</strong> <?php echo htmlspecialchars($pet['raca']); ?></p>
                                        <p><strong>Idade:</strong> <?php echo $pet['idade']; ?> anos</p>
                                        <div class="pet-acoes">
                                            <a href="../cadastrar_pet/cadastrar_pets.php?id=<?php echo $pet['id']; ?>" class="btn-pet">Editar Pet</a>
                                            <a href="../historico_consultas/historico-consultas.php?pet_id=<?php echo $pet['id']; ?>" class="btn-pet">Histórico</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="adicionar-pet">
                                <a href="../cadastrar_pet/cadastrar_pets.php?cliente_id=<?php echo $cliente['id']; ?>" class="btn-adicionar-pet">+ Adicionar Pet</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginação -->
        <div class="paginacao">
            <?php if ($total_paginas > 1): ?>
                <?php if ($pagina_atual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_atual - 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="btn-paginacao">Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="<?php echo ($i == $pagina_atual) ? 'btn-paginacao ativo' : 'btn-paginacao'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($pagina_atual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_atual + 1; ?><?php echo !empty($busca) ? '&busca=' . urlencode($busca) : ''; ?>" class="btn-paginacao">Próximo</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Cliente -->
<div id="modalCliente" class="modal">
    <div class="modal-content">
        <span class="fechar" onclick="fecharModalCliente()">&times;</span>
        <h2 id="tituloModal">Novo Cliente</h2>
        <form id="formCliente" method="POST" action="processar_cliente.php">
            <input type="hidden" id="cliente_id" name="cliente_id" value="">
            
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" required>
            </div>
            
            <div class="form-group">
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf">
            </div>
            
            <div class="form-group">
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" required>
            </div>
            
            <div class="form-group">
                <label for="cidade">Cidade:</label>
                <input type="text" id="cidade" name="cidade" required>
            </div>
            
            <div class="form-group">
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                    <option value="">Selecione</option>
                    <option value="AC">Acre</option>
                    <option value="AL">Alagoas</option>
                    <option value="AP">Amapá</option>
                    <option value="AM">Amazonas</option>
                    <option value="BA">Bahia</option>
                    <option value="CE">Ceará</option>
                    <option value="DF">Distrito Federal</option>
                    <option value="ES">Espírito Santo</option>
                    <option value="GO">Goiás</option>
                    <option value="MA">Maranhão</option>
                    <option value="MT">Mato Grosso</option>
                    <option value="MS">Mato Grosso do Sul</option>
                    <option value="MG">Minas Gerais</option>
                    <option value="PA">Pará</option>
                    <option value="PB">Paraíba</option>
                    <option value="PR">Paraná</option>
                    <option value="PE">Pernambuco</option>
                    <option value="PI">Piauí</option>
                    <option value="RJ">Rio de Janeiro</option>
                    <option value="RN">Rio Grande do Norte</option>
                    <option value="RS">Rio Grande do Sul</option>
                    <option value="RO">Rondônia</option>
                    <option value="RR">Roraima</option>
                    <option value="SC">Santa Catarina</option>
                    <option value="SP">São Paulo</option>
                    <option value="SE">Sergipe</option>
                    <option value="TO">Tocantins</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="cep">CEP:</label>
                <input type="text" id="cep" name="cep">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancelar" onclick="fecharModalCliente()">Cancelar</button>
                <button type="submit" class="btn-salvar">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .container {
        margin-left: 220px;
        padding: 80px 30px 30px;
        transition: margin-left 0.3s;
    }
    
    .card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .header-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    h1 {
        color: #0b3556;
        margin: 0;
    }
    
    .btn-novo {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
        font-weight: 500;
    }
    
    .btn-novo:hover {
        background-color: #0d4371;
    }
    
    .filtro-container {
        margin-bottom: 20px;
    }
    
    .form-busca {
        display: flex;
        gap: 10px;
    }
    
    .form-busca input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .btn-buscar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        cursor: pointer;
    }
    
    .btn-limpar {
        background-color: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        text-decoration: none;
    }
    
    .clientes-lista {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .cliente-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .cliente-info {
        flex: 1;
    }
    
    .cliente-info h3 {
        margin: 0 0 10px 0;
        color: #0b3556;
    }
    
    .cliente-info p {
        margin: 5px 0;
    }
    
    .cliente-acoes {
        display: flex;
        gap: 10px;
    }
    
    .btn-editar, .btn-visualizar {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .btn-editar {
        background-color: #0b3556;
        color: white;
    }
    
    .btn-visualizar {
        background-color: #f0f0f0;
        color: #333;
    }

    .btn-excluir {
        background-color: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 12px;
        cursor: pointer;
    }
    
    .pets-container {
        margin-top: -10px;
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 5px 5px;
        background-color: #fff;
    }
    
    .pets-container h4 {
        margin-top: 0;
        color: #0b3556;
    }
    
    .pets-lista {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .pet-card {
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .pet-card h5 {
        margin: 0 0 10px 0;
        color: #0b3556;
    }
    
    .pet-card p {
        margin: 5px 0;
        font-size: 14px;
    }
    
    .pet-acoes {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .btn-pet {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        background-color: #0b3556;
        color: white;
        text-decoration: none;
        font-size: 12px;
    }
    
    .adicionar-pet {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 15px;
        border: 1px dashed #ddd;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .btn-adicionar-pet {
        color: #0b3556;
        text-decoration: none;
        font-weight: 500;
    }
    
    .mensagem-vazia {
        padding: 20px;
        text-align: center;
        color: #666;
    }

    /* Paginação */
    .paginacao {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .btn-paginacao {
        padding: 8px 12px;
        margin: 0 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
        background-color: #f9f9f9;
    }

    .btn-paginacao:hover {
        background-color: #eee;
    }

    .btn-paginacao.ativo {
        background-color: #0b3556;
        color: white;
        border-color: #0b3556;
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 8px;
        position: relative;
    }
    
    .fechar {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .fechar:hover {
        color: black;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-cancelar {
        background-color: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
    }
    
    .btn-salvar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .container {
            margin-left: 60px;
            padding: 70px 15px 15px;
        }
        
        .cliente-card {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .cliente-acoes {
            margin-top: 15px;
            width: 100%;
            justify-content: space-between;
        }
        
        .pets-lista {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
</style>

<script>
    // Função para visualizar os pets de um cliente
    function visualizarCliente(clienteId) {
        const petsContainer = document.getElementById(`pets-${clienteId}`);
        
        if (petsContainer.style.display === 'none') {
            // Fecha todos os outros containers de pets
            document.querySelectorAll('.pets-container').forEach(container => {
                container.style.display = 'none';
            });
            
            // Abre o container de pets do cliente selecionado
            petsContainer.style.display = 'block';
        } else {
            petsContainer.style.display = 'none';
        }
    }
    
    // Função para abrir o modal de cliente
    function abrirModalCliente(clienteId = null) {
        const modal = document.getElementById('modalCliente');
        const tituloModal = document.getElementById('tituloModal');
        const form = document.getElementById('formCliente');
        
        modal.style.display = 'block';
        
        if (clienteId) {
            tituloModal.textContent = 'Editar Cliente';
            document.getElementById('cliente_id').value = clienteId;
            
            // Simulação de preenchimento do formulário com dados do cliente
            // Em um sistema real, você buscaria os dados do cliente no banco de dados
            const cliente = <?php echo json_encode($clientes); ?>.find(c => c.id === clienteId);
            
            if (cliente) {
                document.getElementById('nome').value = cliente.nome;
                document.getElementById('email').value = cliente.email;
                document.getElementById('telefone').value = cliente.telefone;
                document.getElementById('cpf').value = cliente.cpf;
                document.getElementById('endereco').value = cliente.endereco;
                document.getElementById('cidade').value = cliente.cidade;
                document.getElementById('estado').value = cliente.estado;
                document.getElementById('cep').value = cliente.cep;
                
                // Extrair endereço
                /*const enderecoParts = cliente.endereco.split(' - ');
                document.getElementById('endereco').value = enderecoParts[0];
                
                if (enderecoParts.length > 1) {
                    const cidadeEstado = enderecoParts[1].split(', ');
                    document.getElementById('cidade').value = cidadeEstado[0];
                    
                    if (cidadeEstado.length > 1) {
                        document.getElementById('estado').value = cidadeEstado[1];
                    }
                }*/
            }
        } else {
            tituloModal.textContent = 'Novo Cliente';
            form.reset();
        }
    }
    
    // Função para fechar o modal
    function fecharModalCliente() {
        const modal = document.getElementById('modalCliente');
        modal.style.display = 'none';
    }
    
    // Manipula o envio do formulário
    /*document.getElementById('formCliente').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Aqui você implementaria a lógica para salvar o cliente
        alert('Cliente salvo com sucesso!');
        fecharModalCliente();
        
        // Em um sistema real, você recarregaria a página ou atualizaria a visualização
        // window.location.reload();
    });*/
    
    // Fecha o modal se o usuário clicar fora dele
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalCliente');
        if (event.target == modal) {
            fecharModalCliente();
        }
    });
</script>

</body>
</html>
