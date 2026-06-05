console.log("JS cargado");

/* =========================
   ESPERAR QUE CARGUE EL DOM
========================= */
document.addEventListener("DOMContentLoaded", () => {

    /* ===== MENÚ MOBILE ===== */
    const menuToggle = document.getElementById("menuToggle");
    const menuPrincipal = document.getElementById("menuPrincipal");

    if (menuToggle && menuPrincipal) {
        menuToggle.addEventListener("click", () => {
            const abierto = menuPrincipal.classList.toggle("activo");

            menuToggle.setAttribute("aria-expanded", abierto ? "true" : "false");
            menuToggle.textContent = abierto ? "×" : "☰";
        });

        menuPrincipal.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", () => {
                if (link.getAttribute("href") === "#") return;

                menuPrincipal.classList.remove("activo");
                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.textContent = "☰";
            });
        });
    }

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

    /* ===== FAVORITOS ===== */
    document.querySelectorAll(".btn-favorito-js").forEach(btn => {

        btn.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();

            const productoId = btn.dataset.producto;

            if (!productoId) return;

            const formData = new FormData();
            formData.append("producto_id", productoId);

            fetch(BASE_URL + "toggle_favorito.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
            .then(res => res.json())
            .then(data => {
                if (!data.ok) {
                    window.location.href = BASE_URL + "login.php";
                    return;
                }

                btn.classList.toggle("favorito-activo", data.favorito);
                btn.title = data.favorito
                    ? "Quitar de favoritos"
                    : "Agregar a favoritos";

                const texto = btn.querySelector(".favorito-texto");

                if (texto) {
                    texto.textContent = data.favorito
                        ? "En favoritos"
                        : "Guardar favorito";
                }
            })
            .catch(err => console.error("Error actualizando favorito:", err));
        });

    });

    /* ===== AGREGAR AL CARRITO SIN SALIR DE LA PÁGINA ===== */
    document.querySelectorAll(".btn-agregar-carrito-js").forEach(btn => {

        btn.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();

            const productoId = btn.dataset.producto;

            if (!productoId) return;

            const formData = new FormData();
            formData.append("producto_id", productoId);

            fetch(BASE_URL + "carrito_ajax.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
            .then(res => res.json())
            .then(data => {
                if (!data.ok) {
                    mostrarMensajeCarrito(data.mensaje, false);
                    return;
                }

                actualizarBadgeCarrito(data.cantidad_items);
                mostrarBotonAgregado(btn);

                const carrito = document.getElementById("carrito-lateral");

                if (carrito && carrito.classList.contains("activo")) {
                    cargarCarrito();
                }
            })
            .catch(err => console.error("Error agregando al carrito:", err));
        });

    });

});

function mostrarBotonAgregado(btn) {
    if (!btn) return;

    const textoOriginal = btn.dataset.textoOriginal || btn.innerHTML;

    btn.dataset.textoOriginal = textoOriginal;
    btn.innerHTML = "✓ Agregado";
    btn.classList.add("agregado-carrito");

    clearTimeout(btn._agregadoTimer);

    btn._agregadoTimer = setTimeout(() => {
        btn.innerHTML = textoOriginal;
        btn.classList.remove("agregado-carrito");
    }, 1400);
}

/* =========================
   CARRITO
========================= */
function cargarCarrito() {
    return fetch(BASE_URL + "includes/carrito_lateral.php", {
        credentials: "same-origin"
    })
    .then(res => res.text())
    .then(html => {
        const carrito = document.getElementById("carrito-lateral");
        if (carrito) carrito.innerHTML = html;
    })
    .catch(err => console.error("Error cargando carrito:", err));
}

function abrirCarrito() {
    const carrito = document.getElementById("carrito-lateral");
    const overlay = document.getElementById("overlay");

    if (!carrito || !overlay) return;

    cargarCarrito().then(() => {
        carrito.classList.add("activo");
        overlay.classList.add("activo");
    });
}

function actualizarBadgeCarrito(cantidad) {
    const badge = document.getElementById("carrito-badge");

    if (!badge) return;

    badge.textContent = cantidad;
    badge.classList.toggle("oculto", cantidad <= 0);
}

function mostrarMensajeCarrito(mensaje, ok) {
    if (!mensaje) return;

    let aviso = document.getElementById("carrito-toast");

    if (!aviso) {
        aviso = document.createElement("div");
        aviso.id = "carrito-toast";
        aviso.className = "carrito-toast";
        document.body.appendChild(aviso);
    }

    aviso.textContent = mensaje;
    aviso.classList.toggle("error", !ok);
    aviso.classList.add("visible");

    clearTimeout(aviso.dataset.timer);

    const timer = setTimeout(() => {
        aviso.classList.remove("visible");
    }, 2200);

    aviso.dataset.timer = timer;
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
document.addEventListener("DOMContentLoaded", function(){

    const btn = document.getElementById("asistenteBtn");
    const box = document.getElementById("asistenteBox");
    const cerrar = document.getElementById("cerrarAsistente");

    if(btn && box){

        btn.addEventListener("click", function(){
            box.classList.toggle("activo");
        });

    }

    if(cerrar && box){

        cerrar.addEventListener("click", function(){
            box.classList.remove("activo");
        });

    }

});
