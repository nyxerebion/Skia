document.addEventListener("DOMContentLoaded", function () {
  const avatarInput = document.getElementById("avatarInput");
  const cropBtn = document.getElementById("cropBtn");
  const cropModal = document.getElementById("cropModal");
  const cropImage = document.getElementById("cropImage");
  const cancelCrop = document.getElementById("cancelCrop");
  const confirmCrop = document.getElementById("confirmCrop");
  const cropData = document.getElementById("cropData");
  const avatarForm = document.getElementById("avatarForm");

  let cropper = null;
  let currentFile = null;
  let previewUrl = null;

  if (avatarInput) {
    avatarInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (!file) return;

      if (file.size > 5 * 1024 * 1024) {
        alert("Image must be under 5MB.");
        avatarInput.value = "";
        return;
      }

      const validTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
      if (!validTypes.includes(file.type)) {
        alert("Only JPG, PNG, GIF, and WEBP images are allowed.");
        avatarInput.value = "";
        return;
      }

      currentFile = file;

      // Show preview
      previewUrl = URL.createObjectURL(file);
      const previewContainer = document.querySelector(".avatar-preview");
      if (previewContainer) {
        let previewImg = previewContainer.querySelector("img");
        if (previewImg) {
          previewImg.src = previewUrl;
        } else {
          const newImg = document.createElement("img");
          newImg.src = previewUrl;
          newImg.className = "avatar";
          previewContainer.appendChild(newImg);
        }
      }

      document.getElementById("uploadControls").classList.add("hidden");
      cropBtn.classList.add("visible");
      cropBtn.style.display = "";

      const reader = new FileReader();
      reader.onload = function (event) {
        cropImage.src = event.target.result;
        cropModal.style.display = "flex";
        cropBtn.style.display = "none";

        requestAnimationFrame(function () {
          if (cropper) cropper.destroy();
          cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: "move",
            autoCropArea: 0.8,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
          });
        });
      };
      reader.readAsDataURL(file);
    });
  }

  if (cancelCrop) {
    cancelCrop.addEventListener("click", function () {
      if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
      }
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      cropModal.style.display = "none";
      avatarInput.value = "";
      currentFile = null;
      document.getElementById("uploadControls").classList.remove("hidden");
      cropBtn.classList.remove("visible");
      cropBtn.style.display = "none";
    });
  }

  if (confirmCrop) {
    confirmCrop.addEventListener("click", function () {
      if (!cropper) return;

      const canvas = cropper.getCroppedCanvas({
        width: 300,
        height: 300,
        imageSmoothingQuality: "high",
      });

      canvas.toBlob(
        function (blob) {
          const croppedFile = new File([blob], currentFile.name, {
            type: currentFile.type,
            lastModified: Date.now(),
          });

          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(croppedFile);
          avatarInput.files = dataTransfer.files;

          if (cropper) {
            cropper.destroy();
            cropper = null;
          }
          cropModal.style.display = "none";
          cropBtn.style.display = "inline-block";
        },
        currentFile.type,
        0.9,
      );
    });
  }

  if (cropBtn) {
    cropBtn.addEventListener("click", function () {
      avatarForm.submit();
    });
  }

  if (cropModal) {
    cropModal.addEventListener("click", function (e) {
      if (e.target === cropModal) {
        cancelCrop.click();
      }
    });
  }
});