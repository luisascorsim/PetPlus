<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php'); // Redireciona se não estiver logado
    exit();
}

// Inclui o header
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Inclui o arquivo de conexão e ativa o relatório de erros do MySQLi
require_once('../conecta_db.php');
$conn = conecta_db();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_usuario_logado = (int)$_SESSION['id_usuario']; // Este é o REMETENTE

// Variáveis para feedback e formulário de edição
$mensagem_feedback = '';
$tipo_feedback = '';
$notificacao_para_editar = null;
$edit_mode = false;

try {
    // --- LÓGICA PARA PROCESSAR AÇÕES (POST e GET) ---

    // Ação: Criar ou Atualizar Notificação (via POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        $id_usuario_destino = filter_input(INPUT_POST, 'id_usuario_destino', FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo'] ?? '');
        $mensagem_conteudo = trim($_POST['mensagem'] ?? ''); // Renomeado para não conflitar com $mensagem_feedback

        if (empty($id_usuario_destino) || empty($titulo) || empty($mensagem_conteudo)) {
            $mensagem_feedback = "Por favor, preencha o destinatário, título e mensagem.";
            $tipo_feedback = "erro";
        } else {
            if ($action === 'criar_notificacao') {
                $sql = "INSERT INTO Notificacoes (remetente_id_usuario, id_usuario, titulo, mensagem) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiss", $id_usuario_logado, $id_usuario_destino, $titulo, $mensagem_conteudo);
                $stmt->execute();
                $stmt->close();
                $mensagem_feedback = "Notificação enviada com sucesso!";
                $tipo_feedback = "sucesso";
            } elseif ($action === 'atualizar_notificacao') {
                $id_notificacao_edit = filter_input(INPUT_POST, 'id_notificacao_edit', FILTER_VALIDATE_INT);
                if ($id_notificacao_edit) {
                    // Apenas permite atualizar se o usuário logado for o remetente
                    $sql = "UPDATE Notificacoes SET id_usuario = ?, titulo = ?, mensagem = ? 
                            WHERE id_notificacao = ? AND remetente_id_usuario = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issii", $id_usuario_destino, $titulo, $mensagem_conteudo, $id_notificacao_edit, $id_usuario_logado);
                    $stmt->execute();
                    if ($stmt->affected_rows > 0) {
                        $mensagem_feedback = "Notificação atualizada com sucesso!";
                        $tipo_feedback = "sucesso";
                    } else {
                        $mensagem_feedback = "Não foi possível atualizar a notificação (verifique permissões ou se a notificação existe).";
                        $tipo_feedback = "erro";
                    }
                    $stmt->close();
                } else {
                    $mensagem_feedback = "ID da notificação para edição inválido.";
                    $tipo_feedback = "erro";
                }
            }
            // Limpar POST para não repopular o formulário com os mesmos dados após sucesso
            $_POST = array(); 
            // Para redirecionar e limpar URL, descomente abaixo e ajuste o nome do arquivo
            // header("Location: enviar_gerenciar_notificacoes.php?feedback=" . ($tipo_feedback === 'sucesso' ? 'ok' : 'erro_post'));
            // exit();
        }
    }

    // Ação: Deletar Notificação (via GET)
    if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id_notificacao_excluir = (int)$_GET['id'];
        // Apenas permite deletar se o usuário logado for o remetente
        $sql_excluir = "DELETE FROM Notificacoes WHERE id_notificacao = ? AND remetente_id_usuario = ?";
        $stmt_excluir = $conn->prepare($sql_excluir);
        $stmt_excluir->bind_param("ii", $id_notificacao_excluir, $id_usuario_logado);
        $stmt_excluir->execute();
        if ($stmt_excluir->affected_rows > 0) {
             header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?feedback=excluida_ok");
        } else {
             header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . "?feedback=excluida_falha");
        }
        $stmt_excluir->close();
        exit();
    }

    // Ação: Popular formulário para Edição (via GET)
    if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id_notificacao_get_edit = (int)$_GET['id'];
        // Busca a notificação apenas se o usuário logado for o remetente
        $sql_get_edit = "SELECT id_notificacao, id_usuario, titulo, mensagem 
                         FROM Notificacoes 
                         WHERE id_notificacao = ? AND remetente_id_usuario = ?";
        $stmt_get_edit = $conn->prepare($sql_get_edit);
        $stmt_get_edit->bind_param("ii", $id_notificacao_get_edit, $id_usuario_logado);
        $stmt_get_edit->execute();
        $result_edit = $stmt_get_edit->get_result();
        if ($result_edit && $result_edit->num_rows > 0) {
            $notificacao_para_editar = $result_edit->fetch_assoc();
            $edit_mode = true;
        } else {
            $mensagem_feedback = "Notificação para edição não encontrada ou você não tem permissão para editá-la.";
            $tipo_feedback = "erro";
        }
        $stmt_get_edit->close();
    }

} catch (Exception $e) {
    $mensagem_feedback = "Ocorreu um erro no sistema: " . $e->getMessage();
    $tipo_feedback = "erro";
}

