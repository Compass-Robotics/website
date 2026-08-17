/* Load jQuery.
------------------------------------------------*/
jQuery(document).ready(function ($) {
  // Mobile menu.
  // Keyboard accessible -add attributes to button element
  $('.menu-item-has-children button').attr({
  'role': 'menuitem',
  'aria-haspopup': 'true',
  'aria-expanded': 'false'
  });

  $('.mobile-menu').click(function () {
    const $menuWrapper = $(this).siblings('.primary-menu-wrapper').first();
    $(this).toggleClass('menu-icon-active');
    $menuWrapper.toggleClass('active-menu');

    const isExpanded = $(this).attr('aria-expanded') === 'true';
    $(this).attr('aria-expanded', !isExpanded);
    $(this).attr('aria-label', isExpanded ? 'Open main menu' : 'Close main menu');
  })
  $('.close-mobile-menu').click(function () {
    $(this).closest('.primary-menu-wrapper').toggleClass('active-menu');
    $('.mobile-menu').removeClass('menu-icon-active');
    $('.mobile-menu').attr('aria-expanded', 'false');
    $('.mobile-menu').attr('aria-label', 'Open main menu');
  })

  // Full page search.
  $('.search-icon').click(function () {
    $('.search-box').css('display', 'flex');
    return false;
  });
  $('.search-box-close').click(function () {
    $('.search-box').css('display', 'none');
    return false;
  });

  // Scroll To Top.
  $(window).scroll(function () {
    if ($(this).scrollTop() > 80) {
      $('.scrolltop').css('display', 'flex');
    } else {
      $('.scrolltop').fadeOut('slow');
    }
  });
  $('.scrolltop').click(function () {
    $('html, body').scrollTop(0);
  });
// End document ready.
});

/* Function if device width is more than 767px.
------------------------------------------------*/
jQuery(window).on('load', function () {
  // Add empty space for fixed footer.
  if (jQuery(window).width() > 767) {
    var footerheight = jQuery('#footer').outerHeight(true) + 4;
    jQuery('#last-section').css('height', footerheight);
  }

// end window on load
});

// Toggle submenu using keyboard.
document.querySelectorAll('.submenu-toggle').forEach(button => {
  button.addEventListener('click', function () {
    const expanded = this.getAttribute('aria-expanded') === 'true';

    // Close all first (optional but recommended)
    document.querySelectorAll('.submenu-toggle').forEach(btn => {
      btn.setAttribute('aria-expanded', 'false');
      const sub = btn.nextElementSibling;
      if (sub) sub.style.display = 'none';
    });

    // Toggle current
    this.setAttribute('aria-expanded', !expanded);

    const submenu = this.nextElementSibling;
    if (submenu) {
      submenu.style.display = expanded ? 'none' : 'block';
    }
  });
});

/*
 * Make Views accordion content discoverable by browser find-in-page.
 *
 * This adds hidden="until-found" to collapsed panels, which allows content to
 * be indexed by find-in-page while still hidden. When a match is found,
 * beforematch opens the corresponding accordion panel.
 */
(function (Drupal, once, drupalSettings, $) {
  Drupal.behaviors.taraUntilFoundAccordion = {
    attach(context) {
      if (!drupalSettings.views_accordion) {
        return;
      }

      const initAccordion = (accordionEl) => {
        if (accordionEl.dataset.untilFoundInitialized === '1') {
          return;
        }

        const $accordion = $(accordionEl);

        const syncPanelVisibility = () => {
          const $headers = $accordion.find('.ui-accordion-header');

          $headers.each((index, headerEl) => {
            const $header = $(headerEl);
            const $panel = $header.next('.ui-accordion-content');

            if (!$panel.length) {
              return;
            }

            const isActive = $header.hasClass('ui-accordion-header-active') || $header.attr('aria-expanded') === 'true';
            const isActiveFallback = $header.hasClass('ui-state-active') || $header.attr('aria-selected') === 'true';
            const panelIsActive = isActive || isActiveFallback;

            if (panelIsActive) {
              $panel.removeAttr('hidden');
            }
            else {
              $panel.attr('hidden', 'until-found');
            }

            if (!$panel[0].dataset.untilFoundBound) {
              $panel[0].addEventListener('beforematch', () => {
                // Do not call jQuery UI's single-active API here, because it
                // would close previously matched panels. Keep each matched
                // panel open so browser find can reveal multiple hits at once.
                $panel.removeAttr('hidden');
                $panel.css('display', 'block');

                $header.attr('aria-expanded', 'true');
                $header.attr('aria-selected', 'true');
                $header.removeClass('ui-accordion-header-collapsed ui-corner-all');
                $header.addClass('ui-accordion-header-active ui-state-active ui-corner-top');

                $panel.addClass('ui-accordion-content-active');
              });
              $panel[0].dataset.untilFoundBound = '1';
            }
          });
        };

        syncPanelVisibility();
        $accordion.on('accordionbeforeactivate.taraUntilFound', (event, ui) => {
          if (ui && ui.newPanel && ui.newPanel.length) {
            ui.newPanel.removeAttr('hidden');
          }
        });
        // Defer so jQuery UI finishes updating aria/class state before we read it.
        $accordion.on('accordionactivate.taraUntilFound', () => setTimeout(syncPanelVisibility, 0));
        accordionEl.dataset.untilFoundInitialized = '1';
      };

      Object.values(drupalSettings.views_accordion).forEach((viewSettings) => {
        const selector = `${viewSettings.display}.ui-accordion`;
        once('taraUntilFoundAccordion', selector, context).forEach(initAccordion);

        const listenerKey = `taraUntilFoundAccordionCreate-${selector}`;
        once(listenerKey, 'body', context).forEach(() => {
          $(document).on('accordioncreate.taraUntilFound', viewSettings.display, function () {
            initAccordion(this);
          });
        });
      });
    },
  };
})(Drupal, once, drupalSettings, jQuery);
