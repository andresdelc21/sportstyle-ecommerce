console.log("JS cargado");

/* =========================
   ESPERAR QUE CARGUE EL DOM
========================= */
document.addEventListener("DOMContentLoaded", () => {

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

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
            formData.append("csrf_token", csrfToken);

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
                const icono = btn.querySelector(".favorito-icono");

                if (icono) {
                    icono.textContent = data.favorito ? "♥" : "♡";
                }

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

            const talleSeleccionado = document.querySelector('input[name="talle_id"]:checked');

            if (talleSeleccionado) {
                formData.append("talle_id", talleSeleccionado.value);
            }

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

    clearTimeout(aviso._timer);

    const timer = setTimeout(() => {
        aviso.classList.remove("visible");
    }, 2200);

    aviso._timer = timer;
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
    const chatMensajes = document.getElementById("chatMensajes");
    const chatForm = document.getElementById("chatInputForm");
    const chatInput = document.getElementById("chatInput");

    const respuestasChat = {
        productos: {
            texto: "Podés ver todos los productos desde el catálogo. Si buscás algo puntual, usá el buscador superior o entrá por Hombre, Mujer, Niños, Marcas o SALE.",
            enlace: { texto: "Ir a productos", url: "productos.php" }
        },
        talles: {
            texto: "En el detalle de cada producto vas a ver los talles disponibles y el stock por talle. Calzado usa ARG/US/BR/CM, indumentaria usa S a XXL y medias pueden ir por rango.",
            enlace: { texto: "Ver catálogo", url: "productos.php" }
        },
        pagos: {
            texto: "Hoy podés pagar por transferencia o coordinar por WhatsApp. Mercado Pago queda preparado para usar crédito, débito o dinero en cuenta cuando carguemos credenciales reales.",
            enlace: { texto: "Ver carrito", url: "carrito.php" }
        },
        envios: {
            texto: "El envío se calcula o coordina antes de finalizar la compra. Si tu zona todavía no está cargada, podés consultar por WhatsApp.",
            enlace: { texto: "Contacto", url: "contacto.php" }
        },
        pedidos: {
            texto: "Si ya compraste, podés seguir el estado desde Mis pedidos. Ahí también queda guardada la compra y podés ver el detalle.",
            enlace: { texto: "Mis pedidos", url: "mis_pedidos.php" }
        },
        cambios: {
            texto: "Para cambios o devoluciones, conviene revisar el pedido y enviar la solicitud desde el detalle. Así queda registrado y el admin puede responder.",
            enlace: { texto: "Mis pedidos", url: "mis_pedidos.php" }
        },
        contacto: {
            texto: "Si necesitás atención directa, podés escribir por WhatsApp o usar la página de contacto.",
            enlace: { texto: "Contacto", url: "contacto.php" }
        }
    };

    function agregarMensajeChat(texto, tipo, enlace) {
        if (!chatMensajes || !texto) return;

        const mensaje = document.createElement("div");
        mensaje.className = "chat-msg " + tipo;

        const parrafo = document.createElement("p");
        parrafo.textContent = texto;
        mensaje.appendChild(parrafo);

        if (enlace) {
            const link = document.createElement("a");
            link.href = enlace.url;
            link.textContent = enlace.texto;
            mensaje.appendChild(link);
        }

        chatMensajes.appendChild(mensaje);
        chatMensajes.scrollTop = chatMensajes.scrollHeight;
    }

    function responderChat(clave) {
        const respuesta = respuestasChat[clave] || respuestasChat.contacto;
        agregarMensajeChat(respuesta.texto, "bot", respuesta.enlace);
    }

    function detectarConsulta(texto) {
        const limpio = texto.toLowerCase();

        if (limpio.includes("talle") || limpio.includes("medida") || limpio.includes("stock")) return "talles";
        if (limpio.includes("pago") || limpio.includes("mercado") || limpio.includes("tarjeta") || limpio.includes("transfer")) return "pagos";
        if (limpio.includes("envio") || limpio.includes("envío") || limpio.includes("zona")) return "envios";
        if (limpio.includes("pedido") || limpio.includes("seguimiento") || limpio.includes("compra")) return "pedidos";
        if (limpio.includes("cambio") || limpio.includes("devol")) return "cambios";
        if (limpio.includes("producto") || limpio.includes("zapat") || limpio.includes("remera") || limpio.includes("short")) return "productos";

        return "contacto";
    }

    if(btn && box){

        btn.addEventListener("click", function(){
            box.classList.toggle("activo");
            if (box.classList.contains("activo") && chatInput) {
                setTimeout(() => chatInput.focus(), 120);
            }
        });

    }

    if(cerrar && box){

        cerrar.addEventListener("click", function(){
            box.classList.remove("activo");
        });

    }

    document.querySelectorAll("[data-chat]").forEach(opcion => {
        opcion.addEventListener("click", function(){
            const clave = this.dataset.chat;
            agregarMensajeChat(this.textContent.trim(), "user");
            responderChat(clave);
        });
    });

    if (chatForm && chatInput) {
        chatForm.addEventListener("submit", function(event){
            event.preventDefault();

            const texto = chatInput.value.trim();
            if (!texto) return;

            agregarMensajeChat(texto, "user");
            responderChat(detectarConsulta(texto));
            chatInput.value = "";
        });
    }

});
