<?php

session_start();

include_once __DIR__ . "/config/conexion.php";
include_once __DIR__ . "/config/config.php";
include_once __DIR__ . "/includes/email.php";

$mensaje = "";
$error = "";

/* PROCESAR FORMULARIO */
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST['email']);

    if(empty($email)){

        $error = "Debes ingresar tu correo electrónico.";

    } else {

        $sql = "SELECT nombre, email FROM usuarios WHERE email = ? LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $resultado = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($resultado) > 0){

            /* CREAR TOKEN SEGURO */
            $token = bin2hex(random_bytes(32));

            /* EXPIRA EN 1 HORA */
            $expiracion = date(
                "Y-m-d H:i:s",
                strtotime("+1 hour")
            );

            /* INVALIDAR TOKENS ANTERIORES */
            $sqlInvalidar = "UPDATE password_resets
                             SET usado = 1
                             WHERE email = ?";

            $stmtInvalidar = mysqli_prepare($conn, $sqlInvalidar);

            mysqli_stmt_bind_param(
                $stmtInvalidar,
                "s",
                $email
            );

            mysqli_stmt_execute($stmtInvalidar);

            /* GUARDAR TOKEN NUEVO */
            $sqlToken = "INSERT INTO password_resets
            (
                email,
                token,
                expiracion
            )
            VALUES
            (
                ?,
                ?,
                ?
            )";

            $stmtToken = mysqli_prepare($conn, $sqlToken);

            mysqli_stmt_bind_param(
                $stmtToken,
                "sss",
                $email,
                $token,
                $expiracion
            );

            if(mysqli_stmt_execute($stmtToken)){

                $baseReset = rtrim($URL_TIENDA ?? "http://localhost/sportstyle", "/");
                $linkReset = $baseReset . "/reset_password.php?token=" . $token;

                $usuario = mysqli_fetch_assoc($resultado);
                $errorCorreo = "";
                enviarEmailRecuperacionPassword(
                    $email,
                    $usuario['nombre'] ?? '',
                    $linkReset,
                    $errorCorreo
                );

                $mensaje = "Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.";

            } else {

                $error = "No se pudo generar el enlace. Intenta nuevamente.";

            }

        } else {

            /*
                Mensaje genérico por seguridad:
                no conviene decir si el email existe o no.
            */

            $mensaje = "Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.";

        }

    }

}

?>

<?php include("includes/header.php"); ?>

<section class="login-page">

    <div class="login-container">

        <!-- LADO IZQUIERDO -->
        <div class="login-info">

            <h1>
                Recuperar contraseña
            </h1>

            <p>
                Ingresa tu correo electrónico y te enviaremos un enlace seguro
                para crear una nueva contraseña.
            </p>

            <img src="img/reza.jpg"
                 alt="Recuperar contraseña SportStyle">

        </div>

        <!-- FORMULARIO -->
        <div class="login-box">

            <h2>
                ¿Olvidaste tu contraseña?
            </h2>

            <?php if($error): ?>

                <p class="error-msg">
                    <?= $error ?>
                </p>

            <?php endif; ?>

            <?php if($mensaje): ?>

                <p class="success-msg">
                    <?= $mensaje ?>
                </p>

            <?php endif; ?>

            <form method="POST">

                <div class="input-group">

                    <label>
                        Correo electrónico
                    </label>

                    <input type="email"
                           name="email"
                           placeholder="ejemplo@gmail.com"
                           required>

                </div>

                <button type="submit"
                        class="btn-login">

                    Enviar instrucciones

                </button>

                <p class="registro-link">

                    ¿Recordaste tu contraseña?

                    <a href="login.php">
                        Iniciar sesión
                    </a>

                </p>

            </form>

        </div>

    </div>

</section>

<?php include("includes/footer.php"); ?>
