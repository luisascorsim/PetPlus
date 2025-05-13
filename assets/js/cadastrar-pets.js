document.addEventListener("DOMContentLoaded", () => {
  // Elementos do DOM
  const petsList = document.getElementById("pets-list")
  const petModal = document.getElementById("pet-modal")
  const confirmModal = document.getElementById("confirm-modal")
  const petForm = document.getElementById("pet-form")
  const modalTitle = document.getElementById("modal-title")
  const newPetBtn = document.getElementById("new-pet-btn")
  const savePetBtn = document.getElementById("save-pet")
  const cancelPetBtn = document.getElementById("cancel-pet")
  const confirmDeleteBtn = document.getElementById("confirm-delete")
  const cancelDeleteBtn = document.getElementById("cancel-delete")
  const closeModalBtns = document.querySelectorAll(".close-modal")
  const filterType = document.getElementById("filter-type")
  const prevPageBtn = document.getElementById("prev-page")
  const nextPageBtn = document.getElementById("next-page")
  const pageInfo = document.getElementById("page-info")
  const photoInput = document.getElementById("pet-photo")
  const photoPreview = document.getElementById("photo-preview")

  // Variáveis de estado
  let currentPage = 1
  let totalPages = 1
  let currentPetId = null
  let pets = []
  let selectedPetId = null

  // Carregar lista de pets
  const loadPets = async (page = 1, filter = "") => {
    try {
      const response = await fetch(`api/pets/listar_pets.php?page=${page}&filter=${filter}`)
      if (!response.ok) {
        throw new Error("Falha ao carregar pets")
      }

      const data = await response.json()
      pets = data.pets || []
      totalPages = data.totalPages || 1

      renderPets()
      updatePagination()
    } catch (error) {
      console.error("Erro:", error)
      showAlert("Erro ao carregar lista de pets", "error")
    }
  }

  // Renderizar lista de pets
  const renderPets = () => {
    if (!petsList) return

    petsList.innerHTML = ""

    if (pets.length === 0) {
      petsList.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center">Nenhum pet encontrado</td>
                </tr>
            `
      return
    }

    pets.forEach((pet) => {
      const row = document.createElement("tr")

      row.innerHTML = `
                <td>${pet.id}</td>
                <td>
                    <img src="${pet.foto || "assets/images/pet-placeholder.jpg"}" alt="${pet.nome}">
                </td>
                <td>${pet.nome}</td>
                <td>${pet.tipo}</td>
                <td>${pet.raca || "-"}</td>
                <td>${pet.idade || "-"}</td>
                <td>${pet.proprietario}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-view" data-id="${pet.id}" title="Visualizar">
                            <i class="view-icon"></i>
                        </button>
                        <button class="btn-edit" data-id="${pet.id}" title="Editar">
                            <i class="edit-icon"></i>
                        </button>
                        <button class="btn-delete" data-id="${pet.id}" title="Excluir">
                            <i class="delete-icon"></i>
                        </button>
                    </div>
                </td>
            `

      petsList.appendChild(row)
    })

    // Adicionar event listeners para os botões de ação
    document.querySelectorAll(".btn-edit").forEach((btn) => {
      btn.addEventListener("click", () => {
        const petId = btn.getAttribute("data-id")
        openEditModal(petId)
      })
    })

    document.querySelectorAll(".btn-delete").forEach((btn) => {
      btn.addEventListener("click", () => {
        const petId = btn.getAttribute("data-id")
        openDeleteModal(petId)
      })
    })

    document.querySelectorAll(".btn-view").forEach((btn) => {
      btn.addEventListener("click", () => {
        const petId = btn.getAttribute("data-id")
        window.location.href = `visualizar_pet.php?id=${petId}`
      })
    })
  }

  // Atualizar informações de paginação
  const updatePagination = () => {
    if (pageInfo) {
      pageInfo.textContent = `Página ${currentPage} de ${totalPages}`
    }

    if (prevPageBtn) {
      prevPageBtn.disabled = currentPage <= 1
    }

    if (nextPageBtn) {
      nextPageBtn.disabled = currentPage >= totalPages
    }
  }

  // Carregar proprietários para o select
  const loadOwners = async () => {
    try {
      const response = await fetch("api/clientes/listar_clientes.php")
      if (!response.ok) {
        throw new Error("Falha ao carregar proprietários")
      }

      const data = await response.json()
      const ownerSelect = document.getElementById("pet-owner")

      if (ownerSelect && data.clientes) {
        ownerSelect.innerHTML = '<option value="">Selecione</option>'

        data.clientes.forEach((cliente) => {
          const option = document.createElement("option")
          option.value = cliente.id
          option.textContent = cliente.nome
          ownerSelect.appendChild(option)
        })
      }
    } catch (error) {
      console.error("Erro:", error)
      showAlert("Erro ao carregar lista de proprietários", "error")
    }
  }

  // Abrir modal para novo pet
  const openNewModal = () => {
    modalTitle.textContent = "Novo Pet"
    petForm.reset()
    currentPetId = null
    photoPreview.innerHTML = ""
    petModal.style.display = "block"
  }

  // Abrir modal para editar pet
  const openEditModal = async (petId) => {
    try {
      const response = await fetch(`api/pets/obter_pet.php?id=${petId}`)
      if (!response.ok) {
        throw new Error("Falha ao carregar dados do pet")
      }

      const pet = await response.json()

      modalTitle.textContent = "Editar Pet"

      // Preencher formulário
      document.getElementById("pet-id").value = pet.id
      document.getElementById("pet-name").value = pet.nome
      document.getElementById("pet-type").value = pet.tipo
      document.getElementById("pet-breed").value = pet.raca || ""
      document.getElementById("pet-age").value = pet.idade || ""
      document.getElementById("pet-weight").value = pet.peso || ""
      document.getElementById("pet-gender").value = pet.sexo || ""
      document.getElementById("pet-owner").value = pet.cliente_id
      document.getElementById("pet-birth").value = pet.data_nascimento || ""
      document.getElementById("pet-notes").value = pet.observacoes || ""

      // Exibir foto se existir
      photoPreview.innerHTML = ""
      if (pet.foto) {
        const img = document.createElement("img")
        img.src = pet.foto
        img.alt = pet.nome
        photoPreview.appendChild(img)
      }

      currentPetId = pet.id
      petModal.style.display = "block"
    } catch (error) {
      console.error("Erro:", error)
      showAlert("Erro ao carregar dados do pet", "error")
    }
  }

  // Abrir modal de confirmação de exclusão
  const openDeleteModal = (petId) => {
    selectedPetId = petId
    confirmModal.style.display = "block"
  }

  // Salvar pet (novo ou edição)
  const savePet = async () => {
    // Validar formulário
    if (!petForm.checkValidity()) {
      petForm.reportValidity()
      return
    }

    const formData = new FormData(petForm)

    // Adicionar ID se for edição
    if (currentPetId) {
      formData.append("id", currentPetId)
    }

    try {
      const url = currentPetId ? "api/pets/editar_pet.php" : "api/pets/cadastrar_pet.php"

      const response = await fetch(url, {
        method: "POST",
        body: formData,
      })

      if (!response.ok) {
        throw new Error("Falha ao salvar pet")
      }

      const data = await response.json()

      if (data.success) {
        showAlert(currentPetId ? "Pet atualizado com sucesso!" : "Pet cadastrado com sucesso!", "success")
        petModal.style.display = "none"
        loadPets(currentPage, filterType.value)
      } else {
        showAlert(data.message || "Erro ao salvar pet", "error")
      }
    } catch (error) {
      console.error("Erro:", error)
      showAlert("Erro ao processar solicitação", "error")
    }
  }

  // Excluir pet
  const deletePet = async () => {
    if (!selectedPetId) return

    try {
      const response = await fetch("api/pets/excluir_pet.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ id: selectedPetId }),
      })

      if (!response.ok) {
        throw new Error("Falha ao excluir pet")
      }

      const data = await response.json()

      if (data.success) {
        showAlert("Pet excluído com sucesso!", "success")
        confirmModal.style.display = "none"
        loadPets(currentPage, filterType.value)
      } else {
        showAlert(data.message || "Erro ao excluir pet", "error")
      }
    } catch (error) {
      console.error("Erro:", error)
      showAlert("Erro ao processar solicitação", "error")
    }
  }

  // Exibir alerta
  const showAlert = (message, type) => {
    const alertDiv = document.createElement("div")
    alertDiv.className = `alert alert-${type === "error" ? "danger" : "success"}`
    alertDiv.textContent = message

    const header = document.querySelector(".dashboard-header")
    header.insertAdjacentElement("afterend", alertDiv)

    setTimeout(() => {
      alertDiv.remove()
    }, 5000)
  }

  // Preview da foto
  if (photoInput) {
    photoInput.addEventListener("change", function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader()

        reader.onload = (e) => {
          photoPreview.innerHTML = ""
          const img = document.createElement("img")
          img.src = e.target.result
          photoPreview.appendChild(img)
        }

        reader.readAsDataURL(this.files[0])
      }
    })
  }

  // Event Listeners
  if (newPetBtn) {
    newPetBtn.addEventListener("click", openNewModal)
  }

  if (savePetBtn) {
    savePetBtn.addEventListener("click", savePet)
  }

  if (cancelPetBtn) {
    cancelPetBtn.addEventListener("click", () => {
      petModal.style.display = "none"
    })
  }

  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", deletePet)
  }

  if (cancelDeleteBtn) {
    cancelDeleteBtn.addEventListener("click", () => {
      confirmModal.style.display = "none"
    })
  }

  closeModalBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const modal = this.closest(".modal")
      if (modal) {
        modal.style.display = "none"
      }
    })
  })

  if (filterType) {
    filterType.addEventListener("change", function () {
      currentPage = 1
      loadPets(currentPage, this.value)
    })
  }

  if (prevPageBtn) {
    prevPageBtn.addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage--
        loadPets(currentPage, filterType.value)
      }
    })
  }

  if (nextPageBtn) {
    nextPageBtn.addEventListener("click", () => {
      if (currentPage < totalPages) {
        currentPage++
        loadPets(currentPage, filterType.value)
      }
    })
  }

  // Fechar modal ao clicar fora
  window.addEventListener("click", (event) => {
    if (event.target === petModal) {
      petModal.style.display = "none"
    }

    if (event.target === confirmModal) {
      confirmModal.style.display = "none"
    }
  })

  // Inicializar
  loadPets()
  loadOwners()
})
