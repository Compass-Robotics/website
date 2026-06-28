function SimpleSlider(selector, options = {}) {
  const slider = document.querySelector(selector);
  const track = slider.querySelector('.js-slider-track');
  const slides = track.children;

  const settings = {
    delay: options.delay || 3000,
    dots: options.dots ?? true,
    arrows: options.arrows ?? true
  };

  let index = 0;
  let interval;

function goToSlide(i) {
  index = (i + slides.length) % slides.length;
  track.style.transform = `translateX(-${index * 100}%)`;

  // Active slide class
  Array.from(slides).forEach((slide, idx) => {
    slide.classList.toggle('active-slide', idx === index);
  });

  updateDots();
}

  function next() {
    goToSlide(index + 1);
  }

  function prev() {
    goToSlide(index - 1);
  }

  function startAuto() {
    interval = setInterval(next, settings.delay);
  }

  function stopAuto() {
    clearInterval(interval);
  }

  /* Arrows */
  if (settings.arrows) {
    const prevBtn = document.createElement('button');
    const nextBtn = document.createElement('button');

    prevBtn.className = 'js-slider-arrow js-slider-prev';
    nextBtn.className = 'js-slider-arrow js-slider-next';

    prevBtn.innerHTML = '‹';
    nextBtn.innerHTML = '›';

    prevBtn.onclick = () => { prev(); stopAuto(); startAuto(); };
    nextBtn.onclick = () => { next(); stopAuto(); startAuto(); };

    slider.append(prevBtn, nextBtn);
  }

  /* Dots */
  let dots = [];
  function updateDots() {
    dots.forEach((dot, i) =>
      dot.classList.toggle('active', i === index)
    );
  }

  if (settings.dots) {
    const dotsWrap = document.createElement('div');
    dotsWrap.className = 'js-slider-dots';

    Array.from(slides).forEach((_, i) => {
      const dot = document.createElement('span');
      dot.className = 'js-slider-dot';
      dot.onclick = () => { goToSlide(i); stopAuto(); startAuto(); };
      dotsWrap.appendChild(dot);
      dots.push(dot);
    });

    slider.after(dotsWrap);
    updateDots();
  }
  goToSlide(0);
  startAuto();
}
