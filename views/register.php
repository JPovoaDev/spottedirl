<?php
// iniciamos a sessão para poder ler mensagens de erro/sucesso guardadas pelos controladores
session_start();
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
?>
<?php require_once 'header.php'; ?>
    <h1>Registar</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="../controllers/register_action.php">
        <label>Username: 
            <input type="text" id="username" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+" title="Apenas letras, números e underscores">
            <span id="usernameStatus" style="font-size: 0.8em; margin-left: 10px;"></span>
        </label>
        <label>Email: 
            <input type="email" name="email" required maxlength="100">
        </label>
        <label>Password: 
            <input type="password" id="password" name="password" required minlength="6" maxlength="255" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}" title="A password deve ter pelo menos 6 caracteres, incluir uma letra maiúscula, uma minúscula e um número.">
        </label>
        
        <label>CAPTCHA:
            <div>
                <img src="../controllers/captcha.php" alt="CAPTCHA" style="vertical-align: middle; margin-bottom: 5px;">
            </div>
            <input type="text" name="captcha" required maxlength="5" title="Insere os 5 caracteres da imagem">
        </label>
        
        <button type="submit">Registar</button>
    </form>
    
    <script>
        // Validação AJAX do Username (usando XMLHttpRequest com os 4 states)
        const usernameInput = document.getElementById('username');
        const usernameStatus = document.getElementById('usernameStatus');

        usernameInput.addEventListener('blur', function() {
            const username = this.value.trim();
            if (username.length < 3) {
                usernameStatus.textContent = 'Mínimo de 3 caracteres';
                usernameStatus.style.color = 'red';
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('GET', '../controllers/check_username.php?username=' + encodeURIComponent(username), true);
            xhr.onreadystatechange = function() {
                // state 4 significa que o pedido foi concluído
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        usernameStatus.textContent = response.message;
                        usernameStatus.style.color = response.available ? 'green' : 'red';
                    } else {
                        usernameStatus.textContent = 'Erro ao verificar username';
                        usernameStatus.style.color = 'red';
                    }
                }
            };
            xhr.send();
        });

        // Validação JS customizada no Submit com Regex
        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function(event) {
            const pwd = document.getElementById('password').value;
            // Regex: Pelo menos um número e uma letra
            const regex = /^(?=.*[0-9])(?=.*[a-zA-Z]).{6,}$/;
            
            if (!regex.test(pwd)) {
                event.preventDefault();
                alert('A password deve conter pelo menos uma letra, um número e 6 caracteres (validação JS).');
            }
        });
    </script>
    
    <a href="login.php">Já tenho conta</a>
<?php require_once 'footer.php'; ?>