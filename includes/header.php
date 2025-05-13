<?php
// Inicia a sessão se ainda não estiver iniciada
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
        $niveis = 1; // Por padrão, volta um nível (para a maioria dos diretórios)
        
        // Casos especiais para subdiretórios mais profundos
        if (in_array('perfil', $partesCaminho) || in_array('configuracoes', $partesCaminho)) {
            $niveis = 1;
        }
    }
    
    // Constrói o caminho base
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
        
        /* Responsividade */
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
        <a href="<?php echo $caminhoBase; ?>home/index.php" class="logo">
            <img src="<?php echo $caminhoBase; ?>Imagens/logo.png" alt="PetPlus Logo">
            <span>PetPlus</span>
        </a>
        
        <div class="usuario-acoes">
            <span class="usuario-nome">Olá, <?php echo htmlspecialchars($nomeUsuario); ?>!</span>
            
            <div class="dropdown">
                <img src="<?php echo $caminhoBase; ?>Imagens/usuario.png" alt="Usuário" class="dropdown-icon">
                <div class="dropdown-content">
                    <a href="<?php echo $caminhoBase; ?>perfil/meu-perfil.php">Meu Perfil</a>
                    <a href="<?php echo $caminhoBase; ?>perfil/alterar-senha.php">Alterar Senha</a>
                </div>
            </div>
            
            <div class="dropdown">
                <img src="<?php echo $caminhoBase; ?>Imagens/config.png" alt="Configurações" class="dropdown-icon">
                <div class="dropdown-content">
                    <a href="<?php echo $caminhoBase; ?>configuracoes/sistema.php">Configurações do Sistema</a>
                    <a href="<?php echo $caminhoBase; ?>configuracoes/notificacoes.php">Notificações</a>
                </div>
            </div>
            
            <a href="<?php echo $caminhoBase; ?>home/logout.php">
                <img src="<?php echo $caminhoBase; ?>Imagens/sair.png" alt="Sair" class="dropdown-icon">
            </a>
        </div>
    </header>

    <!-- Script para o header -->
    <script src="<?php echo $caminhoBase; ?>includes/header.js"></script>
