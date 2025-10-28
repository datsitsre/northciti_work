// admin/assets/js/login.js - AJAX-Based Login

$(document).ready(function () {
    // Initialize login form
    initializeLoginForm();

    // Auto-hide alerts after 5 seconds
    setTimeout(function () {
        $(".alert").fadeOut("slow");
    }, 5000);
});

function initializeLoginForm() {
    // Main form submission handler
    $(document).on("submit", "#loginForm", function (event) {
        event.preventDefault();

        // Clear previous messages
        hideAllErrors();
        hideLoginMessage();

        // Get form values
        let email = $("#email").val().trim();
        let password = $("#password").val();
        let rememberMe = $("#remember_me").is(":checked");
        let csrfToken = $("input[name='csrf_token']").val();

        // Validate form
        let emailValid = validateEmail(email);
        let passwordValid = validatePassword(password);

        if (emailValid && passwordValid) {
            // Show loading state
            showLoadingState();

            // Prepare form data
            let formData = new FormData();
            formData.append("email", email);
            formData.append("password", password);
            formData.append("csrf_token", csrfToken);
            if (rememberMe) {
                formData.append("remember_me", "1");
            }

            // Debug: Log what we're sending
            console.log("Sending login data:", {
                email: email,
                password: "***hidden***",
                csrf_token: csrfToken,
                remember_me: rememberMe,
            });

            // Send AJAX request
            fetch(window.location.href, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => {
                    // Check if response is JSON or HTML
                    const contentType = response.headers.get("content-type");
                    if (
                        contentType &&
                        contentType.includes("application/json")
                    ) {
                        return response.json();
                    } else {
                        return response.text().then((text) => {
                            // If we get HTML back, it's likely the login page with an error
                            // Extract error message from the HTML if possible
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(
                                text,
                                "text/html"
                            );
                            const errorElement =
                                doc.querySelector(".alert.bg-red-100");

                            if (errorElement) {
                                const errorText =
                                    errorElement.textContent.trim();
                                throw new Error(errorText);
                            } else if (
                                text.includes("dashboard") ||
                                response.redirected
                            ) {
                                // Successful login - redirect
                                window.location.href =
                                    "/northcity/admin/dashboard";
                                return;
                            } else {
                                throw new Error(
                                    "Login failed - please try again"
                                );
                            }
                        });
                    }
                })
                .then((data) => {
                    hideLoadingState();

                    if (data && data.success) {
                        // Success response
                        showLoginMessage(
                            data.message || "Login successful! Redirecting...",
                            "success"
                        );
                        setTimeout(() => {
                            window.location.href =
                                data.redirect || "/northcity/admin/dashboard";
                        }, 1000);
                    } else if (data && data.error) {
                        // Error response
                        showLoginMessage(data.error, "error");
                    } else {
                        // Fallback for successful login without JSON response
                        showLoginMessage(
                            "Login successful! Redirecting...",
                            "success"
                        );
                        setTimeout(() => {
                            window.location.href = "/northcity/admin/dashboard";
                        }, 1000);
                    }
                })
                .catch((error) => {
                    hideLoadingState();
                    console.error("Login error:", error);
                    showLoginMessage(
                        error.message || "Login failed. Please try again.",
                        "error"
                    );
                });
        }
    });

    // Toggle password visibility
    $("#togglePassword").on("click", function () {
        togglePasswordVisibility();
    });

    // Real-time validation
    $("#email").on("blur", function () {
        validateEmail($(this).val().trim());
    });

    $("#password").on("blur", function () {
        validatePassword($(this).val());
    });

    // Clear errors on input
    $("#email, #password").on("input", function () {
        const fieldName = $(this).attr("name");
        hideError(fieldName);
        hideLoginMessage();
    });

    // Auto-focus on email field
    $("#email").focus();
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!email) {
        showError("email", "Email address is required");
        return false;
    }

    if (!emailRegex.test(email)) {
        showError("email", "Please enter a valid email address");
        return false;
    }

    hideError("email");
    return true;
}

function validatePassword(password) {
    if (!password) {
        showError("password", "Password is required");
        return false;
    }

    if (password.length < 6) {
        showError("password", "Password must be at least 6 characters long");
        return false;
    }

    hideError("password");
    return true;
}

function showError(field, message) {
    $(`#${field}`).addClass("border-red-500").removeClass("border-gray-300");
    $(`#${field}-error`).text(message).removeClass("hidden");
}

function hideError(field) {
    $(`#${field}`).removeClass("border-red-500").addClass("border-gray-300");
    $(`#${field}-error`).addClass("hidden");
}

function hideAllErrors() {
    $("#email-error, #password-error").addClass("hidden");
    $("#email, #password")
        .removeClass("border-red-500")
        .addClass("border-gray-300");
}

function showLoadingState() {
    $("#loginBtn").prop("disabled", true);
    $("#loginBtnText").addClass("hidden");
    $("#loginBtnSpinner").removeClass("hidden");
    $("#email, #password").prop("disabled", true);
}

function hideLoadingState() {
    $("#loginBtn").prop("disabled", false);
    $("#loginBtnText").removeClass("hidden");
    $("#loginBtnSpinner").addClass("hidden");
    $("#email, #password").prop("disabled", false);
}

function showLoginMessage(message, type = "error") {
    const alertClass = {
        success: "bg-green-100 border-green-400 text-green-700",
        error: "bg-red-100 border-red-400 text-red-700",
        warning: "bg-yellow-100 border-yellow-400 text-yellow-700",
        info: "bg-blue-100 border-blue-400 text-blue-700",
    };

    const icon = {
        success: "fa-check-circle",
        error: "fa-exclamation-circle",
        warning: "fa-exclamation-triangle",
        info: "fa-info-circle",
    };

    const messageHtml = `
        <div id="loginMessage" class="alert ${alertClass[type]} px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <i class="fas ${icon[type]} mr-2"></i>
                <span>${message}</span>
            </div>
        </div>
    `;

    // Remove existing message
    $("#loginMessage").remove();

    // Add new message before the form
    $(".login-form").before(messageHtml);

    // Auto-hide error messages after 5 seconds
    if (type === "error") {
        setTimeout(function () {
            $("#loginMessage").fadeOut("slow");
        }, 5000);
    }
}

function hideLoginMessage() {
    $("#loginMessage").remove();
}

function togglePasswordVisibility() {
    const passwordField = $("#password");
    const eyeIcon = $("#eyeIcon");

    if (passwordField.attr("type") === "password") {
        passwordField.attr("type", "text");
        eyeIcon.removeClass("fa-eye").addClass("fa-eye-slash");
    } else {
        passwordField.attr("type", "password");
        eyeIcon.removeClass("fa-eye-slash").addClass("fa-eye");
    }
}

// Security measures
$(document).ready(function () {
    // Add honeypot field
    const honeypot = $("<input>").attr({
        type: "text",
        name: "website",
        style: "display:none",
        tabindex: "-1",
        autocomplete: "off",
    });
    $("#loginForm").append(honeypot);

    // Disable certain keyboard shortcuts
    $(document).on("keydown", function (e) {
        if (
            e.keyCode === 123 ||
            (e.ctrlKey &&
                e.shiftKey &&
                (e.keyCode === 73 || e.keyCode === 74)) ||
            (e.ctrlKey && e.keyCode === 85)
        ) {
            e.preventDefault();
            return false;
        }
    });

    // Disable right-click context menu
    $(document).on("contextmenu", function (e) {
        e.preventDefault();
        return false;
    });
});
