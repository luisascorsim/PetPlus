<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o header
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Use caminho relativo para o conecta_db.php
require_once('../conecta_db.php');
$conn = conecta_db();

// Busca dados do usuário logado
$query = "SELECT nome, email FROM Usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    $usuario = ['nome' => 'Usuário', 'email' => ''];
}
?>

<div class="container">
  <div class="card">
    <h1>PetPlus</h1>
    <div class="botoes">
      <a href="../agenda/agenda.php" class="botao">
        <i class="fas fa-calendar-alt"></i>
        Agenda
      </a>
      <a href="../clientes/clientes.php" class="botao">
        <i class="fas fa-users"></i>
        Clientes
      </a>
      <a href="../servicos/servicos.php" class="botao">
        <i class="fas fa-concierge-bell"></i>
        Serviços
      </a>
      <a href="../cadastrar_pet/cadastrar_pets.php" class="botao">
        <i class="fas fa-paw"></i>
        Cadastro
      </a>
      <a href="../relatorio_atendimentos/relatorio-atendimentos.php" class="botao">
        <i class="fas fa-chart-bar"></i>
        Relatórios
      </a>
      <a href="../diagnostico_consulta/diagnostico-consulta.php" class="botao">
        <i class="fas fa-stethoscope"></i>
        Prontuários
      </a>
      <a href="../historico_consultas/historico-consultas.php" class="botao">
        <i class="fas fa-clipboard-list"></i>
        Consultas
      </a>
      <a href="../vacinas_e_controle_do_peso/vacinas-peso.php" class="botao">
        <i class="fas fa-syringe"></i>
        Vacinas
      </a>
    </div>
  </div>
</div>

<style>
  .container {
    margin-left: 220px;
    padding: 80px 30px 30px;
    transition: margin-left 0.3s;
  }
  
  .card {
    background-color: white;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    padding: 30px;
    margin-bottom: 20px;
  }
  
  h1 {
    color: #0b3556;
    margin-bottom: 30px;
    text-align: center;
  }

  .botoes {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    justify-items: center;
  }

  .botao {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #0b3556;
    color: white;
    text-decoration: none;
    padding: 20px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 500;
    transition: all 0.3s;
    width: 100%;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }

  .botao i {
    margin-right: 10px;
    font-size: 24px;
  }

  .botao:hover {
    background-color: #0d4371;
    transform: translateY(-3px);
    box-shadow: 0 6px 8px rgba(0,0,0,0.15);
  }
  
  /* Responsividade */
  @media (max-width: 768px) {
    .container {
      margin-left: 60px;
      padding: 70px 15px 15px;
    }
    
    .botoes {
      grid-template-columns: 1fr;
    }
    
    .botao {
      width: 100%;
    }
  }
</style>

</body>
</html>
