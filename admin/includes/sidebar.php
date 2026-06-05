<?php

$paginaActual = basename($_SERVER['PHP_SELF']);

$menuPrincipal = [
    'index.php' => ['label' => 'Dashboard', 'match' => ['index.php']],
    'productos.php' => ['label' => 'Productos', 'match' => ['productos.php', 'agregar_productos.php', 'editar_productos.php']],
    'pedidos.php' => ['label' => 'Pedidos', 'match' => ['pedidos.php', 'ver_pedido.php']],
    'usuarios.php' => ['label' => 'Usuarios', 'match' => ['usuarios.php']],
    'ventas.php' => ['label' => 'Ventas', 'match' => ['ventas.php']],
];

$menuGestion = [
    'zonas_envio.php' => ['label' => 'Zonas de envío', 'match' => ['zonas_envio.php']],
    'cupones.php' => ['label' => 'Cupones', 'match' => ['cupones.php']],
    'banners.php' => ['label' => 'Banners', 'match' => ['banners.php']],
    'categorias.php' => ['label' => 'Categorías', 'match' => ['categorias.php']],
    'marcas.php' => ['label' => 'Marcas', 'match' => ['marcas.php']],
    'stock.php' => ['label' => 'Stock', 'match' => ['stock.php']],
    'configuracion.php' => ['label' => 'Configuración', 'match' => ['configuracion.php']],
];

function adminMenuLink(string $href, array $item, string $paginaActual): string {
    $activo = in_array($paginaActual, $item['match'], true)
        ? ' activo-admin'
        : '';

    return '<a href="' . htmlspecialchars($href) . '" class="' . $activo . '"><span>' . htmlspecialchars($item['label']) . '</span></a>';
}

?>

<aside class="admin-sidebar">

    <h2 class="admin-logo">
        Sport<span>Style</span>
    </h2>

    <div class="admin-sidebar-user">
        <strong><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Admin') ?></strong>
        <span>Administrador</span>
    </div>

    <nav class="admin-menu">

        <?php foreach($menuPrincipal as $href => $item): ?>
            <?= adminMenuLink($href, $item, $paginaActual) ?>
        <?php endforeach; ?>

        <hr>

        <?php foreach($menuGestion as $href => $item): ?>
            <?= adminMenuLink($href, $item, $paginaActual) ?>
        <?php endforeach; ?>

        <hr>

        <a href="../index.php">
            <span>Ver tienda</span>
        </a>

        <a href="../logout.php"
           class="logout-btn">

            <span>Cerrar sesión</span>

        </a>

    </nav>

</aside>
