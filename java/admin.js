document.addEventListener("DOMContentLoaded", function(){

    localStorage.removeItem("adminTheme");
    document.body.classList.remove("admin-light");

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
