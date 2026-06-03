document.addEventListener("DOMContentLoaded", function(){

    const imagenPrincipal = document.getElementById("img-principal");
    const miniaturas = document.querySelectorAll(".miniatura-producto");

    const overlay = document.getElementById("zoomOverlay");
    const zoomImage = document.getElementById("zoomImage");

    if(imagenPrincipal && miniaturas.length > 0){

        miniaturas.forEach(function(miniatura){

            miniatura.addEventListener("click", function(){

                const nuevaImagen = this.dataset.img;

                imagenPrincipal.src = nuevaImagen;

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
            overlay.classList.add("activo");

        });

        overlay.addEventListener("click", function(){

            overlay.classList.remove("activo");
            zoomImage.src = "";

        });

    }

});