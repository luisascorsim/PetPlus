<?php
// Arquivo index.php principal para redirecionamento
session_start();

// Verifica se o usuário está logado
if (isset($_SESSION['id_usuario'])) {
    // Redireciona para a página inicial do sistema
    header('Location: home/index.php');
    exit();
} else {
    // Redireciona para a página de login
    header('Location: Tela_de_site/login.php');
    exit();
}
?>
