(function () {
	"use strict";

	var sliders = document.querySelectorAll("[data-loft-slider]");

	if (!sliders.length) {
		return;
	}

	var prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

	function createSlider(slider) {
		var track = slider.querySelector("[data-loft-slider-track]");
		var slides = slider.querySelectorAll("[data-loft-slide]");
		var dotsContainer = slider.querySelector("[data-loft-dots]");
		var prevBtn = slider.querySelector("[data-loft-prev]");
		var nextBtn = slider.querySelector("[data-loft-next]");
		var autoplay = slider.getAttribute("data-autoplay") === "true";
		var autoplayInterval = (window.Loft1325MobileLofts && window.Loft1325MobileLofts.autoplayInterval) ? window.Loft1325MobileLofts.autoplayInterval : 5500;
		var current = 0;
		var timer = null;
		var startX = 0;
		var deltaX = 0;

		if (!track || !slides.length) {
			return;
		}

		function renderDots() {
			if (!dotsContainer) {
				return;
			}

			dotsContainer.innerHTML = "";

			slides.forEach(function (_, index) {
				var dot = document.createElement("button");
				dot.type = "button";
				dot.className = "loft1325-mobile-loft__dot" + (index === current ? " is-active" : "");
				dot.setAttribute("aria-label", "Slide " + (index + 1));
				dot.addEventListener("click", function () {
					goTo(index);
				});
				dotsContainer.appendChild(dot);
			});
		}

		function update() {
			track.style.transform = "translateX(-" + current * 100 + "%)";

			if (!dotsContainer) {
				return;
			}

			var dots = dotsContainer.querySelectorAll(".loft1325-mobile-loft__dot");
			dots.forEach(function (dot, index) {
				if (index === current) {
					dot.classList.add("is-active");
				} else {
					dot.classList.remove("is-active");
				}
			});
		}

		function goTo(index) {
			current = (index + slides.length) % slides.length;
			update();
			restartAutoplay();
		}

		function next() {
			goTo(current + 1);
		}

		function prev() {
			goTo(current - 1);
		}

		function startAutoplay() {
			if (!autoplay || slides.length <= 1 || prefersReducedMotion.matches) {
				return;
			}

			timer = window.setInterval(next, autoplayInterval);
		}

		function stopAutoplay() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		function restartAutoplay() {
			stopAutoplay();
			startAutoplay();
		}

		function handleTouchStart(event) {
			stopAutoplay();
			startX = event.touches[0].clientX;
			deltaX = 0;
		}

		function handleTouchMove(event) {
			if (!startX) {
				return;
			}
			deltaX = event.touches[0].clientX - startX;
		}

		function handleTouchEnd() {
			if (Math.abs(deltaX) > 60) {
				if (deltaX < 0) {
					next();
				} else {
					prev();
				}
			}

			startX = 0;
			deltaX = 0;
			startAutoplay();
		}

		if (nextBtn) {
			nextBtn.addEventListener("click", next);
		}

		if (prevBtn) {
			prevBtn.addEventListener("click", prev);
		}

		track.addEventListener("touchstart", handleTouchStart, { passive: true });
		track.addEventListener("touchmove", handleTouchMove, { passive: true });
		track.addEventListener("touchend", handleTouchEnd);

		renderDots();
		update();
		startAutoplay();
	}

	sliders.forEach(createSlider);
})(); 