//LÓGICA PARA BUSCAR DADOS PARA EXIBIÇÃO
// Buscar todos os usuários para o dropdown de destinatário (exceto o próprio usuário logado)
$usuarios_para_select = [];
try {
    $sql_usuarios = "SELECT id_usuario, nome, email FROM Usuarios WHERE id_usuario != ? ORDER BY nome ASC";
    $stmt_usuarios = $conn->prepare($sql_usuarios);
    $stmt_usuarios->bind_param("i", $id_usuario_logado);
    $stmt_usuarios->execute();
    $result_usuarios = $stmt_usuarios->get_result();
    if ($result_usuarios) {
        while ($row_user = $result_usuarios->fetch_assoc()) {
            $usuarios_para_select[] = $row_user;
        }
    }
    $stmt_usuarios->close();
} catch (Exception $e) {
    if(empty($mensagem_feedback)) {
        $mensagem_feedback = "Erro ao carregar lista de usuários.";
        $tipo_feedback = "erro";
    }
}

// Buscar notificações ENVIADAS PELO usuário logado
$notificacoes_enviadas = [];
try {
    $sql_fetch_sent = "SELECT n.id_notificacao, n.titulo, n.mensagem, n.lida, n.data_criacao, 
                             u_dest.nome as nome_usuario_destinatario 
                      FROM Notificacoes n
                      JOIN Usuarios u_dest ON n.id_usuario = u_dest.id_usuario 
                      WHERE n.remetente_id_usuario = ? 
                      ORDER BY n.data_criacao DESC";
    $stmt_fetch_sent = $conn->prepare($sql_fetch_sent);
    $stmt_fetch_sent->bind_param("i", $id_usuario_logado);
    $stmt_fetch_sent->execute();
    $result_fetch_sent = $stmt_fetch_sent->get_result();
    if ($result_fetch_sent) {
        while ($row_n = $result_fetch_sent->fetch_assoc()) {
            $notificacoes_enviadas[] = $row_n;
        }
    }
    $stmt_fetch_sent->close();
} catch (Exception $e) {
     if(empty($mensagem_feedback)) {
        $mensagem_feedback = "Erro ao carregar suas notificações enviadas.";
        $tipo_feedback = "erro";
    }
}

// Feedback de status via GET (após redirecionamentos)
if (isset($_GET['feedback'])) {
    if ($_GET['feedback'] == 'excluida_ok') {
        $mensagem_feedback = 'Notificação excluída com sucesso.';
        $tipo_feedback = 'sucesso';
    } elseif ($_GET['feedback'] == 'excluida_falha') {
        $mensagem_feedback = 'Não foi possível excluir a notificação (verifique permissões ou se ela existe).';
        $tipo_feedback = 'erro';
    }
}

$conn->close();
?>

