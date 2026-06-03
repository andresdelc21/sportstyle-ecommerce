<?php

session_start();

include("config/conexion.php");

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // VALIDAR EMAIL EXISTENTE
    $check = mysqli_query($conn,
    "SELECT * FROM usuarios WHERE email='$email'");

    if(mysqli_num_rows($check) > 0){

        $error = "El correo ya está registrado";

    } else {

        // ENCRIPTAR PASSWORD
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // INSERTAR
        $sql = "INSERT INTO usuarios
        (nombre,email,password)
        VALUES
        ('$nombre','$email','$passwordHash')";

        if(mysqli_query($conn,$sql)){

            $success = "Cuenta creada correctamente";

        } else {

            $error = "Error al registrar";

        }
    }
}
?>

<?php include("includes/header.php"); ?>

<section class="login-page">

<div class="login-container">

    <!-- IZQUIERDA -->
    <div class="login-info">

        <h1>Únete a SportStyle</h1>

        <p>
            Regístrate para guardar favoritos,
            comprar más rápido y acceder a ofertas.
        </p>

    </div>

    <!-- FORM -->
    <div class="login-box">

        <h2>Crear cuenta</h2>

        <?php if($error): ?>
            <p class="error-msg"><?= $error ?></p>
        <?php endif; ?>

        <?php if($success): ?>
            <p class="success-msg"><?= $success ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">

                <label>Nombre</label>

                <input type="text"
                       name="nombre"
                       required>

            </div>

            <div class="input-group">

                <label>Email</label>

                <input type="email"
                       name="email"
                       required>

            </div>

            <div class="input-group">

                <label>Contraseña</label>

                <input type="password"
                       name="password"
                       required>

            </div>

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