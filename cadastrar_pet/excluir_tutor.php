<?php
// É uma boa prática iniciar a sessão para mensagens de feedback (flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'biblioteca.php'; // Para a função debug(), se necessário
include 'conecta_db.php'; // Para a função conecta_db()

// Estabelece a conexão com o banco de dados
$oMysql = conecta_db();
if (!$oMysql) {
    // Em um caso real, logar o erro e mostrar uma página de erro amigável.
    die("Falha fatal na conexão com o banco de dados: " . mysqli_connect_error());
}

// Habilita o MySQLi para lançar exceções em caso de erro, facilitando o try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Determina a ação a ser executada (listar, mostrar formulário de adição/edição, processar)
$action = $_GET['action'] ?? 'list'; // Ação padrão é listar
$teste_id = isset($_GET['teste_id']) ? (int)$_GET['teste_id'] : null; // ID para editar/deletar

// Variáveis para feedback ao usuário (mensagens flash via sessão)
$mensagem_status = $_SESSION['mensagem_status'] ?? '';
$status_type = $_SESSION['status_type'] ?? ''; // 'sucesso' ou 'erro'
unset($_SESSION['mensagem_status'], $_SESSION['status_type']); // Limpa a mensagem após exibir

$dados_para_edicao = null; // Para preencher o formulário de edição

