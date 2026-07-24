function toggleFollow(userId) {
  const btn = document.querySelector(`.btn-follow[data-user="${userId}"]`);

  if (!btn || btn.disabled) return;

  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = "⏳ Loading...";

  fetch(API_URL + `/follow.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ user_id: userId }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("HTPP " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        btn.textContent = data.following ? "✔️ Following" : "➕ Follow";
        btn.classList.toggle("following");
        toast(
          `${data.following ? "Followed" : "Unfollowed"} successfully`,
          "success",
        );
      } else {
        btn.textContent = originalText;
        toast(data.error || "Action failed", "error");
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      btn.textContent = originalText;
      toast("Network error", "error");
    })
    .finally(() => {
      btn.disabled = false;
    });
}
