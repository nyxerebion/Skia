document.addEventListener("DOMContentLoaded", () => {
  const usernameForm = document.getElementById("usernameForm");
  if (usernameForm) {
    usernameForm.addEventListener("submit", (e) => {
      e.preventDefault();
      handleUsernameUpdate();
    });
  }

  const usernameItem = document.getElementById("usernameItem");
  if (usernameItem) {
    usernameItem.addEventListener("click", () => {
      const popup = document.getElementById("usernamePopup");
      openPopup(popup);
    });
  }

  const nameForm = document.getElementById("nameForm");
  if (nameForm) {
    nameForm.addEventListener("submit", (e) => {
      e.preventDefault();
      handleNameUpdate();
    });
  }

  const nameItem = document.getElementById("nameItem");
  if (nameItem) {
    nameItem.addEventListener("click", () => {
      const popup = document.getElementById("namePopup");
      openPopup(popup);
    });
  }

  // Read URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const popup = urlParams.get("popup");

  // if popup=username, open username popup
  if (popup === "username") {
    const popupEl = document.getElementById("usernamePopup");
    if (popupEl) {
      openPopup(popupEl);
    }
  }

  // if popup=name, open name popup
  if (popup === "name") {
    const popupEl = document.getElementById("namePopup");
    if (popupEl) {
      openPopup(popupEl);
    }
  }

  if (popup === "bio") {
    const popupEl = document.getElementById("bioPopup");
    if (popupEl) {
      openPopup(popupEl);
    }
  }

  const bioItem = document.getElementById("bioItem");
  if (bioItem) {
    bioItem.addEventListener("click", () => {
      const popup = document.getElementById("bioPopup");
      openPopup(popup);
    });
  }

  const bioForm = document.getElementById("bioForm");
  if (bioForm) {
    bioForm.addEventListener("submit", (e) => {
      e.preventDefault();
      handleBioUpdate();
    });
  }

  const passItem = document.getElementById("passItem");
  if (passItem) {
    passItem.addEventListener("click", () => {
      const popup = document.getElementById("passPopup");
      openPopup(popup);
    });
  }

  const passForm = document.getElementById("passForm");
  if (passForm) {
    passForm.addEventListener("submit", (e) => {
      e.preventDefault();
      handlePasswordUpdate();
    });
  }

  updateCharCount();
});

let usernameCheckTimeout = null;
let nameCheckTimeout = null;
let bioCheckTimeout = null;  
let usernameValid = null;
let nameValid = null;
let bioValid = null;
let passwordValid = {
  current: false,
  new: false,
  confirm: false,
};

let currentUsername = document.getElementById("usernameInput")
  ? document.getElementById("usernameInput").value
  : "";
let currentName = document.getElementById("nameInput")
  ? document.getElementById("nameInput").value
  : "";
let currentBio = document.getElementById("bioInput")
  ? document.getElementById("bioInput").value
  : "";

function addMessage(message, type, display = "block", element = null) {
  const messageEl = element || document.querySelector(".validationMessage");
  if (messageEl) {
    messageEl.innerHTML = message;
    messageEl.style.color =
      type === "error" ? "#ef4444" : type === "success" ? "#22c55e" : "#3b82f6";
    messageEl.style.display = display;
  }
}

