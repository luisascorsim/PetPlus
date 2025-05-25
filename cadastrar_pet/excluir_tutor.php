<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Verifica se o ID do tutor foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: cadastrar_pets.php?mensagem=' . urlencode('ID do tutor não fornecido.') . '&tipo=erro');
    exit();
}

$id_tutor = (int)$_GET['id'];

// Inicia uma transação
$conn->begin_transaction();

try {
    // Primeiro, verifica se existem pets associados a este tutor
    $sql = "SELECT id_pet FROM Pets WHERE id_tutor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_tutor);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Se existirem pets, exclui-os primeiro
    if ($result->num_rows > 0) {
        $sql = "DELETE FROM Pets WHERE id_tutor = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_tutor);
        $stmt->execute();
    }
    
    // Agora exclui o tutor
    $sql = "DELETE FROM Tutor WHERE id_tutor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_tutor);
    $stmt->execute();
    
    // Confirma a transação
    $conn->commit();
    
    // Redireciona com mensagem de sucesso
    header('Location: cadastrar_pets.php?mensagem=' . urlencode('Tutor excluído com sucesso!') . '&tipo=sucesso');
    exit();
} catch (Exception $e) {
    // Reverte a transação em caso de erro
    $conn->rollback();
    
    // Redireciona com mensagem de erro
    header('Location: cadastrar_pets.php?mensagem=' . urlencode('Erro ao excluir tutor: ' . $e->getMessage()) . '&tipo=erro');
    exit();
}

$conn->close();
?>
