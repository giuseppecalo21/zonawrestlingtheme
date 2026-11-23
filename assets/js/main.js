/**
 * Zona Wrestling — script del tema (nessuna dipendenza).
 * Ogni blocco parte solo se nella pagina c'è l'elemento che gli serve.
 */
( function () {
	'use strict';

	var ZW = window.ZW || {};
	var $ = function ( sel, root ) { return ( root || document ).querySelector( sel ); };
	var $$ = function ( sel, root ) { return Array.prototype.slice.call( ( root || document ).querySelectorAll( sel ) ); };
	var reducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function store( key, value ) {
		try {
			if ( value === undefined ) { return window.localStorage.getItem( key ); }
			window.localStorage.setItem( key, value );
		} catch ( e ) {}
		return null;
	}

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text == null ? '' : String( text );
		return div.innerHTML;
	}

	/* -----------------------------------------------------------------------
	 * Finestre: menu mobile, ricerca, stories
	 * --------------------------------------------------------------------- */
	var lastFocus = null;
	var openModalEl = null;

	function openModal( modal ) {
		if ( ! modal ) { return; }
		lastFocus = document.activeElement;
		modal.classList.remove( 'hidden' );
		if ( modal.id !== 'mobileMenu' ) { modal.classList.add( 'flex' ); }
		document.documentElement.style.overflow = 'hidden';
		openModalEl = modal;
		var focusable = $( 'input, [data-zw-close]', modal );
		if ( focusable ) { focusable.focus(); }
		modal.dispatchEvent( new CustomEvent( 'zw:open' ) );
	}

	function closeModal( modal ) {
		modal = modal || openModalEl;
		if ( ! modal ) { return; }
		modal.classList.add( 'hidden' );
		modal.classList.remove( 'flex' );
		document.documentElement.style.overflow = '';
		openModalEl = null;
		modal.dispatchEvent( new CustomEvent( 'zw:close' ) );
		if ( lastFocus && lastFocus.focus ) { lastFocus.focus(); }
	}

	document.addEventListener( 'click', function ( event ) {
		var opener = event.target.closest( '[data-zw-open]' );
		if ( opener ) {
			event.preventDefault();
			openModal( document.getElementById( opener.getAttribute( 'data-zw-open' ) ) );
			return;
		}
		if ( event.target.closest( '[data-zw-close]' ) ) {
			closeModal();
			return;
		}
		// Clic sullo sfondo scuro.
		if ( openModalEl && event.target === openModalEl ) { closeModal(); }
	} );
	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && openModalEl ) { closeModal(); }
	} );

	/* -----------------------------------------------------------------------
	 * Conto alla rovescia degli eventi (ricalcolato nel browser: resta giusto
	 * anche quando la pagina arriva dalla cache)
	 * --------------------------------------------------------------------- */
	$$( '[data-zw-countdown]' ).forEach( function ( el ) {
		var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( el.getAttribute( 'data-zw-countdown' ) || '' );
		if ( ! m ) { return; }
		var now = new Date();
		var days = Math.round( ( new Date( +m[1], +m[2] - 1, +m[3] ) - new Date( now.getFullYear(), now.getMonth(), now.getDate() ) ) / 86400000 );
		var tpl = el.getAttribute( 'data-zw-tpl' );
		var text;
		if ( days < 0 ) {
			text = el.getAttribute( 'data-zw-past' ) || '';
		} else if ( tpl === 'tra' ) {
			text = '• ' + ( days === 0 ? 'oggi' : days === 1 ? 'domani' : 'tra ' + days + ' giorni' );
		} else {
			text = days === 0 ? 'Oggi' : days === 1 ? 'Domani' : 'Mancano ' + days + ' giorni';
		}
		el.textContent = text;
	} );

	/* -----------------------------------------------------------------------
	 * Countdown grande degli eventi: al secondo con l'orario di inizio,
	 * in giorni con la sola data. Poi "In corso" (5 ore) ed "Evento concluso".
	 * --------------------------------------------------------------------- */
	( function () {
		var clocks = $$( '[data-zw-clock]' );
		var boxes = $$( '[data-zw-daysbox]' );
		if ( ! clocks.length && ! boxes.length ) { return; }
		function pad( n ) { return n < 10 ? '0' + n : String( n ); }
		function show( root, which ) {
			[ 'boxes', 'live', 'past' ].forEach( function ( name ) {
				var el = $( '[data-clock-' + name + ']', root );
				if ( el ) { el.hidden = name !== which; }
			} );
		}
		function tick() {
			var now = Date.now();
			clocks.forEach( function ( root ) {
				var start = Date.parse( root.getAttribute( 'data-zw-clock' ) );
				if ( isNaN( start ) ) { return; }
				var diff = Math.floor( ( start - now ) / 1000 );
				if ( diff <= 0 ) { show( root, diff > -5 * 3600 ? 'live' : 'past' ); return; }
				show( root, 'boxes' );
				var parts = { d: Math.floor( diff / 86400 ), h: Math.floor( diff % 86400 / 3600 ), m: Math.floor( diff % 3600 / 60 ), s: diff % 60 };
				Object.keys( parts ).forEach( function ( u ) {
					var el = $( '[data-u="' + u + '"]', root );
					if ( el ) { el.textContent = u === 'd' ? String( parts[ u ] ) : pad( parts[ u ] ); }
				} );
			} );
		}
		boxes.forEach( function ( root ) {
			var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec( root.getAttribute( 'data-zw-daysbox' ) || '' );
			if ( ! m ) { return; }
			var today = new Date();
			var days = Math.round( ( new Date( +m[1], +m[2] - 1, +m[3] ) - new Date( today.getFullYear(), today.getMonth(), today.getDate() ) ) / 86400000 );
			if ( days < 0 ) { show( root, 'past' ); return; }
			show( root, 'boxes' );
			var num = $( '[data-num]', root );
			var lbl = $( '[data-lbl]', root );
			if ( num ) { num.textContent = days > 1 ? String( days ) : ( days === 1 ? 'Domani' : 'Oggi' ); }
			if ( lbl ) { lbl.hidden = days <= 1; }
		} );
		if ( clocks.length ) { tick(); window.setInterval( tick, 1000 ); }
	} )();

	/* -----------------------------------------------------------------------
	 * Player dei video: copertina leggera, il player vero si carica al clic
	 * --------------------------------------------------------------------- */
	$$( '[data-zw-player]' ).forEach( function ( box ) {
		var button = $( 'button', box );
		if ( ! button ) { return; }
		button.addEventListener( 'click', function () {
			var frame = document.createElement( 'iframe' );
			frame.src = box.getAttribute( 'data-zw-player' );
			frame.title = box.getAttribute( 'data-title' ) || 'Video';
			frame.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
			frame.setAttribute( 'allowfullscreen', '' );
			frame.className = 'absolute inset-0 w-full h-full';
			box.innerHTML = '';
			box.appendChild( frame );
		} );
	} );

	/* -----------------------------------------------------------------------
	 * Fasce con content-visibility (gallery, video, footer): all'apertura il browser
	 * non le impagina; a ~1500 px di distanza tornano normali, così le loro immagini
	 * "lazy" partono con il solito anticipo e non si vedono riquadri vuoti.
	 * --------------------------------------------------------------------- */
	( function () {
		var bands = $$( '.zw-cv' );
		if ( ! bands.length || ! ( 'IntersectionObserver' in window ) ) { return; }
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( e ) {
				if ( e.isIntersecting ) { e.target.style.contentVisibility = 'visible'; io.unobserve( e.target ); }
			} );
		}, { rootMargin: '1500px 0px' } );
		bands.forEach( function ( band ) { io.observe( band ); } );
	} )();

	/* -----------------------------------------------------------------------
	 * Blocchi con script in attesa (commenti/Disqus): partono quando il blocco
	 * è a circa uno schermo di distanza. Vedi zw_defer_scripts() in content.php.
	 * --------------------------------------------------------------------- */
	$$( '[data-zw-defer]' ).forEach( function ( box ) {
		var done = false;
		function run() {
			if ( done ) { return; }
			done = true;
			$$( 'script[type="text/zw-deferred"]', box ).forEach( function ( old ) {
				var script = document.createElement( 'script' );
				Array.prototype.forEach.call( old.attributes, function ( attr ) {
					if ( attr.name !== 'type' ) { script.setAttribute( attr.name, attr.value ); }
				} );
				script.text = old.text;
				old.parentNode.replaceChild( script, old );
			} );
		}
		if ( ! ( 'IntersectionObserver' in window ) ) { run(); return; }
		var io = new IntersectionObserver( function ( entries ) {
			if ( entries.some( function ( e ) { return e.isIntersecting; } ) ) { io.disconnect(); run(); }
		}, { rootMargin: '900px 0px' } );
		io.observe( box );
		// Chi apre l'articolo direttamente sui commenti li vede subito.
		if ( /^#(commenti|comments|disqus_thread|comment-)/.test( window.location.hash ) ) { run(); }
	} );

	/* -----------------------------------------------------------------------
	 * Pagina evento: "Carica altre notizie"
	 * --------------------------------------------------------------------- */
	( function () {
		var more = $( '[data-zw-event-more]' );
		var list = $( '#eventFeedList' );
		if ( ! more || ! list || ! window.ZW || ! ZW.rest || ! window.fetch ) { return; }
		var page = 1;
		var busy = false;
		more.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			if ( busy ) { return; }
			busy = true;
			page += 1;
			list.classList.add( 'is-loading' );
			fetch( ZW.rest + 'zw/v1/feed?event=' + more.getAttribute( 'data-zw-event-more' ) + '&page=' + page, { credentials: 'omit' } )
				.then( function ( response ) { if ( ! response.ok ) { throw new Error( 'HTTP ' + response.status ); } return response.json(); } )
				.then( function ( data ) {
					list.insertAdjacentHTML( 'beforeend', data.html );
					more.hidden = ! data.has_more;
				} )
				.catch( function () { window.location.href = more.href; } )
				.then( function () { busy = false; list.classList.remove( 'is-loading' ); } );
		} );
	} )();

	/* -----------------------------------------------------------------------
	 * Primo piano: slider
	 * --------------------------------------------------------------------- */
	( function () {
		var box = $( '#heroBillboard' );
		if ( ! box ) { return; }
		var slides = $$( '[data-hero-slide]', box );
		var dots = $$( '[data-hero-dot]', box );
		if ( slides.length < 2 ) { return; }
		var current = 0;
		var timer = null;

		// Le immagini delle slide successive alla prima arrivano senza src (vedi hero.php).
		function hydrate( slide ) {
			var img = slide && $( 'img[data-zw-src]', slide );
			if ( ! img ) { return; }
			if ( img.getAttribute( 'data-zw-srcset' ) ) { img.srcset = img.getAttribute( 'data-zw-srcset' ); }
			img.src = img.getAttribute( 'data-zw-src' );
			img.removeAttribute( 'data-zw-src' );
			img.removeAttribute( 'data-zw-srcset' );
		}
		function hydrateAll() { slides.forEach( hydrate ); }
		if ( document.readyState === 'complete' ) {
			window.setTimeout( hydrateAll, 300 );
		} else {
			window.addEventListener( 'load', function () { window.setTimeout( hydrateAll, 300 ); } );
		}

		function show( index ) {
			current = ( index + slides.length ) % slides.length;
			hydrate( slides[ current ] );
			slides.forEach( function ( slide, i ) {
				var on = i === current;
				slide.classList.toggle( 'is-active', on );
				slide.setAttribute( 'aria-hidden', on ? 'false' : 'true' );
				slide.tabIndex = on ? 0 : -1;
			} );
			dots.forEach( function ( dot, i ) {
				var on = i === current;
				dot.className = 'h-1.5 rounded-full transition-all ' + ( on ? 'w-9 bg-zw-red' : 'w-4 bg-white/30 hover:bg-white/60' );
				dot.setAttribute( 'aria-current', on ? 'true' : 'false' );
			} );
		}
		function start() {
			if ( reducedMotion || timer ) { return; }
			timer = window.setInterval( function () { show( current + 1 ); }, 6500 );
		}
		function stop() { window.clearInterval( timer ); timer = null; }

		box.addEventListener( 'click', function ( event ) {
			var dot = event.target.closest( '[data-hero-dot]' );
			var step = event.target.closest( '[data-hero-step]' );
			if ( dot ) { show( +dot.getAttribute( 'data-hero-dot' ) ); }
			if ( step ) { show( current + ( +step.getAttribute( 'data-hero-step' ) ) ); }
		} );
		box.addEventListener( 'mouseenter', stop );
		box.addEventListener( 'mouseleave', start );
		box.addEventListener( 'focusin', stop );
		box.addEventListener( 'focusout', start );
		document.addEventListener( 'visibilitychange', function () { document.hidden ? stop() : start(); } );

		// Scorrimento con il dito.
		var touchX = null;
		box.addEventListener( 'touchstart', function ( e ) { touchX = e.touches[0].clientX; stop(); }, { passive: true } );
		box.addEventListener( 'touchend', function ( e ) {
			if ( touchX === null ) { return; }
			var dx = e.changedTouches[0].clientX - touchX;
			if ( Math.abs( dx ) > 45 ) { show( current + ( dx < 0 ? 1 : -1 ) ); }
			touchX = null;
			start();
		}, { passive: true } );

		start();
	} )();

	/* -----------------------------------------------------------------------
	 * Stories
	 * --------------------------------------------------------------------- */
	( function () {
		var bar = $( '#storiesBar' );
		var modal = $( '#storiesModal' );
		var stories = ZW.stories || [];
		if ( ! bar || ! modal || ! stories.length ) { return; }

		var STORY_MS = 6000;
		var fed = 0, slide = 0, timer = null;
		var seen = {};
		try { seen = JSON.parse( store( 'zwStoriesSeen' ) || '{}' ) || {}; } catch ( e ) { seen = {}; }

		var el = {
			segments: $( '#storyProgressSegments' ), avatar: $( '#storyFedAvatar' ), title: $( '#storyFedTitle' ),
			time: $( '#storySlideTime' ), photo: $( '#storyActivePhoto' ), category: $( '#storyActiveCategory' ),
			counter: $( '#storySlideCounter' ), headline: $( '#storyActiveHeadline' ), summary: $( '#storyActiveSummary' ),
			button: $( '#storyReadArticleBtn' ), fedLink: $( '#storyFedLink' )
		};

		function markSeenRings() {
			$$( '.story-btn', bar ).forEach( function ( btn ) {
				var id = btn.getAttribute( 'data-story-id' );
				btn.classList.toggle( 'is-seen', String( seen[ id ] ) === btn.getAttribute( 'data-story-latest' ) );
			} );
		}

		function render() {
			var story = stories[ fed ];
			var item = story.slides[ slide ];
			el.segments.innerHTML = story.slides.map( function ( s, i ) {
				return '<span class="story-seg flex-1 h-1 rounded-full bg-white/25 overflow-hidden ' + ( i < slide ? 'is-done' : i === slide ? 'is-active' : '' ) + '"><span class="story-seg-fill block h-full bg-white"></span></span>';
			} ).join( '' );
			el.segments.style.setProperty( '--story-ms', STORY_MS + 'ms' );
			el.avatar.innerHTML = story.logo ? '<img src="' + encodeURI( story.logo ) + '" alt="' + escapeHtml( story.alt || story.label ).replace( /"/g, '&quot;' ) + '" class="w-full h-full ' + ( 'cover' === story.fit ? 'object-cover' : 'object-contain p-1' ) + '" />' : escapeHtml( story.short );
			el.title.textContent = story.label;
			el.fedLink.href = story.url;
			el.time.textContent = item.time;
			el.category.textContent = item.label;
			el.counter.textContent = ( slide + 1 ) + ' / ' + story.slides.length;
			el.headline.textContent = item.title;
			el.summary.textContent = item.summary;
			el.button.href = item.url;
			if ( item.image ) { el.photo.src = item.image; }
			el.photo.style.visibility = item.image ? 'visible' : 'hidden';

			// Vista l'ultima storia della federazione: il cerchio diventa grigio.
			if ( slide === 0 ) {
				seen[ story.id ] = story.latest;
				store( 'zwStoriesSeen', JSON.stringify( seen ) );
				markSeenRings();
			}
			window.clearTimeout( timer );
			timer = window.setTimeout( function () { step( 1 ); }, STORY_MS );
		}

		function step( dir ) {
			var story = stories[ fed ];
			if ( slide + dir >= 0 && slide + dir < story.slides.length ) {
				slide += dir;
			} else if ( dir > 0 && fed < stories.length - 1 ) {
				fed += 1; slide = 0;
			} else if ( dir < 0 && fed > 0 ) {
				fed -= 1; slide = stories[ fed ].slides.length - 1;
			} else if ( dir > 0 ) {
				closeModal( modal );
				return;
			}
			render();
		}

		function changeFed( dir ) {
			fed = ( fed + dir + stories.length ) % stories.length;
			slide = 0;
			render();
		}

		bar.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest( '[data-story]' );
			if ( ! btn || event.metaKey || event.ctrlKey || event.shiftKey ) { return; }
			event.preventDefault();
			fed = +btn.getAttribute( 'data-story' );
			slide = 0;
			openModal( modal );
			render();
		} );
		$( '#storyTapNext' ).addEventListener( 'click', function () { step( 1 ); } );
		$( '#storyTapPrev' ).addEventListener( 'click', function () { step( -1 ); } );
		$( '#storyNextFed' ).addEventListener( 'click', function () { changeFed( 1 ); } );
		$( '#storyPrevFed' ).addEventListener( 'click', function () { changeFed( -1 ); } );
		modal.addEventListener( 'zw:close', function () { window.clearTimeout( timer ); } );
		document.addEventListener( 'keydown', function ( event ) {
			if ( openModalEl !== modal ) { return; }
			if ( event.key === 'ArrowRight' ) { step( 1 ); }
			if ( event.key === 'ArrowLeft' ) { step( -1 ); }
		} );

		markSeenRings();
	} )();

	/* -----------------------------------------------------------------------
	 * Risultati: cassetto e spoiler
	 * --------------------------------------------------------------------- */
	( function () {
		var toggle = $( '#resultsDrawerToggle' );
		var drawer = $( '#resultsDrawer' );
		var bar = $( '#resultsBar' );
		if ( ! toggle || ! drawer ) { return; }

		toggle.addEventListener( 'click', function () {
			var open = drawer.classList.toggle( 'hidden' ) === false;
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			$( '[data-label]', toggle ).textContent = open ? 'Chiudi' : 'Tutti';
			$( 'svg', toggle ).style.transform = open ? 'rotate(180deg)' : '';
			bar.style.borderRadius = open ? 'var(--card-radius)' : 'var(--pill-radius)';
		} );

		var spoiler = $( '#spoilerToggle' );
		function setSpoilers( hidden ) {
			drawer.classList.toggle( 'spoilers-hidden', hidden );
			spoiler.setAttribute( 'aria-pressed', hidden ? 'true' : 'false' );
			$( '[data-label]', spoiler ).textContent = hidden ? 'Spoiler nascosti' : 'Spoiler visibili';
			$( '[data-dot]', spoiler ).className = 'w-2 h-2 rounded-full ' + ( hidden ? 'bg-zw-red' : 'bg-emerald-400' );
		}
		if ( spoiler ) {
			setSpoilers( store( 'zwSpoilers' ) === 'hidden' );
			spoiler.addEventListener( 'click', function () {
				var hidden = ! drawer.classList.contains( 'spoilers-hidden' );
				setSpoilers( hidden );
				store( 'zwSpoilers', hidden ? 'hidden' : 'visible' );
			} );
		}
	} )();

	/* -----------------------------------------------------------------------
	 * Caroselli orizzontali (frecce)
	 * --------------------------------------------------------------------- */
	document.addEventListener( 'click', function ( event ) {
		var btn = event.target.closest( '[data-scroll]' );
		if ( ! btn ) { return; }
		var track = $( btn.getAttribute( 'data-scroll' ) );
		if ( track ) {
			track.scrollBy( { left: ( +btn.getAttribute( 'data-dir' ) ) * track.clientWidth * 0.8, behavior: reducedMotion ? 'auto' : 'smooth' } );
		}
	} );

	/* -----------------------------------------------------------------------
	 * Feed notizie: filtri, "carica altre", modalità lista
	 * --------------------------------------------------------------------- */
	( function () {
		var list = $( '#newsFeedList' );
		var chips = $( '#feedChips' );
		var more = $( '#loadMoreBtn' );
		if ( ! list ) { return; }

		// Modalità lista (vale per tutte le liste della pagina).
		var layoutButtons = $$( '[data-feed-layout]' );
		function setLayout( mode ) {
			$$( '.zw-feed' ).forEach( function ( feed ) { feed.classList.toggle( 'is-compact', mode === 'compact' ); } );
			layoutButtons.forEach( function ( btn ) {
				var on = btn.getAttribute( 'data-feed-layout' ) === mode;
				btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
				btn.className = 'px-3.5 py-1.5 rounded-full font-sofia font-bold text-xs transition-all ' + ( on ? 'bg-zw-red text-white' : 'text-zw-muted hover:text-zw-ink' );
			} );
		}
		layoutButtons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var mode = btn.getAttribute( 'data-feed-layout' );
				setLayout( mode );
				store( 'zwFeedLayout', mode );
			} );
		} );
		if ( store( 'zwFeedLayout' ) === 'compact' ) { setLayout( 'compact' ); }

		if ( ! ZW.rest || ! window.fetch ) { return; }

		var state = { cat: 0, live: 0, page: 1, busy: false };

		function load( append ) {
			if ( state.busy ) { return; }
			state.busy = true;
			// Filtro: la lista resta al suo posto, solo attenuata (nessuno spostamento della pagina).
			// "Carica altre": il pulsante dice che sta caricando.
			list.classList.add( 'is-loading' );
			list.setAttribute( 'aria-busy', 'true' );
			var moreLabel = more ? more.textContent : '';
			if ( append && more ) {
				more.textContent = 'Caricamento…';
				more.setAttribute( 'aria-busy', 'true' );
				more.classList.add( 'is-loading' );
			}
			var url = ZW.rest + 'zw/v1/feed?cat=' + state.cat + '&page=' + state.page + '&live=' + state.live;
			fetch( url, { credentials: 'omit' } )
				.then( function ( response ) { if ( ! response.ok ) { throw new Error( 'HTTP ' + response.status ); } return response.json(); } )
				.then( function ( data ) {
					if ( append ) {
						list.insertAdjacentHTML( 'beforeend', data.html );
					} else {
						list.innerHTML = data.html || '<p class="bg-white rounded-[22px] nf-shadow-light p-8 text-center text-sm text-zw-muted">Nessuna notizia in questa sezione, per ora.</p>';
					}
					if ( more ) { more.hidden = ! data.has_more; }
				} )
				.catch( function () {
					// In caso di errore il pulsante torna a essere un normale link all'archivio.
					if ( more && append ) { window.location.href = more.href; }
				} )
				.then( function () {
					state.busy = false;
					list.classList.remove( 'is-loading' );
					list.removeAttribute( 'aria-busy' );
					if ( append && more ) {
						more.textContent = moreLabel;
						more.removeAttribute( 'aria-busy' );
						more.classList.remove( 'is-loading' );
					}
				} );
		}

		if ( chips ) {
			chips.addEventListener( 'click', function ( event ) {
				var chip = event.target.closest( '[data-feed-cat]' );
				if ( ! chip || event.metaKey || event.ctrlKey || event.shiftKey ) { return; }
				event.preventDefault();
				state.cat = +chip.getAttribute( 'data-feed-cat' );
				state.live = chip.hasAttribute( 'data-feed-live' ) ? 1 : 0;
				state.page = 1;
				$$( '[data-feed-cat]', chips ).forEach( function ( other ) {
					var on = other === chip;
					other.className = 'px-4 py-2 rounded-full font-sofia font-bold text-xs tracking-wide shrink-0 transition-all flex items-center gap-1.5 ' + chips.getAttribute( on ? 'data-active' : 'data-idle' );
					if ( on ) { other.setAttribute( 'aria-current', 'true' ); } else { other.removeAttribute( 'aria-current' ); }
				} );
				load( false );
			} );
		}

		if ( more ) {
			more.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				state.page += 1;
				load( true );
			} );
		}
	} )();

	/* -----------------------------------------------------------------------
	 * Ricerca: primi risultati mentre si scrive
	 * --------------------------------------------------------------------- */
	( function () {
		var input = $( '#searchBoxInput' );
		var results = $( '#searchResultsList' );
		if ( ! input || ! results || ! ZW.rest || ! window.fetch ) { return; }
		var timer = null, lastQuery = '';

		input.addEventListener( 'input', function () {
			window.clearTimeout( timer );
			var query = input.value.trim();
			if ( query.length < 3 ) { lastQuery = ''; return; }
			timer = window.setTimeout( function () {
				lastQuery = query;
				results.setAttribute( 'aria-busy', 'true' );
				results.innerHTML = '<p class="text-xs text-white/50 py-4 text-center zw-pulse">Cerco «' + escapeHtml( query ) + '»…</p>';
				fetch( ZW.rest + 'wp/v2/search?type=post&subtype=post&per_page=6&_fields=title,url&search=' + encodeURIComponent( query ), { credentials: 'omit' } )
					.then( function ( response ) { return response.ok ? response.json() : []; } )
					.then( function ( items ) {
						if ( query !== lastQuery ) { return; }
						if ( ! items.length ) {
							results.innerHTML = '<p class="text-xs text-white/50 py-4 text-center">Nessun risultato per «' + escapeHtml( query ) + '»</p>';
							return;
						}
						results.innerHTML = items.map( function ( item ) {
							// Il titolo arriva con le entità HTML: lo riportiamo a testo semplice.
							var decoder = document.createElement( 'textarea' );
							decoder.innerHTML = item.title;
							return '<a href="' + encodeURI( item.url ) + '" class="block px-4 py-3 rounded-2xl bg-white/[0.04] hover:bg-white/[0.1] font-sofia font-bold text-sm text-white transition-colors">' + escapeHtml( decoder.value ) + '</a>';
						} ).join( '' ) + '<a href="' + encodeURI( ZW.home ) + '?s=' + encodeURIComponent( query ) + '" class="block text-center text-xs font-sofia font-extrabold text-zw-redSoft py-2">Vedi tutti i risultati →</a>';
					} )
					.catch( function () {
						if ( query === lastQuery ) { results.innerHTML = '<p class="text-xs text-white/50 py-4 text-center">Ricerca non riuscita: premi Invio per la pagina dei risultati.</p>'; }
					} )
					.then( function () { results.removeAttribute( 'aria-busy' ); } );
			}, 260 );
		} );
	} )();

	/* -----------------------------------------------------------------------
	 * Articolo: barra di lettura, condivisione, conteggio letture
	 * --------------------------------------------------------------------- */
	( function () {
		var article = $( '#articleBody' );
		var bar = $( '#readProgress' );
		if ( article && bar ) {
			var ticking = false;
			var update = function () {
				ticking = false;
				var rect = article.getBoundingClientRect();
				var total = rect.height - window.innerHeight * 0.5;
				var done = Math.min( Math.max( -rect.top + window.innerHeight * 0.3, 0 ), total );
				// scaleX invece di width: l'animazione resta sulla GPU, senza ricalcoli di layout.
				bar.style.transform = 'scaleX(' + ( total > 0 ? done / total : 0 ).toFixed( 4 ) + ')';
			};
			var onScroll = function () {
				if ( ! ticking ) { ticking = true; window.requestAnimationFrame( update ); }
			};
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			window.addEventListener( 'resize', onScroll );
			update();
		}

		function copyLink( btn ) {
			var done = function () {
				btn.classList.add( '!bg-zw-red', '!text-white' );
				window.setTimeout( function () { btn.classList.remove( '!bg-zw-red', '!text-white' ); }, 1400 );
			};
			if ( navigator.clipboard ) { navigator.clipboard.writeText( window.location.href ).then( done, done ); } else { done(); }
		}
		document.addEventListener( 'click', function ( event ) {
			var copy = event.target.closest( '[data-zw-copy]' );
			var share = event.target.closest( '[data-zw-share]' );
			if ( copy ) { copyLink( copy ); }
			if ( share ) {
				if ( navigator.share ) {
					navigator.share( { title: document.title, url: window.location.href } ).catch( function () {} );
				} else {
					copyLink( share );
				}
			}
		} );

		// Una lettura per articolo e per sessione di navigazione.
		if ( ZW.postId && ZW.views && ZW.rest ) {
			var key = 'zwView' + ZW.postId;
			var counted = false;
			try { counted = window.sessionStorage.getItem( key ) === '1'; } catch ( e ) {}
			if ( ! counted && ! /bot|crawl|spider|lighthouse|headless/i.test( navigator.userAgent ) ) {
				window.setTimeout( function () {
					var url = ZW.rest + 'zw/v1/view/' + ZW.postId;
					if ( ! ( navigator.sendBeacon && navigator.sendBeacon( url ) ) && window.fetch ) {
						fetch( url, { method: 'POST', keepalive: true, credentials: 'omit' } ).catch( function () {} );
					}
					try { window.sessionStorage.setItem( key, '1' ); } catch ( e ) {}
				}, 3000 );
			}
		}
	} )();

	/* -----------------------------------------------------------------------
	 * Podcast: lettore audio (solo se l'episodio ha un file audio)
	 * --------------------------------------------------------------------- */
	( function () {
		var player = $( '#podcastPlayer' );
		if ( ! player ) { return; }
		var section = $( '#podcast' );
		var audio = null;
		var speeds = [ 1, 1.25, 1.5, 2 ];
		var speedIndex = 0;
		var fill = $( '[data-pod-fill]', player ), barEl = $( '[data-pod-bar]', player );
		var current = $( '[data-pod-current]', player ), total = $( '[data-pod-total]', player );

		function format( seconds ) {
			if ( ! isFinite( seconds ) ) { return '--:--'; }
			var m = Math.floor( seconds / 60 ), s = Math.floor( seconds % 60 );
			return m + ':' + ( s < 10 ? '0' : '' ) + s;
		}
		function ensureAudio() {
			if ( audio ) { return audio; }
			// Il file viene richiesto solo al primo "play".
			audio = new Audio( player.getAttribute( 'data-src' ) );
			audio.preload = 'metadata';
			audio.addEventListener( 'timeupdate', function () {
				var pct = audio.duration ? ( audio.currentTime / audio.duration ) * 100 : 0;
				fill.style.width = pct + '%';
				barEl.setAttribute( 'aria-valuenow', Math.round( pct ) );
				current.textContent = format( audio.currentTime );
			} );
			audio.addEventListener( 'loadedmetadata', function () { total.textContent = format( audio.duration ); } );
			[ 'play', 'pause', 'ended' ].forEach( function ( name ) {
				audio.addEventListener( name, function () {
					var playing = ! audio.paused && ! audio.ended;
					section.classList.toggle( 'podcast-is-playing', playing );
					$( '[data-icon-play]', player ).hidden = playing;
					$( '[data-icon-pause]', player ).hidden = ! playing;
				} );
			} );
			return audio;
		}

		player.addEventListener( 'click', function ( event ) {
			var skip = event.target.closest( '[data-pod-skip]' );
			if ( event.target.closest( '[data-pod-play]' ) ) {
				var a = ensureAudio();
				if ( a.paused ) { a.play().catch( function () {} ); } else { a.pause(); }
			} else if ( skip && audio ) {
				audio.currentTime = Math.max( 0, audio.currentTime + ( +skip.getAttribute( 'data-pod-skip' ) ) );
			} else if ( event.target.closest( '[data-pod-speed]' ) ) {
				speedIndex = ( speedIndex + 1 ) % speeds.length;
				ensureAudio().playbackRate = speeds[ speedIndex ];
				event.target.closest( '[data-pod-speed]' ).textContent = String( speeds[ speedIndex ] ).replace( '.', ',' ) + 'x';
			} else if ( event.target.closest( '[data-pod-bar]' ) && audio && audio.duration ) {
				var rect = barEl.getBoundingClientRect();
				audio.currentTime = Math.max( 0, Math.min( 1, ( event.clientX - rect.left ) / rect.width ) ) * audio.duration;
			}
		} );
	} )();
} )();
