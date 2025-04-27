<?php
		include 'biblioteca.php';
		include 'header.php';
		include 'conecta_db.php';
		if(isset($_GET['page'])){
			if($_GET['page'] == 1){
				include 'login.php';
				
			}else if($_GET['page'] == 2){
				include 'cadastro.html';

			}else{
				include 'tela_site.html';
			}
		}else{
			include 'tela_site.html';
		}
?>
