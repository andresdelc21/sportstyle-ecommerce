# SportStyle Ecommerce

SportStyle es una tienda online desarrollada en PHP, MySQL, HTML, CSS y JavaScript. El proyecto está pensado como práctica profesional y portfolio personal, con funcionalidades reales de un ecommerce: catálogo, carrito, favoritos, checkout, gestión administrativa, stock, envíos, cupones y configuración de tienda.

## Funcionalidades principales

### Tienda

- Home con banner principal administrable.
- Catálogo de productos con filtros por categoría, marca, género y ofertas.
- Detalle de producto con imágenes, stock, favoritos y opiniones.
- Productos sin stock visibles con aviso, sin permitir compra.
- Diseño responsive para escritorio y mobile.
- Menú superior con categorías y marcas.
- Página de contacto con canales de atención.

### Usuarios

- Registro de clientes.
- Inicio y cierre de sesión.
- Roles de usuario: cliente y administrador.
- Recuperación de contraseña.
- Favoritos por usuario.

### Carrito y checkout

- Carrito lateral y carrito completo.
- Agregado de productos por AJAX.
- Control de stock al agregar y modificar cantidades.
- Cálculo de envío por código postal y zona.
- Cupones de descuento:
  - porcentaje;
  - monto fijo;
  - envío gratis.
- Checkout con datos del cliente, método de pago y resumen.
- Flujo de transferencia con datos bancarios y botón para enviar comprobante.
- Flujo de Mercado Pago preparado para Checkout Pro.

### Opiniones

- Sistema de reviews por producto.
- Calificación con estrellas.
- Comentarios de clientes.
- Opiniones visibles en el detalle del producto.

### Panel administrativo

- Dashboard administrativo.
- Gestión de productos.
- Carga y edición de imágenes.
- Gestión de stock.
- Gestión de pedidos y estados.
- Ventas.
- Usuarios.
- Categorías.
- Marcas.
- Zonas de envío.
- Cupones.
- Banners del home.
- Configuración general de la tienda.

### Seguridad aplicada

- Login con consultas preparadas.
- Registro con validaciones.
- Roles protegidos para el admin.
- Protección CSRF en acciones críticas del panel.
- Validación segura de subida de imágenes.
- Restricción de tipos de archivo permitidos.
- Control de acceso a páginas administrativas.

## Tecnologías utilizadas

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- XAMPP
- Git y GitHub

## Estructura del proyecto

```text
sportstyle/
├── admin/
├── config/
├── css/
├── data/
├── img/
├── includes/
├── java/
├── uploads/
├── index.php
├── productos.php
├── detalle.php
├── carrito.php
├── checkout.php
├── favoritos.php
├── contacto.php
├── login.php
├── registro.php
└── README.md
```

## Instalación local

1. Clonar el repositorio:

```bash
git clone https://github.com/progrmair21/sportstyle-ecommerce.git
```

2. Copiar o mantener el proyecto dentro de:

```text
C:/xampp/htdocs/sportstyle
```

3. Crear la base de datos:

```sql
CREATE DATABASE sportstyle;
```

4. Importar las tablas necesarias desde la base usada en el proyecto.

5. Configurar la conexión en:

```text
config/conexion.php
```

6. Iniciar Apache y MySQL desde XAMPP.

7. Abrir la tienda:

```text
http://localhost/sportstyle/
```

## Configuración de tienda

Desde el panel administrativo se pueden configurar datos generales:

- nombre de la tienda;
- email;
- WhatsApp;
- redes sociales;
- alias y CBU/CVU para transferencia;
- credenciales de Mercado Pago.

## Mercado Pago

El proyecto tiene preparado el flujo base para Mercado Pago Checkout Pro:

- creación de preferencia de pago;
- redirección a Mercado Pago;
- páginas de retorno para pago exitoso, pendiente o fallido;
- guardado de `mp_preference_id`, `mp_payment_id` y `mp_status` en pedidos.

Para probarlo se deben cargar credenciales de prueba desde el admin:

- `MP_PUBLIC_KEY`
- `MP_ACCESS_TOKEN`

## Próximas mejoras posibles

- Prueba completa con credenciales reales o de prueba de Mercado Pago.
- Webhook de Mercado Pago para actualizar pagos automáticamente en producción.
- Exportación de ventas/pedidos.
- Mejoras SEO.
- Historial de pedidos para clientes.
- Notificaciones por email.
- Deploy en hosting.

## Autor

**Andrés Del Carpio**

Analista de Sistemas Informáticos.

Proyecto desarrollado como práctica profesional y portfolio personal.
