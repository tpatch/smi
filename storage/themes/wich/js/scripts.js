/*jslint devel: false, browser: false, white: true */
/*global $: false, window: false */

(function () {
	"use strict";

	window.wich = {
		init: function () {
			this.tabs();
			this.contact();

			var imgToLoad = parseInt($('.filmstrip img').length),
				imgCounter = 1;
			$('.filmstrip img').on('load', function(){
				if ( imgCounter === imgToLoad ) {
					window.wich.filmstrip();
				};

				imgCounter += 1;
			});
		},

		addthis: function () {
			window.addthis_config = {
				ui_click: true,
				data_ga_property: ''
			};
		},

		tabs: function () {
			$('.tab').on('click', function(){
				var current = $(this).attr('id');

				$('.tab.active').removeClass('active');
				$(this).addClass('active');

				$('.tabcontent').hide();
				$('.tabcontent.' + current).show();
			});
		},

		contact: function () {
			$('#submit').on('click', function(e){
				e.preventDefault;
				var name = $('#name').val(),
					email = $('#email').val(),
					message = $('#message').val(),
					varString = 'name=' + name + '&email=' + email + '&message=' + message;

				if ( name.length > 0 && email.length > 0 && message.length > 0 ) {

					$.ajax({
						type: 'POST',
						url: "../formprocess.php",
						datatype: "json",
						cache: false,
						data: varString,
						success: function (response) {
							$('form').replaceWith("<h3><strong>We'll be in touch soon!</strong></h3>");
						}, 
						error: function (response) {
							$('form').before("<h3><strong>There's been an error. Please try again.</strong></h3>");
						}
					});

				} else {
					alert('Please fill in all fields');
				};

				return false;
			});
		},

		lemmon: function () {
			var images = $('.filmstrip img').length,
				wh = $(window).height(),
				ih = wh * .85,
				index,
				total = $('.slider li').not('.-before, .-after').length;;

			$('.close').on('click', function(){
				$('#slider').css('visibility', 'hidden');
			});

			$('#slider .wrap').lemmonSlider({
				'infinite' : true
			});

			// Copied/unedited code
			var fun = function (e){
				$('.pagination').text('');

				var $li = $('.slider li').not('.-before, .-after');

				index = $li.filter('.active').index()-15;
				index = (index == -16)?1:index;

				$('.pagination').append(index +'/'+ total);
			};

			$('.next-slide, .prev-slide').click(fun);

			// Firefox fix for LI width
			$('.inner > li > img').each(function(){
				var w = $(this).width();

				console.log(w);

				$(this).parent('li').width(w);
			});

			/* Bring up lightbox
			$('.filmstrip a').click(function(e){
				e.preventDefault();
				var active = $(this).attr('data-index');	

				$('#slider li').removeClass('active');
				$('#slider li[data-index=' + active + ']').not('.-before, .-after').addClass('active');
				fun();
				$('#slider').css('visibility', 'visible');
			}); */
		},

		filmstrip: function () {
			var imgsW = ($('.filmstrip img').length * 6) + 26,
				w = $(window).width(),
				slideW;

			// Get widths of all filmstrip images
			$('.filmstrip img').each(function(){
				var thisWidth = parseInt($(this).width());

				imgsW += thisWidth;
			});

			$('.filmstrip').css('min-width', imgsW).append('<span class="filmstripprev"></span>').prepend('<span class="filmstripnext"></span>');

			var h = $('.filmstrip').height() / 3,
				t = $('.filmstrip').offset().top,
				pos = h + t;

			$('.filmstripprev,.filmstripnext').css('top', pos);

			$('.filmstripprev').on('click', function(){
				slideW = $(window).width();

				$('body').animate({
					'scrollLeft': '-=' + slideW + 'px'
				}, 800);
			});

			$('.filmstripnext').on('click', function(){
				slideW = $(window).width();

				$('body').animate({
					'scrollLeft': '+=' + slideW + 'px'
				}, 800);
			});

			$('.filmstripprev').hide();
			$(window).on('scroll', function(){
				if ( ($(window).scrollLeft() + $(window).width()) >= $('.filmstrip').width() ) {
					$('.filmstripnext').hide();
				} else {
					$('.filmstripnext').show();
				};
				if ( $(window).scrollLeft() <= 0 ) {
					$('.filmstripprev').hide();
				} else {
					$('.filmstripprev').show();
				};
			});

			window.wich.lemmon();
		}
	};

	$(window.document).ready(function () {
		window.wich.init();
	});

	$(window).on('load', function(){
		//window.wich.preload();
	});
}());