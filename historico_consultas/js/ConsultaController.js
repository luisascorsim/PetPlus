class ControladorConsulta {
  constructor() {
    this.apiUrl = "api/consultas/"
    this.carregarConsultas()
    document.getElementById("formConsulta").addEventListener("submit", (e) => {
      e.preventDefault()
      this.salvarConsulta()
    })

    // Simular um pet selecionado para testes
    this.simularPetSelecionado()
  }

  // Função para simular um pet selecionado (para testes)
  simularPetSelecionado() {
    // Criar um cookie ou localStorage para simular a sessão
    localStorage.setItem("pet_id", "1")
    localStorage.setItem("pet_nome", "Rex")

    // Atualizar o nome do pet na interface
    const nomePetElement = document.getElementById("nomePet")
    if (nomePetElement) {
      nomePetElement.textContent = localStorage.getItem("pet_nome") || "Pet"
    }
  }

  carregarConsultas() {
    fetch(this.apiUrl + "listarConsultas.php")
      .then((res) => res.json())
      .then((dados) => this.renderizarTabela(dados))
      .catch((err) => console.error("Erro ao carregar consultas:", err))
  }

  salvarConsulta() {
    const data = document.getElementById("dataConsulta").value
    const descricao = document.getElementById("descricao").value
    const status = document.getElementById("status").value
    const novaConsulta = { data: data, descricao: descricao, status: status }

    fetch(this.apiUrl + "cadastrarConsulta.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(novaConsulta),
    })
      .then((res) => res.json())
      .then((msg) => {
        alert(msg.mensagem || "Consulta salva com sucesso!")
        this.carregarConsultas()
        document.getElementById("formConsulta").reset()
      })
      .catch((err) => console.error("Erro ao salvar consulta:", err))
  }

  excluirConsulta(id) {
    if (!confirm("Tem certeza que deseja excluir esta consulta?")) return
    fetch(this.apiUrl + "excluirConsulta.php?id=" + id)
      .then((res) => res.json())
      .then((msg) => {
        alert(msg.mensagem || "Consulta excluída com sucesso!")
        this.carregarConsultas()
      })
      .catch((err) => console.error("Erro ao excluir consulta:", err))
  }

  renderizarTabela(consultas) {
    const corpoTabela = document.getElementById("listaConsultas")
    corpoTabela.innerHTML = ""

    if (!Array.isArray(consultas) || consultas.length === 0) {
      const tr = document.createElement("tr")
      tr.innerHTML = '<td colspan="5">Nenhuma consulta encontrada</td>'
      corpoTabela.appendChild(tr)
      return
    }

    consultas.forEach((consulta) => {
      const linha = document.createElement("tr")
      linha.innerHTML = `
        <td>${consulta.id}</td>
        <td>${consulta.data}</td>
        <td>${consulta.descricao}</td>
        <td>${consulta.status}</td>
        <td>
          <button class="btn-secondary" onclick="controlador.excluirConsulta(${consulta.id})">Excluir</button>
        </td>
      `
      corpoTabela.appendChild(linha)
    })
  }
}

const controlador = new ControladorConsulta()
