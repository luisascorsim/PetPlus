<?php
/**
 * Funções utilitárias para o sistema PetPlus
 */

/**
 * Determina o caminho base para os recursos
 * 
 * @return string Caminho base relativo (ex: '../' ou '../../')
 */
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

/**
 * Gera o caminho completo para uma imagem
 * 
 * @param string $caminho Caminho relativo da imagem (ex: 'home/icones/usuario.png')
 * @return string Caminho completo para a imagem
 */
function caminhoImagem($caminho) {
    $caminhoBase = determinarCaminhoBase();
    return $caminhoBase . $caminho;
}

/**
 * Verifica se um arquivo existe
 * 
 * @param string $caminho Caminho do arquivo
 * @return bool True se o arquivo existe, false caso contrário
 */
function arquivoExiste($caminho) {
    $caminhoCompleto = $_SERVER['DOCUMENT_ROOT'] . '/' . $caminho;
    return file_exists($caminhoCompleto);
}

/**
 * Formata uma data para exibição
 * 
 * @param string $data Data no formato do banco de dados (YYYY-MM-DD)
 * @param string $formato Formato desejado (padrão: d/m/Y)
 * @return string Data formatada
 */
function formatarData($data, $formato = 'd/m/Y') {
    $timestamp = strtotime($data);
    return date($formato, $timestamp);
}