function checkUsername(username) {
  const messageEl = document.getElementById("usernameValidationMessage");
  const input = document.getElementById("usernameInput");
  const wrapper = input ? input.closest(".input-group") : null;

  if (!messageEl || !wrapper || !input) {
    toast("Elements are not valid", "error");
    return;
  }

  const errors = [];
  const usernameVal = username.trim();

  if (usernameCheckTimeout) {
    clearTimeout(usernameCheckTimeout);
  }

  if (!username || username.length < 4) {
    if (username === "") {
      addMessage("", "info", "none", messageEl);
      input.classList.remove("valid", "invalid");
      wrapper.classList.remove("valid", "invalid");
    } else if (username.length < 4 && username.length > 0) {
      addMessage("⏳ Type at least 4 characters", "info", "block", messageEl);
      input.classList.remove("valid");
      input.classList.add("invalid");
      wrapper.classList.remove("valid");
      wrapper.classList.add("invalid");
    }
    return;
  }

  if (!usernameVal) {
    errors.push("Username is required");
  } else if (usernameVal.length < 4) {
    errors.push("Username must be at least 4 characters");
  } else if ((usernameVal.match(/[a-zA-Z]/g) || []).length < 2) {
    errors.push("Username must contain at least 2 letters");
  } else if ((usernameVal.match(/[0-9]/g) || []).length < 1) {
    errors.push("Username must contain at least 1 number");
  } else if (!/^[a-zA-Z0-9_]+$/.test(usernameVal)) {
    errors.push(
      "Username can only contain letters, numbers, and one underscore",
    );
  } else if ((usernameVal.match(/_/g) || []).length > 1) {
    errors.push("Username can contain at most 1 underscore");
  } else if (usernameVal.length > 12) {
    errors.push("Username must be at most 12 characters");
  }

  if (errors.length > 0) {
    addMessage(
      errors.map((e) => `• ${e}`).join("<br>"),
      "error",
      "block",
      messageEl,
    );
    input.classList.remove("valid");
    input.classList.add("invalid");
    wrapper.classList.remove("valid");
    wrapper.classList.add("invalid");
    usernameValid = false;
    return;
  }

  usernameCheckTimeout = setTimeout(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = meta ? meta.content : "";

    fetch(API_URL + "/user/check_username.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        csrf_token: csrfToken,
        username: usernameVal,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success === false && data.errors) {
          addMessage(
            data.errors.map((e) => `• ${e}`).join("<br>"),
            "error",
            "block",
            messageEl,
          );
          input.classList.remove("valid");
          input.classList.add("invalid");
          wrapper.classList.remove("valid");
          wrapper.classList.add("invalid");
          usernameValid = false;
          return;
        }

        if (data.same) {
          addMessage(
            "ℹ️ This is your current username",
            "info",
            "block",
            messageEl,
          );
          input.classList.remove("valid", "invalid");
          wrapper.classList.remove("valid", "invalid");
          usernameValid = true;
        } else if (data.exist) {
          addMessage("❌ Username already taken", "error", "block", messageEl);
          input.classList.remove("valid");
          input.classList.add("invalid");
          wrapper.classList.remove("valid");
          wrapper.classList.add("invalid");
          usernameValid = false;
        } else {
          addMessage("✅ Username available", "success", "block", messageEl);
          input.classList.remove("invalid");
          input.classList.add("valid");
          wrapper.classList.remove("invalid");
          wrapper.classList.add("valid");
          usernameValid = true;
        }
      })
      .catch(() => {
        addMessage("⚠️ Error checking username", "error", "block", messageEl);
        input.classList.remove("valid", "invalid");
        wrapper.classList.remove("valid", "invalid");
        usernameValid = true;
      });
  }, 500);
}

function checkName(name) {
  const messageEl = document.getElementById("nameValidationMessage");
  const input = document.getElementById("nameInput");
  const wrapper = input ? input.closest(".input-group") : null;

  if (!messageEl || !wrapper || !input) {
    toast("Elements are not valid", "error");
    return;
  }

  const nameVal = name.trim();
  const normalized = nameVal.replace(/\s+/g, " ");
  const errors = [];

  if (normalized.length > 20) {
    errors.push("Name must be at most 20 characters");
  }

  if (nameCheckTimeout) {
    clearTimeout(nameCheckTimeout);
  }

  if (errors.length > 0) {
    addMessage(
      errors.map((e) => `• ${e}`).join("<br>"),
      "error",
      "block",
      messageEl,
    );
    input.classList.remove("valid");
    input.classList.add("invalid");
    wrapper.classList.remove("valid");
    wrapper.classList.add("invalid");
    nameValid = false;
    return;
  }

  if (!name || name.length < 2) {
    if (name === "") {
      addMessage("", "info", "none", messageEl);
      input.classList.remove("valid", "invalid");
      wrapper.classList.remove("valid", "invalid");
    } else if (name.length < 2 && name.length > 0) {
      addMessage("⏳ Type at least 2 characters", "info", "block", messageEl);
      input.classList.remove("valid");
      input.classList.add("invalid");
      wrapper.classList.remove("valid");
      wrapper.classList.add("invalid");
    }
    return;
  }

  nameCheckTimeout = setTimeout(() => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = meta ? meta.content : "";

    fetch(API_URL + "/user/check_name.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        csrf_token: csrfToken,
        name: normalized,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success === false && data.errors) {
          addMessage(
            data.errors.map((e) => `• ${e}`).join("<br>"),
            "error",
            "block",
            messageEl,
          );
          input.classList.remove("valid");
          input.classList.add("invalid");
          wrapper.classList.remove("valid");
          wrapper.classList.add("invalid");
          nameValid = false;
          return;
        }

        if (data.same) {
          addMessage(
            "ℹ️ This is your current name",
            "info",
            "block",
            messageEl,
          );
          input.classList.remove("valid", "invalid");
          wrapper.classList.remove("valid", "invalid");
          nameValid = true;
        } else {
          addMessage("✅ Name looks good", "success", "block", messageEl);
          input.classList.remove("invalid");
          input.classList.add("valid");
          wrapper.classList.remove("invalid");
          wrapper.classList.add("valid");
          nameValid = true;
        }
      })
      .catch(() => {
        addMessage("⚠️ Error checking name", "error", "block", messageEl);
        input.classList.remove("valid", "invalid");
        wrapper.classList.remove("valid", "invalid");
        nameValid = true;
      });
  }, 500);
}

