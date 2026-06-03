<?php

session_start();

include_once __DIR__ . "/config/conexion.php";

$error = "";
$mensaje = "";
$token = $_GET['token'] ?? "";
$emailToken = "";
$tokenValido = false;

/* VALIDAR TOKEN */
if(!empty($token)){

    $sql = "SELECT * FROM password_resets
            WHERE token = ?
            AND usado = 0
            AND expiracion >= NOW()
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $token
    );

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($resultado) > 0){

        $reset = mysqli_fetch_assoc($resultado);

        $emailToken = $reset['email'];

        $tokenValido = true;

    } else {

        $error = "El enlace no es válido o ya expiró.";

    }

} else {

    $error = "Token no válido.";

}

/* CAMBIAR CONTRASEÑA */
if($_SERVER["REQUEST_METHOD"] == "POST" && $tokenValido){

    $password = trim($_POST['password']);
    $confirmar = trim($_POST['confirmar_password']);

    if(empty($password) || empty($confirmar)){

        $error = "Debes completar ambos campos.";

    } elseif(strlen($password) < 6){

        $error = "La contraseña debe tener al menos 6 caracteres.";

    } elseif($password !== $confirmar){

        $error = "Las contraseñas no coinciden.";

    } else {

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        /* ACTUALIZAR CONTRASEÑA */
        $sqlUpdate = "UPDATE usuarios
                      SET password = ?
                      WHERE email = ?";

        $stmtUpdate = mysqli_prepare($conn, $sqlUpdate);

        mysqli_stmt_bind_param(
            $stmtUpdate,
            "ss",
            $passwordHash,
            $emailToken
        );

        if(mysqli_stmt_execute($stmtUpdate)){

            /* MARCAR TOKEN COMO USADO */
            $sqlUsado = "UPDATE password_resets
                         SET usado = 1
                         WHERE token = ?";

            $stmtUsado = mysqli_prepare($conn, $sqlUsado);

            mysqli_stmt_bind_param(
                $stmtUsado,
                "s",
                $token
            );

            mysqli_stmt_execute($stmtUsado);

            $mensaje = "Tu contraseña fue actualizada correctamente.";

            $tokenValido = false;

        } else {

            $error = "No se pudo actualizar la contraseña.";

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
                Nueva contraseña
            </h1>

            <p>
                Crea una contraseña segura para volver a ingresar a tu cuenta.
            </p>

            <img src="img/reza.jpg"
                 alt="Restablecer contraseña SportStyle">

        </div>

        <!-- FORMULARIO -->
        <div class="login-box">

            <h2>
                Restablecer contraseña
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

                <p class="registro-link">

                    <a href="login.php">
                        Iniciar sesión
                    </a>

                </p>

            <?php endif; ?>

            <?php if($tokenValido): ?>

                <form method="POST">

                    <div class="input-group">

                        <label>
                            Nueva contraseña
                        </label>

                        <input type="password"
                               name="password"
                               placeholder="Nueva contraseña"
                               required>

                    </div>

                    <div class="input-group">

                        <label>
                            Confirmar contraseña
                        </label>

                        <input type="password"
                               name="confirmar_password"
                               placeholder="Confirmar contraseña"
                               required>

                    </div>

                    <button type="submit"
                            class="btn-login">

                        Guardar nueva contraseña

                    </button>

                </form>

            <?php endif; ?>

            <?php if(!$tokenValido && !$mensaje): ?>

                <p class="registro-link">

                    <a href="recuperar_password.php">
                        Solicitar nuevo enlace
                    </a>

                </p>

            <?php endif; ?>

        </div>

    </div>

</section>

<?php include("includes/footer.php"); ?>