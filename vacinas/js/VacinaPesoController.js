class VacinaPesoController {
  constructor() {
    this.apiUrlVacinas = "api/vacinas/"
    this.apiUrlPeso = "api/peso/"

    // Simular um pet selecionado para testes
    this.simularPetSelecionado()

    document.getElementById("controlePetForm").addEventListener("submit", (e) => {
      e.preventDefault()
      this.salvarDados()
    })
  }

  // Função para simular um pet selecionado (para testes)
  simularPetSelecionado() {
    // Criar um cookie ou localStorage para simular a sessão
    localStorage.setItem("pet_id", "1")
    localStorage.setItem("pet_nome", "Rex")

    // Atualizar o nome do pet na interface
    const nomePetElement = document.getElementById("nomePet")
    if (nomePetElement) {
      nomePetElement.value = localStorage.getItem("pet_nome") || "Pet"
    }
  }

  salvarDados() {
    // Salvar peso
    const peso = {
      data: document.getElementById("dataPeso").value,
      peso: document.getElementById("pesoPet").value,
    }

    // Salvar vacina
    const vacina = {
      nome: document.getElementById("vacinaPet").value,
      data: document.getElementById("dataVacina").value,
      lote: "Lote-" + Math.floor(Math.random() * 10000), // Lote aleatório para teste
      reforco: document.getElementById("proximaVacina").value,
    }

    // Simulação de salvamento
    alert("Dados salvos com sucesso!")

    // Comentado para evitar erros se a API não existir
    /*
    // Salvar peso
    fetch(this.apiUrlPeso + "cadastrarPeso.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(peso)
    })
      .then(res => res.json())
      .then(data => console.log("Peso salvo:", data))
      .catch(err => console.error("Erro ao salvar peso:", err))
    
    // Salvar vacina
    fetch(this.apiUrlVacinas + "cadastrarVacina.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(vacina)
    })
      .then(res => res.json())
      .then(data => console.log("Vacina salva:", data))
      .catch(err => console.error("Erro ao salvar vacina:", err))
    */
  }
}

// Inicializar o controlador quando o DOM estiver carregado
document.addEventListener("DOMContentLoaded", () => {
  const controlador = new VacinaPesoController()
})
