document.addEventListener("DOMContentLoaded", () => {
  // Sidebar toggle functionality
  const sidebarToggle = document.getElementById("sidebar-toggle")
  const dashboardContainer = document.querySelector(".dashboard-container")

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", () => {
      dashboardContainer.classList.toggle("sidebar-collapsed")
    })
  }

  // Mobile sidebar toggle
  const createMobileToggle = () => {
    if (window.innerWidth <= 768) {
      const mobileToggle = document.createElement("button")
      mobileToggle.className = "mobile-sidebar-toggle"
      mobileToggle.innerHTML = "<span></span>"

      const header = document.querySelector(".dashboard-header")
      if (header && !document.querySelector(".mobile-sidebar-toggle")) {
        header.prepend(mobileToggle)

        mobileToggle.addEventListener("click", () => {
          const sidebar = document.querySelector(".sidebar")
          sidebar.classList.toggle("mobile-open")
        })
      }
    } else {
      const mobileToggle = document.querySelector(".mobile-sidebar-toggle")
      if (mobileToggle) {
        mobileToggle.remove()
      }
    }
  }

  createMobileToggle()

  window.addEventListener("resize", createMobileToggle)

  // Toggle sidebar no mobile
  const mobileToggle = document.querySelector(".mobile-toggle")
  const sidebar = document.querySelector(".sidebar")

  if (mobileToggle && sidebar) {
    mobileToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active")
    })
  }

  // Fechar sidebar ao clicar fora dela no mobile
  document.addEventListener("click", (event) => {
    if (
      window.innerWidth <= 1024 &&
      sidebar &&
      sidebar.classList.contains("active") &&
      !sidebar.contains(event.target) &&
      !mobileToggle.contains(event.target)
    ) {
      sidebar.classList.remove("active")
    }
  })

  // Ajustar layout quando a janela for redimensionada
  window.addEventListener("resize", () => {
    if (window.innerWidth > 1024 && sidebar) {
      sidebar.classList.remove("active")
    }
  })

  // Fetch dashboard data
  const fetchDashboardData = async () => {
    try {
      const response = await fetch("api/dashboard/get_stats.php")
      if (!response.ok) {
        throw new Error("Falha ao carregar dados do dashboard")
      }

      const data = await response.json()
      updateDashboardStats(data)
    } catch (error) {
      console.error("Erro:", error)
    }
  }

  const updateDashboardStats = (data) => {
    // Atualizar contadores
    if (data.totalPets) {
      document.querySelector(".pets-icon + .card-info .card-value").textContent = data.totalPets
    }

    if (data.totalClients) {
      document.querySelector(".clients-icon + .card-info .card-value").textContent = data.totalClients
    }

    if (data.appointmentsToday) {
      document.querySelector(".appointments-icon + .card-info .card-value").textContent = data.appointmentsToday
    }

    if (data.monthlyRevenue) {
      document.querySelector(".revenue-icon + .card-info .card-value").textContent =
        `R$ ${Number.parseFloat(data.monthlyRevenue).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`
    }

    // Atualizar tabela de consultas
    if (data.upcomingAppointments && data.upcomingAppointments.length > 0) {
      const appointmentsTable = document.querySelector(".appointments-card tbody")
      if (appointmentsTable) {
        appointmentsTable.innerHTML = ""

        data.upcomingAppointments.forEach((appointment) => {
          const row = document.createElement("tr")

          const statusClass =
            appointment.status === "Confirmada"
              ? "status-confirmed"
              : appointment.status === "Pendente"
                ? "status-pending"
                : "status-cancelled"

          row.innerHTML = `
                        <td>${appointment.pet_name}</td>
                        <td>${appointment.client_name}</td>
                        <td>${appointment.date}</td>
                        <td>${appointment.time}</td>
                        <td><span class="status-badge ${statusClass}">${appointment.status}</span></td>
                    `

          appointmentsTable.appendChild(row)
        })
      }
    }

    // Atualizar lista de pets recentes
    if (data.recentPets && data.recentPets.length > 0) {
      const petsList = document.querySelector(".recent-pets-list")
      if (petsList) {
        petsList.innerHTML = ""

        data.recentPets.forEach((pet) => {
          const petItem = document.createElement("li")
          petItem.className = "recent-pet-item"

          petItem.innerHTML = `
                        <div class="pet-avatar">
                            <img src="${pet.image || "assets/images/pet-placeholder.jpg"}" alt="${pet.name}">
                        </div>
                        <div class="pet-info">
                            <h4>${pet.name}</h4>
                            <p>${pet.type} - ${pet.breed}</p>
                            <p class="pet-owner">Dono: ${pet.owner_name}</p>
                        </div>
                    `

          petsList.appendChild(petItem)
        })
      }
    }
  }

  // Inicializar dashboard
  fetchDashboardData()
})
