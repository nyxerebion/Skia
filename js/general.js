let minimized = false;
let isPhone = window.matchMedia("(max-width: 576px)");

let sidebar, sidebar_title, header_right, toggleBtn, settingsMenu;

const isLocalhost =
  window.location.hostname === "localhost" ||
  window.location.hostname === "127.0.0.1";

const SITE_URL = isLocalhost
  ? "http://localhost/skia"
  : "https://skia.unaux.com";

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
  getOnlineUsers();
  getUserStatuses();

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
  console.log("markRead called:", { id, link });

  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  // If link is empty or null, still mark as read then reload
  const hasLink =
    link && link !== "null" && link !== "" && link !== "undefined";

  // Clean the link if it exists
  if (hasLink) {
    link = link.trim();
    if (
      !link.startsWith("http://") &&
      !link.startsWith("https://") &&
      !link.startsWith("/")
    ) {
      link = window.location.origin + "/" + link.replace(/^\/+/, "");
    }
  }

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
      console.log("markRead response:", data);

      if (data.success) {
        // Update UI - remove unread class
        const item = document.querySelector(
          `.notification-item[data-id="${id}"]`,
        );
        if (item) {
          item.classList.remove("unread");
        }

        // Update badge count
        const badge = document.querySelector(".notification-badge");
        if (badge) {
          const count = parseInt(badge.textContent) - 1;
          if (count > 0) {
            badge.textContent = count;
          } else {
            badge.remove();
          }
        }

        // Redirect or reload
        if (hasLink) {
          console.log("Replacing with:", link);
          window.location.replace(link);
        } else {
          console.log("No link, reloading");
          location.reload();
        }
      } else {
        // If mark read fails
        if (hasLink) {
          window.location.replace(link);
        } else {
          location.reload();
        }
      }
    })
    .catch((error) => {
      console.error("Error marking as read:", error);
      if (hasLink) {
        window.location.replace(link);
      } else {
        location.reload();
      }
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

document.addEventListener("click", (e) => {
  const onlineUsersView = document.querySelector(".online-users-view");
  const trigger = document.querySelector(".view-online-users");
  if (!onlineUsersView) return;

  if (
    !onlineUsersView.contains(e.target) &&
    (!trigger || !trigger.contains(e.target))
  ) {
    onlineUsersView.style.display = "none";
  }
});

function toast(message, type = "info") {
  // Remove existing toast
  const existing = document.querySelector(".toast-container");
  if (existing) existing.remove();

  const container = document.createElement("div");
  container.className = "toast-container";

  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = message;

  container.appendChild(toast);
  document.body.appendChild(container);

  // Auto-remove after 3 seconds
  setTimeout(() => {
    toast.classList.add("toast-fade-out");
    setTimeout(() => container.remove(), 300);
  }, 3000);
}

function formatTime(seconds) {
  if (seconds < 60) return seconds + "s";

  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  if (hours > 0) {
    return hours + "h " + minutes + "m";
  } else if (minutes > 0) {
    return minutes + "m " + secs + "s";
  }
  return seconds + "s";
}

document.addEventListener("DOMContentLoaded", function () {
  const typing = document.querySelector(".typing");
  if (typing) {
    const typingDuration = 5000;

    setTimeout(() => {
      typing.classList.remove("typing");
    }, typingDuration);
  }

  // Event delegation for notification items
  document.addEventListener("click", function (e) {
    const item = e.target.closest(".notification-item");
    if (item) {
      const id = item.dataset.id;
      const link = item.dataset.link || "";
      if (id) {
        markRead(id, link);
      }
    }
  });
});

function getOnlineUsers() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/get-online-users.php", {
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
      if (data.success) {
        // Update count
        const onlineUsersElement = document.querySelector(".online-users");
        if (onlineUsersElement) {
          updateOnlineCountDisplay(data.online_users_count);

          const onlineUsersCount = document.getElementById("onlineUsersCount");
          if (onlineUsersCount)
            onlineUsersCount.textContent = `(${data.online_users_count})`;
        }

        // Update user list
        updateOnlineUsersDisplay(data.online_users);
      }
    })
    .catch((error) => {
      console.error("Error fetching online users:", error);
    });
}

function updateOnlineCountDisplay(data) {
  const onlineUsersElement = document.querySelector(".online-users");
  if (!onlineUsersElement) return;

  const newCount = data ?? "0";

  // Only animate if count changed
  if (onlineUsersElement.textContent !== String(newCount)) {
    onlineUsersElement.classList.remove("pop");
    // Trigger reflow
    void onlineUsersElement.offsetWidth;
    onlineUsersElement.textContent = newCount;
    onlineUsersElement.classList.add("pop");
  } else {
    onlineUsersElement.textContent = newCount;
  }
}

setInterval(
  getOnlineUsers,
  10000, // Update every 10 seconds
);

