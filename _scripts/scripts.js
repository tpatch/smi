/*jslint devel: false, browser: false, white: true */
/*global $: false, window: false */

(function () {
	"use strict";

	window.review = {
		init: function () {
			this.colorbox();
			this.loginForm();
		},

		colorbox: function () {
			$('.row a').colorbox({
				rel:'gallery',
				transition: 'none',
				scalePhotos: true,
				speed: 0,
				fixed: true,
				current: '',
				maxHeight: '80%'
			});
		},

		loginForm: function () {
			$('#clientlogin').submit(function(e){
				e.preventDefault();

				var username = $('#username').val(),
					password = $('#password').val(),
					keyword = $('#keyword').val();

				console.log(username);

				if ( username.length > 0 && password.length > 0 && keyword.length > 0 ) {
					$('#clientlogin').unbind('submit').submit();
				} else {
					alert('Please fill in all fields.');
				};
			});
		}
	};

	$(window.document).ready(function () {
		window.review.init();
	});
}());