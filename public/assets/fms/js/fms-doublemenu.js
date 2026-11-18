/**
 * Double-menu (icon-only sidebar) hover behavior.
 * - Shows a tooltip with the menu name when hovering a main menu icon.
 * - Opens the parent's second panel when hovering a parent menu icon.
 *
 * Loads after defaultmenu.min.js (see FMSController::fmsInitializeBackend)
 * and only activates for [data-vertical-style=doublemenu] on desktop.
 */
(function () {
  "use strict";

  var MIN_WIDTH = 992;

  function isDoublemenu() {
    var html = document.documentElement;
    return html.getAttribute("data-vertical-style") === "doublemenu" &&
      html.getAttribute("data-nav-layout") === "vertical" &&
      window.innerWidth >= MIN_WIDTH;
  }

  function closeAllPanels() {
    var html = document.documentElement;
    document.querySelectorAll(".slide-menu.double-menu-active").forEach(function (el) {
      el.classList.remove("double-menu-active");
    });
    html.setAttribute("data-toggled", "double-menu-close");
  }

  function openPanel(link) {
    var html = document.documentElement;
    var panel = link.nextElementSibling;
    if (!panel || !panel.classList.contains("slide-menu")) return;

    // Only one second-level panel at a time
    document.querySelectorAll(".slide-menu.double-menu-active").forEach(function (el) {
      if (el !== panel) el.classList.remove("double-menu-active");
    });

    panel.classList.add("double-menu-active");
    html.setAttribute("data-toggled", "double-menu-open");
  }

  /* ---------- tooltip ---------- */

  var tooltip = null;

  function ensureTooltip() {
    if (tooltip) return tooltip;
    tooltip = document.createElement("div");
    tooltip.className = "custome-tooltip fms-doublemenu-tooltip";
    tooltip.style.setProperty("position", "fixed");
    tooltip.style.setProperty("display", "none");
    tooltip.style.setProperty("padding", "0.5rem");
    tooltip.style.setProperty("font-weight", "500");
    tooltip.style.setProperty("font-size", "0.75rem");
    tooltip.style.setProperty("background-color", "rgb(15, 23, 42)");
    tooltip.style.setProperty("color", "rgb(255, 255, 255)");
    tooltip.style.setProperty("white-space", "nowrap");
    tooltip.style.setProperty("border-radius", "0.25rem");
    tooltip.style.setProperty("z-index", "99");
    tooltip.style.setProperty("pointer-events", "none");
    document.body.appendChild(tooltip);
    return tooltip;
  }

  function showTooltip(link) {
    if (!isDoublemenu()) return;
    var label = link.querySelector(".side-menu__label");
    if (!label) return;
    var tip = ensureTooltip();
    tip.textContent = label.textContent.trim();
    tip.style.setProperty("display", "block");
    var rect = link.getBoundingClientRect();
    var top = rect.top + rect.height / 2 - tip.offsetHeight / 2;
    tip.style.setProperty("top", Math.max(4, top) + "px");
    tip.style.setProperty("inset-inline-start", (rect.right + 8) + "px");
    tip.style.setProperty("left", (rect.right + 8) + "px");
  }

  function hideTooltip() {
    if (tooltip) tooltip.style.setProperty("display", "none");
  }

  /* ---------- wiring ---------- */

  function bind() {
    // Main menu only; skip the fixed bottom menu (theme/logout/avatar)
    var links = document.querySelectorAll(
      ".app-sidebar .main-menu:not(.doublemenu_bottom-menu) > li > .side-menu__item"
    );

    links.forEach(function (link) {
      if (link.dataset.fmsDoublemenuBound) return;
      link.dataset.fmsDoublemenuBound = "1";

      link.addEventListener("mouseenter", function () {
        if (!isDoublemenu()) return;
        if (link.closest("li.has-sub")) {
          // Parent menu: the opened panel already shows the name in its
          // header (side-menu__label1), so open the panel without a tooltip.
          hideTooltip();
          openPanel(link);
        } else {
          // Leaf menu: no panel to show, so close any open panel.
          showTooltip(link);
          closeAllPanels();
        }
      });

      link.addEventListener("mouseleave", hideTooltip);
    });

    // Hovering inside the opened panel must not leave a stale tooltip
    document.querySelectorAll(".app-sidebar .slide-menu").forEach(function (panel) {
      if (panel.dataset.fmsDoublemenuBound) return;
      panel.dataset.fmsDoublemenuBound = "1";
      panel.addEventListener("mouseenter", hideTooltip);
    });
  }

  function init() {
    bind();
    // Menu may be re-rendered/late-bound; re-check shortly after load
    setTimeout(bind, 800);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
