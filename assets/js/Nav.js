export default class Nav {
    init() {
        let open = document.getElementById('mobileMenuOpen');
        let menu = document.getElementById('mobileMenu');
        let backdrop = document.getElementById('mobileMenuBackdrop');
        let close = document.getElementById('mobileMenuClose');
        open.addEventListener("click", () => {
            menu.classList.toggle("hidden");
            backdrop.classList.toggle("hidden");
        });
        backdrop.addEventListener("click", () => {
            menu.classList.toggle("hidden");
            backdrop.classList.toggle("hidden");
        });
        close.addEventListener("click", () => {
            menu.classList.toggle("hidden");
            backdrop.classList.toggle("hidden");
        });
    }
}
