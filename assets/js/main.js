(function($) {
	"use strict";

	$(document).ready(function() {

		/* ==================================================
		    # Tooltip Init
		===============================================*/
		$('[data-toggle="tooltip"]').tooltip();

		/* ==================================================
		    # Wow Init
		 ===============================================*/
		var wow = new WOW({
			boxClass: 'wow',
			animateClass: 'animated',
			offset: 0,
			mobile: true,
			live: true
		});
		wow.init();

		/* ==================================================
		    # imagesLoaded active
		===============================================*/
		$('#portfolio-grid,.blog-masonry').imagesLoaded(function() {

			$('.mix-item-menu').on('click', 'button', function() {
				var filterValue = $(this).attr('data-filter');
				$grid.isotope({ filter: filterValue });
			});

			$('.mix-item-menu button').on('click', function(event) {
				$(this).siblings('.active').removeClass('active');
				$(this).addClass('active');
				event.preventDefault();
			});

			var $grid = $('#portfolio-grid').isotope({
				itemSelector: '.pf-item',
				percentPosition: true,
				masonry: { columnWidth: '.pf-item' }
			});

			$('.blog-masonry').isotope({
				itemSelector: '.blog-item',
				percentPosition: true,
				masonry: { columnWidth: '.blog-item' }
			});

		});


		/* ==================================================
		    # Fun Factor Init
		===============================================*/
		$('.timer').countTo();
		$('.fun-fact').appear(function() {
			$('.timer').countTo();
		}, { accY: -100 });

		/* ==================================================
		    # Magnific popup init
		 ===============================================*/
		$(".popup-link").magnificPopup({ type: 'image' });

		$(".popup-gallery").magnificPopup({
			type: 'image',
			gallery: { enabled: true }
		});

		$(".popup-youtube, .popup-vimeo, .popup-gmaps").magnificPopup({
			type: "iframe",
			mainClass: "mfp-fade",
			removalDelay: 160,
			preloader: false,
			fixedContentPos: false
		});

		$('.magnific-mix-gallery').each(function() {
			var $container = $(this);
			var $imageLinks = $container.find('.item');

			var items = [];
			$imageLinks.each(function() {
				var $item = $(this);
				var type = $item.hasClass('magnific-iframe') ? 'iframe' : 'image';
				items.push({
					src: $item.attr('href'),
					type: type,
					title: $item.data('title')
				});
			});

			$imageLinks.magnificPopup({
				mainClass: 'mfp-fade',
				items: items,
				gallery: {
					enabled: true,
					tPrev: $(this).data('prev-text'),
					tNext: $(this).data('next-text')
				},
				type: 'image',
				callbacks: {
					beforeOpen: function() {
						var index = $imageLinks.index(this.st.el);
						if (index !== -1) this.goTo(index);
					}
				}
			});
		});

		/* ==================================================
		    # Banner Carousel
		===============================================*/
		new Swiper(".banner-fade", {
			direction: "horizontal",
			loop: true,
			effect: "fade",
			fadeEffect: { crossFade: true },
			speed: 2000,
			autoplay: { delay: 5000, disableOnInteraction: false },
			pagination: { el: '.swiper-pagination', type: 'bullets', clickable: true },
			navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
		});

		/* ==================================================
		    # Service Carousel
		===============================================*/
		new Swiper(".service-carousel", {
			loop: true,
			slidesPerView: 1,
			spaceBetween: 30,
			autoplay: true,
			pagination: { el: '.services-pagination', type: 'fraction', clickable: true },
			navigation: { nextEl: ".services-button-next", prevEl: ".services-button-prev" },
			breakpoints: {
				768: { slidesPerView: 2 },
				992: { slidesPerView: 3 }
			}
		});

		/* ==================================================
		    # Offer Carousel
		===============================================*/
		new Swiper(".offser-carousel", {
			loop: true,
			autoplay: true,
			effect: "fade",
			fadeEffect: { crossFade: true },
			speed: 1000,
			autoplay: { delay: 4000, disableOnInteraction: false },
		});

		/* ==================================================
		    # Testimonials
		===============================================*/
		new Swiper(".testimonial-style-one-carousel", {
			loop: true,
			autoplay: true,
			pagination: { el: '.swiper-pagination', clickable: true },
			navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
		});

		/* ==================================================
		    Carousels (Food / Brand / Product)
		====================================================*/
		new Swiper(".food-cat-carousel", {
			loop: true,
			slidesPerView: 1,
			spaceBetween: 30,
			autoplay: true,
			breakpoints: {
				768: { slidesPerView: 2 },
				992: { slidesPerView: 3 }
			}
		});

		new Swiper(".brand-style-one-carousel", {
			loop: true,
			slidesPerView: 2,
			spaceBetween: 30,
			autoplay: true,
			breakpoints: {
				768: { slidesPerView: 3 },
				992: { slidesPerView: 4 },
				1400: { slidesPerView: 5 }
			}
		});

		new Swiper(".product-gallery-carousel", {
			loop: true,
			slidesPerView: 2,
			spaceBetween: 30,
			autoplay: true,
			breakpoints: {
				768: { slidesPerView: 3 },
				1200: { slidesPerView: 4 }
			}
		});

		new Swiper(".related-product-carousel", {
			loop: true,
			slidesPerView: 1,
			spaceBetween: 30,
			autoplay: true,
			breakpoints: {
				768: { slidesPerView: 2 },
				992: { slidesPerView: 3 },
				1400: { slidesPerView: 4 }
			}
		});

		/* ==================================================
		    Date Picker & Select
		===============================================*/
		$('.date-picker-one').datepicker();
		$('.reservation-form select, .checkout-form select').niceSelect();

		/* ==================================================
		    Split Text
		===============================================*/
		let text_split = document.querySelector(".split-text");
		if (text_split) {
			document.querySelectorAll('.split-text').forEach(el => {
				var splitEl = new SplitText(el, { type: "lines, words" });
				gsap.timeline({
					scrollTrigger: { trigger: el, start: 'top 90%' }
				}).from(splitEl.words, { yPercent: 100, stagger: 0.025 });
			});
		}

		/* ==================================================
		    Dark / Light Switcher
		====================================================*/
		$(".radio-btn").on("click", function() {
			$(".radio-inner").toggleClass("active");
			$("body").toggleClass("bg-dark-secondary");
		});

		$(".radio-btn-light").on("click", function() {
			$(".radio-inner-light").toggleClass("active");
			$("body").toggleClass("bg-dark-secondary");
		});

		/* ==================================================
		    Contact Form
		====================================================*/
		$('.contact-form').each(function() {
			var form = $(this);
			form.submit(function() {
				var action = $(this).attr('action');
				$("#message").slideUp(750, function() {
					$('#message').hide();
					$('#submit').after('<img src="assets/img/ajax-loader.gif" class="loader" />')
						.attr('disabled', 'disabled');

					$.post(action, {
						name: $('#name').val(),
						email: $('#email').val(),
						phone: $('#phone').val(),
						comments: $('#comments').val()
					}, function(data) {
						document.getElementById('message').innerHTML = data;
						$('#message').slideDown('slow');
						$('.contact-form img.loader').fadeOut('slow', function() {
							$(this).remove()
						});
						$('#submit').removeAttr('disabled');
					});
				});
				return false;
			});
		});

	}); // end doc ready



	/* ==================================================
	    Preloader
	====================================================*/
	function loader() {
		$(window).on('load', function() {
			$('#restan-preloader').addClass('loaded');
			$("#loading").fadeOut(500);

			if ($('#restan-preloader').hasClass('loaded')) {
				$('#preloader').delay(900).queue(function() {
					$(this).remove();
				});
			}
		});
	}
	loader();



/* ===================================================================
   🔥 NEW BLOCK: CATEGORY COUNT + SEARCH + AUTO-HIGHLIGHT + CART SYSTEM
===================================================================*/
document.addEventListener("DOMContentLoaded", function () {

	/* -------------------------
	   CATEGORY COUNT
	--------------------------*/
	let totalCat = document.querySelectorAll("#categoryList li").length;
	if (document.getElementById("catCount")) {
		document.getElementById("catCount").innerText = totalCat;
	}

	/* -------------------------
	   SEARCH FILTER
	--------------------------*/
	const searchInput = document.getElementById("searchInput");
	if (searchInput) {
		searchInput.addEventListener("input", function () {
			let keyword = this.value.toLowerCase();
			document.querySelectorAll(".menu-item").forEach(item => {
				let name = item.dataset.name.toLowerCase();
				let desc = item.dataset.desc.toLowerCase();

				item.style.display =
					name.includes(keyword) || desc.includes(keyword) ? "" : "none";
			});
		});
	}

	/* -------------------------
	   AUTO SCROLL CATEGORY HIGHLIGHT
	--------------------------*/
	const categoryBlocks = document.querySelectorAll(".category-block");
	const categoryListItems = document.querySelectorAll("#categoryList li");

	window.addEventListener("scroll", () => {
		let current = "";
		categoryBlocks.forEach(block => {
			let top = block.offsetTop - 150;
			if (window.scrollY >= top) current = block.id;
		});

		categoryListItems.forEach(li => {
			li.classList.remove("active");
			if (li.dataset.target === current) li.classList.add("active");
		});
	});

	/* -------------------------
	  AJAX ADD TO CART + Qty + Remove
	--------------------------*/
	let cart = [];
	let cartItems = document.getElementById("cartItems");
	let cartTotal = document.getElementById("cartTotal");

	function updateCartUI() {
		cartItems.innerHTML = "";
		let total = 0;

		cart.forEach((item, index) => {
			total += item.price * item.qty;

			cartItems.innerHTML += `
                <li class="cart-item">
                    <div class="cart-item-row">
                        <strong>${item.name}</strong>
                        <span>$${(item.price * item.qty).toFixed(2)}</span>
                    </div>

                    <div class="cart-item-row">
                        <div class="qty-controls">
                            <span class="qty-btn" data-index="${index}" data-action="minus">-</span>
                            <strong>${item.qty}</strong>
                            <span class="qty-btn" data-index="${index}" data-action="plus">+</span>
                        </div>
                        <span class="remove-btn" data-index="${index}">🗑️</span>
                    </div>
                </li>
            `;
		});

		cartTotal.innerText = total.toFixed(2);
	}

	/* -------------------------
	   ADD TO CART Click
	--------------------------*/
	document.addEventListener("click", function (e) {
		if (e.target.classList.contains("order-btn")) {

			let name = e.target.dataset.name;
			let price = parseFloat(e.target.dataset.price);

			let existing = cart.find(i => i.name === name);

			if (existing) {
				existing.qty++;
			} else {
				cart.push({ name, price, qty: 1 });
			}

			updateCartUI();
		}
	});

	/* -------------------------
	   Qty + / -  & Remove
	--------------------------*/
	document.addEventListener("click", function (e) {

		// Quantity
		if (e.target.classList.contains("qty-btn")) {
			let index = e.target.dataset.index;
			let action = e.target.dataset.action;

			if (action === "plus") cart[index].qty++;
			else if (action === "minus" && cart[index].qty > 1) cart[index].qty--;

			updateCartUI();
		}

		// Remove
		if (e.target.classList.contains("remove-btn")) {
			let index = e.target.dataset.index;
			cart.splice(index, 1);
			updateCartUI();
		}
	});

}); // DOMContentLoaded END



})(jQuery);
