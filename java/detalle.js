document.addEventListener("DOMContentLoaded", function(){

    const imagenPrincipal = document.getElementById("img-principal");
    const miniaturas = document.querySelectorAll(".miniatura-producto");

    const overlay = document.getElementById("zoomOverlay");
    const zoomImage = document.getElementById("zoomImage");
    const zoomCerrar = document.getElementById("zoomCerrar");

    if(imagenPrincipal && miniaturas.length > 0){

        miniaturas.forEach(function(miniatura){

            miniatura.addEventListener("click", function(){

                const nuevaImagen = this.dataset.img;
                const nuevoAlt = this.dataset.alt || imagenPrincipal.alt;

                imagenPrincipal.src = nuevaImagen;
                imagenPrincipal.alt = nuevoAlt;

                miniaturas.forEach(function(item){
                    item.classList.remove("activa");
                });

                this.classList.add("activa");

            });

        });

    }

    if(imagenPrincipal && overlay && zoomImage){

        imagenPrincipal.addEventListener("click", function(){

            zoomImage.src = imagenPrincipal.src;
            zoomImage.alt = imagenPrincipal.alt;
            overlay.classList.add("activo");
            overlay.classList.remove("zoom-mas");

        });

        overlay.addEventListener("click", function(){

            overlay.classList.remove("activo");
            overlay.classList.remove("zoom-mas");
            zoomImage.src = "";
            zoomImage.alt = "";

        });

        zoomImage.addEventListener("click", function(event){
            event.stopPropagation();
            overlay.classList.toggle("zoom-mas");
        });

        if(zoomCerrar){
            zoomCerrar.addEventListener("click", function(event){
                event.stopPropagation();
                overlay.classList.remove("activo");
                overlay.classList.remove("zoom-mas");
                zoomImage.src = "";
                zoomImage.alt = "";
            });
        }

    }

});
