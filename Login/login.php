<?php
include 'conecta_db.php';
if (isset($_POST['email']) && isset($_POST['senha'])) {
    $oMysql = conecta_db();

    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $query = "SELECT nome, senha FROM Cliente WHERE email = '$email' AND senha = '$senha'";
    $resultado = $oMysql->query($query);

    if ($resultado && $resultado->num_rows == 1) {
        // Login exitoso
        session_start();
        $_SESSION['cliente'] = $email;
        header('Location: Tela_Principal.php');
        exit();
    } else {
        echo "<script>alert('Email ou senha incorretos.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - PetPlus</title>
  <link rel="stylesheet" href="login.css" />


<style>
  body {
    background-color: #ffffff;
    font-family: 'Arial', sans-serif;
  }
  
  .container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
  }
  
  .login-box {
    background-color: #cde0f0;
    padding: 50px;
    border-radius: 15px;
    width: 300px;
    position: relative;
    text-align: center;
  }
  
  .logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 5px;
  }
  
  .logo img {
    width: 120px;
  }

  .botao-voltar {
    position: absolute;
    top: 20px;
    left: 20px; 
  }

  .icone-botao {
    width: 30px;
    height: 30px;
  }
   
  h2 {
    font-family: Tahoma, sans-serif;
    font-size: 25px;
    color: #013a63;
    margin: 15px 0;
  }
  
  form {
    display: flex;
    flex-direction: column;
    gap: 12px;
    
  }
  
  label {
    text-align: left;
    font-weight: 500;
    margin-bottom: -8px;
    color: #0D3B66;
    font-family: Tahoma, sans-serif;
    font-weight: bold;
  }
  
  input {
    padding: 10px;
    border-radius: 5px;
    border: none;
    font-size: 14px;
  }
  
  .recuperar {
    text-align: right;
  }
  
  .recuperar a {
    font-size: 12px;
    color: #012a4a;
    text-decoration: none;
  }
  
  button {
    margin-top: 10px;
    background-color: #012a4a;
    color: #ffffff;
    padding: 12px;
    border: none;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
  }
  
  .cadastro-texto {
    margin-top: 20px;
    font-size: 13px;
    color: #000000;
  }
  
  .azul {
    color: #007bff;
  }
</style>
</head>
<body>

<div class="container mt-3"> 
  <div class="login-box">
    <div class="logo">
      <img src="logo.png" alt="Logo PetPlus" />
    </div>
    <a href="main.php" class="botao-voltar">
      <img src="seta.png" alt="Voltar" class="icone-botao" />
    </a>
    <h2>Entrar no PetPlus</h2>

    <form method="POST" action="login.php">
      <label for="email">Login</label>
      <input type="email" id="email" name="email" placeholder="Email" required />

      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" placeholder="Senha" required />

      <div class="recuperar">
        <a href="#">Recuperar senha</a>
      </div>

      <button type="submit">Entrar</button>
    </form>

    <p class="cadastro-texto">
      Não tem uma conta? <a href="cadastre-se.php">Crie a sua conta aqui</a>
    </p>
  </div>
</div>

</body>
</html>
