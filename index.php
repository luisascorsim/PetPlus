<?php
    include 'biblioteca.php';
    include 'header.php';
    include 'conecta_db.php';

    if (isset($_GET['page'])) {
        if ($_GET['page'] == 1) {
            include 'login.php';
        } else if ($_GET['page'] == 2) {
            include 'cadastre-se.php';
        } else if ($_GET['page'] == 3) {
            include 'Tela_Principal.php';
		} else if ($_GET['page'] == 4) {
            include 'cadastro_usuario.php';
        } else {
            include 'main.php';
        }
    } else {
        include 'main.php';
    }
?>
