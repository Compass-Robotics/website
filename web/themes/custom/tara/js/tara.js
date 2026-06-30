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
