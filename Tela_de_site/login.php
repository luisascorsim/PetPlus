<?php
include '../conecta_db.php';
$conn = conecta_db();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha'];

    $sql = "SELECT id_usuario, nome, senha FROM Usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($senha, $usuario['senha'])) {
            session_start();
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nome'] = $usuario['nome'];
            header('Location: ../home/index.php');
            exit();
        }
    }
    
    echo "<script>alert('Login inválido'); window.history.back();</script>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
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
    <a href="tela_site.html" class="botao-voltar">
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
      Não tem uma conta? <a href="cadastro.html">Crie a sua conta aqui</a>
    </p>
  </div>
</div>

</body>
</html>
