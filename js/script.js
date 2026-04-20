'use strict';

const navMain = document.querySelector('.main-nav');
const navToggle = document.querySelector('.main-nav__toggle');

if (navMain) {
  navMain.classList.remove('main-nav--nojs');
}

if (navMain && navToggle) {
  navToggle.addEventListener('click', function () {
    if (navMain.classList.contains('main-nav--closed')) {
      navMain.classList.remove('main-nav--closed');
      navMain.classList.add('main-nav--opened');
    } else {
      navMain.classList.add('main-nav--closed');
      navMain.classList.remove('main-nav--opened');
    }
  });
}

const text = 'Starting code...';
const delay = 90;
const elem = document.getElementById('code');

function printText(textValue, element, delayValue) {
  if (!element) {
    return;
  }

  if (textValue.length > 0) {
    element.innerHTML += textValue[0];
    setTimeout(function () {
      printText(textValue.slice(1), element, delayValue);
    }, delayValue);
  }
}

if (elem) {
  printText(text, elem, delay);

  setInterval(function () {
    elem.innerHTML = '';
    printText(text, elem, delay);
  }, 8000);
}