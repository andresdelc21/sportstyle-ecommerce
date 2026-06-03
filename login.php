<?php

session_start();



include_once __DIR__ . "/config/conexion.php";

$error = "";

/* LOGIN */
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM usuarios WHERE email='$email'";

    $resultado = mysqli_query($conn, $sql);

    if(mysqli_num_rows($resultado) > 0){

        $usuario = mysqli_fetch_assoc($resultado);

        /* VERIFICAR PASSWORD */
        if(password_verify($password, $usuario['password'])){

            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['usuario_email'] = $usuario['email'];

            header("Location: admin/index.php");
            exit;

        } else {

            $error = "Contraseña incorrecta";

        }

    } else {

        $error = "El correo no existe";

    }
}
?>

<?php include("includes/header.php"); ?>

<section class="login-page">

    <div class="login-container">

        <!-- LADO IZQUIERDO -->
        <div class="login-info">

            <h1>Bienvenido a SportStyle</h1>

            <p>
                Accede a tu cuenta para guardar favoritos,
                seguir tus pedidos y vivir la experiencia completa.
            </p>

            <img src="img/reza.jpg" alt="Login SportStyle">

        </div>

        <!-- FORMULARIO -->
        <div class="login-box">

            <h2>Iniciar Sesión</h2>

            <?php if($error): ?>
                <p class="error-msg"><?= $error ?></p>
            <?php endif; ?>

            <form method="POST">

                <!-- EMAIL -->
                <div class="input-group">

                    <label>Correo electrónico</label>

                    <input type="email"
                           name="email"
                           placeholder="ejemplo@gmail.com"
                           required>

                </div>

                <!-- PASSWORD -->
                <div class="input-group">

                    <label>Contraseña</label>

                    <div class="password-box">

                        <input type="password"
                               name="password"
                               id="password"
                               placeholder="********"
                               required>

                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword()">

                            👁️

                        </button>

                    </div>

                </div>

                <!-- OPCIONES -->
                <div class="login-options">

                    <label class="remember">
                        <input type="checkbox">
                        Recordarme
                    </label>

                    <a href="recuperar_password.php">
                        ¿Olvidaste tu contraseña?
                    </a>

                </div>

                <!-- BOTÓN -->
                <button type="submit" class="btn-login">
                    Ingresar
                </button>

                <!-- DIVISOR -->
                <div class="divider">
                    <span>o continuar con</span>
                </div>

                <!-- GOOGLE -->
                <button type="button" class="btn-google">

                    <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png">

                    Google

                </button>

                <!-- REGISTRO -->
                <p class="registro-link">

                    ¿No tienes cuenta?

                    <a href="registro.php">
                        Crear cuenta
                    </a>

                </p>

            </form>

        </div>

    </div>

</section>

<script>

function togglePassword() {

    const input = document.getElementById("password");

    if(input.type === "password"){
        input.type = "text";
    } else {
        input.type = "password";
    }

}

</script>

<?php include("includes/footer.php"); ?>