function closePopup(element) {
  if (element) {
    element.style.display = "none";

    // Remove popup param from URL
    const url = new URL(window.location);
    url.searchParams.delete("popup");
    window.history.pushState({}, "", url);
  }
}

function openPopup(element) {
  if (element) {
    element.style.display = "flex";

    // Add popup param to URL
    const popupID = element.id;
    const popupName = popupID.replace("Popup", "");
    const url = new URL(window.location);
    url.searchParams.set("popup", popupName);
    window.history.pushState({}, "", url);
  }
}

function clearInput(parent) {
  if (parent) {
    const input = parent.querySelector("input");
    input.value = "";
    input.focus();
  }
}

function clearTextarea(parent) {
  if (parent) {
    const inputGroup = parent.querySelector(".input-group");
    const textarea = inputGroup.querySelector("textarea");
    textarea.value = "";
    textarea.focus();
  }
}

function handleUsernameUpdate() {
  const input = document.getElementById("usernameInput");
  const messageEl = document.getElementById("usernameValidationMessage");
  const submitBtn = document.getElementById("submitUsernameBtn");

  if (!input || !messageEl || !submitBtn) {
    toast("Elements are not valid", "error");
    return;
  }

  const username = input.value.trim();

  if (username === currentUsername) {
    toast("No changes made to the username", "info");
    return;
  }

  if (!usernameValid) {
    toast("Please fix the username errors before submitting", "error");
    messageEl.scrollIntoView({ behavior: "smooth", block: "center" });
    return;
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.value = "Updating...";
  }

  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/user/update_username.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      csrf_token: csrfToken,
      username: username,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        toast("Username updated successfully", "success");
        currentUsername = username;

        addMessage("", "info", "none", messageEl);
        input.classList.remove("valid", "invalid");

        const displayEl = document.querySelector(".user-info .username");
        if (displayEl) {
          displayEl.textContent = username;
        }

        const popup = document.getElementById("usernamePopup");
        if (popup) {
          closePopup(popup);
        }
      } else {
        if (data.errors) {
          toast(data.errors.join(" • "), "error");
        } else {
          toast(data.error || "Failed to update username", "error");
        }
      }
    })
    .catch(() => {
      toast("Error updating username", "error");
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.value = "Confirm Changes";
      }
    });
}