<style>
    .geral-notificacoes-container { max-width: 900px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
    .geral-notificacoes-container h1, .geral-notificacoes-container h2 { color: #0b3556; margin-bottom: 20px; }
    .form-section { margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 5px; background-color: #f9f9f9; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
    .form-group input[type="text"],
    .form-group input[type="url"],
    .form-group select,
    .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .form-group textarea { min-height: 100px; }
    .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; text-align: center; }
    .btn-primary { background-color: #0b3556; color: white; }
    .btn-primary:hover { background-color: #082840; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .btn-secondary:hover { background-color: #5a6268;}
    .btn-danger { background-color: #dc3545; color: white; }
    .btn-danger:hover { background-color: #c82333; }
    .btn-edit { background-color: #007bff; color: white; margin-right: 5px; font-size: 0.9em; padding: 5px 10px; }
    .btn-edit:hover { background-color: #0069d9; }

    .feedback-message { padding: 15px; margin-bottom: 20px; border-radius: 4px; border: 1px solid transparent; }
    .feedback-sucesso { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
    .feedback-erro { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    table th { background-color: #f2f2f2; color: #333; }
    table td .actions a { margin-right: 5px; }
    .status-lida { color: green; font-weight: bold; }
    .status-nao-lida { color: orange; font-weight: bold; }
</style>

<div class="geral-notificacoes-container">
    <h1>Enviar e Gerenciar Notificações</h1>

    <?php if (!empty($mensagem_feedback)): ?>
        <div class="feedback-message <?php echo $tipo_feedback === 'sucesso' ? 'feedback-sucesso' : 'feedback-erro'; ?>">
            <?php echo htmlspecialchars($mensagem_feedback); ?>
        </div>
    <?php endif; ?>

    <div class="form-section">
        <h2><?php echo $edit_mode ? 'Editar Notificação Enviada' : 'Enviar Nova Notificação'; ?></h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <?php if ($edit_mode && $notificacao_para_editar): ?>
                <input type="hidden" name="action" value="atualizar_notificacao">
                <input type="hidden" name="id_notificacao_edit" value="<?php echo htmlspecialchars($notificacao_para_editar['id_notificacao']); ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="criar_notificacao">
            <?php endif; ?>

            <div class="form-group">
                <label for="id_usuario_destino">Enviar para Usuário (Destinatário):</label>
                <select name="id_usuario_destino" id="id_usuario_destino" required>
                    <option value="">Selecione um usuário</option>
                    <?php foreach ($usuarios_para_select as $usuario): ?>
                        <option value="<?php echo htmlspecialchars($usuario['id_usuario']); ?>" 
                            <?php echo ($edit_mode && $notificacao_para_editar && $notificacao_para_editar['id_usuario'] == $usuario['id_usuario']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($usuario['nome']) . ' (' . htmlspecialchars($usuario['email']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" name="titulo" id="titulo" value="<?php echo $edit_mode && $notificacao_para_editar ? htmlspecialchars($notificacao_para_editar['titulo']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="mensagem">Mensagem:</label>
                <textarea name="mensagem" id="mensagem" rows="5" required><?php echo $edit_mode && $notificacao_para_editar ? htmlspecialchars($notificacao_para_editar['mensagem']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $edit_mode ? 'Atualizar Notificação' : 'Enviar Notificação'; ?></button>
            <?php if ($edit_mode): ?>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-secondary">Cancelar Edição</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>Minhas Notificações Enviadas</h2>
    <?php if (empty($notificacoes_enviadas)): ?>
        <p>Você ainda não enviou nenhuma notificação.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Destinatário</th>
                    <th>Título</th>
                    <th>Mensagem (início)</th>
                    <th>Data Criação</th>
                    <th>Status Leitura (Dest.)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notificacoes_enviadas as $notificacao): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($notificacao['id_notificacao']); ?></td>
                        <td><?php echo htmlspecialchars($notificacao['nome_usuario_destinatario']); ?></td>
                        <td><?php echo htmlspecialchars($notificacao['titulo']); ?></td>
                        <td><?php echo htmlspecialchars(substr($notificacao['mensagem'], 0, 50)) . (strlen($notificacao['mensagem']) > 50 ? '...' : ''); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></td>
                        <td>
                            <?php if ($notificacao['lida']): ?>
                                <span class="status-lida">Lida</span>
                            <?php else: ?>
                                <span class="status-nao-lida">Não Lida</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="?action=editar&id=<?php echo $notificacao['id_notificacao']; ?>" class="btn btn-edit">Editar</a>
                            <a href="?action=excluir&id=<?php echo $notificacao['id_notificacao']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta notificação que você enviou?');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>