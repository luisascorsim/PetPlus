class ControladorFatura {
  constructor() {
    this.apiUrl = "api/fatura/"
    this.carregarFaturas()
    document.getElementById("formularioFatura").addEventListener("submit", (e) => {
      e.preventDefault()
      this.salvarFatura()
    })
  }

  carregarFaturas() {
    fetch(this.apiUrl + "listarFaturas.php")
      .then((response) => response.json())
      .then((data) => {
        if (Array.isArray(data)) {
          this.renderizarTabela(data)
        } else {
          console.log("Nenhuma fatura encontrada ou erro:", data.mensagem)
          this.renderizarTabela([])
        }
      })
      .catch((error) => console.error("Erro ao carregar faturas:", error))
  }

  salvarFatura() {
    const fatura = {
      nome_tutor: document.getElementById("nomeTutor").value,
      nome_pet: document.getElementById("nomePet").value,
      data_fatura: document.getElementById("dataFatura").value,
      servico: document.getElementById("servico").value,
      valor: document.getElementById("valor").value,
      pagamento: document.getElementById("pagamento").value,
      observacoes: document.getElementById("observacoes").value,
      clinica: document.getElementById("clinica").value,
      profissional: document.getElementById("profissional").value,
    }

    fetch(this.apiUrl + "cadastrarFatura.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(fatura),
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.mensagem)
        this.carregarFaturas()
        document.getElementById("formularioFatura").reset()
      })
      .catch((error) => console.error("Erro ao salvar fatura:", error))
  }

  excluirFatura(id) {
    if (confirm("Tem certeza que deseja excluir esta fatura?")) {
      fetch(this.apiUrl + "excluirFatura.php?id=" + id)
        .then((response) => response.json())
        .then((data) => {
          alert(data.mensagem)
          this.carregarFaturas()
        })
        .catch((error) => console.error("Erro ao excluir fatura:", error))
    }
  }

  renderizarTabela(faturas) {
    const tbody = document.getElementById("listaFaturas")
    tbody.innerHTML = ""

    if (faturas.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9">Nenhuma fatura encontrada</td></tr>'
      return
    }

    faturas.forEach((fatura) => {
      const tr = document.createElement("tr")
      tr.innerHTML = `
        <td>${fatura.id}</td>
        <td>${fatura.nome_tutor}</td>
        <td>${fatura.nome_pet}</td>
        <td>${fatura.data_fatura}</td>
        <td>${fatura.servico}</td>
        <td>${fatura.valor}</td>
        <td>${fatura.pagamento}</td>
        <td>${fatura.profissional}</td>
        <td>
          <button onclick="controlador.excluirFatura(${fatura.id})">Excluir</button>
        </td>
      `
      tbody.appendChild(tr)
    })
  }
}

const controlador = new ControladorFatura()
