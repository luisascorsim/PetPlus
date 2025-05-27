<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Define o caminho base para os recursos
$caminhoBase = '../';

// Inclui o header e sidebar
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Conexão com o banco de dados
require_once('../conecta_db.php');
$conn = conecta_db();

$mensagem = '';
$tipo_mensagem = '';

// --- Definir categorias estáticas aqui ---
// Você pode adicionar, remover ou editar as categorias conforme necessário.
$categoriasEstaticas = [
    'Prevenção',
    'Estética',
    'Diagnóstico',
    'Cirurgia',
    'Odontologia',
];
sort($categoriasEstaticas); // Opcional: ordenar categorias alfabeticamente

// Lógica para Salvar/Atualizar Serviço (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_action']) && $_POST['form_action'] == 'save_service') {
    $id_servico = isset($_POST['servico_id']) && !empty($_POST['servico_id']) ? (int)$_POST['servico_id'] : null;

    $nome = trim($_POST['nome']); // Usar trim para remover espaços em branco
    $descricao = trim($_POST['descricao']);
    $preco = filter_var($_POST['preco'], FILTER_VALIDATE_FLOAT); // Valida e converte para float
    $duracao = filter_var($_POST['duracao'], FILTER_VALIDATE_INT); // Valida e converte para int

    // Determina a categoria: se for 'nova', usa o valor de novaCategoria, senão, usa a categoria selecionada
    $categoria = $_POST['categoria'] === 'nova' ? trim($_POST['novaCategoria']) : trim($_POST['categoria']);
    $status_s = 'ativo'; // Definido como 'ativo'

    // Validações
    if (empty($nome) || empty($descricao) || $preco === false || $preco === null || $duracao === false || $duracao === null || empty($categoria)) {
        $mensagem = "Todos os campos obrigatórios devem ser preenchidos corretamente.";
        $tipo_mensagem = "erro";
    } else {
        $conn->begin_transaction();
        try {
            if ($id_servico) {
                // Atualiza os dados do serviço
                $sql = "UPDATE Servicos SET nome=?, descricao=?, preco=?, duracao=?, categoria=?, status_s=? WHERE id_servico=?";
                $stmt = $conn->prepare($sql);
                // Tipos: ssdissi (string, string, double, integer, string, string para status_s, integer para id_servico)
                $stmt->bind_param("ssdissi", $nome, $descricao, $preco, $duracao, $categoria, $status_s, $id_servico);
            } else {
                // Insere um novo serviço
                $sql = "INSERT INTO Servicos (nome, descricao, preco, duracao, categoria, status_s) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                // Tipos: ssdiss (string, string, double, integer, string, string)
                $stmt->bind_param("ssdiss", $nome, $descricao, $preco, $duracao, $categoria, $status_s);
            }

            if (!$stmt->execute()) {
                throw new Exception("Erro ao executar a declaração: " . $stmt->error);
            }
            $conn->commit();

            $mensagem = $id_servico ? "Serviço atualizado com sucesso!" : "Serviço cadastrado com sucesso!";
            $tipo_mensagem = "sucesso";

            // Limpa os dados do formulário após um cadastro bem-sucedido
            if (!$id_servico) {
                $_POST = array(); // Limpa os campos POST para evitar reenvio
            }
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem = "Erro ao " . ($id_servico ? "atualizar" : "cadastrar") . " serviço: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// Lógica para Excluir Serviço (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['form_action']) && $_POST['form_action'] == 'delete_service' && isset($_POST['id_excluir']) && is_numeric($_POST['id_excluir'])) {
    $id_servico = (int)$_POST['id_excluir'];
    $conn->begin_transaction();
    try {
        $sql = "DELETE FROM Servicos WHERE id_servico = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_servico);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar a declaração de exclusão: " . $stmt->error);
        }
        $conn->commit();
        $mensagem = "Serviço excluído com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = "Erro ao excluir serviço: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}


// --- Lógica de Carregamento e Filtro de Serviços (GET) ---