function getUserStatuses() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const csrfToken = meta ? meta.content : "";

  fetch(API_URL + "/get-user-status.php", {
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
      if (data.success) {
        data.users.forEach((user) => {
          updateUserStatus(user.id, user.status);
        });
      }
    })
    .catch((error) => {
      console.error("Error fetching user statuses:", error);
    });
}

function updateUserStatus(userId, status) {
  document.querySelectorAll(`.status[data-user="${userId}"]`).forEach((dot) => {
    dot.className = "status";
    dot.classList.add(`status-${status}`);
  });
}

setInterval(getUserStatuses, 10000); // Update every 10 seconds

function updateOnlineUsersDisplay(users) {
  const pathname = window.location.pathname;

  const onlineUsersList = document.querySelector(".online-users-list");
  if (!onlineUsersList) return;

  onlineUsersList.innerHTML = "";

  if (!users || users.length === 0) {
    onlineUsersList.innerHTML = '<span class="no-users">No one online</span>';
    return;
  }

  users.forEach((user) => {
    const item = document.createElement("div");
    item.className = "online-user-item";

    let avatarHtml;
    if (user.avatar_url) {
      avatarHtml = `<img src="${user.avatar_url}" alt="${user.username}" class="avatar-img">`;
    } else {
      avatarHtml = `<div class="avatar-placeholder">${user.username.charAt(0).toUpperCase()}</div>`;
    }

    const isSelf = user.is_self || false;
    const isFollowing = user.is_following || false;
    const followText = isFollowing ? "Unfollow" : "Follow";
    const followClass = isFollowing ? "following" : "";

    // Build controls HTML
    let controlsHtml;
    if (isSelf) {
      controlsHtml = `<span class="self-label">You</span>`;
    } else {
      controlsHtml = `
                <button class="follow-btn ${followClass}" 
                        data-user="${user.id}" 
                        onclick="event.stopPropagation(); event.preventDefault(); toggleFollow(${user.id});">
                    ${followText}
                </button>
            `;
    }

    item.innerHTML = `
            <div class="online-user-content" onclick="redirectToProfile(event, '${user.hashed_id}')">
                <div class="wrapper-left">
                    <div class="user-info">
                        <div class="avatar-wrapper">
                            <span class="avatar-container avatar-sm">
                                ${avatarHtml}
                                <div class="status status-online" data-user="${user.id}"></div>
                            </span>
                        </div>

                        <div class="user-details">
                            <div class="main-details">
                                <span class="username">${user.username}</span>
                                <span class="role-badge ${user.role}">${user.role}</span>
                            </div>
                            <div class="follow-stats">
                                <span class="followers">${user.followers || 0} Followers</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wrapper-right">
                    ${!pathname.includes("guest-page.php") ? `<div class="user-controls">${controlsHtml}</div>` : ""}
                </div>
            </div>
        `;
    onlineUsersList.appendChild(item);
  });
}

function redirectToProfile(event, profileId) {
  if (event.target.closest(".follow-btn")) return;
  location.href = `${SITE_URL}/pages/view-profile.php?id=${profileId}`;
}

function viewOnlineUsers() {
  const onlineUsersView = document.querySelector(".online-users-view");
  if (!onlineUsersView) return;

  // Toggle visibility
  if (
    onlineUsersView.style.display === "none" ||
    onlineUsersView.style.display === ""
  ) {
    onlineUsersView.style.display = "block";
  } else {
    onlineUsersView.style.display = "none";
  }
}

function closeOnlineUsers() {
  const onlineUsersView = document.querySelector(".online-users-view");
  if (onlineUsersView) onlineUsersView.style.display = "none";
}

function toggleFollow(userId) {
  const btns = document.querySelectorAll(`.follow-btn[data-user="${userId}"]`);

  if (!btns.length) return;

  // Store original texts for all buttons
  const originalTexts = [];
  btns.forEach((btn, index) => {
    originalTexts[index] = btn.textContent;
    btn.disabled = true;
    btn.textContent = "⏳";
  });

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/follow.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      user_id: userId,
      csrf_token: csrfToken,
    }),
  })
    .then((response) => {
      if (!response.ok) throw new Error("HTTP " + response.status);
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        // Update all buttons
        btns.forEach((btn, index) => {
          btn.textContent = data.following ? "Unfollow" : "Follow";
          if (data.following) {
            btn.classList.add("following");
          } else {
            btn.classList.remove("following");
          }
          btn.disabled = false;
        });

        toast(
          data.following ? "Followed successfully" : "Unfollowed successfully",
          "success",
        );

        if (typeof getOnlineUsers === "function") {
          getOnlineUsers();
        }
      } else {
        // Restore all buttons
        btns.forEach((btn, index) => {
          btn.textContent = originalTexts[index];
          btn.disabled = false;
        });
        toast(data.error || "Action failed", "error");
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      btns.forEach((btn, index) => {
        btn.textContent = originalTexts[index];
        btn.disabled = false;
      });
      toast("Network error", "error");
    });
}

function autoResize(textarea) {
  textarea.style.height = "auto";
  textarea.style.height = textarea.scrollHeight + "px";
}

function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}