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
        let likeBtn;
        if (type === "post") {
          // FIX: Added quotes around ${id}
          likeBtn = document.querySelector(
            `.like-btn[onclick*="toggleLike('${id}', 'post')"]`,
          );
          if (!likeBtn) {
            likeBtn = document.querySelector(
              `.like-btn[onclick*="toggleLike('${id}')"]`,
            );
          }
        } else {
          // FIX: Added quotes around ${id}
          likeBtn = document.querySelector(
            `.comment-like-btn[onclick*="toggleLike('${id}', 'comment')"]`,
          );
        }

        if (likeBtn) {
          if (type === "post") {
            likeBtn.innerHTML =
              (data.action === "liked" ? "❤️ Liked" : "🤍 Like") +
              " (" +
              data.count +
              ")";
            likeBtn.classList.toggle("liked", data.action === "liked");
          } else {
            likeBtn.innerHTML =
              (data.action === "liked" ? "❤️" : "🤍") + " " + data.count;
          }

          toast(data.action === "liked" ? "❤️ Liked" : "🤍 Unliked", "success");
        }
      }
    })
    .catch(() => {
      console.log("Error toggling like");
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

const rulesContent = document.getElementById("rulesContent");
let canProceed = false;

function handlePost() {
  const form = document.getElementById("createPostForm");
  const csrfTokenEl = document.getElementById("csrfToken");
  const textareaEl = document.getElementById("content");

  if (!form || !csrfTokenEl || !textareaEl) {
    toast("Form elements not found", "error");
    return;
  }

  const csrfToken = csrfTokenEl.value.trim();
  const contentVal = textareaEl.value.trim();

  if (!contentVal) {
    toast("Content cannot be empty", "error");
    return;
  }
  if (!canProceed) {
    showRules();
    return;
  }

  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn ? submitBtn.textContent : "Post";
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Posting...";
  }

  fetch(API_URL + "/create-post.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      csrf_token: csrfToken,
      content: contentVal,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        toast(data.message || "Post created!", "success");
        textareaEl.disabled = true;
        setTimeout(() => {
          const currentPage = window.location.pathname;
          const hashedId = data.hashed_id ?? "";
          location.href = `${currentPage}?scroll_to=${hashedId}`;
        }, 1500);
      } else {
        toast(data.error || "Failed to create post", "error");
      }
    })
    .catch(() => {
      console.error("Fetch error:", error);
      toast("Network error. Please try again.", "error");
    })
    .finally(() => {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    });
}

function proceedPost() {
  canProceed = true;
  handlePost();
  if (rulesContent) {
    rulesContent.style.display = "none";
  }
}

function cancelPost() {
  if (rulesContent) {
    rulesContent.style.display = "none";
  }
  const content = document.getElementById("content");
  if (content) {
    content.focus();
  }
  canProceed = false;
}

function showRules() {
  if (rulesContent) {
    rulesContent.style.display = "flex";
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("createPostForm");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      handlePost();
    });
  }

  const textarea = document.getElementById("content");
  const charCount = document.getElementById("charCount");

  if (textarea && charCount) {
    textarea.addEventListener("input", function () {
      const length = this.value.length;
      charCount.textContent = length;

      // Color feedback
      charCount.classList.remove("warning", "danger");
      if (length > 4000) {
        charCount.classList.add("warning");
      }
      if (length > 4800) {
        charCount.classList.add("danger");
      }
    });
  }
});
