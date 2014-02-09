/*
	1. Load images
	2. Size images
	3. Call lemmon slider
*/

scrolls: function () {
	$('html, body, *').mousewheel(function(e, delta) {
		this.scrollLeft -= (delta * 60);
		e.preventDefault();
	});
},

colorbox: function () {
	var wh = $(window).height() - 300,
		ih = wh * .8698;

	$('.lightbox').height(wh);
	$('#cboxLoadedContent img').height(ih);

	$('.filmstrip a').colorbox({
		rel:'gallery',
		transition: 'none',
		scalePhotos: true,
		speed: 0,
		opacity: 0,
		width: '100%',
		height: ih,
		fixed: true,
		top: '24px',
		current: '',
		onComplete:function(){
			window.wich.carousel();
		}
	});

	var allImgs = $('.filmstrip img').length;
},

lightbox: function () {
	var featuredItem = 0;

	$('.close').on('click', function(){
		$('.preload').css('visibility', 'hidden');
		$('.preload')
			.animate({
				'scrollLeft': '0px'
			}, 1000);
	});

	$('.preload .controls').on('click', function(){
		var firstImg = $('.preload .inner > div').eq(0),
			lastImg = $('.preload .inner > div').eq(total - 1),
			total = $('.inner div').length,
			currentOffset,
			newOffset,
			featuredItemW,
			screenW = $(window).width(),
			scrollDistance;

		// This shit got messy but holy damn it's working
		if ( $(this).is('#next') ) {
			if (featuredItem < total) { 
				featuredItem += 1;
			} else { 
				featuredItem = 1; 
			};

			featuredItemW = $('.preload img[data-slidenumber=' + featuredItem + ']').width();
			currentOffset = $('.preload img[data-slidenumber=' + featuredItem + ']').offset().left;
			newOffset = (screenW / 2) - (featuredItemW / 2);
			scrollDistance = currentOffset - newOffset;
			$('.preload')
				.animate({
					'scrollLeft': '+=' + scrollDistance + 'px'
				}, 1000);

			/*firstImg
				.clone()
				.appendTo($('.preload .inner'));*/
		};
		if ( $(this).is('#prev') ) {
			if (featuredItem > 1) { 
				featuredItem -= 1;
			} else { 
				featuredItem = total;
			};

			featuredItemW = $('.preload img[data-slidenumber=' + featuredItem + ']').width();
			currentOffset = $('.preload img[data-slidenumber=' + featuredItem + ']').offset().left;
			newOffset = (screenW / 2) - (featuredItemW / 2);
			scrollDistance =  newOffset - currentOffset;
			$('.preload')
				.animate({
					'scrollLeft': '-=' + scrollDistance + 'px'
				}, 1000);

			/*lastImg
				.clone()
				.prependTo($('.preload .inner'));*/
		};

		// Change opacity/filter
		$('.preload img').css({ 'opacity': '.25', '-webkit-filter': 'sepia(50%) grayscale(75%)' });
		$('.preload img[data-slidenumber=' + featuredItem + ']').css({ 'opacity': '1', '-webkit-filter': 'none' });
	});
},

preload: function () {
	var images = $('.filmstrip img').length,
		wh = $(window).height() - 200,
		ih = wh * .8698 + 42;

	$('.preload .inner').height(ih);

	for ( var i=1; i <= images; i++ ) {
		$('.preload .inner').append('<div><img id="preload-' + i + '" height="' + ih + '" /></div>');

		if ( i === images ) {
			preloader();
		}
	};

	function preloader() {
		for ( var j=1; j <= images; j++ ) {
			$("#preload-" + j).attr("src", $('.filmstrip img').eq( j-1 ).parent('a').attr('href')).attr('data-slidenumber', j);
			if ( j === images ) {
				setTimeout(window.wich.lightboxInit, 300);
			};
		};
	};
},

fred: function () {
	var wh = $(window).height() - 300,
		ih = wh * .8698;

	$('.lightbox').height(wh);

	$('.preload').carouFredSel({
		width: '100%',
		height: wh,
		auto: false,
		items: {
			visible: 3,
			height: ih
		},
		scroll: {
			fx: 'cover',
			duration: 5
		},
		prev: {
			button: '#boxprev'
		},
		next: {
			button: '#boxnext'
		}
	});
},

carousel: function () {
	var picSrc = $('.cboxPhoto').attr('src'),
		picQuery = picSrc.split('?'),
		picId = picQuery[1].split('id='),
		index = $('.filmstrip img').index( $('.filmstrip img#' + picId[1]) ),
		total = $('.filmstrip img').length,
		first = $('.filmstrip img').eq( index - 2 ).parent('a').attr('href'),
		second = $('.filmstrip img').eq( index - 1 ).parent('a').attr('href'),
		third = $('.filmstrip img').eq( index + 1 ).parent('a').attr('href'),
		fourth = $('.filmstrip img').eq( index + 2 ).parent('a').attr('href'),
		sWidth = $(window).width(),
		fullWidth = 4000,
		counter = 0,
		wh = $(window).height() - 300,
		ih = wh * .8698;

		if ( (index + 1) === total ) {
			third = $('.filmstrip img').eq(0).parent('a').attr('href');
			fourth = $('.filmstrip img').eq(1).parent('a').attr('href');
		} else if ( (index + 1) === (total - 1)  ) {
			fourth = $('.filmstrip img').eq(0).parent('a').attr('href');	
		}

		$('.cboxPhoto').before('<img src="' + first + '" class="firstImg" /><img src="' + second + '" class="secondImg" />');
		$('.cboxPhoto').after('<img src="' + third + '" class="thirdImg" /><img src="' + fourth + '" class="fourthImg" />');
		$('#cboxLoadedContent img').wrapAll('<div class="lightwrap"></div>');

		function setOffset() {
			// Add in initial photo as well
			fullWidth += parseInt($('.cboxPhoto').width());

			$('.lightwrap').width(fullWidth + 140)

			var offset = (fullWidth/2) - (sWidth/2),
				newOffset = $('.cboxPhoto').position().left - (( sWidth - parseInt($('.cboxPhoto').width())) / 2);
				// - parseInt($('.cboxPhoto').width()) - (sWidth/2)

			$('.lightwrap').css({'left':-newOffset});
		};

		function setWidth() {
			counter = 0;
			fullWidth = 0;
			$('#cboxLoadedContent img').on('load', function(){
				fullWidth += parseInt($(this).width());
				counter++;

				if ( counter === 4 ) {
					setOffset();
				};
			});
		};

		setWidth();
},

lightboxInit: function () {
	var imgLen = $('.preload img').length - 1,
		sliderW = 0;

	for (var i=0; i <= imgLen; i++) {
		sliderW += $('.preload div img').eq(i).width() + 20;
		if ( i === imgLen ) {
			//$('.preload .inner').css('min-width', sliderW + 40);
		}
	};
},