// Busca todos os serviços para exibir
$servicos = [];
// CORREÇÃO AQUI: Adicionado 'categoria' à consulta SELECT
$sql = "SELECT id_servico, nome, descricao, preco, duracao, categoria, status_s FROM Servicos ORDER BY nome";
$result = $conn->query($sql);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $servicos[] = $row;
        }
    }
} else {
    error_log("Erro ao buscar serviços: " . $conn->error);
    $mensagem = "Erro ao carregar serviços do banco de dados.";
    $tipo_mensagem = "erro";
}

// O array $categorias agora será $categoriasEstaticas para o filtro e select
$categoriasParaFiltroEFormulario = $categoriasEstaticas;

// Filtros
$categoriaFiltro = $_GET['categoria'] ?? '';
$termoBusca = $_GET['busca'] ?? '';

// Aplicar filtro de categoria (ainda filtra pelos dados do banco)
if (!empty($categoriaFiltro)) {
    // Filtra os serviços já carregados em PHP
    $servicos = array_filter($servicos, fn($s) => isset($s['categoria']) && $s['categoria'] === $categoriaFiltro);
}
// Aplicar filtro de busca
if (!empty($termoBusca)) {
    // Filtra os serviços já carregados em PHP
    $servicos = array_filter($servicos, fn($s) =>
        stripos($s['nome'], $termoBusca) !== false || stripos($s['descricao'], $termoBusca) !== false
    );
}

// Fecha a conexão com o banco de dados
$conn->close();
?>

