(function () {
  function hidePreloaderNow() {
    var preloader = document.querySelector(".preloader");
    if (!preloader) {
      return;
    }
    preloader.style.setProperty("opacity", "0", "important");
    preloader.style.setProperty("pointer-events", "none", "important");
    preloader.style.setProperty("visibility", "hidden", "important");
    window.setTimeout(function () {
      preloader.style.setProperty("display", "none", "important");
    }, 400);
  }

  window.setTimeout(hidePreloaderNow, 900);
  window.setTimeout(hidePreloaderNow, 1800);

  var MENU_OPEN = "mobile-menu--open";
  var BODY_LOCK = "popup-open";
  var FIXED_ACTIVE = "is-active";
  var FIXED_SELECTOR = ".header--fixed";
  var SCROLL_OFFSET = 80;

  function initMobileMenu() {
    var menu = document.querySelector("._menu");
    if (!menu) {
      return;
    }

    var openButtons = document.querySelectorAll("._menuBtn");
    var closeButton = document.querySelector("._menuClose");

    function openMenu() {
      document.body.classList.add(BODY_LOCK);
      menu.classList.add(MENU_OPEN);
    }

    function closeMenu() {
      document.body.classList.remove(BODY_LOCK);
      menu.classList.remove(MENU_OPEN);
    }

    openButtons.forEach(function (button) {
      if (button.dataset.siteMenuBound === "true") {
        return;
      }
      button.dataset.siteMenuBound = "true";
      button.addEventListener("click", function (event) {
        event.preventDefault();
        openMenu();
      });
    });

    if (closeButton && closeButton.dataset.siteMenuBound !== "true") {
      closeButton.dataset.siteMenuBound = "true";
      closeButton.addEventListener("click", function (event) {
        event.preventDefault();
        closeMenu();
      });
    }

    menu.querySelectorAll(".mobile-menu__item").forEach(function (link) {
      link.addEventListener("click", closeMenu);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && menu.classList.contains(MENU_OPEN)) {
        closeMenu();
      }
    });
  }

  function initFixedHeader() {
    var fixedHeader = document.querySelector(FIXED_SELECTOR);
    if (!fixedHeader) {
      return;
    }

    function updateFixedHeader() {
      if (window.pageYOffset > SCROLL_OFFSET) {
        fixedHeader.classList.add(FIXED_ACTIVE);
      } else {
        fixedHeader.classList.remove(FIXED_ACTIVE);
      }
    }

    updateFixedHeader();
    window.addEventListener("scroll", updateFixedHeader, { passive: true });
    window.addEventListener("resize", updateFixedHeader, { passive: true });
  }

  function initPreloaderDismiss() {
    window.addEventListener("load", hidePreloaderNow);
    if (document.readyState === "complete") {
      hidePreloaderNow();
    }
  }

  function boot() {
    initMobileMenu();
    initFixedHeader();
    initPreloaderDismiss();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
