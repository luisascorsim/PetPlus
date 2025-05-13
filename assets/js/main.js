document.addEventListener("DOMContentLoaded", () => {
  // Smooth scrolling para links de navegação
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault()

      const targetId = this.getAttribute("href")
      if (targetId === "#") return

      const targetElement = document.querySelector(targetId)
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80,
          behavior: "smooth",
        })
      }
    })
  })

  // Formulário de contato
  const contactForm = document.querySelector(".contact-form")
  if (contactForm) {
    contactForm.addEventListener("submit", (e) => {
      e.preventDefault()

      const nome = document.getElementById("nome").value
      const email = document.getElementById("email").value
      const mensagem = document.getElementById("mensagem").value

      // Aqui você pode adicionar validação ou enviar para um backend
      console.log("Formulário enviado:", { nome, email, mensagem })

      // Simulando envio bem-sucedido
      alert("Mensagem enviada com sucesso! Entraremos em contato em breve.")
      contactForm.reset()
    })
  }
})
