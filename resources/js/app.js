import "./bootstrap";
import Alpine from "alpinejs";
import collapse from '@alpinejs/collapse';

// IMPORT COMPONENT
import profileForm from "./pages/profile.js";
import favoriteManager from "./pages/favoriteManager.js";

window.Alpine = Alpine;

// TAMBAHKAN INI
window.favoriteManager = favoriteManager;

Alpine.plugin(collapse);

// REGISTER COMPONENT
Alpine.data("profileForm", profileForm);
Alpine.data("favoriteManager", favoriteManager);

// START
Alpine.start();

// Global store
document.addEventListener("alpine:init", () => {
    Alpine.store("app", {
        isMenuOpen: false,
        toggleMenu() {
            this.isMenuOpen = !this.isMenuOpen;
        },
    });
});