function handleNameUpdate() {
  const input = document.getElementById("nameInput");
  const messageEl = document.getElementById("nameValidationMessage");
  const submitBtn = document.getElementById("submitNameBtn");

  if (!input || !messageEl || !submitBtn) {
    toast("Elements are not valid", "error");
    return;
  }

  const originalText = submitBtn.value ?? "Confirm Changes";
  const name = input.value.trim();

  if (name === currentName) {
    toast("No changes made to the name", "info");
    return;
  }

  if (!nameValid) {
    toast("Please fix the name errors before submitting", "error");
    messageEl.scrollIntoView({ behavior: "smooth", block: "center" });
    return;
  }

  submitBtn.disabled = true;
  submitBtn.value = "Updating...";

  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/user/update_name.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      csrf_token: csrfToken,
      name: name,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        toast("Name updated successfully", "success");
        currentName = name;

        addMessage("", "info", "none", messageEl);
        input.classList.remove("valid", "invalid");

        const displayEl = document.querySelector(".user-info .name");
        if (displayEl) {
          displayEl.textContent = name;
        }

        const popup = document.getElementById("namePopup");
        if (popup) {
          closePopup(popup);
        }
      } else {
        if (data.errors) {
          toast(data.errors.join(" • "), "error");
        } else {
          toast(data.error || "Failed to update name", "error");
        }
      }
    })
    .catch(() => {
      toast("Error updating name", "error");
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.value = originalText;
      }
    });
}

function checkBio(bio) {
  const messageEl = document.getElementById("bioValidationMessage");
  const input = document.getElementById("bioInput");
  const wrapper = input ? input.closest(".input-group") : null;

  if (!messageEl || !wrapper || !input) {
    toast("Elements are not valid", "error");
    return;
  }

  const bioVal = bio.trim();

  if (bioCheckTimeout) {
    clearTimeout(bioCheckTimeout);
  }

  if (!bio || bio.length === 0) {
    addMessage("", "info", "none", messageEl);
    input.classList.remove("valid", "invalid");
    wrapper.classList.remove("valid", "invalid");
    bioValid = true;
    return;
  }

  const errors = [];
  if (bioVal.length > 1000) {
    errors.push("Bio must be at most 1000 characters");
  } else if (bioVal.length < 5) {
    errors.push("Bio must be at least 5 characters");
  }

  if (errors.length > 0) {
    addMessage(
      errors.map((e) => `• ${e}`).join("<br>"),
      "error",
      "block",
      messageEl,
    );
    input.classList.remove("valid");
    input.classList.add("invalid");
    wrapper.classList.remove("valid");
    wrapper.classList.add("invalid");
    bioValid = false;
    return;
  }

  addMessage("✅ Bio looks good", "success", "block", messageEl);
  input.classList.remove("invalid");
  input.classList.add("valid");
  wrapper.classList.remove("invalid");
  wrapper.classList.add("valid");
  bioValid = true;
}

function handleBioUpdate() {
  const input = document.getElementById("bioInput");
  const messageEl = document.getElementById("bioValidationMessage");
  const submitBtn = document.getElementById("submitBioBtn");

  if (!input || !messageEl || !submitBtn) {
    toast("Elements are not valid", "error");
    return;
  }

  const originalText = submitBtn.value ?? "Confirm Changes";
  const bio = input.value.trim();

  if (bio === currentBio) {
    toast("No changes made to the bio", "info");
    return;
  }

  if (!bioValid) {
    toast("Please fix the bio errors before submitting", "error");
    return;
  }

  submitBtn.disabled = true;
  submitBtn.value = "Updating...";

  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/user/update_bio.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      csrf_token: csrfToken,
      bio: bio,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        toast("Bio updated successfully", "success");
        currentBio = bio;

        addMessage("", "info", "none", messageEl);
        input.classList.remove("valid", "invalid");

        const popup = document.getElementById("bioPopup");
        if (popup) {
          closePopup(popup);
        }
      } else {
        if (data.errors) {
          toast(data.errors.join(" • "), "error");
        } else {
          toast(data.error || "Failed to update bio", "error");
        }
      }
    })
    .catch(() => {
      toast("Error updating bio", "error");
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.value = originalText;
      }
    });
}

function updateCharCount() {
  const input = document.getElementById("bioInput");
  const countEl = document.getElementById("bioCharCount");

  if (!input || !countEl) return;

  countEl.textContent = input.value.length + " / 1000";
}

