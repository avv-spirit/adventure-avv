'use strict';
const navMain = document.querySelector('.main-nav');
const navToggle = document.querySelector('.main-nav__toggle');

navMain.classList.remove('main-nav--nojs');

navToggle.addEventListener('click', function() {
  if (navMain.classList.contains('main-nav--closed')) {
    navMain.classList.remove('main-nav--closed');
    navMain.classList.add('main-nav--opened');
  } else {
    navMain.classList.add('main-nav--closed');
    navMain.classList.remove('main-nav--opened');
  }
});

const text = "Starting code...";
let delay = 90;
const elem = document.getElementById("code");

let print_text = function(text, elem, delay) {
  if(text.length > 0) {
    elem.innerHTML += text[0];
    setTimeout(
      function() {
        print_text(text.slice(1), elem, delay);
      }, delay
    );
  }
}
print_text(text, elem, delay);
setInterval(function() {
  elem.innerHTML = "";
  print_text(text, elem, delay);
}, 8 * 1000);
