import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    publicDir: "public",
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/custom/admin.css", // Tambahkan file CSS admin baru
                "resources/js/app.js",
                "node_modules/bootstrap-icons/font/bootstrap-icons.css",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
