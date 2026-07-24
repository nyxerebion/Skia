document.addEventListener("click", function (e) {
  const deleteBtn = e.target.closest(".delete-btn");
  if (!deleteBtn) return;

  e.preventDefault();

  if (!confirm("Delete this update?")) return;

  const id = deleteBtn.dataset.id;

  fetch("delete-update.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "id=" + id,
  })
    .then((res) => res.text())
    .then(() => location.reload())
    .catch(() => alert("Delete failed"));
});

function toggleUpdateLike(updateId) {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  fetch("api/update-like.php", {
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
            `${data.action === "liked" ? "❤️ Liked" : "🤍 Unliked"} successfully`,
            "success",
          );
        }
      }
    })
    .catch(() => {
      toast("Error toggling like", "error");
    });
}
