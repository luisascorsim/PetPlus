class StatusConsultaController {
  constructor() {
    this.apiUrlAtualizar = "api/atualizarStatusConsulta.php"
    this.apiUrlConsulta = "api/consultarStatusConsulta.php"

    const form = document.getElementById("formStatus")
    form.addEventListener("submit", (evento) => {
      evento.preventDefault()
      this.atualizarStatus()
    })

    const consultaId = document.getElementById("consultaId").value
    this.consultarStatus(consultaId)
  }

  consultarStatus(consultaId) {
    fetch(this.apiUrlConsulta, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ consulta_id: consultaId }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.sucesso) {
          document.getElementById("status").value = data.status
        } else {
          console.warn("Status não encontrado:", data.mensagem)
        }
      })
      .catch((erro) => {
        console.error("Erro ao consultar o status:", erro)
      })
  }

  atualizarStatus() {
    const statusSelecionado = document.getElementById("status").value
    const consultaId = document.getElementById("consultaId").value

    const dados = {
      status: statusSelecionado,
      consulta_id: consultaId,
    }

    fetch(this.apiUrlAtualizar, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(dados),
    })
      .then((res) => res.json())
      .then((respostaJson) => {
        if (respostaJson.sucesso) {
          alert("Status atualizado com sucesso!")
        } else {
          alert("Erro ao atualizar: " + respostaJson.mensagem)
        }
      })
      .catch((erro) => {
        console.error("Erro ao conectar com a API:", erro)
        alert("Erro na comunicação com o servidor.")
      })
  }
}

new StatusConsultaController()
