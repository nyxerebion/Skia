document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      handleUpdate();
    });
  }

  validationMessage = document.getElementById("validationMessage");
});

let usernameCheckTimeout = null;
let validationMessage = null;
let usernameValid = null;

function handleUpdate() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";
  const username = document.getElementById("usernameField");
  const name = document.getElementById("nameField");
  const bio = document.getElementById("bioField");

  const errors = [];

  if (!username || !name || !bio) {
    toast("Elements are not valid", "error");
    return;
  }

  const usernameVal = username.value.trim();
  const nameVal = name.value.trim();
  const bioVal = bio.value.trim();

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

  if (!usernameValid) {
    errors.push("Username already exist!");
  }

  // Name valdation
  if (!nameVal) {
    errors.push("Name is required");
  }

  // Bio validation
  if (!bioVal) {
    errors.push("Bio is required");
  }

  // Show errors and stop if any
  if (errors.length > 0) {
    toast(errors.map((e) => `• ${e}`).join("<br>"), "error");
    return;
  }

  const submitBtn = document.querySelector('input[type="submit"]');
  const originalText = submitBtn ? submitBtn.textContent : "Register";
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Registering...";
  }

  fetch(API_URL + "/account-update.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      csrf_token: csrfToken,
      username: usernameVal,
      name: nameVal,
      bio: bioVal,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Data response:", data);
      if (data.success) {
        toast(data.message || "Registration successful!", "success");
        setTimeout(() => {
          window.location.reload();
        }, 1000);
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

function checkUsername(username) {
  const messageEl = document.getElementById("validationMessage");
  const parent = messageEl.parentElement;
  const item = parent.querySelector(".info-item");

  // Clear previous timeout
  if (usernameCheckTimeout) {
    clearTimeout(usernameCheckTimeout);
  }

  // Only clear if empty, keep previous message if short
  if (!username || username.length < 3) {
    if (username === "") {
      messageEl.textContent = "";
      item.classList.remove("valid", "invalid");
      messageEl.style.display = "none";
    }
    return;
  }

  usernameCheckTimeout = setTimeout(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = meta ? meta.content : "";

    console.log("Checking username:", username);

    fetch(API_URL + "/user/check_username.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        csrf_token: csrfToken,
        username: username.trim(),
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        console.log("Response:", data);
        if (data.exist) {
          messageEl.textContent = "❌ Username already taken";
          messageEl.style.color = "#ef4444";
          messageEl.style.display = "block";
          item.classList.remove("valid");
          item.classList.add("invalid");

          usernameValid = false;
        } else {
          messageEl.textContent = "✅ Username available";
          messageEl.style.color = "#22c55e";
          messageEl.style.display = "block";
          item.classList.remove("invalid");
          item.classList.add("valid");
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        messageEl.textContent = "⚠️ Error checking username";
        messageEl.style.color = "#f59e0b";
        messageEl.style.display = "block";
        item.classList.remove("valid", "invalid");

        usernameValid = true;
      });
  }, 500);
}


function closePopup(element) {
  if (element) {
    element.style.display = "none";
  }
}