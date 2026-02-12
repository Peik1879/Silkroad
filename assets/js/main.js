/* assets/js/main.js
   Client-side utilities: cookie consent, map accessibility, contact form validation.
   No tracking is loaded unless the user explicitly accepts.
*/

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    initCookieBanner();
    enhanceMapPoints();
    initContactFormValidation();
    initRegistrationForm();
    lazyImages();
    initMapPopup();
    initDetailsPopup();
  });

  // ------------------ Cookie banner ------------------
  function initCookieBanner() {
    try {
      const key = 'sr_cookie_consent';
      const consent = localStorage.getItem(key);
      if (consent === 'accepted' || consent === 'declined') return; // already decided

      // build banner dynamically (keeps HTML clean)
      const banner = document.createElement('div');
      banner.className = 'cookie-banner';
      banner.id = 'cookieBanner';
      banner.innerHTML = `
        <p>Diese Website verwendet Cookies, um die Nutzererfahrung zu verbessern. Es werden keine Tracking-Cookies ohne Ihre Einwilligung gesetzt. Mehr Informationen in unserer <a href="datenschutz.html" style="color: #ffd59a">Datenschutzerklärung</a>.</p>
        <div class="cookie-actions">
          <button class="btn secondary" id="cookieDecline">Ablehnen</button>
          <button class="btn btn-primary" id="cookieAccept">Akzeptieren</button>
        </div>`;

      document.body.appendChild(banner);

      document.getElementById('cookieAccept').addEventListener('click', () => {
        localStorage.setItem(key, 'accepted');
        banner.remove();
        // place to load analytics or other optional scripts, e.g.:
        // loadAnalytics();
      });

      document.getElementById('cookieDecline').addEventListener('click', () => {
        localStorage.setItem(key, 'declined');
        banner.remove();
      });
    } catch (e) {
      // localStorage blocked or other error, don't crash site
      console.warn('Cookie banner init failed', e);
    }
  }

  // ------------------ Map accessibility & interactivity ------------------
  function enhanceMapPoints() {
    const points = document.querySelectorAll('.map-point');
    if (!points || !points.length) return;

    const infoTag = document.getElementById('infoTag');
    const infoTitle = document.getElementById('infoTitle');
    const infoText = document.getElementById('infoText');
    const infoMeta = document.getElementById('infoMeta');

    points.forEach(point => {
      // keyboard focusable and ARIA
      point.setAttribute('tabindex', '0');
      point.setAttribute('role', 'button');
      point.setAttribute('aria-pressed', point.classList.contains('active') ? 'true' : 'false');

      point.addEventListener('click', () => {
        showStop(point);
      });

      point.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          showStop(point);
        }
      });
    });

    function showStop(point) {
      const key = point.dataset.stop; // prefer dataset
      // stops object is defined inline in index.html; check safely
      if (typeof stops === 'undefined' || !stops[key]) return;

      points.forEach(p => {
        p.classList.remove('active');
        p.setAttribute('aria-pressed', 'false');
      });
      point.classList.add('active');
      point.setAttribute('aria-pressed', 'true');

      const stop = stops[key];
      if (infoTag) infoTag.textContent = stop.tag;
      if (infoTitle) infoTitle.textContent = stop.titel;
      if (infoText) infoText.textContent = stop.text;
      if (infoMeta) infoMeta.textContent = stop.meta;
    }
  }

  // ------------------ Contact form validation ------------------
  function initContactFormValidation() {
    const form = document.getElementById('contactForm');
    if (!form) return;

    const successBox = document.getElementById('contactSuccess');
    const errorBox = document.getElementById('contactError');

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Hide previous messages
      if (successBox) successBox.style.display = 'none';
      if (errorBox) { errorBox.style.display = 'none'; errorBox.textContent = ''; }

      // Privacy check
      const privacy = form.querySelector('input[name="privacy"]');
      if (!privacy || !privacy.checked) {
        alert('Bitte bestätigen Sie die Datenschutzerklärung, bevor Sie das Formular absenden.');
        privacy?.focus();
        return;
      }

      // Collect values
      const email = form.querySelector('#email')?.value.trim();
      const name = form.querySelector('#name')?.value.trim() || '';
      const message = form.querySelector('#message')?.value.trim();

      // Validate
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email || !emailRegex.test(email)) {
        showError('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
        form.querySelector('#email')?.focus();
        return;
      }
      if (!message) {
        showError('Bitte geben Sie eine Nachricht ein.');
        form.querySelector('#message')?.focus();
        return;
      }

      // Submit to backend
      try {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn?.textContent || '';
        if (submitBtn) { submitBtn.textContent = 'Wird gesendet...'; submitBtn.disabled = true; }

        const response = await fetch('silkroad_db/contact_submit.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, name, message })
        });

        const result = await response.json().catch(() => ({ error: 'Ungültige Server-Antwort' }));
        if (!response.ok || result.error) {
          throw new Error(result.error || 'Server-Fehler beim Senden');
        }

        // Success
        if (successBox) successBox.style.display = 'block';
        form.reset();
        if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }

      } catch (err) {
        showError(err.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) { submitBtn.textContent = 'Anfrage absenden'; submitBtn.disabled = false; }
      }

      function showError(text) {
        if (errorBox) { errorBox.textContent = text; errorBox.style.display = 'block'; }
      }
    });
  }

  // ------------------ Registration form validation & submission ------------------
  function initRegistrationForm() {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    const successMsg = document.getElementById('formSuccess');
    const errorMsg = document.getElementById('formError');

    // Counter buttons
    const counterBtns = form.querySelectorAll('.counter-btn');
    counterBtns.forEach(btn => {
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const targetId = btn.dataset.target;
        const action = btn.dataset.action;
        const valueEl = document.getElementById(targetId);
        if (!valueEl) return;
        let current = parseInt(valueEl.textContent, 10) || 0;

        if (action === 'increase') {
          current += 1;
        } else if (action === 'decrease') {
          // keep adults >= 1, others >= 0
          if (targetId === 'erwachsene' && current <= 1) return;
          if (targetId !== 'erwachsene' && current <= 0) return;
          current -= 1;
        }
        valueEl.textContent = String(current);
      });
    });

    // Counter buttons functionality

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Hide previous messages
      successMsg?.classList.remove('show');
      errorMsg?.classList.remove('show');

      // Get form values
      const fullname = form.querySelector('#fullname')?.value.trim();
      const email = form.querySelector('#email')?.value.trim();
      const telefon = form.querySelector('#telefon')?.value.trim();
      const reisedatum = form.querySelector('#reisedatum')?.value;
      const erwachseneEl = document.getElementById('erwachsene');
      const kinderEl = document.getElementById('kinder');
      const kleinkinderEl = document.getElementById('kleinkinder');
      const erwachsene = erwachseneEl ? parseInt(erwachseneEl.textContent, 10) : 0;
      const kinder = kinderEl ? parseInt(kinderEl.textContent, 10) : 0;
      const kleinkinder = kleinkinderEl ? parseInt(kleinkinderEl.textContent, 10) : 0;
      const wuensche = form.querySelector('#wuensche')?.value.trim() || '';
      const personenGesamt = erwachsene + kinder + kleinkinder;

      // Validate required fields
      if (!fullname) {
        showError('Bitte geben Sie Ihren Namen ein.');
        form.querySelector('#fullname')?.focus();
        return;
      }

      if (!email) {
        showError('Bitte geben Sie Ihre E-Mail-Adresse ein.');
        form.querySelector('#email')?.focus();
        return;
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showError('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
        form.querySelector('#email')?.focus();
        return;
      }
      if (!reisedatum) {
        showError('Bitte wählen Sie ein Reisedatum aus.');
        form.querySelector('#reisedatum')?.focus();
        return;
      }

      if (erwachsene < 1) {
        showError('Es muss mindestens ein Erwachsener mitreisen.');
        erwachseneEl?.focus();
        return;
      }

      // Collect form data
      const formData = {
        fullname,
        email,
        telefon,
        reisedatum,
        reisende: {
          erwachsene,
          kinder,
          kleinkinder,
          gesamt: personenGesamt
        },
        timestamp: new Date().toISOString()
      };

      // Send to database
      try {
        const submitBtn = form.querySelector('.btn-submit');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Wird gesendet...';
        submitBtn.disabled = true;

        // Send to PHP backend - korrektes JSON
        const body = JSON.stringify({
          name: fullname,
          email: email,
          phone: telefon,
          tour: reisedatum,
          travel_date: reisedatum.split('-')[0], // Nimmt erstes Datum
          adults: erwachsene,
          children: kinder,
          toddlers: kleinkinder,
          message: wuensche
        });

        const response = await fetch('silkroad_db/submit.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: body
        });

        if (!response.ok) {
          throw new Error('Server-Fehler');
        }

        let result;
        try {
          result = await response.json();
        } catch (jsonError) {
          console.warn('JSON Parse Error:', jsonError);
          throw new Error('Ungültige Server-Antwort');
        }
        
        // Prüfe auf Fehler in der Response
        if (result.error) {
          throw new Error(result.error);
        }
        
        console.log('Erfolgreich gespeichert:', result);
        console.log('successMsg element:', successMsg); // Debug

        // Show success message
        showSuccess();

        // Reset form
        form.reset();
        if (erwachseneEl) erwachseneEl.textContent = '1';
        if (kinderEl) kinderEl.textContent = '0';
        if (kleinkinderEl) kleinkinderEl.textContent = '0';

        // Restore button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;

        // Scroll to success message
        successMsg?.scrollIntoView({ behavior: 'smooth', block: 'center' });

      } catch (error) {
        console.error('Fehler beim Absenden:', error);
        showError(error.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.');
        
        const submitBtn = form.querySelector('.btn-submit');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Jetzt anmelden';
      }
    });

    function showSuccess() {
      if (successMsg) {
        successMsg.classList.add('show');
        
        // Verstecke nur die echten Form-Inhalte mit visibility (nimmt Platz weg aber Formular bleibt strukturiert)
        const formGroups = form.querySelectorAll('.form-group, .form-actions');
        formGroups.forEach(group => {
          group.style.visibility = 'hidden';
          group.style.height = '0';
          group.style.margin = '0';
          group.style.padding = '0';
          group.style.overflow = 'hidden';
        });
        
        // Verstecke auch den "Jetzt anmelden" Header
        const header = document.querySelector('.registration-header');
        if (header) {
          header.style.visibility = 'hidden';
          header.style.height = '0';
          header.style.margin = '0';
          header.style.padding = '0';
          header.style.overflow = 'hidden';
        }
        
        // Stelle sicher, dass Success-Nachricht sichtbar ist
        successMsg.style.display = 'block';
        successMsg.style.visibility = 'visible';
      }
    }

    function showError(message) {
      if (errorMsg) {
        const errorText = errorMsg.querySelector('p');
        if (errorText) errorText.textContent = message;
        errorMsg.classList.add('show');
        
        // Stelle sicher, dass Formular sichtbar bleibt - KEINE Versteckungen!
        // (Der Error wird oben angezeigt, aber Formular bleibt normal)
        
        // Fehlermeldung bleibt sichtbar (kein Auto-Hide)
        // User kann dann erneut absenden oder Formular korrigieren
      }
    }
  }

  // ------------------ Lazy images helper ------------------
  function lazyImages() {
    const imgs = document.querySelectorAll('img[data-lazy]');
    imgs.forEach(img => {
      img.setAttribute('loading', 'lazy');
      const src = img.dataset.src;
      if (src) img.src = src;
      img.removeAttribute('data-lazy');
    });
  }

  // ------------------ Map Popup ------------------
  function initMapPopup() {
    const startButton = document.getElementById('startJourney');
    const mapPopup = document.getElementById('mapPopup');
    const closeButton = document.getElementById('closeMapPopup');

    if (!startButton || !mapPopup || !closeButton) return;

    // Open popup when "Reise beginnen" is clicked
    startButton.addEventListener('click', () => {
      mapPopup.style.display = 'flex';
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    });

    // Close popup when X is clicked
    closeButton.addEventListener('click', () => {
      mapPopup.style.display = 'none';
      document.body.style.overflow = ''; // Restore scrolling
    });

    // Close popup when clicking outside the map
    mapPopup.addEventListener('click', (e) => {
      if (e.target === mapPopup) {
        mapPopup.style.display = 'none';
        document.body.style.overflow = '';
      }
    });

    // Close popup with Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && mapPopup.style.display === 'flex') {
        mapPopup.style.display = 'none';
        document.body.style.overflow = '';
      }
    });
  }

  // ------------------ Details Popup for Stickers ------------------
  function initDetailsPopup() {
    const stickers = document.querySelectorAll('.map-pin.sticker');
    const detailsPopup = document.getElementById('detailsPopup');
    const closeButton = document.getElementById('closeDetailsPopup');
    
    if (!detailsPopup || !closeButton) return;

    // Add click handlers to all stickers
    stickers.forEach(sticker => {
      sticker.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent closing map popup
        const stopId = sticker.dataset.stop;
        showDetailsForStop(stopId);
      });
    });

    // Close details popup
    closeButton.addEventListener('click', () => {
      detailsPopup.style.display = 'none';
    });

    // Close when clicking outside
    detailsPopup.addEventListener('click', (e) => {
      if (e.target === detailsPopup) {
        detailsPopup.style.display = 'none';
      }
    });

    // Close with Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && detailsPopup.style.display === 'flex') {
        detailsPopup.style.display = 'none';
      }
    });

    function showDetailsForStop(stopId) {
      // Find the corresponding itinerary day
      const dayElement = document.querySelector(`.itinerary-day[data-stop="${stopId}"]`);
      if (!dayElement) return;

      const dayContent = dayElement.querySelector('.day-content');
      const dayText = dayContent.querySelector('.day-text');
      
      // Extract data
      const tag = dayText.querySelector('h3').textContent;
      const title = dayText.querySelector('h2').textContent;
      const description = dayText.querySelector('p').textContent;
      const program = dayText.querySelector('div')?.innerHTML || '';
      
      // Get all images
      const images = dayContent.querySelectorAll('.slide-image');
      
      // Populate popup
      document.getElementById('detailsTag').textContent = tag;
      document.getElementById('detailsTitle').textContent = title;
      document.getElementById('detailsDescription').textContent = description;
      document.getElementById('detailsProgram').innerHTML = program;
      
      // Create slideshow
      const slideshowWrapper = document.getElementById('detailsSlideshow');
      slideshowWrapper.innerHTML = '';
      
      images.forEach((img, index) => {
        const newImg = document.createElement('img');
        newImg.src = img.src;
        newImg.alt = img.alt;
        newImg.className = 'slide-image' + (index === 0 ? ' active' : '');
        slideshowWrapper.appendChild(newImg);
      });
      
      // Initialize slideshow controls
      initDetailsSlideshow();
      
      // Show popup
      detailsPopup.style.display = 'flex';
    }

    function initDetailsSlideshow() {
      const wrapper = document.getElementById('detailsSlideshow');
      const slides = wrapper.querySelectorAll('.slide-image');
      const prevBtn = document.getElementById('detailsPrev');
      const nextBtn = document.getElementById('detailsNext');
      
      if (slides.length === 0) return;
      
      let currentIndex = 0;
      
      function showSlide(index) {
        slides.forEach((slide, i) => {
          slide.classList.toggle('active', i === index);
        });
      }
      
      prevBtn.onclick = () => {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(currentIndex);
      };
      
      nextBtn.onclick = () => {
        currentIndex = (currentIndex + 1) % slides.length;
        showSlide(currentIndex);
      };
    }
  }

})();
