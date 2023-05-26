document.addEventListener('DOMContentLoaded', function () {
  var intervalDuration = 5000; // 5 seconds
  var slidesContainer = document.querySelector('.slides');
  var slides = slidesContainer.querySelectorAll('li.slide');
  var totalSlides = slides.length;
  var currentSlide = 0;
  var slideInterval;

  function showSlide(index) {
    slides[currentSlide].style.display = 'none';
    slides[index].style.display = 'flex';
    currentSlide = index;
  }

  function showNextSlide() {
    var nextSlide = (currentSlide + 1) % totalSlides;
    showSlide(nextSlide);
  }

  function startSlider() {
    slideInterval = setInterval(showNextSlide, intervalDuration);
  }

  function pauseSlider() {
    clearInterval(slideInterval);
  }

  startSlider();

  slidesContainer.addEventListener('mouseenter', pauseSlider);
  slidesContainer.addEventListener('mouseleave', startSlider);
});
