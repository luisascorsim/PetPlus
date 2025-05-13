<?php
$current_page = 'configuracoes';
$page_title = 'Configurações do Sistema';
include_once('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações do Sistema - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    <style>
        .container {
            margin-left: 250px;
            padding: 20px;
        }
        .config-section {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .config-section h2 {
            color: #4a6baf;
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-save {
            background-color: #4a6baf;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-save:hover {
            background-color: #3a5a9f;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #4a6baf;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include_once('../includes/sidebar.php'); ?>
    
    <div class="container">
        <h1>Configurações do Sistema</h1>
        
        <div class="config-section">
            <h2>Notificações</h2>
            <form action="#" method="post">
                <div class="form-group">
                    <label>Notificações por e-mail</label>
                    <label class="switch">
                        <input type="checkbox" name="email_notifications" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Notificações de consultas</label>
                    <label class="switch">
                        <input type="checkbox" name="appointment_notifications" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Lembretes de vacinação</label>
                    <label class="switch">
                        <input type="checkbox" name="vaccination_reminders" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Frequência de notificações</label>
                    <select name="notification_frequency" class="form-control">
                        <option value="immediately">Imediatamente</option>
                        <option value="daily">Diariamente</option>
                        <option value="weekly">Semanalmente</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-save">Salvar Configurações</button>
            </form>
        </div>
    </div>
    
    <script src="../includes/header.js"></script>
</body>
</html>
