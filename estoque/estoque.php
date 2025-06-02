<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['usuario_id']) && !isset($_SESSION['id_usuario'])) {
    $_SESSION['usuario_id'] = 1; 
}


require_once('../conecta_db.php'); 
$conn = conecta_db();


$conexaoRealizadaComSucesso = false;
$mensagemErroConexao = ''; 

if ($conn) { 
    if ($conn instanceof mysqli) { 
        if ($conn->connect_error) {
            // A conexão foi instanciada mas falhou ao conectar efetivamente
            $mensagemErroConexao = "Erro de conexão MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error;
            error_log("estoque.php: " . $mensagemErroConexao);
        } elseif (!$conn->ping()) {
            // A conexão pode ter sido estabelecida, mas não está mais ativa/responsiva
            $mensagemErroConexao = "Conexão MySQL estabelecida, mas o ping falhou. Erro: " . $conn->error;
            error_log("estoque.php: " . $mensagemErroConexao);
        } else {
            // Tudo OK com a conexão
            $conexaoRealizadaComSucesso = true;
        }
    } else {
        // conecta_db() retornou algo que não é um objeto mysqli (inesperado se conecta_db() for para mysqli)
        $tipoRetornado = gettype($conn);
        if(is_object($conn)) $tipoRetornado .= " - Classe: " . get_class($conn);
        $mensagemErroConexao = "conecta_db() não retornou um objeto mysqli esperado. Tipo retornado: " . $tipoRetornado;
        error_log("estoque.php: " . $mensagemErroConexao);
    }
} else {
    // conecta_db() retornou um valor falso (false, null), indicando falha na obtenção da conexão.
    $mensagemErroConexao = "A função conecta_db() retornou um valor falso (provavelmente false ou null), indicando falha ao obter a conexão.";
    error_log("estoque.php: " . $mensagemErroConexao);
}


