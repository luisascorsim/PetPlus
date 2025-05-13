document.addEventListener("DOMContentLoaded", () => {
  // Dados de exemplo para os cards
  const registros = [
    { nome: "Cliente Fulano de Tal", tipo: "Cliente" },
    { nome: "Secretário Fulano", tipo: "Secretária" },
    { nome: "Pet Fulano", tipo: "Pet" },
    { nome: "Veterinário Fulano", tipo: "Veterinário" },
  ]

  // Função para criar um card de cadastro
  function criarCard(registro) {
    const card = document.createElement("div")
    card.classList.add("card")

    const infoDiv = document.createElement("div")
    infoDiv.classList.add("info")

    const nomeSpan = document.createElement("span")
    nomeSpan.classList.add("nome")
    nomeSpan.textContent = registro.nome

    const tipoSpan = document.createElement("span")
    tipoSpan.classList.add("tipo")
    tipoSpan.textContent = registro.tipo

    infoDiv.appendChild(nomeSpan)
    infoDiv.appendChild(tipoSpan)

    const acoesDiv = document.createElement("div")
    acoesDiv.classList.add("acoes")

    const editIcon = document.createElement("img")
    editIcon.src = "icon-edit.png"
    editIcon.alt = "Editar"

    const deleteIcon = document.createElement("img")
    deleteIcon.src = "icon-trash.png"
    deleteIcon.alt = "Excluir"

    acoesDiv.appendChild(editIcon)
    acoesDiv.appendChild(deleteIcon)

    card.appendChild(infoDiv)
    card.appendChild(acoesDiv)

    return card
  }

  // Função para renderizar todos os registros como cards
  function renderizarCards() {
    const container = document.getElementById("cards-container")
    container.innerHTML = "" // Limpa o conteúdo antes de adicionar os novos cards

    registros.forEach((registro) => {
      const card = criarCard(registro)
      container.appendChild(card)
    })
  }

  // Inicializa a página renderizando os cards
  renderizarCards()
})
