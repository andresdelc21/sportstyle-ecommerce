<?php

session_start();

include("config/conexion.php");

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if(strlen($nombre) < 2){

        $error = "Ingresá un nombre válido";

    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){

        $error = "Ingresá un email válido";

    } elseif(strlen($password) < 6){

        $error = "La contraseña debe tener al menos 6 caracteres";

    } else {

        // VALIDAR EMAIL EXISTENTE
        $sqlCheck = "SELECT id FROM usuarios WHERE email = ? LIMIT 1";

        $stmtCheck = mysqli_prepare($conn, $sqlCheck);

        mysqli_stmt_bind_param(
            $stmtCheck,
            "s",
            $email
        );

        mysqli_stmt_execute($stmtCheck);

        $check = mysqli_stmt_get_result($stmtCheck);

        if(mysqli_num_rows($check) > 0){

        $error = "El correo ya está registrado";

        } else {

        // ENCRIPTAR PASSWORD
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // INSERTAR
        $sql = "INSERT INTO usuarios
        (
            nombre,
            email,
            password,
            rol
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'cliente'
        )";

        $stmtInsert = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmtInsert,
            "sss",
            $nombre,
            $email,
            $passwordHash
        );

        if(mysqli_stmt_execute($stmtInsert)){

            $success = "Cuenta creada correctamente";

        } else {

            $error = "Error al registrar";

        }
        }
    }
}
?>

<?php include("includes/header.php"); ?>

<section class="login-page">

<div class="login-container">

    <div class="login-info">

        <span class="auth-eyebrow">Crear cuenta</span>

        <h1>Comprá más rápido la próxima vez</h1>

        <p>
            Guardá tus datos, armá tu lista de favoritos y consultá el estado de tus pedidos desde tu cuenta.
        </p>

        <div class="auth-benefits">
            <span>Cuenta de cliente</span>
            <span>Historial de pedidos</span>
            <span>Favoritos disponibles</span>
        </div>

    </div>

    <div class="login-box">

        <div class="auth-form-header">
            <span>SportStyle</span>
            <h2>Crear cuenta</h2>
            <p>Completá tus datos básicos para empezar a comprar.</p>
        </div>

        <?php if($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if($success): ?>
            <p class="success-msg"><?= htmlspecialchars($success) ?>. Ya podés iniciar sesión.</p>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">

                <label>Nombre</label>

                <input type="text"
                       name="nombre"
                       placeholder="Tu nombre"
                       required>

            </div>

            <div class="input-group">

                <label>Correo electrónico</label>

                <input type="email"
                       name="email"
                       placeholder="ejemplo@gmail.com"
                       required>

            </div>

            <div class="input-group">

                <label>Contraseña</label>

                <input type="password"
                       name="password"
                       placeholder="Mínimo 6 caracteres"
                       required>

            </div>

            <p class="auth-note">
                La contraseña se guarda protegida. No la compartas con nadie.
            </p>

            <button type="submit"
                    class="btn-login">

                Crear cuenta

            </button>

        </form>

        <p class="registro-link">

            ¿Ya tienes cuenta?

            <a href="login.php">
                Iniciar sesión
            </a>

        </p>

    </div>

</div>

</section>

<?php include("includes/footer.php"); ?>
