<?php
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

session_destroy();

// Redirecionar para a página de login
header("Location: ../Tela_de_site/tela_site.html");
exit;
?>