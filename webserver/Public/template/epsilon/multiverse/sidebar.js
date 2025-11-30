document.addEventListener("DOMContentLoaded", function () {
  const overlay = document.getElementById("overlay");
  const sidebar = document.getElementById("sidebar");
  const openButton = document.getElementById("navbar_mobile_menu")?.parentElement;
  const closeButton = document.getElementById("navbar_mobile_menu_close")?.parentElement;

  // Function to open sidebar
  const openSideBar = function () {
    overlay.style.display = "block";
    sidebar.style.transform = "translateX(0)";
  };

  // Function to close sidebar
  const closeSideBar = function () {
    overlay.style.display = "none";
    sidebar.style.transform = "translateX(100%)";
  };

  // Event listener for opening sidebar
  openButton.addEventListener("click", openSideBar);

   // Event listener for closing sidebar
   //closeButton.addEventListener("click", closeSideBar);

  // Event listener for closing sidebar when overlay is clicked
  overlay.addEventListener("click", closeSideBar);
});
