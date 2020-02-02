jQuery(function () {
  $("a").on('click', function(event) {
    if (this.hash !== "") {
      event.preventDefault();
      var hash = this.hash;
      $('html').animate({
        scrollTop: $(hash).offset().top
      }, 500, function(){
        window.location.hash = hash;
      });
    }
  });
  $('.main-nav__item').on('click', function(){
    $('.main-nav').removeClass('main-nav--opened').addClass('main-nav--closed');
  });
});
