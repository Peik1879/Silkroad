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
    prefillDateFromURL();
    initUsbekistanHeroVideo();
    loadAvailableTours(); // Load tours from database
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

  // ------------------ Usbekistan hero video ------------------
  function initUsbekistanHeroVideo() {
    const video = document.getElementById('heroVideo');
    if (!video) return;
    const heroSub = document.getElementById('heroSub');

    function showHeroSub() {
      if (!heroSub) return;
      heroSub.classList.remove('is-hidden');
      heroSub.classList.add('is-visible');
    }

    video.classList.add('is-active');
    video.currentTime = 0;
    const playPromise = video.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {
        // Autoplay might be blocked; still show the poster briefly.
        showHeroSub();
      });
    }

    video.addEventListener('ended', showHeroSub);

    window.setTimeout(() => {
      video.classList.add('is-hidden');
      video.pause();
      showHeroSub();
    }, 6000);
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

      const abflughafen = form.querySelector('#abflughafen')?.value;
      if (!abflughafen) {
        showError('Bitte wählen Sie einen Abflughafen aus.');
        form.querySelector('#abflughafen')?.focus();
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
          abflughafen: abflughafen,
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
    const prevNavBtn = document.getElementById('detailsNavPrev');
    const nextNavBtn = document.getElementById('detailsNavNext');
    
    if (!detailsPopup || !closeButton) return;

    let currentStopId = null;
    const stopIds = Array.from(stickers).map(s => s.dataset.stop);

    // Add click handlers to all stickers
    stickers.forEach(sticker => {
      sticker.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent closing map popup
        const stopId = sticker.dataset.stop;
        currentStopId = stopId;
        showDetailsForStop(stopId);
        updateNavButtons();
      });
    });

    // Close details popup
    closeButton.addEventListener('click', () => {
      detailsPopup.style.display = 'none';
    });

    // Navigation buttons
    if (prevNavBtn) {
      prevNavBtn.addEventListener('click', () => {
        const currentIndex = stopIds.indexOf(currentStopId);
        if (currentIndex > 0) {
          currentStopId = stopIds[currentIndex - 1];
          showDetailsForStop(currentStopId);
          updateNavButtons();
        }
      });
    }

    if (nextNavBtn) {
      nextNavBtn.addEventListener('click', () => {
        const currentIndex = stopIds.indexOf(currentStopId);
        if (currentIndex < stopIds.length - 1) {
          currentStopId = stopIds[currentIndex + 1];
          showDetailsForStop(currentStopId);
          updateNavButtons();
        }
      });
    }

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

    function updateNavButtons() {
      if (!prevNavBtn || !nextNavBtn) return;
      const currentIndex = stopIds.indexOf(currentStopId);
      
      // Update prev button
      if (currentIndex > 0) {
        prevNavBtn.style.opacity = '1';
        prevNavBtn.style.pointerEvents = 'auto';
        const prevStopId = stopIds[currentIndex - 1];
        const prevDayElement = document.querySelector(`.accordion-item[data-stop="${prevStopId}"]`);
        const prevTag = prevDayElement?.querySelector('.day-text h3')?.textContent || '';
        document.getElementById('prevNavTag').textContent = prevTag;
      } else {
        prevNavBtn.style.opacity = '0.4';
        prevNavBtn.style.pointerEvents = 'none';
        document.getElementById('prevNavTag').textContent = '';
      }
      
      // Update next button
      if (currentIndex < stopIds.length - 1) {
        nextNavBtn.style.opacity = '1';
        nextNavBtn.style.pointerEvents = 'auto';
        const nextStopId = stopIds[currentIndex + 1];
        const nextDayElement = document.querySelector(`.accordion-item[data-stop="${nextStopId}"]`);
        const nextTag = nextDayElement?.querySelector('.day-text h3')?.textContent || '';
        document.getElementById('nextNavTag').textContent = nextTag;
      } else {
        nextNavBtn.style.opacity = '0.4';
        nextNavBtn.style.pointerEvents = 'none';
        document.getElementById('nextNavTag').textContent = '';
      }
    }

    function showDetailsForStop(stopId) {
      // Find the corresponding itinerary day
      const dayElement = document.querySelector(`.accordion-item[data-stop="${stopId}"]`);
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

  // ------------------ Prefill date from URL parameter ------------------
  function prefillDateFromURL() {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const dateParam = urlParams.get('date');
      
      if (dateParam) {
        const reisedatumSelect = document.getElementById('reisedatum');
        if (reisedatumSelect) {
          // Try to find and select the matching option
          const options = reisedatumSelect.options;
          for (let i = 0; i < options.length; i++) {
            if (options[i].value === dateParam) {
              reisedatumSelect.selectedIndex = i;
              break;
            }
          }
        }
      }
    } catch (e) {
      console.error('Error prefilling date:', e);
    }
  }

  // ========== COMPACT DATE SELECTION ==========
  function initCompactDateSelection() {
    const compactDateOptions = document.querySelectorAll('.compact-date-option');
    
    compactDateOptions.forEach(option => {
      // Remove old listener if exists
      option.replaceWith(option.cloneNode(true));
    });
    
    // Re-select after cloning
    const refreshedOptions = document.querySelectorAll('.compact-date-option');
    
    refreshedOptions.forEach(option => {
      option.addEventListener('click', function() {
        const dateValue = this.getAttribute('data-date');
        if (dateValue) {
          // Try to select directly in the form first (if on same page)
          const reisedatumSelect = document.getElementById('reisedatum');
          if (reisedatumSelect) {
            // Find matching option in dropdown
            const options = reisedatumSelect.options;
            for (let i = 0; i < options.length; i++) {
              if (options[i].value === dateValue) {
                reisedatumSelect.selectedIndex = i;
                // Scroll to form
                document.getElementById('anmeldung').scrollIntoView({ 
                  behavior: 'smooth', 
                  block: 'start' 
                });
                return;
              }
            }
          }
          
          // Fallback: Navigate with URL parameter
          window.location.href = `?date=${dateValue}#anmeldung`;
        }
      });
    });
  }

  // Initialize compact date selection (will be called again after dynamic load)
  initCompactDateSelection();

  // ------------------ Load Available Tours from Database ------------------
  function loadAvailableTours() {
    const reisedatumSelect = document.getElementById('reisedatum');
    const tourSelect = document.getElementById('contact-tour'); // For contact form
    const termineList = document.getElementById('termine-list'); // Visual date selection list
    
    // Only load if these elements exist on the page
    if (!reisedatumSelect && !tourSelect && !termineList) return;

    fetch('api/tours_available.php')
      .then(response => response.json())
      .then(data => {
        if (!data.success || !data.tours) {
          console.warn('Keine Touren verfügbar');
          return;
        }

        // Use all tours for display, but only non-full tours for booking dropdowns
        const availableTours = data.tours;
        const bookableTours = availableTours.filter(tour => {
          const participants = Number(tour.current_participants) || 0;
          const maxParticipants = Number(tour.max_participants) || 10;
          return participants < maxParticipants;
        });

        // Fill reisedatum dropdown (for tour page form)
        if (reisedatumSelect) {
          if (bookableTours.length > 0) {
            bookableTours.forEach(tour => {
              const startDate = formatDate(tour.start_date);
              const endDate = formatDate(tour.end_date);
              const dateRange = `${startDate} - ${endDate}`;
              const option = new Option(dateRange, dateRange);
              option.dataset.tourId = tour.id;
              option.dataset.tourName = tour.name;
              reisedatumSelect.add(option);
            });
          } else {
            const option = new Option('Keine Termine verfügbar', '');
            option.disabled = true;
            reisedatumSelect.add(option);
          }
        }

        // Fill visual termine-list (for presentation section)
        if (termineList && availableTours.length > 0) {
          termineList.innerHTML = ''; // Clear existing content
          
          availableTours.forEach(tour => {
            const startDate = formatDateLong(tour.start_date);
            const endDate = formatDateLong(tour.end_date);
            const dateRange = `${startDate} – ${endDate}`;
            const shortRange = `${formatDate(tour.start_date)} - ${formatDate(tour.end_date)}`;
            
            // Calculate status
            const participants = tour.current_participants || 0;
            const maxParticipants = tour.max_participants || 10;
            let statusText = 'Restplätze verfügbar';
            let statusClass = 'status-available';
            let isFeatured = false;
            
            if (participants >= maxParticipants) {
              statusText = 'Ausgebucht';
              statusClass = 'status-full';
            } else if (participants >= 4) {
              statusText = 'Garantiert';
              statusClass = 'status-guaranteed';
              isFeatured = true;
            }
            
            // Format price
            const price = Number(tour.price_per_person).toFixed(0);
            
            // Create HTML
            const optionDiv = document.createElement('div');
            optionDiv.className = 'compact-date-option' + (isFeatured ? ' featured' : '');
            optionDiv.dataset.date = shortRange;
            
            optionDiv.innerHTML = `
              <div class="compact-date-info">
                <div class="compact-date-main">${dateRange}</div>
                <div class="compact-date-status ${statusClass}">${statusText}</div>
              </div>
              <div class="compact-date-price">ab ${price} €</div>
            `;
            
            termineList.appendChild(optionDiv);
          });
          
          // After adding all date options, initialize click events
          initCompactDateSelection();
        }

        // Fill tour dropdown (for contact form - all tours)
        if (tourSelect) {
          const uniqueTours = [...new Set(data.tours.map(t => t.name))];
          
          uniqueTours.forEach(tourName => {
            const option = new Option(tourName, tourName);
            tourSelect.add(option);
          });
        }
      })
      .catch(error => {
        console.error('Fehler beim Laden der Touren:', error);
      });
  }

  // Helper function to format dates (YYYY-MM-DD -> DD.MM.YYYY)
  function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
  }

  // Helper function to format dates with month names (YYYY-MM-DD -> "30. März 2026")
  function formatDateLong(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const monthNames = ['Jan', 'Feb', 'März', 'April', 'Mai', 'Juni', 'Juli', 'Aug', 'Sept', 'Okt', 'Nov', 'Dez'];
    const month = monthNames[date.getMonth()];
    const year = date.getFullYear();
    return `${day}. ${month} ${year}`;
  }

})();
