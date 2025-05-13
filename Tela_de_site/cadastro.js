document.getElementById("formCadastro").addEventListener("submit", function (e) {
  e.preventDefault()

  // Validações
  const cpf = document.getElementById("cpf").value
  if (!/^\d{11}$/.test(cpf)) {
    alert("CPF deve ter 11 dígitos numéricos")
    return
  }

  const dataNasc = new Date(document.getElementById("data_nasc").value)
  if (dataNasc >= new Date()) {
    alert("Data de nascimento inválida")
    return
  }

  // Envio AJAX
  fetch("cadastro.php", {
    method: "POST",
    body: new FormData(this),
  }).catch((error) => {
    console.error("Erro:", error)
    alert("Falha na conexão")
  })
})
