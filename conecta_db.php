<?php
/**
 * Função para conectar ao banco de dados
 * 
 * @return mysqli Conexão com o banco de dados
 */
function conecta_db() {
    $db_name = "petPlus";
    $user = "root";
    $pass = "PUC@1234";
    $server = "localhost";
    
    $conexao = new mysqli($server, $user, $pass, $db_name);
    
    // Verifica erros de conexão
    if ($conexao->connect_error) {
        die("Erro de conexão: " . $conexao->connect_error);
    }
    
    // Define o charset para utf8
    $conexao->set_charset("utf8");
    
    return $conexao; 
}

/**
 * Função para executar uma consulta SQL com segurança
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parâmetros para a consulta
 * @param string $types Tipos dos parâmetros (i: inteiro, d: double, s: string, b: blob)
 * @return mysqli_result|bool Resultado da consulta
 */
function executar_query($sql, $params = [], $types = '') {
    $conn = conecta_db();
    
    if (empty($params)) {
        // Consulta simples sem parâmetros
        $result = $conn->query($sql);
    } else {
        // Consulta com parâmetros (prepared statement)
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            die("Erro na preparação da consulta: " . $conn->error);
        }
        
        // Se não foi especificado o tipo dos parâmetros, assume string para todos
        if (empty($types)) {
            $types = str_repeat('s', count($params));
        }
        
        // Vincula os parâmetros
        $stmt->bind_param($types, ...$params);
        
        // Executa a consulta
        $stmt->execute();
        
        // Obtém o resultado
        $result = $stmt->get_result();
        
        $stmt->close();
    }
    
    $conn->close();
    
    return $result;
}

/**
 * Função para obter um único registro do banco de dados
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parâmetros para a consulta
 * @param string $types Tipos dos parâmetros
 * @return array|null Registro encontrado ou null
 */
function obter_registro($sql, $params = [], $types = '') {
    $result = executar_query($sql, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Função para obter múltiplos registros do banco de dados
 * 
 * @param string $sql Consulta SQL
 * @param array $params Parâmetros para a consulta
 * @param string $types Tipos dos parâmetros
 * @return array Registros encontrados
 */
function obter_registros($sql, $params = [], $types = '') {
    $result = executar_query($sql, $params, $types);
    $registros = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $registros[] = $row;
        }
    }
    
    return $registros;
}

/**
 * Função para inserir um registro no banco de dados
 * 
 * @param string $tabela Nome da tabela
 * @param array $dados Dados a serem inseridos (campo => valor)
 * @return int|bool ID do registro inserido ou false em caso de erro
 */
function inserir_registro($tabela, $dados) {
    $conn = conecta_db();
    
    $campos = array_keys($dados);
    $valores = array_values($dados);
    $placeholders = array_fill(0, count($dados), '?');
    
    $sql = "INSERT INTO $tabela (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . $conn->error);
    }
    
    // Determina os tipos dos parâmetros
    $types = '';
    foreach ($valores as $valor) {
        if (is_int($valor)) {
            $types .= 'i';
        } elseif (is_float($valor)) {
            $types .= 'd';
        } elseif (is_string($valor)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    // Vincula os parâmetros
    $stmt->bind_param($types, ...$valores);
    
    // Executa a consulta
    $resultado = $stmt->execute();
    
    // Obtém o ID do registro inserido
    $id_inserido = $resultado ? $conn->insert_id : false;
    
    $stmt->close();
    $conn->close();
    
    return $id_inserido;
}

/**
 * Função para atualizar um registro no banco de dados
 * 
 * @param string $tabela Nome da tabela
 * @param array $dados Dados a serem atualizados (campo => valor)
 * @param string $condicao Condição para a atualização (ex: "id = ?")
 * @param array $params_condicao Parâmetros para a condição
 * @return bool Resultado da operação
 */
function atualizar_registro($tabela, $dados, $condicao, $params_condicao) {
    $conn = conecta_db();
    
    $campos_valores = [];
    foreach ($dados as $campo => $valor) {
        $campos_valores[] = "$campo = ?";
    }
    
    $sql = "UPDATE $tabela SET " . implode(', ', $campos_valores) . " WHERE $condicao";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . $conn->error);
    }
    
    // Combina os valores dos dados com os parâmetros da condição
    $valores = array_values($dados);
    $params = array_merge($valores, $params_condicao);
    
    // Determina os tipos dos parâmetros
    $types = '';
    foreach ($params as $valor) {
        if (is_int($valor)) {
            $types .= 'i';
        } elseif (is_float($valor)) {
            $types .= 'd';
        } elseif (is_string($valor)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    // Vincula os parâmetros
    $stmt->bind_param($types, ...$params);
    
    // Executa a consulta
    $resultado = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $resultado;
}

/**
 * Função para excluir um registro do banco de dados
 * 
 * @param string $tabela Nome da tabela
 * @param string $condicao Condição para a exclusão (ex: "id = ?")
 * @param array $params Parâmetros para a condição
 * @return bool Resultado da operação
 */
function excluir_registro($tabela, $condicao, $params) {
    $conn = conecta_db();
    
    $sql = "DELETE FROM $tabela WHERE $condicao";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . $conn->error);
    }
    
    // Determina os tipos dos parâmetros
    $types = '';
    foreach ($params as $valor) {
        if (is_int($valor)) {
            $types .= 'i';
        } elseif (is_float($valor)) {
            $types .= 'd';
        } elseif (is_string($valor)) {
            $types .= 's';
        } else {
            $types .= 'b';
        }
    }
    
    // Vincula os parâmetros
    $stmt->bind_param($types, ...$params);
    
    // Executa a consulta
    $resultado = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $resultado;
}
?>