try {
    // --- PROCESSAMENTO DAS AÇÕES DE ESCRITA (POST para Adicionar/Atualizar, GET para Deletar) ---

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $descricao = trim($_POST['descricao'] ?? '');

        if (isset($_POST['submit_add'])) { // Formulário de adição enviado
            if (empty($descricao)) {
                $_SESSION['mensagem_status'] = "O campo 'Descrição' não pode estar vazio.";
                $_SESSION['status_type'] = "erro";
            } else {
                $stmt = $oMysql->prepare("INSERT INTO tb_teste (descricao) VALUES (?)");
                $stmt->bind_param("s", $descricao);
                $stmt->execute();
                $stmt->close();
                $_SESSION['mensagem_status'] = "Registro adicionado com sucesso!";
                $_SESSION['status_type'] = "sucesso";
            }
            header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF'])); // Redireciona para a lista
            exit;

        } elseif (isset($_POST['submit_edit'])) { // Formulário de edição enviado
            $id_para_atualizar = filter_input(INPUT_POST, 'teste_id_hidden', FILTER_VALIDATE_INT);
            if (empty($descricao)) {
                $_SESSION['mensagem_status'] = "O campo 'Descrição' não pode estar vazio.";
                $_SESSION['status_type'] = "erro";
                 // Mantém o ID para o formulário de edição ser recarregado corretamente
                header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF']) . '?action=edit&teste_id=' . $id_para_atualizar);
                exit;
            } elseif ($id_para_atualizar) {
                $stmt = $oMysql->prepare("UPDATE tb_teste SET descricao = ? WHERE teste_id = ?");
                $stmt->bind_param("si", $descricao, $id_para_atualizar);
                $stmt->execute();
                $stmt->close();
                $_SESSION['mensagem_status'] = "Registro atualizado com sucesso!";
                $_SESSION['status_type'] = "sucesso";
            } else {
                $_SESSION['mensagem_status'] = "ID inválido para atualização.";
                $_SESSION['status_type'] = "erro";
            }
            header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF'])); // Redireciona para a lista
            exit;
        }
    } elseif ($action === 'delete' && $teste_id) {
        // Ação de deletar (geralmente após uma confirmação do usuário)
        $stmt = $oMysql->prepare("DELETE FROM tb_teste WHERE teste_id = ?");
        $stmt->bind_param("i", $teste_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensagem_status'] = "Registro excluído com sucesso!";
        $_SESSION['status_type'] = "sucesso";
        header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF'])); // Redireciona para a lista
        exit;
    }

    // Se a ação for editar, busca os dados do registro para preencher o formulário
    if ($action === 'edit' && $teste_id) {
        $stmt = $oMysql->prepare("SELECT teste_id, descricao FROM tb_teste WHERE teste_id = ?");
        $stmt->bind_param("i", $teste_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $dados_para_edicao = $result->fetch_assoc();
        $stmt->close();
        if (!$dados_para_edicao) {
            $_SESSION['mensagem_status'] = "Registro com ID " . htmlspecialchars($teste_id) . " não encontrado para edição.";
            $_SESSION['status_type'] = "erro";
            header('Location: ' . htmlspecialchars($_SERVER['PHP_SELF']));
            exit;
        }
    }

    // Busca todos os registros para exibir na lista (ação padrão 'list')
    $registros = [];
    // A lista só é mostrada se não estivermos em um formulário de adição/edição explícito
    // ou se for a ação 'list' (após CUD ou carregamento inicial)
    if ($action === 'list' || ($action !== 'add' && !($action === 'edit' && $dados_para_edicao))) {
         $query_select_all = "SELECT teste_id, descricao FROM tb_teste ORDER BY teste_id DESC";
         $result_select_all = $oMysql->query($query_select_all);
         if ($result_select_all) {
             while ($linha = $result_select_all->fetch_object()) {
                 $registros[] = $linha;
             }
         }
    }

} catch (mysqli_sql_exception $e) {
    // Captura exceções de SQL (erros de query, etc.)
    // Em um ambiente de produção, logar $e->getMessage() em vez de exibi-lo diretamente.
    $mensagem_status = "Erro de Banco de Dados: Ocorreu um problema ao processar sua solicitação. Por favor, tente novamente. Detalhe: " . htmlspecialchars($e->getMessage());
    $status_type = "erro";
    // Se o erro ocorrer durante uma operação POST ou DELETE com redirect, a mensagem pode não ser vista.
    // Por isso, mensagens de sucesso/erro para CUD são passadas via sessão.
    // Este catch aqui é mais para erros nos SELECTs ou se um redirect falhar.
} catch (Exception $e) {
    $mensagem_status = "Erro Geral: " . htmlspecialchars($e->getMessage());
    $status_type = "erro";
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <title>CRUD Simples em PHP - Tabela Teste</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    /* Pequenos ajustes para botões na tabela */
    .actions-column a {
        margin-right: 5px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-sm bg-light navbar-light"> <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">CRUD Teste</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="collapsibleNavbar">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link <?php echo ($action === 'list' || $action === '') ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Listar Registros</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($action === 'add') ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=add">Adicionar Novo</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">

    <?php if (!empty($mensagem_status)): ?>
        <div class="alert <?php echo $status_type === 'sucesso' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensagem_status); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add' || ($action === 'edit' && $dados_para_edicao)): ?>
        
        <h2><?php echo $action === 'add' ? 'Adicionar Novo Registro' : 'Atualizar Registro (ID: ' . htmlspecialchars($dados_para_edicao['teste_id']) . ')'; ?></h2>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <?php if ($action === 'edit' && $dados_para_edicao): ?>
                <input type="hidden" name="teste_id_hidden" value="<?php echo htmlspecialchars($dados_para_edicao['teste_id']); ?>">
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição:*</label>
                <input type="text" class="form-control" id="descricao" name="descricao" 
                       value="<?php echo $action === 'edit' && $dados_para_edicao ? htmlspecialchars($dados_para_edicao['descricao']) : ''; ?>" 
                       placeholder="Digite a descrição" required>
            </div>
            
            <?php if ($action === 'add'): ?>
                <button type="submit" name="submit_add" class="btn btn-primary">Salvar Novo Registro</button>
            <?php elseif ($action === 'edit' && $dados_para_edicao): ?>
                <button type="submit" name="submit_edit" class="btn btn-primary">Atualizar Registro</button>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">Cancelar</a>
        </form>

    <?php else: // Ação padrão: 'list' ou após uma operação CUD bem-sucedida (após redirect) ?>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Lista de Registros</h2>
            <a class="btn btn-primary" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=add">Adicionar Novo Registro</a>
        </div>
        <p>Os dados que estão registrados na tabela <code>tb_teste</code> são:</p>
        
        <?php if (empty($registros)): ?>
            <div class="alert alert-info">Nenhum registro encontrado. Clique em "Adicionar Novo Registro" para começar.</div>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th style="width: 200px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($registro->teste_id); ?></td>
                        <td><?php echo htmlspecialchars($registro->descricao); ?></td>
                        <td class="actions-column">
                            <a class="btn btn-success btn-sm" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=edit&teste_id=<?php echo $registro->teste_id; ?>">Alterar</a>
                            <a class="btn btn-danger btn-sm" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=delete&teste_id=<?php echo $registro->teste_id; ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir o registro ID: <?php echo $registro->teste_id; ?>?');">Excluir</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

</div> <?php
// Fecha a conexão com o banco de dados no final do script
if ($oMysql instanceof mysqli) { // Verifica se é um objeto mysqli válido
    $oMysql->close();
}
?>
</body>
</html>