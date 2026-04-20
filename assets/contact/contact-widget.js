(function () {
  function initContactWidget() {
    const modal =
      document.querySelector('.avv-contact__modal') ||
      document.getElementById('avvContactModal');

    const openBtn =
      document.querySelector('.avv-contact__open');

    const closeBtn =
      document.querySelector('.avv-contact__close');

    const form =
      document.querySelector('.avv-contact__form') ||
      document.getElementById('avvContactForm');

    const statusEl = document.getElementById('avvContactStatus');
    const submitBtn = form ? form.querySelector('.avv-contact__submit') : null;
    const turnstileNode = document.querySelector('.cf-turnstile');
    const dialog = document.querySelector('.avv-contact__dialog');
    const siteKey = window.ADVENTURE_CONTACT_SITE_KEY || '';

    if (!modal || !openBtn) {
      console.warn('Popup init stopped: modal or open button not found.');
      return false;
    }

    if (turnstileNode && siteKey) {
      turnstileNode.setAttribute('data-sitekey', siteKey);
    }

    function setStatus(text, type) {
      if (!statusEl) return;
      statusEl.textContent = text || '';
      statusEl.classList.remove('is-error', 'is-success');
      if (type) {
        statusEl.classList.add(type);
      }
    }

    function resetTurnstile() {
      if (!window.turnstile || !turnstileNode) return;
      try {
        window.turnstile.reset(turnstileNode);
      } catch (e) {
        console.warn('Turnstile reset failed:', e);
      }
    }

    function openModal() {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('avv-modal-lock');
      setStatus('', '');
    }

    function closeModal() {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('avv-modal-lock');
      setStatus('', '');
    }

    openBtn.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      openModal();
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        closeModal();
      });
    }

    modal.addEventListener('click', function (event) {
      if (event.target === modal || event.target.closest('[data-close="true"]')) {
        closeModal();
      }
    });

    if (dialog) {
      dialog.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });

    if (!form || !submitBtn) {
      console.warn('Form controls not found. Modal open/close works, submit disabled.');
      return true;
    }

    form.addEventListener('submit', async function (event) {
      event.preventDefault();

      if (!form.reportValidity()) {
        setStatus('Please fill in all required fields.', 'is-error');
        return;
      }

      submitBtn.disabled = true;
      setStatus('Sending...', '');

      try {
        const formData = new FormData(form);

        const response = await fetch('/send.php', {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        let payload = null;

        try {
          payload = await response.json();
        } catch (e) {
          throw new Error('Invalid server response.');
        }

        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'Failed to send message.');
        }

        setStatus(payload.message || 'Message sent successfully.', 'is-success');
        form.reset();
        resetTurnstile();

        setTimeout(function () {
          closeModal();
        }, 1400);
      } catch (error) {
        setStatus(error.message || 'Error while sending message.', 'is-error');
      } finally {
        submitBtn.disabled = false;
      }
    });

    return true;
  }

  function bootContactWidget(retries) {
    if (initContactWidget()) {
      return;
    }

    if (retries > 0) {
      setTimeout(function () {
        bootContactWidget(retries - 1);
      }, 300);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      bootContactWidget(10);
    });
  } else {
    bootContactWidget(10);
  }
})();