function validatePasswordForm() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  const current = document.getElementById("currentPassInput");
  const newPass = document.getElementById("newPassInput");
  const confirm = document.getElementById("confirmPassInput");
  const messageEl = document.getElementById("passwordValidationMessage");

  const errors = [];

  // Current password
  if (!current.value.trim()) {
    errors.push("Current password is required");
    passwordValid.current = false;
  } else if (current.value.trim().length < 6) {
    errors.push("Current password must be at least 6 characters");
  } else {
    passwordValid.current = true;
  }

  // New password
  if (!newPass.value.trim()) {
    errors.push("New password is required");
    passwordValid.new = false;
  } else if (newPass.value.length < 6) {
    errors.push("New password must be at least 6 characters");
    passwordValid.new = false;
  } else if (newPass.value.length > 100) {
    errors.push("New password must be at most 100 characters");
    passwordValid.new = false;
  } else {
    passwordValid.new = true;
  }

  // Confirm password
  if (!confirm.value.trim()) {
    errors.push("Please confirm your new password");
    passwordValid.confirm = false;
  } else if (confirm.value !== newPass.value) {
    errors.push("Passwords do not match");
    passwordValid.confirm = false;
  } else {
    passwordValid.confirm = true;
  }

  // Display errors
  if (errors.length > 0) {
    addMessage(
      errors.map((e) => `• ${e}`).join("<br>"),
      "error",
      "block",
      messageEl,
    );

    return;
  }

  fetch(API_URL + "/user/check_password.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      csrf_token: csrfToken,
      current_password: current.value,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("API response:", data);
      if (data.success) {
        addMessage(
          "✅ All set, you're good to go.",
          "success",
          "block",
          messageEl,
        );
        passwordValid.current = true;
      } else {
        if (data.errors && data.errors.length > 0) {
          addMessage(
            data.errors.map((e) => `• ${e}`).join("<br>"),
            "error",
            "block",
            messageEl,
          );
        } else {
          addMessage(
            "❌ Current password is incorrect",
            "error",
            "block",
            messageEl,
          );
        }
        passwordValid.current = false;
      }
    })
    .catch(() => {
      addMessage("⚠️ Error checking password", "error", "block", messageEl);
      toast("Error checking password", "error");
    });
}

function isPasswordFormValid() {
  return passwordValid.current && passwordValid.new && passwordValid.confirm;
}

function togglePasswordVisibility(parent) {
  const input = parent.querySelector("input");
  const btn = parent.querySelector("button");

  if (!input || !btn) return;

  if (input.type === "password") {
    input.type = "text";
    btn.textContent = "Hide";
  } else {
    input.type = "password";
    btn.textContent = "Show";
  }
}

function handlePasswordUpdate() {
  const currentInput = document.getElementById("currentPassInput");
  const newInput = document.getElementById("newPassInput");
  const confirmInput = document.getElementById("confirmPassInput");
  const messageEl = document.getElementById("passwordValidationMessage");
  const submitBtn = document.getElementById("submitPassBtn");

  if (!currentInput || !newInput || !confirmInput || !messageEl || !submitBtn) {
    toast("Elements are not valid", "error");
    return;
  }

  const originalText = submitBtn.value ?? "Confirm Changes";

  if (!isPasswordFormValid()) {
    toast("Please fix all password errors before submitting", "error");
    return;
  }

  const current = currentInput.value;
  const newPass = newInput.value;

  submitBtn.disabled = true;
  submitBtn.value = "Updating...";

  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/user/update_password.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      csrf_token: csrfToken,
      current_password: current,
      new_password: newPass,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        toast("Password updated successfully", "success");

        // Clear fields
        currentInput.value = "";
        newInput.value = "";
        confirmInput.value = "";

        // Clear validation
        messageEl.innerHTML = "";
        messageEl.style.display = "none";
        currentInput.classList.remove("valid", "invalid");
        newInput.classList.remove("valid", "invalid");
        confirmInput.classList.remove("valid", "invalid");

        // Reset validation state
        passwordValid = {
          current: false,
          new: false,
          confirm: false,
        };

        const popup = document.getElementById("passPopup");
        if (popup) {
          closePopup(popup);
        }
      } else {
        if (data.errors) {
          toast(data.errors.join(" • "), "error");
        } else {
          toast(data.error || "Failed to update password", "error");
        }
      }
    })
    .catch(() => {
      toast("Error updating password", "error");
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.value = originalText;
      }
    });
}
