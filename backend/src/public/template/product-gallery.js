(function () {
  function initGallery(root) {
    var slides = root.querySelectorAll('.product-gallery__slide');
    var dots = root.querySelectorAll('.product-gallery__dot');
    var prev = root.querySelector('.product-gallery__btn--prev');
    var next = root.querySelector('.product-gallery__btn--next');
    var n = slides.length;
    if (!n) return;

    var i = 0;

    function show(idx) {
      i = (idx + n) % n;
      slides.forEach(function (el, j) {
        el.classList.toggle('is-active', j === i);
      });
      dots.forEach(function (el, j) {
        el.classList.toggle('is-active', j === i);
      });
    }

    if (prev) prev.addEventListener('click', function () { show(i - 1); });
    if (next) next.addEventListener('click', function () { show(i + 1); });
    dots.forEach(function (dot, j) {
      dot.addEventListener('click', function () { show(j); });
    });
  }

  document.querySelectorAll('[data-product-gallery]').forEach(initGallery);
})();
