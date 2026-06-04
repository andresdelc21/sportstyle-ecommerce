document.addEventListener("DOMContentLoaded", function(){

    /* =========================
       MODO CLARO / OSCURO
    ========================= */

    const btnTheme = document.getElementById("adminThemeToggle");
    const body = document.body;

    if(localStorage.getItem("adminTheme") === "light"){
        body.classList.add("admin-light");
    }

    if(btnTheme){

        btnTheme.addEventListener("click", function(){

            body.classList.toggle("admin-light");

            if(body.classList.contains("admin-light")){
                localStorage.setItem("adminTheme", "light");
            }else{
                localStorage.setItem("adminTheme", "dark");
            }

        });

    }

    /* =========================
       GRÁFICO DE VENTAS
    ========================= */

    const ventasCanvas =
        document.getElementById("ventasMesChart");

    if(
        ventasCanvas &&
        typeof Chart !== "undefined"
    ){

        new Chart(ventasCanvas, {

            type: "line",

            data: {

                labels: ventasLabels,

                datasets: [{
                    label: "Ventas Confirmadas",

                    data: ventasData,

                    tension: 0.35,

                    fill: true,

                    borderWidth: 3
                }]
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        display: true
                    }

                },

                scales: {

                    y: {
                        beginAtZero: true
                    }

                }

            }

        });

    }

});