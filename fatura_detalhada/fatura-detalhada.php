<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Fatura Detalhada</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f6f9fc;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    h1 {
      text-align: center;
      color: #333;
    }

    .grupo-formulario {
      margin-bottom: 20px;
    }

    .grupo-formulario label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .grupo-formulario input,
    .grupo-formulario select,
    .grupo-formulario textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }

    .botoes-formulario {
      text-align: center;
      margin-top: 20px;
    }

    button {
      padding: 10px 20px;
      margin: 5px;
      border: none;
      border-radius: 6px;
      background-color: #4caf50;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }

    button:hover {
      background-color: #45a049;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 40px;
    }

    table th,
    table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }

    table th {
      background-color: #f2f2f2;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .container {
        padding: 15px;
      }

      h1 {
        font-size: 1.5em;
      }

      .grupo-formulario input,
      .grupo-formulario select,
      .grupo-formulario textarea {
        font-size: 14px;
        padding: 8px;
      }

      table th, table td {
        padding: 8px;
      }

      button {
        width: 100%;
        font-size: 14px;
        padding: 12px 20px;
      }
    }
  </style>
</head>
<body>
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

  <script src="js/ControladorFatura.js"></script>
</body>
</html>
