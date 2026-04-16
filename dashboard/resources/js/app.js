import Alpine from "alpinejs";
import focus from "@alpinejs/focus";
import collapse from "@alpinejs/collapse";
import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.min.css";
import Swal from "sweetalert2";
import Chart from "chart.js/auto";

window.Alpine = Alpine;
window.flatpickr = flatpickr;
window.Swal = Swal;
window.Chart = Chart;

Alpine.plugin(focus);
Alpine.plugin(collapse);

// Toast helper -- dispatched dari mana saja via window.toast(message, type)
window.toast = function (message, type = "info", timeout = 3500) {
    window.dispatchEvent(
        new CustomEvent("toast", {
            detail: { message, type, timeout, id: Date.now() + Math.random() },
        }),
    );
};

// Confirm helper memakai SweetAlert2 dengan tampilan editorial Paper Editorial.
window.confirmAction = async function ({
    title = "Konfirmasi",
    text = "",
    confirmText = "Lanjutkan",
    cancelText = "Batal",
    danger = false,
} = {}) {
    const result = await Swal.fire({
        title,
        text,
        icon: danger ? "warning" : "question",
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: danger ? "#c92a2a" : "#1a1a1a",
        cancelButtonColor: "#a89b6a",
        background: "#ffffff",
        color: "#1a1a1a",
    });
    return result.isConfirmed;
};

Alpine.start();
