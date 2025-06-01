<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determina o caminho base para os recursos
function determinarCaminhoBase() {
    // Obtém o caminho do script atual em relação à raiz do servidor
    $caminhoAtual = $_SERVER['SCRIPT_NAME'];
    $partesCaminho = explode('/', $caminhoAtual);
    
    // Remove o nome do arquivo e o diretório atual
    array_pop($partesCaminho);
    $diretorioAtual = end($partesCaminho);
    
    // Determina quantos níveis precisamos voltar para chegar à raiz do projeto
    $niveis = 0;
    if ($diretorioAtual != 'PetPlus' && $diretorioAtual != '') {
        $niveis = 1; 
        if (in_array('perfil', $partesCaminho) || in_array('configuracoes', $partesCaminho)) {
            $niveis = 1;
        }
    }
    
    $caminhoBase = '';
    for ($i = 0; $i < $niveis; $i++) {
        $caminhoBase .= '../';
    }
    
    return $caminhoBase;
}

$caminhoBase = determinarCaminhoBase();

// Busca o nome do usuário da sessão
$nomeUsuario = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetPlus</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $caminhoBase; ?>includes/temas.css">
    <link rel="stylesheet" href="<?php echo $caminhoBase; ?>includes/global.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <style>
        .header {
            background-color: #2196f3;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 60px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }
        
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        
        .logo span {
            font-size: 20px;
            font-weight: 700;
        }
        
        .usuario-acoes {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-icon {
            width: 24px;
            height: 24px;
            cursor: pointer;
            vertical-align: middle;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        
        .usuario-nome {
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 10px;
            }
            .logo img {
                height: 30px;
            }
            .logo span {
                font-size: 16px;
            }
            .usuario-acoes {
                gap: 10px;
            }
            .dropdown-icon {
                width: 20px;
                height: 20px;
            }
            .usuario-nome {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="<?php echo $caminhoBase; ?>home/index.php" class="logo nav-link">
            <img src="<?php echo $caminhoBase; ?>Imagens/logo.png" alt="PetPlus Logo">
            <span>PetPlus</span>
        </a>
        
        <div class="usuario-acoes">
            <span class="usuario-nome">Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!</span>
            
            <div class="dropdown">
                <img src="<?php echo $caminhoBase; ?>Imagens/usuario.png" alt="Usuário" class="dropdown-icon">
                <div class="dropdown-content">
                    <a href="<?php echo $caminhoBase; ?>perfil/meu-perfil.php" class="nav-link">Meu Perfil</a>
                    <a href="<?php echo $caminhoBase; ?>perfil/alterar-senha.php" class="nav-link">Alterar Senha</a>
                </div>
            </div>
            
            <div class="dropdown">
                <img src="<?php echo $caminhoBase; ?>Imagens/config.png" alt="Configurações" class="dropdown-icon">
                <div class="dropdown-content">
                    <a href="<?php echo $caminhoBase; ?>configuracoes/sistema.php" class="nav-link">Configurações do Sistema</a>
                    <a href="<?php echo $caminhoBase; ?>configuracoes/notificacoes.php" class="nav-link">Notificações</a>
                </div>
            </div>
            
            <a href="<?php echo $caminhoBase; ?>home/logout.php" id="logout-link" title="Sair">
                <img src="<?php echo $caminhoBase; ?>Imagens/sair.png" alt="Sair" class="dropdown-icon">
            </a>
        </div>
    </header>

    <script src="<?php echo $caminhoBase; ?>includes/header.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica para confirmação de logout
        const logoutButton = document.getElementById('logout-link');
        if (logoutButton) {
            const logoutUrl = logoutButton.href; // Pega a URL original do link
            logoutButton.addEventListener('click', function(event) {
                event.preventDefault(); // Previne o comportamento padrão do link

                Swal.fire({
                    title: 'Confirmar Saída',
                    text: "Você realmente deseja sair do sistema?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0b3556',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, Sair!',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true // Coloca o botão de confirmação à direita
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Saindo...',
                            text: 'Aguarde um momento.',
                            icon: 'info',
                            timer: 1000, // 1 segundo para o usuário ver
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            },
                            // Redireciona após o timer
                            didClose: () => {
                                window.location.href = logoutUrl;
                            }
                        });
                    }
                });
            });
        }

        const headerNavLinks = document.querySelectorAll('.header a.nav-link'); 
        headerNavLinks.forEach(link => {
            if (link.href && link.getAttribute('href') !== '#' && !link.getAttribute('href').startsWith('javascript:')) {
                link.addEventListener('click', function(event) {
                    event.preventDefault(); 
                    const targetUrl = this.href;
                    
                    let linkDescription = 'a página solicitada';
                    if (this.textContent.trim()) {
                        linkDescription = `"${this.textContent.trim()}"`;
                    } else if (this.querySelector('img') && this.querySelector('img').alt) {
                        linkDescription = `"${this.querySelector('img').alt}"`;
                    } else if (this.title) {
                        linkDescription = `"${this.title}"`;
                    }

                    Swal.fire({
                        title: 'Redirecionando...',
                        html: `Aguarde, estamos te levando para ${linkDescription}.`,
                        icon: 'info',
                        timer: 700, 
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                        // Redireciona após o timer
                        didClose: () => {
                             window.location.href = targetUrl;
                        }
                    });
                });
            }
        });
    });
    </script>
</body>
</html>