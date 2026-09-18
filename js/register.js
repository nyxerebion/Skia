document.addEventListener("DOMContentLoaded", () => {
  handleMessage();

  const form = document.querySelector("form");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      handleRegister();
    });
  }
});

function handleMessage() {
  const flashMessage = document.querySelector(".flash-message");
  const errorMessage = document.querySelector(".error-message");
  const successMessage = document.querySelector(".success-message");

  const updateMessage = errorMessage || successMessage;

  if (!flashMessage && !updateMessage) return;

  setTimeout(() => {
    if (flashMessage) flashMessage.style.display = "none";
    if (updateMessage) updateMessage.style.display = "none";
  }, 10000);
}

function togglePasswordVisibility(fieldId, btn) {
  const field = document.getElementById(fieldId);
  if (field.type === "password") {
    field.type = "text";
    btn.textContent = "Hide";
  } else {
    field.type = "password";
    btn.textContent = "Show";
  }
}

function handleRegister() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";
  const username = document.getElementById("usernameField");
  const email = document.getElementById("emailField");
  const password = document.getElementById("passwordField");
  const confirm_password = document.getElementById("confirmField");

  const errors = [];

  if (!username || !email || !password || !confirm_password) {
    toast("Elements are not valid", "error");
    return;
  }

  const usernameVal = username.value.trim();
  const emailVal = email.value.trim();
  const passwordVal = password.value;
  const confirmVal = confirm_password.value;

  // Username validation
  if (!usernameVal) {
    errors.push("Username is required");
  } else if (usernameVal.length < 4) {
    errors.push("Username must be at least 4 characters");
  } else if ((usernameVal.match(/[a-zA-Z]/g) || []).length < 2) {
    errors.push("Username must contain at least 2 letters");
  } else if ((usernameVal.match(/[0-9]/g) || []).length < 1) {
    errors.push("Username must contain at least 1 number");
  } else if (!/^[a-zA-Z0-9_]+$/.test(usernameVal)) {
    errors.push("Username can only contain letters, numbers, and underscores");
  } else if ((usernameVal.match(/_/g) || []).length > 1) {
    errors.push("Username can contain at most 1 underscore");
  }

  // Email validation
  if (!emailVal) {
    errors.push("Email is required");
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(emailVal)) {
    errors.push("Please enter a valid email address (e.g., user@gmail.com)");
  } else {
    const domain = emailVal.split("@")[1];
    if (!domain) {
      errors.push("Invalid email format");
    }
  }

  // Password validation
  if (!passwordVal) {
    errors.push("Password is required");
  } else if (passwordVal.length < 6) {
    errors.push("Password must be at least 6 characters");
  } else if (passwordVal.length > 100) {
    errors.push("Password must be at most 100 characters")
  }

  // Confirm password validation
  if (!confirmVal) {
    errors.push("Please confirm your password");
  } else if (passwordVal !== confirmVal) {
    errors.push("Passwords do not match");
  }

  // Show errors and stop if any
  if (errors.length > 0) {
    toast(errors.map((e) => `• ${e}`).join("<br>"), "error");
    return;
  }

  const submitBtn = document.querySelector('button[type="submit"]');
  const originalText = submitBtn ? submitBtn.textContent : "Register";
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Registering...";
  }

  fetch(API_URL + "/register.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      csrf_token: csrfToken,
      username: usernameVal,
      email: emailVal,
      password: passwordVal,
      confirm_password: confirmVal,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        toast(data.message || "Registration successful!", "success");
        setTimeout(() => {
          window.location.href = "../index.php";
        }, 1500);
      } else {
        if (data.errors && Array.isArray(data.errors)) {
          const errorMsg = data.errors.join(" • ");
          toast(errorMsg, "error");
        } else if (data.error) {
          toast(data.error, "error");
        } else {
          toast("Registration failed. Please try again.", "error");
        }
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      toast("Network error. Please try again.", "error");
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
}
