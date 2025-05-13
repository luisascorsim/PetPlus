<?php
include '../conecta_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = conecta_db();
    
    // Obter dados do formulário
    $nome_pet = $_POST['nome_pet'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'];
    $idade = $_POST['idade'];
    $sexo = $_POST['sexo'];
    $peso_atual = $_POST['peso_atual'];
    $descricao = $_POST['descricao'];
    
    // Usar um ID de tutor temporário para testes (1)
    $id_tutor = 1;
    
    // Inserir na tabela Pet
    $sql = "INSERT INTO Pet (id_tutor, nome, especie, raca, idade, sexo, peso_atual, descricao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssidss", $id_tutor, $nome_pet, $especie, $raca, $idade, $sexo, $peso_atual, $descricao);
    
    if ($stmt->execute()) {
        echo "<script>alert('Pet cadastrado com sucesso!'); window.location.href='cadastrar_pets.html';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar pet: " . $stmt->error . "'); window.history.back();</script>";
    }
    
    $stmt->close();
    $conn->close();
}
?>
