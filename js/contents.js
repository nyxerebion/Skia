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
