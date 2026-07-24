let minimized = false;
let isPhone = window.matchMedia("(max-width: 576px)");

let sidebar, sidebar_title, header_right, toggleBtn, settingsMenu;

function initializeElements() {
  sidebar = document.querySelector("aside");
  sidebar_title = document.querySelector(".sidebar-title");
  header_right = document.querySelector(".header-right");
  toggleBtn = document.getElementById("toggleBtn");
  settingsMenu = document.getElementById("settingsMenu");
}

document.addEventListener("DOMContentLoaded", () => {
  initializeElements();
  handleMessage();
  loadTheme();

  if (!sidebar) return;
  createSidebarButton();

  if (isPhone.matches) {
    minimized = true;
    sidebar.classList.add("minimized");
    updateSidebar();
    checkLogout();
  } else {
    updateSidebar();
    checkLogout();
  }
});

function createSidebarButton() {
  if (!sidebar) return;

  const oldBtn = document.getElementById("toggleBtn");
  if (oldBtn) oldBtn.remove();

  const sidebar_svg =
    '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu-icon lucide-menu"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>';

  const showSpan = !isPhone.matches;
  const html = `<button id="toggleBtn">${sidebar_svg}${showSpan ? "<span class='toggleBtn-title'>Sidebar</span>" : ""}</button>`;

  const container = isPhone.matches ? header_right : sidebar;

  if (container) {
    container.insertAdjacentHTML("afterbegin", html);
  }

  toggleBtn = document.getElementById("toggleBtn");

  if (toggleBtn) {
    toggleBtn.addEventListener("click", handleToggle);
  }
}

function updateTitle() {
  if (!sidebar_title) return;

  if (isPhone.matches && !minimized) {
    sidebar_title.textContent = "Sidebar";
  } else {
    sidebar_title.textContent = "";
  }
}

function updateSidebar() {
  const toggleSpan = document.querySelector(".toggleBtn-title");

  if (isPhone.matches) {
    sidebar.classList.toggle("hidden", minimized);
    if (toggleSpan) {
      toggleSpan.style.display = "none";
    }
  } else {
    sidebar.classList.remove("hidden");
    if (toggleBtn) toggleBtn.style.width = "100%";
    if (toggleSpan) {
      toggleSpan.style.display = minimized ? "none" : "inline";
    }
  }

  updateTitle();
}

function handleToggle() {
  minimized = !minimized;
  sidebar.classList.toggle("minimized", minimized);
  updateSidebar();
}

isPhone.addEventListener("change", () => {
  if (!sidebar) return;

  const oldBtn = document.getElementById("toggleBtn");
  if (oldBtn) oldBtn.remove();

  createSidebarButton();

  minimized = isPhone.matches ? true : false;
  sidebar.classList.toggle("minimized", minimized);
  updateSidebar();
  checkLogout();
});

function checkLogout() {
  const responsiveBtn = document.getElementById("responsiveBtn");
  const normalBtn = document.querySelector(".logout-btn");

  if (isPhone.matches) {
    if (responsiveBtn) responsiveBtn.style.display = "flex";
    if (normalBtn) normalBtn.style.display = "none";
  } else {
    if (responsiveBtn) responsiveBtn.style.display = "none";
    if (normalBtn) normalBtn.style.display = "flex";
  }
}

function toggleSettings() {
  const settingsMenu = document.getElementById("settingsMenu");
  if (!settingsMenu) return;

  settingsMenu.style.display =
    settingsMenu.style.display === "flex" ? "none" : "flex";
}

function handleClose() {
  if (!settingsMenu) return;
  settingsMenu.style.display = "none";
}

function toggleTheme() {
  const html = document.documentElement;
  const currentTheme = html.getAttribute("data-theme");
  const newTheme = currentTheme === "dark" ? "light" : "dark";

  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);

  const checkbox = document.getElementById("darkMode");
  if (checkbox) checkbox.checked = newTheme === "dark";
}

function loadTheme() {
  const savedTheme = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", savedTheme);

  const checkbox = document.getElementById("darkMode");
  if (checkbox) checkbox.checked = savedTheme === "dark";
}

function handleMessage() {
  const flashMessage = document.querySelector(".flash-message");
  const errorMessage = document.querySelector(".error-message");
  const successMessage = document.querySelector(".success-message");
  const updateMessage = errorMessage || successMessage;

  if (!flashMessage && !updateMessage) return;

  setTimeout(() => {
    if (flashMessage) flashMessage.style.display = "none";
    if (updateMessage) updateMessage.style.display = "none";
  }, 5000);
}

function toggleNotifications() {
  if (settingsMenu) settingsMenu.style.display = "none";

  const dropdown = document.getElementById("notificationDropdown");
  if (dropdown) dropdown.classList.toggle("show");
}

const API_URL = window.location.pathname.includes("/skia")
  ? "/skia/api"
  : "/api";

function markRead(id, link) {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/mark-notification-read.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      id: id,
      csrf_token: csrfToken,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && link) {
        window.location.href = link;
      } else {
        location.reload();
      }
    })
    .catch(() => {
      location.reload();
    });
}

function markAllRead() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/mark-all-read.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      csrf_token: csrfToken,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Mark all read response:", data); // ← Check this
      if (data.success) {
        location.reload();
      } else {
        console.error("Failed:", data.error);
        alert("Failed to mark all as read");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Network error");
    });
}

// Close dropdown when clicking outside
document.addEventListener("click", (e) => {
  const wrapper = document.querySelector(".notification-wrapper");
  const dropdown = document.getElementById("notificationDropdown");

  if (wrapper && dropdown && !wrapper.contains(e.target)) {
    dropdown.classList.remove("show");
  }
});

function toast(message, type = 'info') {
    // Remove existing toast
    const existing = document.querySelector('.toast-container');
    if (existing) existing.remove();

    const container = document.createElement('div');
    container.className = 'toast-container';
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    container.appendChild(toast);
    document.body.appendChild(container);

    // Auto-remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('toast-fade-out');
        setTimeout(() => container.remove(), 300);
    }, 3000);
}

function formatTime(seconds) {
    if (seconds < 60) return seconds + 's';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return hours + 'h ' + minutes + 'm';
    } else if (minutes > 0) {
        return minutes + 'm ' + secs + 's';
    }
    return seconds + 's';
}