// Farmer layout interactions

document.addEventListener("DOMContentLoaded", function () {
    const menuBtn   = document.querySelector(".menu-btn");
    const sidebar   = document.querySelector(".sidebar");
    const overlay   = document.querySelector(".menu-overlay");

    if (menuBtn && sidebar && overlay) {
        menuBtn.addEventListener("click", () => {
            sidebar.classList.toggle("active");
            overlay.classList.toggle("active");
        });

        overlay.addEventListener("click", () => {
            sidebar.classList.remove("active");
            overlay.classList.remove("active");
        });
    }
});
