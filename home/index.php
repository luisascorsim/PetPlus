<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o header
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Use caminho relativo para o conecta_db.php
require_once('../conecta_db.php');
$conn = conecta_db();


// Busca dados do usuário logado
$query = "SELECT nome, email FROM Usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    $usuario = ['nome' => 'Usuário', 'email' => ''];
}

// Processa apenas o formulário de contato (notificações)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_contato') {
    header('Content-Type: application/json');
    
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $mensagem_conteudo = $_POST['mensagem'] ?? '';
    $user_id = $_SESSION['id_usuario'];
    
    if (!empty($nome) && !empty($email) && !empty($telefone) && !empty($mensagem_conteudo)) {
        try {
            // Busca um usuário admin para enviar a notificação (ou cria uma lógica específica)
            $query_admin = "SELECT id_usuario FROM Usuarios WHERE email = 'admin@petplus.com' LIMIT 1";
            $result_admin = $conn->query($query_admin);
            
            if ($result_admin && $result_admin->num_rows > 0) {
                $admin = $result_admin->fetch_assoc();
                $id_usuario_destino = $admin['id_usuario'];
            } else {
                // Se não encontrar admin, usa o primeiro usuário disponível
                $query_first_user = "SELECT id_usuario FROM Usuarios WHERE id_usuario != ? LIMIT 1";
                $stmt = $conn->prepare($query_first_user);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $first_user = $result->fetch_assoc();
                    $id_usuario_destino = $first_user['id_usuario'];
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erro interno do sistema.']);
                    exit();
                }
            }
            
            $titulo = "Nova mensagem de contato - " . $nome;
            $mensagem_completa = "Nome: " . $nome . "\nEmail: " . $email . "\nTelefone: " . $telefone . "\n\nMensagem:\n" . $mensagem_conteudo;
            
            $sql = "INSERT INTO Notificacoes (remetente_id_usuario, id_usuario, titulo, mensagem) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $user_id, $id_usuario_destino, $titulo, $mensagem_completa);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao enviar mensagem.']);
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro interno do sistema.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios.']);
    }
    exit();
}

// Busca dados do usuário logado
$query = "SELECT nome, email FROM Usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['id_usuario']);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    $usuario = ['nome' => 'Usuário', 'email' => ''];
}

// Busca serviços do banco de dados
$query_servicos = "SELECT nome, descricao, preco, categoria FROM Servicos WHERE status_s = 'ativo' LIMIT 6";
$result_servicos = $conn->query($query_servicos);
$servicos = [];

