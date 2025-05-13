// Arquivo JavaScript principal para o projeto PetPlus
document.addEventListener("DOMContentLoaded", () => {
  // Função para aplicar o tema selecionado
  function aplicarTema() {
    const tema = getCookie("tema") || "claro"
    const tamanhoFonte = getCookie("tamanho_fonte") || "medio"

    // Remove classes anteriores
    document.body.classList.remove("tema-claro", "tema-escuro", "tema-azul")
    document.body.classList.remove("fonte-pequena", "fonte-media", "fonte-grande")

    // Adiciona as novas classes
    document.body.classList.add("tema-" + tema)
    document.body.classList.add("fonte-" + tamanhoFonte)
  }

  // Função para obter o valor de um cookie
  function getCookie(nome) {
    const cookies = document.cookie.split(";")
    for (let i = 0; i < cookies.length; i++) {
      const cookie = cookies[i].trim()
      if (cookie.startsWith(nome + "=")) {
        return cookie.substring(nome.length + 1)
      }
    }
    return null
  }

  // Aplica o tema ao carregar a página
  aplicarTema()

  // Configuração dos dropdowns no header
  setupHeaderDropdowns()
})

// Função para configurar os dropdowns no header
function setupHeaderDropdowns() {
  const dropdowns = document.querySelectorAll(".dropdown")

  dropdowns.forEach((dropdown) => {
    dropdown.addEventListener("click", function (e) {
      // Verifica se estamos em um dispositivo móvel
      if (window.innerWidth <= 768) {
        const content = this.querySelector(".dropdown-content")

        // Alterna a visibilidade do dropdown
        if (content.style.display === "block") {
          content.style.display = "none"
        } else {
          // Fecha todos os outros dropdowns
          document.querySelectorAll(".dropdown-content").forEach((el) => {
            el.style.display = "none"
          })

          content.style.display = "block"
        }

        // Impede que o evento se propague para o documento
        e.stopPropagation()
      }
    })
  })

  // Fecha os dropdowns quando clicar fora deles
  document.addEventListener("click", () => {
    if (window.innerWidth <= 768) {
      document.querySelectorAll(".dropdown-content").forEach((el) => {
        el.style.display = "none"
      })
    }
  })
}
