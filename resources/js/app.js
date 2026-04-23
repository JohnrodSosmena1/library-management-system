import "./bootstrap";

/**
 * Davao City Library — LMS
 * Main JavaScript
 */

// ── Delete Confirmation ──────────────────────────────────
function confirmDelete(form, name) {
    return confirm(`Delete "${name}"? This action cannot be undone.`);
}

// ── Auto-dismiss flash messages ──────────────────────────
document.addEventListener("DOMContentLoaded", () => {
    const flash = document.querySelector(".flash");
    if (flash) {
        setTimeout(() => {
            flash.style.transition = "opacity .5s";
            flash.style.opacity = "0";
            setTimeout(() => flash.remove(), 500);
        }, 4000);
    }
});
