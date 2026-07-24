function toggleLike(id, type = "post") {
  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.content || "";

  const like_api =
    type === "post" ? "../api/like.php" : "../api/like-comment.php";

  fetch(like_api, {
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
      if (data.success) {
        // Find button based on type and id
        let likeBtn;
        if (type === "post") {
          likeBtn = document.querySelector(
            `.like-btn[onclick*="toggleLike(${id}, 'post')"]`,
          );
          if (!likeBtn) {
            likeBtn = document.querySelector(
              `.like-btn[onclick*="toggleLike(${id})"]`,
            );
          }
        } else {
          likeBtn = document.querySelector(
            `.comment-like-btn[onclick*="toggleLike(${id}, 'comment')"]`,
          );
        }

        if (likeBtn) {
          likeBtn.innerHTML =
            (data.action === "liked" ? "❤️" : "🤍") + " " + data.count;
          likeBtn.classList.toggle("liked", data.action === "liked");
          toast(
            `${data.action === "liked" ? "❤️ Liked" : "🤍 Unliked"}`,
            "success",
          );
        }
      }
    })
    .catch(() => {
      toast("Error toggling like", "error");
    });
}

function toggleComments(postId) {
  const container = document.getElementById("comments-" + postId);
  if (container.style.display === "none") {
    container.style.display = "block";
  } else {
    container.style.display = "none";
  }
}

document.querySelectorAll("textarea").forEach((textarea) => {
  textarea.addEventListener("input", function () {
    this.style.height = "auto";
    this.style.height = this.scrollHeight + "px";
  });
});
