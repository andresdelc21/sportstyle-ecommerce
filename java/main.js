console.log("JS cargado");

/* =========================
   ESPERAR QUE CARGUE EL DOM
========================= */
document.addEventListener("DOMContentLoaded", () => {

    /* ===== CAMBIO DE IMAGEN EN DETALLE ===== */
    window.cambiarImagen = function(img) {
        const principal = document.getElementById("img-principal");
        if (principal) {
            principal.src = img.src;
        }
    };

    /* ===== EFECTO HOVER IMÁGENES PRODUCTO ===== */
    document.querySelectorAll(".img-principal").forEach(img => {

        const imagenes = JSON.parse(img.dataset.imgs || "[]");
        let index = 0;
        let intervalo;

        if (imagenes.length <= 1) return;

        img.addEventListener("mouseenter", () => {
            intervalo = setInterval(() => {
                index = (index + 1) % imagenes.length;
                img.src = imagenes[index];
            }, 800);
        });

        img.addEventListener("mouseleave", () => {
            clearInterval(intervalo);
            img.src = imagenes[0];
            index = 0;
        });

    });

});

/* =========================
   CARRITO
========================= */
function cargarCarrito() {
    fetch(BASE_URL + "includes/carrito_lateral.php", {
        credentials: "same-origin"
    })
    .then(res => res.text())
    .then(html => {
        const carrito = document.getElementById("carrito-lateral");
        if (carrito) carrito.innerHTML = html;
    })
    .catch(err => console.error("Error cargando carrito:", err));
}

function toggleCarrito() {
    const carrito = document.getElementById("carrito-lateral");
    const overlay = document.getElementById("overlay");

    if (!carrito || !overlay) return;

    carrito.classList.toggle("activo");
    overlay.classList.toggle("activo");

    // cargar contenido SOLO cuando se abre
    if (carrito.classList.contains("activo")) {
        cargarCarrito();
    }
}
