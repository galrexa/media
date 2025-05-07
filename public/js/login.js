document.addEventListener("DOMContentLoaded", function () {
    // Toggle password visibility
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");

    if (togglePassword && password) {
        togglePassword.addEventListener("click", function () {
            const type =
                password.getAttribute("type") === "password"
                    ? "text"
                    : "password";
            password.setAttribute("type", type);
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    }

    // Clear error messages on input
    const inputs = document.querySelectorAll(".form-control");
    inputs.forEach((input) => {
        input.addEventListener("input", function () {
            const formGroup = this.closest(".form-group");
            const inputWithIcon = this.closest(".input-with-icon");

            if (formGroup) {
                const errorMessage = formGroup.querySelector(".error-message");
                if (errorMessage) {
                    errorMessage.remove();
                }
            }

            if (inputWithIcon) {
                inputWithIcon.classList.remove("has-error");
                const errorIcon = inputWithIcon.querySelector(".error-icon");
                if (errorIcon) {
                    errorIcon.remove();
                }
            }

            this.classList.remove("is-invalid");
        });
    });
});
