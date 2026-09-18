function switchTab(tabId) {
  document
    .querySelectorAll(".tab-content")
    .forEach((el) => el.classList.remove("active"));
  document
    .querySelectorAll(".tab-btn")
    .forEach((el) => el.classList.remove("active"));

  document.getElementById(tabId).classList.add("active");
  document
    .querySelector(`.tab-btn[data-tab="${tabId}"]`)
    .classList.add("active");
}

function switchSubTab(subTabId) {
  document
    .querySelectorAll(".sub-tab-content")
    .forEach((el) => el.classList.remove("active"));
  document
    .querySelectorAll(".sub-tab-btn")
    .forEach((el) => el.classList.remove("active"));

  document.getElementById(subTabId).classList.add("active");
  document
    .querySelector(`.sub-tab-btn[data-tab="${subTabId}"]`)
    .classList.add("active");
}

function toggleUpdateLike(updateId) {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch(API_URL + "/update-like.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      update_id: updateId,
      csrf_token: csrfToken,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const countSpan = document.getElementById(
          "update-like-count-" + updateId,
        );
        if (countSpan) countSpan.textContent = data.count;

        const btn = document.querySelector(
          `.like-btn[onclick="toggleUpdateLike(${updateId})"]`,
        );
        if (btn) {
          btn.innerHTML =
            (data.action === "liked" ? "❤️" : "🤍") + " " + data.count;
          btn.classList.toggle("liked", data.action === "liked");
          toast(
            `${data.action === "liked" ? "❤️ Liked" : "🤍 Unliked"}`,
            "success",
          );
        }
      }
    })
    .catch(() => {
      alert("Error toggling like");
    });
}

function showBioModal(element) {
    // Check if text is actually truncated
    if (element.scrollWidth <= element.clientWidth) {
        return; // No ellipsis, don't open modal
    }

    const bio = element.textContent.trim();
    if (!bio) return;

    const overlay = document.createElement("div");
    overlay.className = "bio-modal-overlay";
    overlay.onclick = (e) => {
        if (e.target === overlay) overlay.remove();
    };

    overlay.innerHTML = `
        <div class="bio-modal">
            <button class="close-modal" onclick="this.closest('.bio-modal-overlay').remove()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
            <h3>Bio</h3>
            <p>${escapeHtml(bio)}</p>
        </div>
    `;

    document.body.appendChild(overlay);
}

// Close on Escape key
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        const overlay = document.querySelector(".bio-modal-overlay");
        if (overlay) overlay.remove();
    }
});