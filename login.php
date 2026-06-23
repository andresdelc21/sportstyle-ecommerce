<?php

session_start();

include_once __DIR__ . "/config/conexion.php";

$error = "";
$intentosLogin = $_SESSION['login_intentos'] ?? [];
$intentosLogin = array_filter(
    $intentosLogin,
    fn($timestamp) => $timestamp > time() - 900
);
$_SESSION['login_intentos'] = $intentosLogin;

/* LOGIN */
if($_SERVER["REQUEST_METHOD"] == "POST"){

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if(count($intentosLogin) >= 5){

        $error = "Demasiados intentos. Esperá unos minutos y volvé a probar.";

    } else {

        $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $resultado = mysqli_stmt_get_result($stmt);

        if(mysqli_num_rows($resultado) > 0){

            $usuario = mysqli_fetch_assoc($resultado);

            /* VERIFICAR PASSWORD */
            if(password_verify($password, $usuario['password'])){

                session_regenerate_id(true);

                unset($_SESSION['login_intentos']);

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_rol'] = $usuario['rol'];
                $_SESSION['usuario_email'] = $usuario['email'];

                if(($usuario['rol'] ?? 'cliente') === 'admin'){
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }

                exit;

            }

        }

        $_SESSION['login_intentos'][] = time();
        $error = "Correo o contraseña incorrectos.";

    }
}
?>

<?php include("includes/header.php"); ?>

<section class="login-page">

    <div class="login-container">

        <div class="login-info">

            <span class="auth-eyebrow">Mi cuenta</span>

            <h1>Ingresá y seguí tu compra</h1>

            <p>
                Revisá tus pedidos, guardá favoritos y finalizá tus compras con tus datos ya cargados.
            </p>

            <div class="auth-benefits">
                <span>Pedidos y seguimiento</span>
                <span>Favoritos guardados</span>
                <span>Checkout más rápido</span>
            </div>

        </div>

        <div class="login-box">

            <div class="auth-form-header">
                <span>SportStyle</span>
                <h2>Iniciar sesión</h2>
                <p>Usá el correo con el que compraste o creaste tu cuenta.</p>
            </div>

            <?php if($error): ?>
                <p class="error-msg"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">

                <div class="input-group">

                    <label>Correo electrónico</label>

                    <input type="email"
                           name="email"
                           placeholder="ejemplo@gmail.com"
                           required>

                </div>

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
                                aria-label="Mostrar u ocultar contraseña"
                                onclick="togglePassword()">

                            Ver

                        </button>

                    </div>

                </div>

                <div class="login-options">

                    <label class="remember">
                        <input type="checkbox">
                        Recordarme
                    </label>

                    <a href="recuperar_password.php">
                        ¿Olvidaste tu contraseña?
                    </a>

                </div>

                <button type="submit" class="btn-login">
                    Ingresar
                </button>

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
