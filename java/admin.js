document.addEventListener("DOMContentLoaded", function(){

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
            } else {
                localStorage.setItem("adminTheme", "dark");
            }

        });

    }

});