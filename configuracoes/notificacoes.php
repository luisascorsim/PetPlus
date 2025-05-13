<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o header
include_once('../includes/header.php');

// Simula algumas notificações para demonstração
$notificacoes = [
    [
        'id' => 1,
        'titulo' => 'Consulta Agendada',
        'mensagem' => 'Nova consulta agendada para o pet Rex no dia 15/05/2023.',
        'data' => '2023-05-10 14:30:00',
        'lida' => true
    ],
    [
        'id' => 2,
        'titulo' => 'Vacina Pendente',
        'mensagem' => 'A vacina antirrábica do pet Luna está pendente.',
        'data' => '2023-05-12 09:15:00',
        'lida' => false
    ],
    [
        'id' => 3,
        'titulo' => 'Fatura Gerada',
        'mensagem' => 'Nova fatura gerada no valor de R$ 150,00.',
        'data' => '2023-05-13 16:45:00',
        'lida' => false
    ]
];

// Processa a marcação de notificações como lidas
if (isset($_GET['marcar_lida']) && is_numeric($_GET['marcar_lida'])) {
    $id_notificacao = (int)$_GET['marcar_lida'];
    
    // Em um sistema real, aqui seria feita uma atualização no banco de dados
    // Para este exemplo, apenas atualizamos o array
    foreach ($notificacoes as $key => $notificacao) {
        if ($notificacao['id'] === $id_notificacao) {
            $notificacoes[$key]['lida'] = true;
            break;
        }
    }
}

// Processa a exclusão de notificações
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_notificacao = (int)$_GET['excluir'];
    
    // Em um sistema real, aqui seria feita uma exclusão no banco de dados
    // Para este exemplo, apenas removemos do array
    foreach ($notificacoes as $key => $notificacao) {
        if ($notificacao['id'] === $id_notificacao) {
            unset($notificacoes[$key]);
            break;
        }
    }
}
?>

<div class="container" style="max-width: 800px; margin: 20px auto; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <h1 style="color: #2196f3; text-align: center; margin-bottom: 30px;">Notificações</h1>
    
    <?php if (empty($notificacoes)): ?>
        <p style="text-align: center; color: #666; padding: 20px;">Você não possui notificações.</p>
    <?php else: ?>
        <div style="margin-bottom: 20px; text-align: right;">
            <a href="?marcar_todas=1" style="text-decoration: none; color: #2196f3; font-weight: bold;">Marcar todas como lidas</a>
        </div>
        
        <?php foreach ($notificacoes as $notificacao): ?>
            <div style="margin-bottom: 15px; padding: 15px; border-radius: 4px; background-color: <?php echo $notificacao['lida'] ? '#f9f9f9' : '#e3f2fd'; ?>; border-left: 4px solid <?php echo $notificacao['lida'] ? '#ddd' : '#2196f3'; ?>;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <h3 style="margin: 0; color: #333; font-size: 18px;"><?php echo htmlspecialchars($notificacao['titulo']); ?></h3>
                    <span style="color: #666; font-size: 14px;"><?php echo date('d/m/Y H:i', strtotime($notificacao['data'])); ?></span>
                </div>
                
                <p style="margin: 0 0 15px 0; color: #555;"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <?php if (!$notificacao['lida']): ?>
                        <a href="?marcar_lida=<?php echo $notificacao['id']; ?>" style="text-decoration: none; color: #2196f3; font-size: 14px;">Marcar como lida</a>
                    <?php endif; ?>
                    <a href="?excluir=<?php echo $notificacao['id']; ?>" style="text-decoration: none; color: #f44336; font-size: 14px;">Excluir</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