// Processar formulário de cadastro/edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if (!$conexaoRealizadaComSucesso) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de conexão ao processar o formulário. Verifique o banco de dados. Detalhe: ' . $mensagemErroConexao]);
        exit;
    }
    $resposta = array('status' => 'erro', 'mensagem' => 'Ocorreu um erro desconhecido.');
    
    $erros_msg = [];
    if (empty(trim($_POST['nome']))) $erros_msg[] = "Nome";
    if (empty(trim($_POST['categoria']))) $erros_msg[] = "Categoria";
    if (!isset($_POST['quantidade_atual']) || trim($_POST['quantidade_atual']) === '' || !is_numeric($_POST['quantidade_atual'])) $erros_msg[] = "Quantidade Atual";
    if (!isset($_POST['quantidade_minima']) || trim($_POST['quantidade_minima']) === '' || !is_numeric($_POST['quantidade_minima'])) $erros_msg[] = "Quantidade Mínima";
    if (!isset($_POST['quantidade_maxima']) || trim($_POST['quantidade_maxima']) === '' || !is_numeric($_POST['quantidade_maxima'])) $erros_msg[] = "Quantidade Máxima";
    if (empty(trim($_POST['unidade_medida']))) $erros_msg[] = "Unidade de Medida";
    
    $preco_original_post = isset($_POST['preco_unitario']) ? trim($_POST['preco_unitario']) : '';
    if ($preco_original_post === '' || !is_numeric(str_replace(',', '.', $preco_original_post))) $erros_msg[] = "Preço Unitário";

    if (!empty($erros_msg)) {
        $resposta = ['status' => 'erro', 'mensagem' => 'Campos obrigatórios inválidos ou não preenchidos: ' . implode(', ', $erros_msg) . '.'];
        echo json_encode($resposta);
        exit;
    }

    $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT) : null;
    $nome = trim($_POST['nome']);
    $categoria = trim($_POST['categoria']);
    $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');
    $unidade_medida = trim($_POST['unidade_medida']);
    $fornecedor = trim(isset($_POST['fornecedor']) ? $_POST['fornecedor'] : '');
    $localizacao = trim(isset($_POST['localizacao']) ? $_POST['localizacao'] : '');

    $quantidade_atual = (int)$_POST['quantidade_atual'];
    $quantidade_minima = (int)$_POST['quantidade_minima'];
    $quantidade_maxima = (int)$_POST['quantidade_maxima'];
    $preco_unitario = (float)str_replace(',', '.', $_POST['preco_unitario']);

    $data_validade_post = isset($_POST['data_validade']) ? trim($_POST['data_validade']) : '';
    if (!empty($data_validade_post) && !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $data_validade_post)) {
        $resposta = array('status' => 'erro', 'mensagem' => 'Formato de Data de Validade inválido. Use AAAA-MM-DD.');
        echo json_encode($resposta);
        exit;
    }
    $data_validade = !empty($data_validade_post) ? $data_validade_post : null;

    if ($_POST['acao'] === 'cadastrar') {
        $stmt = $conn->prepare("INSERT INTO estoque (nome, categoria, descricao, quantidade_atual, quantidade_minima, quantidade_maxima, unidade_medida, preco_unitario, data_validade, fornecedor, localizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiisdsss", $nome, $categoria, $descricao, $quantidade_atual, $quantidade_minima, $quantidade_maxima, $unidade_medida, $preco_unitario, $data_validade, $fornecedor, $localizacao);
        
        if ($stmt->execute()) {
            $resposta = array('status' => 'sucesso', 'mensagem' => 'Item cadastrado com sucesso!');
        } else {
            error_log("Erro ao cadastrar item (estoque.php): Statement Error: " . $stmt->error . " (SQLSTATE: " . $stmt->sqlstate . ") - Connection Error: " . $conn->error);
            $resposta = array('status' => 'erro', 'mensagem' => 'Erro ao cadastrar item. Se o problema persistir, contate o suporte. Detalhe: '. $stmt->error);
        }
        $stmt->close();
    } 
    elseif ($_POST['acao'] === 'editar' && !empty($id)) {
        $stmt = $conn->prepare("UPDATE estoque SET nome = ?, categoria = ?, descricao = ?, quantidade_atual = ?, quantidade_minima = ?, quantidade_maxima = ?, unidade_medida = ?, preco_unitario = ?, data_validade = ?, fornecedor = ?, localizacao = ? WHERE id = ?");
        $stmt->bind_param("sssiiisdsssi", $nome, $categoria, $descricao, $quantidade_atual, $quantidade_minima, $quantidade_maxima, $unidade_medida, $preco_unitario, $data_validade, $fornecedor, $localizacao, $id);
        
        if ($stmt->execute()) {
            $resposta = array('status' => 'sucesso', 'mensagem' => 'Item atualizado com sucesso!');
        } else {
            error_log("Erro ao atualizar item (estoque.php): Statement Error: " . $stmt->error . " (SQLSTATE: " . $stmt->sqlstate . ") - Connection Error: " . $conn->error);
            $resposta = array('status' => 'erro', 'mensagem' => 'Erro ao atualizar item. Se o problema persistir, contate o suporte. Detalhe: '. $stmt->error);
        }
        $stmt->close();
    }
    
    echo json_encode($resposta);
    exit;
}

// Processar exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    if (!$conexaoRealizadaComSucesso) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de conexão ao excluir. Verifique o banco de dados. Detalhe: ' . $mensagemErroConexao]);
        exit;
    }
    $id = filter_var($_POST['excluir_id'], FILTER_SANITIZE_NUMBER_INT);
    
    if (empty($id)) {
        echo json_encode(array('status' => 'erro', 'mensagem' => 'ID inválido para exclusão.'));
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM estoque WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(array('status' => 'sucesso', 'mensagem' => 'Item excluído com sucesso!'));
    } else {
        error_log("Erro ao excluir item (estoque.php): Statement Error: " . $stmt->error . " (SQLSTATE: " . $stmt->sqlstate . ") - Connection Error: " . $conn->error);
        echo json_encode(array('status' => 'erro', 'mensagem' => 'Erro ao excluir item. Pode estar relacionado a outros registros. Detalhe: ' . $stmt->error));
    }
    $stmt->close();
    exit;
}

// Buscar item específico para edição (via GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    if (!$conexaoRealizadaComSucesso) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de conexão ao buscar item. Verifique o banco de dados. Detalhe: ' . $mensagemErroConexao]);
        exit;
    }
    $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
    
    if (empty($id)) {
        echo json_encode(array('status' => 'erro', 'mensagem' => 'ID inválido para buscar item.'));
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM estoque WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if ($item) {
        if (!empty($item['data_validade']) && $item['data_validade'] === '0000-00-00') {
            $item['data_validade'] = ''; 
        }
        echo json_encode($item);
    } else {
        echo json_encode(array('status' => 'erro', 'mensagem' => 'Item não encontrado.'));
    }
    exit;
}

