<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'Acesso não autorizado. Faça login.'];
    // Tenta determinar o caminho base se ../Tela_de_site não for fixo
    // Para este exemplo, vamos assumir que o caminho é conhecido ou o header.php lida com isso.
    header('Location: ../Tela_de_site/login.php');
    exit();
}

require_once('../conecta_db.php');
$conn = conecta_db();

if (!$conn) {
    $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'Erro de conexão com o banco de dados ao tentar excluir tutor.'];
    header('Location: cadastrar_pets.php#tab-tutores');
    exit();
}

$id_tutor = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_tutor <= 0) {
    $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'ID do tutor inválido para exclusão.'];
    header('Location: cadastrar_pets.php#tab-tutores');
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Habilita exceções para erros do MySQLi

$conn->begin_transaction();

try {
    // Antes de excluir pets, verificar se há dependências deles (ex: Vacinas, Consultas)
    // Se houver, a exclusão pode falhar devido a chaves estrangeiras ou pode ser necessário excluir essas dependências primeiro.
    // Exemplo:
    /*
    $stmt_check_vacinas = $conn->prepare("SELECT COUNT(*) FROM Vacinas v JOIN Pets p ON v.pet_id = p.id_pet WHERE p.id_tutor = ?");
    $stmt_check_vacinas->bind_param("i", $id_tutor);
    $stmt_check_vacinas->execute();
    $result_check_vacinas = $stmt_check_vacinas->get_result()->fetch_row();
    $stmt_check_vacinas->close();
    if ($result_check_vacinas[0] > 0) {
        throw new Exception("Não é possível excluir o tutor pois existem vacinas registradas para seus pets. Remova-as primeiro.");
    }
    */

    // 1. Excluir pets associados ao tutor
    // (Se ON DELETE CASCADE estiver configurado no DB para Pets.id_tutor, este passo manual pode não ser necessário para Pets,
    // mas outras dependências de Pets ainda precisariam ser tratadas)
    $stmt_delete_pets = $conn->prepare("DELETE FROM Pets WHERE id_tutor = ?");
    $stmt_delete_pets->bind_param("i", $id_tutor);
    $stmt_delete_pets->execute();
    // Não precisamos verificar affected_rows aqui, pois um tutor pode não ter pets.
    $stmt_delete_pets->close();

    // 2. Excluir o tutor
    $stmt_delete_tutor = $conn->prepare("DELETE FROM Tutor WHERE id_tutor = ?");
    $stmt_delete_tutor->bind_param("i", $id_tutor);
    $stmt_delete_tutor->execute();

    if ($stmt_delete_tutor->affected_rows > 0) {
        $conn->commit();
        $_SESSION['mensagem_flash'] = ['tipo' => 'sucesso', 'texto' => 'Tutor e seus pets associados foram excluídos com sucesso!'];
    } else {
        // Se o tutor não foi encontrado, pode ser que já foi excluído.
        // Pets podem ter sido excluídos se existissem e o tutor não, mas isso seria estranho.
        $conn->rollback();
        $_SESSION['mensagem_flash'] = ['tipo' => 'aviso', 'texto' => 'Tutor não encontrado. Nenhuma alteração foi feita.'];
    }
    $stmt_delete_tutor->close();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    $error_code = $e->getCode();
    $error_message = $e->getMessage();
    // Logar $error_message em um ambiente de produção.

    if ($error_code == 1451) { // Código de erro para falha de chave estrangeira (ER_ROW_IS_REFERENCED_2)
        $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'Não é possível excluir o tutor. Existem outros registros (ex: consultas, agendamentos não tratados) associados a este tutor ou seus pets. Verifique as dependências.'];
    } else {
        $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'Erro de banco de dados ao excluir o tutor. Por favor, tente novamente. (Cód: ' . $error_code . ')'];
    }
} catch (Exception $e) {
    $conn->rollback();
    // Logar $e->getMessage()
    $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'Erro geral ao processar a exclusão do tutor: ' . htmlspecialchars($e->getMessage())];
}

if ($conn) {
    $conn->close();
}

header('Location: cadastrar_pets.php#tab-tutores'); // Redireciona de volta para a aba de tutores
exit();
?>