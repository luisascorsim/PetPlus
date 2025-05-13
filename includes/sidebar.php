<?php
// Determina o caminho base para os recursos
if (!isset($caminhoBase)) {
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
}

// Função para verificar se o link atual está ativo
function isActive($pagina) {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $currentPage = basename($scriptName);
    $checkPage = basename($pagina);
    
    // Verifica se o nome do arquivo atual contém o nome da página que estamos verificando
    return (strpos($currentPage, $checkPage) !== false) ? 'active' : '';
}
?>

<aside class="sidebar">
    
    <nav class="sidebar-menu">
        <div class="menu-category">
            <h3 class="category-title">Principal</h3>
            <a href="<?php echo $caminhoBase; ?>home/index.php" class="menu-item <?php echo isActive('home/index.php'); ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>agenda/agenda.php" class="menu-item <?php echo isActive('agenda/agenda.php'); ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Agenda</span>
            </a>
        </div>
        
        <div class="menu-category">
            <h3 class="category-title">Gerenciamento</h3>
            <a href="<?php echo $caminhoBase; ?>cadastrar_pet/cadastrar_pets.php" class="menu-item <?php echo isActive('cadastrar_pet/cadastrar_pets.php'); ?>">
                <i class="fas fa-paw"></i>
                <span>Cadastro de Pets</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>clientes/clientes.php" class="menu-item <?php echo isActive('clientes/clientes.php'); ?>" onclick="event.preventDefault(); window.location.href='<?php echo $caminhoBase; ?>clientes/clientes.php';">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>servicos/servicos.php" class="menu-item <?php echo isActive('servicos/servicos.php'); ?>">
                <i class="fas fa-concierge-bell"></i>
                <span>Serviços</span>
            </a>
        </div>
        
        <div class="menu-category">
            <h3 class="category-title">Clínica</h3>
            <a href="<?php echo $caminhoBase; ?>historico_consultas/historico-consultas.php" class="menu-item <?php echo isActive('historico_consultas/historico-consultas.php'); ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Consultas</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>diagnostico_consulta/diagnostico-consulta.php" class="menu-item <?php echo isActive('diagnostico_consulta/diagnostico-consulta.php'); ?>">
                <i class="fas fa-stethoscope"></i>
                <span>Prontuários</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>vacinas_e_controle_do_peso/vacinas-peso.php" class="menu-item <?php echo isActive('vacinas_e_controle_do_peso/vacinas-peso.php'); ?>">
                <i class="fas fa-syringe"></i>
                <span>Vacinas e Peso</span>
            </a>
        </div>
        
        <div class="menu-category">
            <h3 class="category-title">Financeiro</h3>
            <a href="<?php echo $caminhoBase; ?>fatura_detalhada/fatura-detalhada.php" class="menu-item <?php echo isActive('fatura_detalhada/fatura-detalhada.php'); ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Faturas</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>relatorio_atendimentos/relatorio-atendimentos.php" class="menu-item <?php echo isActive('relatorio_atendimentos/relatorio-atendimentos.php'); ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
        </div>
        
        <div class="menu-category">
            <h3 class="category-title">Sistema</h3>
            <a href="<?php echo $caminhoBase; ?>perfil/meu-perfil.php" class="menu-item <?php echo isActive('perfil/meu-perfil.php'); ?>">
                <i class="fas fa-user-circle"></i>
                <span>Meu Perfil</span>
            </a>
            <a href="<?php echo $caminhoBase; ?>configuracoes/sistema.php" class="menu-item <?php echo isActive('configuracoes/sistema.php'); ?>">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo $caminhoBase; ?>logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sair</span>
        </a>
    </div>
</aside>

<div class="mobile-toggle">
    <i class="fas fa-bars"></i>
</div>

<style>
    .sidebar {
        width: 220px;
        background-color: #003b66;
        color: white;
        position: fixed;
        top: 60px;
        bottom: 0;
        left: 0;
        overflow-y: auto;
        z-index: 900;
        transition: width 0.3s;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }

    .menu-category {
        margin-bottom: 15px;
        padding: 0 10px;
    }

    .category-title {
        font-size: 0.8rem;
        text-transform: uppercase;
        color: rgba(255,255,255,0.6);
        margin: 10px 0;
        padding: 0 10px;
    }

    .menu-item {
        display: flex;
        align-items: center;
        padding: 10px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.2s;
        border-radius: 5px;
        margin-bottom: 5px;
    }

    .menu-item i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .menu-item:hover {
        background-color: rgba(255,255,255,0.1);
        color: white;
    }

    .menu-item.active {
        background-color: #0d4371;
        color: white;
        font-weight: 500;
    }

    .sidebar-footer {
        padding: 15px;
        border-top: 1px solid rgba(255,255,255,0.1);
        position: sticky;
        bottom: 0;
        background-color: #003b66;
    }

    .logout-btn {
        display: flex;
        align-items: center;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        padding: 10px;
        border-radius: 5px;
        transition: all 0.2s;
    }

    .logout-btn i {
        margin-right: 10px;
    }

    .logout-btn:hover {
        background-color: rgba(255,0,0,0.2);
        color: white;
    }

    .mobile-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        background-color: #003b66;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 5px;
        z-index: 1000;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .sidebar {
            width: 60px;
            transform: translateX(0);
        }

        .sidebar.expanded {
            width: 220px;
        }

        .sidebar-header h2,
        .menu-item span,
        .category-title,
        .logout-btn span {
            display: none;
        }

        .sidebar.expanded .sidebar-header h2,
        .sidebar.expanded .menu-item span,
        .sidebar.expanded .category-title,
        .sidebar.expanded .logout-btn span {
            display: block;
        }

        .menu-item {
            justify-content: center;
        }

        .sidebar.expanded .menu-item {
            justify-content: flex-start;
        }

        .menu-item i {
            margin-right: 0;
        }

        .sidebar.expanded .menu-item i {
            margin-right: 10px;
        }

        .mobile-toggle {
            display: flex;
        }

        .container {
            margin-left: 60px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileToggle = document.querySelector('.mobile-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('expanded');
            });
        }
    });
</script>
