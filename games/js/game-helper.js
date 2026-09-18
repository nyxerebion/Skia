function toastMessage(text, type = "info") {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const msg = document.createElement("div");
  msg.textContent = text;
  msg.className = `message ${type}`;

  let timeOut = type === "win" || type === "lost" ? 3500 : 2000;
  msg.style.animation = `showThenHide ${timeOut / 1000}s ease`;

  container.appendChild(msg);
  setTimeout(() => msg.remove(), timeOut);
}

function formatTime(seconds) {
  if (!seconds || seconds < 0) return "0s";
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = Math.floor(seconds % 60);
  if (hours > 0) return hours + "h " + minutes + "m";
  if (minutes > 0) return minutes + "m " + secs + "s";
  return secs + "s";
}