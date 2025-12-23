function toggleOverlay() {
  const overlay = document.getElementById("overlay");
  if (overlay.style.display === "block") {
    overlay.style.display = "none";
  } else {
    overlay.style.display = "block";
  }
}