while ($row = $result_servicos->fetch_assoc()) {
    $icone = 'bi-heart-fill';
    $cor = 'blue';
    
    
    $servicos[] = [
        'titulo' => $row['nome'],
        'descricao' => $row['descricao'],
        'preco' => 'A partir de R$ ' . number_format($row['preco'], 2, ',', '.'),
        'icone' => $icone,
        'cor' => $cor
    ];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetPlus - Cuidado PLUS para seu Pet</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-pink: #e91e63;
            --light-purple: #f3e5f5;
            --dark-text: #2d3748;
            --gray-text: #718096;
            --service-blue: #4285f4;
            --service-green: #34a853;
            --service-red: #ea4335;
            --service-purple: #9c27b0;
            --service-pink: #e91e63;
            --service-orange: #ff9800;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark-text);
        }

        /* Hero Section */
        .hero-section {
            background: #e9f4ff;
            min-height: 80vh;
            display: flex;
            align-items: center;
            padding: 60px 0;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: var(--dark-text);
            line-height: 1.2;
            margin-bottom: 1.5rem;
        }

        .hero-title .plus {
            color: var(--service-blue);
        }

        .hero-description {
            font-size: 1.1rem;
            color: var(--gray-text);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .rating {
            display: flex;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .estrelas {
            color: #ffc107;
            margin-right: 10px;
        }

        .rating-text {
            color: var(--gray-text);
            font-size: 0.95rem;
        }

        .btn-agendar {
            background-color: var(--service-blue);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        .btn-agendar:hover {
            background-color: #0a74de;
            color: white;
            transform: translateY(-2px);
        }

        .btn-produtos {
            border: 2px solid var(--service-blue);
            color: var(--service-blue);
            background: transparent;
            padding: 10px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-produtos:hover {
            background-color: var(--service-blue);
            color: white;
        }

        .hero-image-container {
            position: relative;
        }

        .hero-image {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .client-badge {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
        }

        .client-name {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .client-status {
            color: var(--gray-text);
            font-size: 0.9rem;
        }

        /* Services Section */
        .services-section {
            padding: 80px 0;
            background: white;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            color: var(--dark-text);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            color: var(--gray-text);
            font-size: 1.1rem;
            margin-bottom: 4rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .service-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .service-icon.blue { background-color: var(--service-blue); }
        .service-icon.green { background-color: var(--service-green); }
        .service-icon.pink { background-color: var(--service-pink); }
        .service-icon.purple { background-color: var(--service-purple); }
        .service-icon.red { background-color: var(--service-red); }
        .service-icon.orange { background-color: var(--service-orange); }

        .service-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px;
        }

        .service-description {
            color: var(--gray-text);
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .service-price {
            color: var(--service-blue);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .btn-agendar-service {
            color: var(--service-blue);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .btn-agendar-service:hover {
            color: #0a74de;
            transform: translateX(5px);
        }

        /* Contact Section */
        .contact-section {
            padding: 80px 0;
            background: #fafafa;
        }

        .contact-info {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: fit-content;
        }

        .contact-form {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 20px;
        }
        .contact-icon.pink { background-color: var(--service-pink); }
        .contact-icon.blue { background-color: var(--service-blue); }
        .contact-icon.red { background-color: var(--service-red); }
        .contact-icon.green { background-color: var(--service-green); }
        .contact-icon.orange { background-color: var(--service-orange); }

        .contact-details h6 {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 5px;
        }

        .contact-details p {
            color: var(--gray-text);
            margin: 0;
            font-size: 0.95rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--service-blue);
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
        }

        .btn-enviar {
            background-color: var(--service-blue);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-enviar:hover {
            background-color: #0a74de;
            transform: translateY(-2px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-section {
                text-align: center;
            }
            
            .client-badge {
                position: static;
                margin-top: 20px;
                display: inline-block;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">
                        Cuidado <span class="plus">PLUS</span> para<br>
                        seu Pet
                    </h1>
                    <p class="hero-description">
                        Olá, <?php echo htmlspecialchars($usuario['nome']); ?>! No PetPlus, oferecemos os melhores serviços para 
                        o bem-estar do seu companheiro de quatro patas. Há mais de 6 
                        meses cuidando com carinho!
                    </p>
                    
                    <div class="rating">
                        <div class="estrelas">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <span class="rating-text">4.9/5 - Mais de 10.000 clientes satisfeitos</span>
                    </div>
                    
                    <div class="hero-buttons">
                        <a href="#" class="btn-agendar" onclick="agendarConsulta()">
                            <i class="bi bi-calendar-plus me-2"></i>Agendar Consulta
                        </a>
                        <a href="#servicos" class="btn-produtos">Ver Serviços</a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="hero-image-container">
                        <img src="../Imagens/cachorrofeliz.png" alt="Dog Feliz" class="hero-image">
                        <div class="client-badge">
                            <div class="client-name">Bob - Cliente VIP</div>
                            <div class="client-status">Sempre bem cuidado!</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="servicos" class="services-section">
        <div class="container">
            <h2 class="section-title">Nossos Serviços</h2>
            <p class="section-subtitle">
                Oferecemos uma gama completa de serviços para manter seu pet feliz e 
                saudável
            </p>
            
            <div class="row">
                <?php foreach($servicos as $servico): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon <?php echo $servico['cor']; ?>">
                            <i class="<?php echo $servico['icone']; ?>"></i>
                        </div>
                        <h3 class="service-title"><?php echo htmlspecialchars($servico['titulo']); ?></h3>
                        <p class="service-description"><?php echo htmlspecialchars($servico['descricao']); ?></p>
                        <div class="service-price"><?php echo htmlspecialchars($servico['preco']); ?></div>
                        <a href="#" class="btn-agendar-service" onclick="agendarServico('<?php echo htmlspecialchars($servico['titulo']); ?>')">
                            Agendar <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Contato -->
    <section class="contact-section">
        <div class="container">
            <h2 class="section-title">Entre em Contato</h2>
            <p class="section-subtitle">
                Estamos aqui para ajudar você e seu pet. Entre em contato conosco!
            </p>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h3 class="mb-4">Informações de Contato</h3>
                        
                        <div class="contact-item">
                            <div class="contact-icon pink">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Endereço</h6>
                                <p>Rua dos Pets, 123 - Centro, Curitiba - PR</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon blue">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Telefone</h6>
                                <p>(41) 99893-4895</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon green">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Email</h6>
                                <p>suporte@petplus.com.br</p>
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <div class="contact-icon orange">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h6>Horário de Funcionamento</h6>
                                <p>Segunda a Domingo: 8h às 18h</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="contact-form">
                        <h3 class="mb-4">Envie uma Mensagem</h3>
                        
                        <form id="contactForm">
                            <div class="mb-3">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="nome" placeholder="Seu nome completo" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" placeholder="seu@email.com" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="tel" class="form-control" name="telefone" placeholder="(41) 99999-9999" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Mensagem</label>
                                <textarea class="form-control" name="mensagem" rows="4" placeholder="Como podemos ajudar você e seu pet?" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn-enviar">
                                <i class="bi bi-send me-2"></i>Enviar Mensagem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Função para fazer requisições AJAX
        function makeRequest(data) {
            return fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            }).then(response => response.json());
        }
        // Função para agendar consulta - redireciona para agenda
        function agendarConsulta() {
            window.location.href = '../agenda/agenda.php';
        }

        // Função para agendar serviço específico - redireciona para agenda
        function agendarServico(nomeServico) {
            // Redireciona para agenda com parâmetro do serviço
            window.location.href = '../agenda/agenda.php?servico=' + encodeURIComponent(nomeServico);
        }

        // Função para envio do formulário de contato
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const nome = formData.get('nome');
            const email = formData.get('email');
            const telefone = formData.get('telefone');
            const mensagem = formData.get('mensagem');
            
            if (!nome || !email || !telefone || !mensagem) {
                Swal.fire({
                    title: 'Campos Obrigatórios',
                    text: 'Por favor, preencha todos os campos do formulário.',
                    icon: 'warning',
                    confirmButtonColor: '#4285f4'
                });
                return;
            }
            
            // Mostra loading
            Swal.fire({
                title: 'Enviando mensagem...',
                text: 'Aguarde um momento',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Envia os dados
            makeRequest({
                action: 'enviar_contato',
                nome: nome,
                email: email,
                telefone: telefone,
                mensagem: mensagem
            }).then(response => {
                if (response.success) {
                    Swal.fire({
                        title: 'Mensagem Enviada!',
                        html: `
                            <div class="text-center">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">Obrigado pelo contato!</h4>
                                <p>Recebemos sua mensagem e retornaremos em breve.</p>
                                <div class="alert alert-info">
                                    <small>Tempo médio de resposta: 1 hora</small>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'Perfeito!',
                        confirmButtonColor: '#27ae60'
                    }).then(() => {
                        document.getElementById('contactForm').reset();
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: 'Erro ao enviar mensagem. Tente novamente.',
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            });
        });

        // Smooth scroll para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animação de entrada dos cards
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Aplicar animação aos cards de serviço
        document.addEventListener('DOMContentLoaded', function() {
            const serviceCards = document.querySelectorAll('.service-card');
            serviceCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });
    </script>
</body>
</html>