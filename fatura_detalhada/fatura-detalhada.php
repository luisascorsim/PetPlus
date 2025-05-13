<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = 'fatura';
$page_title = 'Fatura Detalhada';
include_once('../includes/header.php');

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
}

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Detalhada - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    <style>
        /* Estilos específicos da página */
        .container {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="container">
        <h1>Fatura Detalhada</h1>

    <!-- Formulário da Fatura -->
    <form id="formularioFatura">
      <div class="grupo-formulario">
        <label for="nomeTutor">Nome do Tutor</label>
        <input type="text" id="nomeTutor" required />
      </div>

      <div class="grupo-formulario">
        <label for="nomePet">Nome do Pet</label>
        <input type="text" id="nomePet" required />
      </div>

      <div class="grupo-formulario">
        <label for="dataFatura">Data da Fatura</label>
        <input type="date" id="dataFatura" required />
      </div>

      <div class="grupo-formulario">
        <label for="servico">Serviço Realizado</label>
        <input type="text" id="servico" required />
      </div>

      <div class="grupo-formulario">
        <label for="valor">Valor do Serviço (R$)</label>
        <input type="number" id="valor" step="0.01" required />
      </div>

      <div class="grupo-formulario">
        <label for="pagamento">Forma de Pagamento</label>
        <select id="pagamento" required>
          <option value="">Selecione</option>
          <option value="Dinheiro">Dinheiro</option>
          <option value="Cartão de Crédito">Cartão de Crédito</option>
          <option value="Cartão de Débito">Cartão de Débito</option>
          <option value="Pix">Pix</option>
        </select>
      </div>

      <div class="grupo-formulario">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" rows="3"></textarea>
      </div>

      <div class="grupo-formulario">
        <label for="clinica">Nome da Clínica</label>
        <input type="text" id="clinica" required />
      </div>

      <div class="grupo-formulario">
        <label for="profissional">Nome do Profissional Responsável</label>
        <input type="text" id="profissional" required />
      </div>

      <div class="botoes-formulario">
        <button type="submit">Cadastrar Fatura</button>
      </div>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tutor</th>
          <th>Pet</th>
          <th>Data</th>
          <th>Serviço</th>
          <th>Valor (R$)</th>
          <th>Pagamento</th>
          <th>Profissional</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="listaFaturas"></tbody>
    </table>
    </div>
    <script src="../includes/header.js"></script>
</body>
</html>