<div class="container">
    <?php if (!empty($mensagem)): ?>
        <div class="alerta alerta-<?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="header-card">
            <h1>Serviços</h1>
            <button class="btn-novo" onclick="abrirModalServico()">Novo Serviço</button>
        </div>

        <div class="filtro-container">
            <div class="categorias-filtro">
                <a href="servicos.php" class="categoria-item <?php echo empty($categoriaFiltro) ? 'ativo' : ''; ?>">Todos</a>
                <?php foreach ($categoriasEstaticas as $categoria): // Usa as categorias estáticas para o filtro ?>
                    <a href="?categoria=<?php echo urlencode($categoria); ?>" class="categoria-item <?php echo $categoriaFiltro === $categoria ? 'ativo' : ''; ?>">
                        <?php echo htmlspecialchars($categoria); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form action="" method="GET" class="form-busca">
                <?php if (!empty($categoriaFiltro)): ?>
                    <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoriaFiltro); ?>">
                <?php endif; ?>
                <input type="text" name="busca" placeholder="Buscar serviços..." value="<?php echo htmlspecialchars($termoBusca); ?>">
                <button type="submit" class="btn-buscar">Buscar</button>
                <?php if (!empty($termoBusca) || !empty($categoriaFiltro)): ?>
                    <a href="servicos.php" class="btn-limpar">Limpar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="servicos-grid">
            <?php if (empty($servicos)): ?>
                <div class="mensagem-vazia">
                    <p>Nenhum serviço encontrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($servicos as $servico): ?>
                    <div class="servico-card">
                        <div class="servico-categoria"><?php echo isset($servico['categoria']) ? htmlspecialchars($servico['categoria']) : 'N/A'; ?></div>
                        <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>
                        <p class="servico-descricao"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                        <div class="servico-detalhes">
                            <span><strong>Duração:</strong> <?php echo htmlspecialchars($servico['duracao']); ?> min</span>
                            <span><strong>Preço:</strong> R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="servico-acoes">
                            <button class="btn-editar" onclick="abrirModalServico(<?php echo htmlspecialchars($servico['id_servico']); ?>)">Editar</button>
                            <button class="btn-excluir" onclick="confirmarExclusao(<?php echo htmlspecialchars($servico['id_servico']); ?>)">Excluir</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="modalServico" class="modal">
    <div class="modal-content">
        <span class="fechar" onclick="fecharModalServico()">&times;</span>
        <h2 id="tituloModal">Novo Serviço</h2>
        <form id="formServico" action="servicos.php" method="POST">
            <input type="hidden" name="form_action" value="save_service">
            <input type="hidden" id="servico_id" name="servico_id" value="">

            <div class="form-group">
                <label for="nome">Nome do Serviço:</label>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="form-group">
                <label for="categoria">Categoria:</label>
                <select id="categoria" name="categoria" required>
                    <option value="">Selecione</option>
                    <?php foreach ($categoriasEstaticas as $categoria): // Usa as categorias estáticas no select do formulário ?>
                        <option value="<?php echo htmlspecialchars($categoria); ?>"><?php echo htmlspecialchars($categoria); ?></option>
                    <?php endforeach; ?>
                    <option value="nova">Nova Categoria...</option>
                </select>
            </div>

            <div id="novaCategoriaGroup" class="form-group" style="display: none;">
                <label for="novaCategoria">Nova Categoria:</label>
                <input type="text" id="novaCategoria" name="novaCategoria">
            </div>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="duracao">Duração (minutos):</label>
                <input type="number" id="duracao" name="duracao" min="1" required>
            </div>

            <div class="form-group">
                <label for="preco">Preço (R$):</label>
                <input type="number" id="preco" name="preco" min="0" step="0.01" required>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancelar" onclick="fecharModalServico()">Cancelar</button>
                <button type="submit" class="btn-salvar">Salvar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalConfirmacao" class="modal">
    <div class="modal-content modal-confirmacao">
        <h2>Confirmar Exclusão</h2>
        <p>Tem certeza que deseja excluir este serviço?</p>
        <div class="form-actions">
            <button type="button" class="btn-cancelar" onclick="fecharModalConfirmacao()">Cancelar</button>
            <button type="button" class="btn-excluir-confirmar" onclick="excluirServico()">Excluir</button>
        </div>
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
    
    .categorias-filtro {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .categoria-item {
        padding: 8px 12px;
        background-color: #f0f0f0;
        color: #333;
        border-radius: 20px;
        text-decoration: none;
        font-size: 14px;
        transition: background-color 0.2s;
    }
    
    .categoria-item:hover {
        background-color: #e0e0e0;
    }
    
    .categoria-item.ativo {
        background-color: #0b3556;
        color: white;
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
    
    .servicos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .servico-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        position: relative;
        background-color: #f9f9f9;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .servico-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .servico-categoria {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: #0b3556;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .servico-card h3 {
        margin: 0 0 10px 0;
        color: #0b3556;
        padding-right: 80px;
    }
    
    .servico-descricao {
        margin: 0 0 15px 0;
        color: #555;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .servico-detalhes {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .servico-acoes {
        display: flex;
        gap: 10px;
    }
    
    .btn-editar, .btn-excluir {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        flex: 1;
        text-align: center;
    }
    
    .btn-editar {
        background-color: #0b3556;
        color: white;
    }
    
    .btn-excluir {
        background-color: #f44336;
        color: white;
    }
    
    .mensagem-vazia {
        padding: 20px;
        text-align: center;
        color: #666;
        grid-column: 1 / -1;
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
    
    .modal-confirmacao {
        max-width: 400px;
        text-align: center;
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
    .form-group select,
    .form-group textarea {
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
    
    .btn-excluir-confirmar {
        background-color: #f44336;
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
        
        .servicos-grid {
            grid-template-columns: 1fr;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
</style>

<script>
    // Variável para armazenar o ID do serviço a ser excluído
    let servicoParaExcluir = null;

    // Função para abrir o modal de serviço
    function abrirModalServico(servicoId = null) {
        const modal = document.getElementById('modalServico');
        const tituloModal = document.getElementById('tituloModal');
        const form = document.getElementById('formServico');

        modal.style.display = 'block';

        if (servicoId) {
            tituloModal.textContent = 'Editar Serviço';
            document.getElementById('servico_id').value = servicoId;

            // Busca os dados do serviço na lista de serviços que o PHP já disponibilizou
            const servicosData = <?php echo json_encode($servicos); ?>;
            const servico = servicosData.find(s => s.id_servico == servicoId);

            if (servico) {
                document.getElementById('nome').value = servico.nome;
                document.getElementById('descricao').value = servico.descricao;
                document.getElementById('duracao').value = servico.duracao;
                document.getElementById('preco').value = servico.preco;

                // Preencher a categoria
                const categoriaSelect = document.getElementById('categoria');
                let categoriaEncontradaNaListaEstatica = false;

                // Percorre as categorias estáticas para ver se a categoria do serviço existe na lista
                // Adicionado verificação `if (servico.categoria)` para evitar erro se 'categoria' estiver faltando
                // embora com a correção SQL, não deva mais faltar.
                if (servico.categoria) {
                    <?php foreach ($categoriasEstaticas as $cat): ?>
                        if (servico.categoria === '<?php echo htmlspecialchars($cat); ?>') {
                            categoriaSelect.value = servico.categoria;
                            categoriaEncontradaNaListaEstatica = true;
                        }
                    <?php endforeach; ?>
                }


                // Se a categoria do serviço não estiver nas categorias estáticas,
                // selecionar 'nova' e preencher o campo 'novaCategoria'.
                if (!categoriaEncontradaNaListaEstatica && servico.categoria) {
                    categoriaSelect.value = 'nova';
                    document.getElementById('novaCategoriaGroup').style.display = 'block';
                    document.getElementById('novaCategoria').value = servico.categoria;
                    document.getElementById('novaCategoria').setAttribute('required', 'required');
                } else {
                    document.getElementById('novaCategoriaGroup').style.display = 'none';
                    document.getElementById('novaCategoria').value = '';
                    document.getElementById('novaCategoria').removeAttribute('required');
                }

            } else {
                console.error("Serviço com ID " + servicoId + " não encontrado na lista.");
                fecharModalServico();
            }
        } else {
            // Lógica para Novo Serviço
            tituloModal.textContent = 'Novo Serviço';
            form.reset();
            document.getElementById('servico_id').value = '';
            document.getElementById('novaCategoriaGroup').style.display = 'none';
            document.getElementById('novaCategoria').value = '';
            document.getElementById('novaCategoria').removeAttribute('required');
            document.getElementById('categoria').value = ''; // Garante que o select de categoria esteja "Selecione"
        }
    }

    // Função para fechar o modal de serviço
    function fecharModalServico() {
        const modal = document.getElementById('modalServico');
        modal.style.display = 'none';
        document.getElementById('formServico').reset();
        document.getElementById('servico_id').value = '';
        document.getElementById('novaCategoriaGroup').style.display = 'none';
        document.getElementById('novaCategoria').value = '';
        document.getElementById('novaCategoria').removeAttribute('required');
        document.getElementById('categoria').value = ''; // Garante que o select de categoria esteja "Selecione"
    }

    // Função para abrir o modal de confirmação de exclusão
    function confirmarExclusao(servicoId) {
        servicoParaExcluir = servicoId;
        const modal = document.getElementById('modalConfirmacao');
        modal.style.display = 'block';
    }

    // Função para fechar o modal de confirmação
    function fecharModalConfirmacao() {
        const modal = document.getElementById('modalConfirmacao');
        modal.style.display = 'none';
        servicoParaExcluir = null;
    }

    // Função para excluir o serviço
    function excluirServico() {
        if (servicoParaExcluir) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'servicos.php';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id_excluir';
            inputId.value = servicoParaExcluir;
            form.appendChild(inputId);

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'form_action';
            inputAction.value = 'delete_service';
            form.appendChild(inputAction);

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Mostrar campo de nova categoria quando "Nova Categoria" for selecionada
    document.getElementById('categoria').addEventListener('change', function() {
        const novaCategoriaGroup = document.getElementById('novaCategoriaGroup');
        const novaCategoriaInput = document.getElementById('novaCategoria');
        if (this.value === 'nova') {
            novaCategoriaGroup.style.display = 'block';
            novaCategoriaInput.setAttribute('required', 'required');
        } else {
            novaCategoriaGroup.style.display = 'none';
            novaCategoriaInput.removeAttribute('required');
            novaCategoriaInput.value = '';
        }
    });

    // Fecha os modais se o usuário clicar fora deles
    window.addEventListener('click', function(event) {
        const modalServico = document.getElementById('modalServico');
        const modalConfirmacao = document.getElementById('modalConfirmacao');

        if (event.target == modalServico) {
            fecharModalServico();
        }

        if (event.target == modalConfirmacao) {
            fecharModalConfirmacao();
        }
    });
</script>

</body>
</html>
