class ControleDeAtendimentos {
  constructor() {
    this.apiUrl = "api/";
    this.carregarAtendimentos();

    document.getElementById("formAtendimento").addEventListener("submit", (e) => {
      e.preventDefault();
      this.salvarAtendimento();
    });
  }

  carregarAtendimentos() {
    fetch(this.apiUrl + "listarAtendimentos.php")
      .then(response => response.json())
      .then(data => this.renderizarTabela(data))
      .catch(error => console.error("Erro ao listar atendimentos:", error));
  }

  renderizarTabela(atendimentos) {
    const tbody = document.getElementById("listaAtendimentos");
    tbody.innerHTML = "";

    atendimentos.forEach(atendimento => {
      const tr = document.createElement("tr");

      tr.innerHTML = `
        <td>${atendimento.id}</td>
        <td>${atendimento.nome_tutor}</td>
        <td>${atendimento.nome_pet}</td>
        <td>${atendimento.data}</td>
        <td>${atendimento.descricao}</td>
        <td class="actions">
          <button class="edit" onclick="controle.editarAtendimento(${atendimento.id})">Editar</button>
          <button onclick="controle.excluirAtendimento(${atendimento.id})">Excluir</button>
        </td>
      `;

      tbody.appendChild(tr);
    });
  }

  salvarAtendimento() {
    const id = document.getElementById("idAtendimento").value;
    const nomeTutor = document.getElementById("nomeTutor").value;
    const nomePet = document.getElementById("nomePet").value;
    const data = document.getElementById("data").value;
    const descricao = document.getElementById("descricao").value;

    const dados = { id, nomeTutor, nomePet, data, descricao };
    const url = id ? "editarAtendimento.php" : "cadastrarAtendimento.php";

    fetch(this.apiUrl + url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(dados)
    })
      .then(response => response.json())
      .then(() => {
        this.limparFormulario();
        this.carregarAtendimentos();
      })
      .catch(error => console.error("Erro ao salvar atendimento:", error));
  }

  editarAtendimento(id) {
    const linha = [...document.querySelectorAll("#listaAtendimentos tr")]
      .find(tr => tr.children[0].innerText == id);

    document.getElementById("idAtendimento").value = linha.children[0].innerText;
    document.getElementById("nomeTutor").value = linha.children[1].innerText;
    document.getElementById("nomePet").value = linha.children[2].innerText;
    document.getElementById("data").value = linha.children[3].innerText;
    document.getElementById("descricao").value = linha.children[4].innerText;
  }

  excluirAtendimento(id) {
    if (confirm("Tem certeza que deseja excluir este atendimento?")) {
      fetch(this.apiUrl + "excluirAtendimento.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
      })
        .then(response => response.json())
        .then(() => this.carregarAtendimentos())
        .catch(error => console.error("Erro ao excluir atendimento:", error));
    }
  }

  limparFormulario() {
    document.getElementById("formAtendimento").reset();
    document.getElementById("idAtendimento").value = "";
  }
}

const controle = new ControleDeAtendimentos();
