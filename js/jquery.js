jQuery(function ($) {
  $('a').on('click', function (event) {
    const hash = this.hash;
    const isContactButton = $(this).hasClass('avv-contact__open');

    if (isContactButton) {
      return;
    }

    if (!hash || hash === '#') {
      return;
    }

    const $target = $(hash);
    if (!$target.length) {
      return;
    }

    event.preventDefault();

    $('html, body').animate({
      scrollTop: $target.offset().top
    }, 500, function () {
      window.location.hash = hash;
    });
  });

  $('.main-nav__item').on('click', function () {
    $('.main-nav').removeClass('main-nav--opened').addClass('main-nav--closed');
  });
});