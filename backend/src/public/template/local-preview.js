(function () {
  if (window.location.protocol !== "file:") {
    return;
  }

  window.BX = window.BX || {};
  if (typeof window.BX.ajax !== "function") {
    window.BX.ajax = function () {
      return { then: function () { return this; }, catch: function () { return this; } };
    };
  }
  if (typeof window.BX.ajax.runAction !== "function") {
    window.BX.ajax.runAction = function () {
      return Promise.resolve({ data: {} });
    };
  }

  document.querySelectorAll('script[src*="ba.js"], script[src*="tag.js"]').forEach(function (node) {
    node.remove();
  });
})();
