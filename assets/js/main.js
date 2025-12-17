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


})(jQuery);