// Buscar todos os itens para listagem (com filtros via GET)
$itens = array();
if ($conexaoRealizadaComSucesso) { 
    $filtro_categoria = isset($_GET['filtro_categoria']) ? trim($_GET['filtro_categoria']) : '';
    $filtro_nome = isset($_GET['filtro_nome']) ? trim($_GET['filtro_nome']) : '';
    $filtro_estoque = isset($_GET['filtro_estoque']) ? trim($_GET['filtro_estoque']) : '';

    $sql = "SELECT * FROM estoque WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($filtro_categoria)) {
        $sql .= " AND categoria = ?";
        $params[] = $filtro_categoria;
        $types .= "s";
    }

    if (!empty($filtro_nome)) {
        $sql .= " AND nome LIKE ?";
        $params[] = "%" . $filtro_nome . "%"; 
        $types .= "s";
    }

    if ($filtro_estoque === 'baixo') {
        $sql .= " AND quantidade_atual <= quantidade_minima";
    }

    $sql .= " ORDER BY nome ASC";

    $stmt_list = $conn->prepare($sql);

    if ($stmt_list) {
        if (!empty($types)) {
            $stmt_list->bind_param($types, ...$params);
        }
        if ($stmt_list->execute()) {
            $result = $stmt_list->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $itens[] = $row;
                }
            } else {
                error_log("Erro ao obter resultado da listagem de estoque: Statement Error: " . $stmt_list->error . " - Connection Error: " . $conn->error);
            }
        } else {
            error_log("Erro ao executar listagem de estoque: Statement Error: " . $stmt_list->error . " - Connection Error: " . $conn->error);
        }
        $stmt_list->close();
    } else {
        error_log("Erro ao preparar query de listagem de estoque: " . $conn->error);
    }
}
// Se $conexaoRealizadaComSucesso for false, $itens permanecerá vazio.
// A mensagem de erro $mensagemErroConexao será usada na tabela.
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque - PetPlus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* Seu CSS existente ... (mantido como no original para brevidade) */
        .container-estoque { padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
        .form-estoque { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { height: 80px; resize: vertical; }
        .form-group.full-width { grid-column: 1 / -1; }
        .btn-container { display: flex; justify-content: flex-end; gap: 10px; grid-column: 1 / -1; margin-top: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: background-color 0.2s; }
        .btn-primary { background-color: #4CAF50; color: white; }
        .btn-primary:hover { background-color: #45a049; }
        .btn-secondary { background-color: #aaa; color: white; }
        .btn-secondary:hover { background-color: #888; }
        .btn-info { background-color: #2196F3; color: white; }
        .btn-info:hover { background-color: #1e88e5; }
        .filtros { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; align-items: flex-end; }
        .filtros > div { flex-grow: 1; min-width: 180px; }
        .filtros label { display: block; margin-bottom: 5px; font-size: 0.9em; }
        .filtros select, .filtros input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.95em; }
        table th { background-color: #f2f2f2; font-weight: 600; }
        .acoes { display: flex; gap: 8px; }
        .acoes button { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85em; }
        .btn-editar { background-color: #FFC107; color: black; }
        .btn-editar:hover { background-color: #f9b000; }
        .btn-excluir { background-color: #f44336; color: white; }
        .btn-excluir:hover { background-color: #e53935; }
        .estoque-baixo { color: #f44336; font-weight: bold; }
        .estoque-baixo span[title="Estoque baixo"] { font-size: 1.1em; vertical-align: middle; }
        .tabela-container { overflow-x: auto; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container">
        <?php include '../includes/sidebar.php'; ?>
        <main class="content">
            <h1>Controle de Estoque de Medicamentos e Insumos</h1>
            
            <div class="container-estoque">
                <h2 id="form-titulo">Cadastrar Novo Item</h2>
                <form id="formEstoque" class="form-estoque">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" id="acao" name="acao" value="cadastrar">
                    
                    <div class="form-group"> <label for="nome">Nome*</label> <input type="text" id="nome" name="nome" required> </div>
                    <div class="form-group">
                        <label for="categoria">Categoria*</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecione</option>
                            <option value="Medicamento">Medicamento</option>
                            <option value="Insumo Médico">Insumo Médico</option>
                            <option value="Produto de Higiene">Produto de Higiene</option>
                            <option value="Alimento">Alimento</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="unidade_medida">Unidade de Medida*</label>
                        <select id="unidade_medida" name="unidade_medida" required>
                            <option value="">Selecione</option>
                            <option value="Unidade">Unidade</option> <option value="Caixa">Caixa</option> <option value="Frasco">Frasco</option> <option value="Ampola">Ampola</option> <option value="Pacote">Pacote</option> <option value="Kg">Kg</option> <option value="g">g</option> <option value="mg">mg</option> <option value="mL">mL</option> <option value="L">L</option>
                        </select>
                    </div>
                    <div class="form-group"> <label for="quantidade_atual">Quantidade Atual*</label> <input type="number" id="quantidade_atual" name="quantidade_atual" min="0" step="any" required> </div>
                    <div class="form-group"> <label for="quantidade_minima">Quantidade Mínima*</label> <input type="number" id="quantidade_minima" name="quantidade_minima" min="0" step="any" value="5" required> </div>
                    <div class="form-group"> <label for="quantidade_maxima">Quantidade Máxima*</label> <input type="number" id="quantidade_maxima" name="quantidade_maxima" min="0" step="any" value="100" required> </div>
                    <div class="form-group"> <label for="preco_unitario">Preço Unitário (R$)*</label> <input type="text" id="preco_unitario" name="preco_unitario" placeholder="Ex: 10.50 ou 10,50" required> </div>
                    <div class="form-group"> <label for="data_validade">Data de Validade</label> <input type="date" id="data_validade" name="data_validade"> </div>
                    <div class="form-group"> <label for="fornecedor">Fornecedor</label> <input type="text" id="fornecedor" name="fornecedor"> </div>
                    <div class="form-group"> <label for="localizacao">Localização no Estoque</label> <input type="text" id="localizacao" name="localizacao"> </div>
                    <div class="form-group full-width"> <label for="descricao">Descrição</label> <textarea id="descricao" name="descricao"></textarea> </div>
                    <div class="btn-container"> <button type="button" id="btnCancelar" class="btn btn-secondary" style="display: none;">Cancelar</button> <button type="submit" id="btnSalvar" class="btn btn-primary">Salvar</button> </div>
                </form>
            </div>
            
            <div class="container-estoque">
                <h2>Itens em Estoque</h2>
                <div class="filtros">
                    <div>
                        <label for="filtro_categoria">Categoria:</label>
                        <select id="filtro_categoria">
                            <option value="">Todas</option>
                            <option value="Medicamento" <?php echo (isset($filtro_categoria) && $filtro_categoria === 'Medicamento') ? 'selected' : ''; ?>>Medicamento</option>
                            <option value="Insumo Médico" <?php echo (isset($filtro_categoria) && $filtro_categoria === 'Insumo Médico') ? 'selected' : ''; ?>>Insumo Médico</option>
                            <option value="Produto de Higiene" <?php echo (isset($filtro_categoria) && $filtro_categoria === 'Produto de Higiene') ? 'selected' : ''; ?>>Produto de Higiene</option>
                            <option value="Alimento" <?php echo (isset($filtro_categoria) && $filtro_categoria === 'Alimento') ? 'selected' : ''; ?>>Alimento</option>
                            <option value="Outro" <?php echo (isset($filtro_categoria) && $filtro_categoria === 'Outro') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                    <div> <label for="filtro_nome">Nome:</label> <input type="text" id="filtro_nome" placeholder="Buscar por nome..." value="<?php echo isset($filtro_nome) ? htmlspecialchars($filtro_nome) : ''; ?>"> </div>
                    <div>
                        <label for="filtro_estoque">Estoque:</label>
                        <select id="filtro_estoque">
                            <option value="" <?php echo (isset($filtro_estoque) && $filtro_estoque === '') ? 'selected' : ''; ?>>Todos</option>
                            <option value="baixo" <?php echo (isset($filtro_estoque) && $filtro_estoque === 'baixo') ? 'selected' : ''; ?>>Estoque Baixo</option>
                        </select>
                    </div>
                    <button id="btnFiltrar" class="btn btn-info">Filtrar</button>
                </div>
                
                <div class="tabela-container">
                    <table>
                        <thead>
                            <tr> <th>Nome</th> <th>Categoria</th> <th>Quantidade</th> <th>Unidade</th> <th>Preço Unit.</th> <th>Validade</th> <th>Ações</th> </tr>
                        </thead>
                        <tbody id="tabelaEstoque">
                            <?php if (!$conexaoRealizadaComSucesso): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">
                                        Não foi possível carregar os itens. Verifique a conexão com o banco de dados.
                                        <?php if (!empty($mensagemErroConexao)) { echo "<br><small style='color:red;'>Detalhe do erro: " . htmlspecialchars($mensagemErroConexao) . "</small>"; } ?>
                                    </td>
                                </tr>
                            <?php elseif (empty($itens)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">
                                        Nenhum item encontrado com os filtros atuais.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($itens as $item_row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item_row['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($item_row['categoria']); ?></td>
                                        <td class="<?php echo $item_row['quantidade_atual'] <= $item_row['quantidade_minima'] ? 'estoque-baixo' : ''; ?>">
                                            <?php echo $item_row['quantidade_atual']; ?>
                                            <?php if ($item_row['quantidade_atual'] <= $item_row['quantidade_minima']): ?>
                                                <span title="Estoque baixo"> ⚠️</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item_row['unidade_medida']); ?></td>
                                        <td>R$ <?php echo number_format($item_row['preco_unitario'], 2, ',', '.'); ?></td>
                                        <td><?php echo (!empty($item_row['data_validade']) && $item_row['data_validade'] !== '0000-00-00') ? date('d/m/Y', strtotime($item_row['data_validade'])) : '-'; ?></td>
                                        <td class="acoes">
                                            <button class="btn-editar" onclick="editarItem(<?php echo $item_row['id']; ?>)" title="Editar Item">Editar</button>
                                            <button class="btn-excluir" onclick="confirmarExclusao(<?php echo $item_row['id']; ?>)" title="Excluir Item">Excluir</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // SEU JAVASCRIPT EXISTENTE (Não precisa de alteração devido a esta mudança no PHP)
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const formEstoque = document.getElementById('formEstoque');
            const btnCancelar = document.getElementById('btnCancelar');
            const btnFiltrar = document.getElementById('btnFiltrar');
            
            if (formEstoque) {
                formEstoque.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(formEstoque);
                    Swal.showLoading();

                    fetch('estoque.php', { // O endpoint continua o mesmo
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => { // Pega o texto do erro do corpo da resposta
                                try {
                                    const errorData = JSON.parse(text); // Tenta parsear como JSON
                                    throw new Error(errorData.mensagem || 'Erro na rede ou servidor: ' + response.status + ' ' + response.statusText);
                                } catch (jsonError) { // Se não for JSON, usa o texto ou um erro genérico
                                    throw new Error(text || 'Erro na rede ou servidor: ' + response.status + ' ' + response.statusText);
                                }
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        Swal.close();
                        if (data.status === 'sucesso') {
                            Swal.fire({
                                icon: 'success', title: 'Sucesso!', text: data.mensagem,
                                confirmButtonColor: '#4CAF50', timer: 2000, timerProgressBar: true
                            }).then(() => {
                                const cleanUrl = window.location.pathname; // Remove query params para limpar filtros visuais
                                window.location.href = cleanUrl; 
                            });
                        } else {
                            Swal.fire({
                                icon: 'error', title: 'Erro!', text: data.mensagem || 'Ocorreu um erro ao processar a solicitação.',
                                confirmButtonColor: '#f44336'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Erro no fetch:', error);
                        Swal.fire({
                            icon: 'error', title: 'Erro de Conexão!',
                            text: error.message || 'Não foi possível conectar ao servidor.', // error.message pode conter a msg do PHP
                            confirmButtonColor: '#f44336'
                        });
                    });
                });
            }
            
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function() {
                    limparFormulario();
                });
            }
            
            if(btnFiltrar) {
                btnFiltrar.addEventListener('click', function() {
                    const filtroCategoria = document.getElementById('filtro_categoria').value;
                    const filtroNome = document.getElementById('filtro_nome').value;
                    const filtroEstoque = document.getElementById('filtro_estoque').value;
                    
                    const params = new URLSearchParams();
                    if (filtroCategoria) params.append('filtro_categoria', filtroCategoria);
                    if (filtroNome) params.append('filtro_nome', filtroNome);
                    if (filtroEstoque) params.append('filtro_estoque', filtroEstoque);
                    
                    window.location.href = `estoque.php?${params.toString()}`;
                });
            }
        });
        
        function editarItem(id) {
            Swal.showLoading();
            fetch(`estoque.php?id=${id}`) // O endpoint continua o mesmo
                .then(response => {
                    if (!response.ok) { throw new Error('Erro ao buscar item: ' + response.status + ' ' + response.statusText); }
                    return response.json();
                })
                .then(data => {
                    Swal.close();
                    if (data.status && data.status === 'erro') {
                        Swal.fire('Erro!', data.mensagem, 'error');
                        return;
                    }
                    
                    document.getElementById('id').value = data.id;
                    document.getElementById('nome').value = data.nome;
                    document.getElementById('categoria').value = data.categoria;
                    document.getElementById('descricao').value = data.descricao || '';
                    document.getElementById('quantidade_atual').value = data.quantidade_atual;
                    document.getElementById('quantidade_minima').value = data.quantidade_minima;
                    document.getElementById('quantidade_maxima').value = data.quantidade_maxima;
                    document.getElementById('unidade_medida').value = data.unidade_medida;
                    document.getElementById('preco_unitario').value = String(data.preco_unitario).replace('.', ',');
                    document.getElementById('data_validade').value = data.data_validade && data.data_validade !== '0000-00-00' ? data.data_validade : '';
                    document.getElementById('fornecedor').value = data.fornecedor || '';
                    document.getElementById('localizacao').value = data.localizacao || '';
                    
                    document.getElementById('acao').value = 'editar';
                    document.getElementById('btnCancelar').style.display = 'inline-block';
                    document.getElementById('form-titulo').textContent = 'Editar Item';
                    document.getElementById('btnSalvar').textContent = 'Atualizar';
                    
                    document.getElementById('formEstoque').scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(error => {
                    Swal.close();
                    console.error('Erro ao carregar dados para edição:', error);
                    Swal.fire('Erro!', error.message || 'Não foi possível carregar os dados do item para edição.', 'error');
                });
        }
        
        function confirmarExclusao(id) {
            Swal.fire({
                title: 'Tem certeza?', text: "Esta ação não poderá ser revertida!", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#aaa',
                confirmButtonText: 'Sim, excluir!', cancelButtonText: 'Cancelar', reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    excluirItem(id);
                }
            });
        }
        
        function excluirItem(id) {
            const formData = new FormData();
            formData.append('excluir_id', id);
            
            Swal.showLoading();
            fetch('estoque.php', { // O endpoint continua o mesmo
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) { throw new Error('Erro ao excluir: ' + response.status + ' ' + response.statusText); }
                return response.json();
            })
            .then(data => {
                Swal.close();
                if (data.status === 'sucesso') {
                    Swal.fire('Excluído!', data.mensagem, 'success')
                    .then(() => {
                        // Recarrega a página mantendo os filtros atuais, se houver.
                        window.location.href = window.location.href; 
                    });
                } else {
                    Swal.fire('Erro!', data.mensagem || 'Não foi possível excluir o item.', 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Erro ao excluir item:', error);
                Swal.fire('Erro!', error.message || 'Ocorreu um problema na comunicação para excluir o item.', 'error');
            });
        }
        
        function limparFormulario() {
            const form = document.getElementById('formEstoque');
            if (form) form.reset();
            document.getElementById('id').value = '';
            document.getElementById('acao').value = 'cadastrar';
            document.getElementById('btnCancelar').style.display = 'none';
            document.getElementById('form-titulo').textContent = 'Cadastrar Novo Item';
            document.getElementById('btnSalvar').textContent = 'Salvar';
            const nomeInput = document.getElementById('nome');
            if (nomeInput) nomeInput.focus();
        }

        window.editarItem = editarItem;
        window.confirmarExclusao = confirmarExclusao;
    </script>
</body>
</html>
<?php

if ($conexaoRealizadaComSucesso && $conn instanceof mysqli) {
    $conn->close();
}
?>