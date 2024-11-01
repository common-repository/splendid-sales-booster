/* global wcssbDrawersSettings */
var wcssbDrawersSettings = window.wcssbDrawersSettings;

jQuery( document ).ready( function( $ ) {

	// Set custom CSS property with current viewport height
	var vh = window.innerHeight * 0.01;
	document.documentElement.style.setProperty( '--wcssb-vh', `${vh}px` );

	window.addEventListener( 'resize', function(){
		var vh = window.innerHeight * 0.01;
		document.documentElement.style.setProperty( '--wcssb-vh', `${vh}px`) ;
	});

	// Prevent body scrolling
	function wcssbPreventBodyTouchScroll( e ){
		var changed_touch = event.changedTouches[0];
		var elem = document.elementFromPoint( changed_touch.clientX, changed_touch.clientY );
		var $elem = $(elem);

		// Allow scrolling and touch events for only selected elements
		if ( ! ( $elem.is( '.js-wcssb-allow-scroll' ) || $elem.parents().is( '.js-wcssb-allow-scroll' ) || $elem.is( '.js-wcssb-drawer-close' ) ) ) {
			e.preventDefault();
		}
	}

	// Force prevent body scrolling
	function wcssbForcePreventBodyTouchScroll( e ) {
		e.preventDefault();
	}

	// Disable body scrolling (eg. when drawer is open)
	var window_offset_y = 0;

	var wcssbDisableBodyScroll = function(){

		window_offset_y = window.pageYOffset;

		$( document.body ).addClass( 'wcssb-no-scroll' );

		var page_offset_y = window_offset_y;

	  	if ( $( '#wpadminbar:visible' ).length ) {
			page_offset_y -= $( '#wpadminbar:visible' ).outerHeight();
		}

	    $( document.body ).css( 'top', ( - page_offset_y ) + 'px' );

	    // Extra prevent body touch scrolling on iOS
	    if ( navigator.userAgent.indexOf( 'iOS' ) != -1 || navigator.userAgent.indexOf( 'iPhone' ) != -1 || navigator.userAgent.indexOf( 'iPad' ) != -1 ) {

		    document.body.addEventListener( 'touchmove', wcssbPreventBodyTouchScroll, { passive: false } );
		    document.body.addEventListener( 'pointermove', wcssbForcePreventBodyTouchScroll, { passive: false } );
		    document.body.addEventListener( 'touchforcechange', wcssbPreventBodyTouchScroll, { passive: false } );
		}
	}

	// Restore body scrolling
	var wcssbEnableBodyScroll = function(){
		$( document.body ).removeClass( 'wcssb-no-scroll' ).css( 'top', '' );

		window.scrollTo( { left: 0, top: window_offset_y, behavior: 'instant' } );

		document.body.removeEventListener( 'touchmove', wcssbPreventBodyTouchScroll );
		document.body.removeEventListener( 'pointermove', wcssbForcePreventBodyTouchScroll );
		document.body.removeEventListener( 'touchforcechange', wcssbPreventBodyTouchScroll );
	}

	// Open drawer and show its mask
	var wcssbOpenDrawer = function( drawer, attrs ){
		drawer = typeof drawer !== 'undefined' ? drawer : false ;

		if ( ! drawer ) {
			return;
		}

		attrs = typeof attrs !== 'undefined' ? attrs : {} ;

		wcssbShowDrawerMask();

		$( '.js-wcssb-drawer[data-wcssb-drawer="' + drawer + '"]' ).show().addClass( 'active' );

		$( document.body ).trigger( 'wcssb_drawer_opened', [drawer, attrs] );
	}

	// Close drawer and hide its mask
	var wcssbCloseDrawer = function( drawer ){
		drawer = typeof drawer !== 'undefined' ? drawer : false ;

		wcssbHideDrawerMask();

		if ( drawer ) {
			$( '.js-wcssb-drawer[data-wcssb-drawer="' + drawer + '"]' ).removeClass( 'active' );

			$( document.body ).trigger( 'wcssb_drawer_closed', drawer );
		} else {
			$( document.body ).find( '.js-wcssb-drawer.active' ).each( function( index ){
				var $this = $(this);
				$( document.body ).trigger( 'wcssb_drawer_closed', $this.attr( 'data-wcssb-drawer' ) );
			});

			// Hide all drawers
			$( '.js-wcssb-drawer' ).removeClass( 'active' );
		}
	}

	// Close drawer when clicking on its x button
	$( document.body ).on( 'click', '.js-wcssb-drawer-close', function( e ){
		var drawer = $( this ).parents( '.js-wcssb-drawer' ).attr( 'data-wcssb-drawer' );
		wcssbCloseDrawer( drawer );
	});

	// Show drawer mask
	var wcssbShowDrawerMask = function(){
		$( '.js-wcssb-drawer-mask' ).addClass( 'active' );

		$( document.body ).trigger( 'wcssb_drawer_mask_showed' );

		// Prevent body scrolling when drawer is open
		wcssbDisableBodyScroll();

		// Disable zoom on input focus on iOS
		if ( navigator.userAgent.indexOf( 'iOS' ) != -1 || navigator.userAgent.indexOf( 'iPhone' ) != -1 || navigator.userAgent.indexOf( 'iPad' ) != -1 ) {
			var meta_vieport = document.querySelector( 'meta[name=viewport]' );

			if ( meta_vieport !== null ) {
				var content = meta_vieport.getAttribute( 'content' ),
					re = /maximum\-scale=[0-9\.]+/g;

				if ( re.test( content ) ) {
					content = content.replace( re, 'maximum-scale=1.0' );
				} else {
					content = [content, 'maximum-scale=1.0'].join( ', ' );
				}

				meta_vieport.setAttribute( 'content', content );
			}
		}
	}

	// Hide drawer mask
	var wcssbHideDrawerMask = function(){
		$( document.body ).find( '.js-wcssb-drawer.active' ).each( function( index ){
			var $this = $( this );
			$( document.body ).trigger( 'wcssb_drawer_mask_hidden' );
		});

		$( '.js-wcssb-drawer-mask' ).removeClass( 'active' );

		// Restore body scrolling when drawer is open
		wcssbEnableBodyScroll();
	}

	// Close all drawers when clicking on mask below when visible
	$( document.body ).on( 'click', '.js-wcssb-drawer-mask.active', function( e ){
		if ( ! $( this ).hasClass( 'active' ) ) {
			return;
		}

		wcssbCloseDrawer();
	});

	// Add To Cart drawer
	if ( typeof wcssbDrawersSettings.drawers.add_to_cart !== 'undefined' ) {

		var add_to_cart_drawer_product_id  = 0,
			$add_to_cart_drawer            = $( '.js-wcssb-drawer[data-wcssb-drawer="add_to_cart"]' ),
			$drawer_section                = $add_to_cart_drawer.find( '.js-wcssb-drawer-section' ),
			$drawer_products               = $drawer_section.find( 'table.js-wcssb-drawer-cross-sells tbody' ),
			$drawer_section_title          = $drawer_section.find( '.js-wcssb-drawer-section-title' );

		// Process Add To Cart drawer on open
		$( document.body ).on( 'wcssb_drawer_opened', function( e, drawer, attrs ){
			if ( 'add_to_cart' != drawer ) {
				return;
			}

			force_api_request = 'undefined' === typeof attrs.force_api_request ? false : attrs.force_api_request;

			$message = 'undefined' === typeof attrs.$message ? false : attrs.$message;

			add_to_cart_drawer_product_id = $message.find( 'span[data-wcssb-added-product-id]' ).attr( 'data-wcssb-added-product-id' );

			$add_to_cart_drawer.find( '.js-wcssb-drawer-title' ).html( $message.html() );

			$drawer_section_title.find( 'h3' ).html( '' );
			$drawer_products.html( '' );

			if( ! force_api_request ) {
				if ( 'undefined' !== typeof wcssbDrawersSettings.drawers.add_to_cart.section ) {
					wcssbProcessAddToCartDrawerSection( wcssbDrawersSettings.drawers.add_to_cart.section );

					return;
				}
			}

			$drawer_section.removeClass( 'is-empty' ).addClass( 'loading' );

			wcssb.apiRequest( {
				path: 'wc/v3/wcssb-section/' + add_to_cart_drawer_product_id,
				method: 'GET',
			} )
			.done( function( response ) {
				var section = response;

				wcssbProcessAddToCartDrawerSection( section );
			} )
			.fail( function( response ) {
				$drawer_section.removeClass( 'loading' ).addClass( 'is-empty' );
			} );
		});

		// Process Add to cart drawer section
		var wcssbProcessAddToCartDrawerSection = function( section ) {
			if ( 'undefined' === typeof section.products || ! section.products.length ) {
				$drawer_section.removeClass( 'loading' ).addClass( 'is-empty' );

				return false;
			}

			if ( 'undefined' === typeof section.section_title || ! section.section_title ) {
				$drawer_section_title.hide();
			} else {
				$drawer_section_title.find( 'h3' ).html( section.section_title );
				$drawer_section_title.show();
			}

			var drawer_product_row_template = window.wp.template( 'wcssb-drawer-product-row' );

			$.each( section.products, function( key, product ) {
				product.adding_with_product_id = add_to_cart_drawer_product_id;

				$drawer_products.append( drawer_product_row_template( product ) );
			} );

			$( document.body ).trigger( 'wcssb_add_to_cart_drawer_section_processed', [ $add_to_cart_drawer ] );

			$drawer_section.removeClass( 'loading' );
		}

		// Toggle Add to cart drawer header shadow on scroll
		function wcssbBrowserSupportsPositionSticky() {
			var prop = 'position:',
				value = 'sticky',
				prefixes = ' -webkit- -moz- -o- -ms- '.split(' ');

			var el = document.createElement( 'a' );
			var mStyle = el.style;
			mStyle.cssText = prop + prefixes.join( value + ';' + prop ).slice( 0, - prop.length );

			return mStyle.position.indexOf( value ) !== -1;
		};

		browser_supports_postion_sticky = wcssbBrowserSupportsPositionSticky();

		if ( browser_supports_postion_sticky ) {
			$( '.js-wcssb-drawer' ).on( 'scroll', function( e ){
				var $drawer_wrap = $( this );

				$drawer_wrap.find( '.js-wcssb-drawer-header' ).toggleClass( 'is-stuck', $drawer_wrap.scrollTop() > 0 );
			});
		}

		// Open Add to cart drawer on page load if a notice is found
		if ( $( '.js-wcssb-add-to-cart-message' ).length ) {
			var $message = $( '.js-wcssb-add-to-cart-message' ).eq( 0 );

			wcssbOpenDrawer( 'add_to_cart', { '$message': $message, 'force_api_request': false } );
		}

		// Open Add to cart drawer on AJAX product added to Cart
		$( document.body ).on( 'added_to_cart', function( e, fragments ) {
			var message = fragments['.js-wcssb-ajax-added-to-cart'];

			if ( 'undefined' === typeof message ) {
				return;
			}

			var $message = $( message );

			wcssbOpenDrawer( 'add_to_cart', { '$message': $message, 'force_api_request': true } );
		});

		// Add spacing between Add to cart drawer header buttons
		$( document.body ).on( 'wcssb_cart_button_updated', function( e, $button ){
			if ( $button.parent().find( '.js-wcssb-buttons-spacer' ).length ) {
				return;
			}

			$button.after( '<div class="js-wcssb-buttons-spacer wcssb-buttons-spacer"></div>' );
		});

		// Close Add to cart drawer insted of page refresh on Cart page
		$( document.body ).on( 'click', 'a.js-wcssb-forward', function( e ){
			if ( ! wcssbDrawersSettings.drawers.add_to_cart.is_cart ) {
				return;
			}

			e.preventDefault();

			wcssbCloseDrawer( 'add_to_cart' );
		});
	}

});


/**
 * Thin jQuery.ajax wrapper for WP REST API requests
 * based on code from `wp-api-request.js` included in WP core.
 */
( function( $ ) {
	function apiRequest( options ) {
		options = apiRequest.buildAjaxOptions( options );
		return apiRequest.transport( options );
	}

	apiRequest.buildAjaxOptions = function( options ) {
		var url = options.url;
		var path = options.path;
		var method = options.method;
		var namespaceTrimmed, endpointTrimmed, apiRoot;
		var headers;

		if (
			typeof options.namespace === 'string' &&
			typeof options.endpoint === 'string'
		) {
			namespaceTrimmed = options.namespace.replace( /^\/|\/$/g, '' );
			endpointTrimmed = options.endpoint.replace( /^\//, '' );
			if ( endpointTrimmed ) {
				path = namespaceTrimmed + '/' + endpointTrimmed;
			} else {
				path = namespaceTrimmed;
			}
		}
		if ( typeof path === 'string' ) {
			apiRoot = wcssbDrawersSettings.rest_url;
			path = path.replace( /^\//, '' );

			// API root may already include query parameter prefix
			// if site is configured to use plain permalinks.
			if ( 'string' === typeof apiRoot && -1 !== apiRoot.indexOf( '?' ) ) {
				path = path.replace( '?', '&' );
			}

			url = apiRoot + path;
		}

		headers = options.headers || {};

		headers = $.extend( {
			'Accept': 'application/json, */*;q=0.1'
		}, headers );

		if ( typeof method === 'string' ) {
			method = method.toUpperCase();
		}

		// Do not mutate the original options object.
		options = $.extend( {}, options, {
			headers: headers,
			url: url,
			method: method
		} );

		delete options.path;
		delete options.namespace;
		delete options.endpoint;

		return options;
	};

	apiRequest.transport = $.ajax;

	/** @namespace wcssb */
	window.wcssb = window.wcssb || {};
	window.wcssb.apiRequest = apiRequest;
} )( jQuery );


/**
 * Handle adding drawer Cross-sell products to Cart via WC AJAX
 * based on code from `add-to-cart.js` included in WooCommerce plugin.
 */
jQuery( function( $ ) {

	if ( typeof wcssbDrawersSettings.drawers.add_to_cart === 'undefined' ) {
		return false;
	}

	/**
	 * wcssbAddToCartHandler class.
	 */
	var wcssbAddToCartHandler = function() {
		this.requests   = [];
		this.addRequest = this.addRequest.bind( this );
		this.run        = this.run.bind( this );

		$( document.body )
			.on( 'click', '.add_to_cart_button', { addToCartHandler: this }, this.onAddToCart )
			.on( 'added_to_cart', this.updateButton )
			.on( 'added_to_cart removed_from_cart', { addToCartHandler: this }, this.updateFragments );
	};

	/**
	 * Add add to cart event.
	 */
	wcssbAddToCartHandler.prototype.addRequest = function( request ) {
		this.requests.push( request );

		if ( 1 === this.requests.length ) {
			this.run();
		}
	};

	/**
	 * Run add to cart events.
	 */
	wcssbAddToCartHandler.prototype.run = function() {
		var requestManager = this,
			originalCallback = requestManager.requests[0].complete;

		requestManager.requests[0].complete = function() {
			if ( typeof originalCallback === 'function' ) {
				originalCallback();
			}

			requestManager.requests.shift();

			if ( requestManager.requests.length > 0 ) {
				requestManager.run();
			}
		};

		$.ajax( this.requests[0] );
	};

	/**
	 * Handle the add to cart event.
	 */
	wcssbAddToCartHandler.prototype.onAddToCart = function( e ) {
		var $thisbutton = $( this );

		if ( $thisbutton.is( '.js-wcssb-ajax-add-to-cart' ) ) {
			if ( ! $thisbutton.attr( 'data-product_id' ) ) {
				return true;
			}

			e.preventDefault();

			$thisbutton.removeClass( 'added' );
			$thisbutton.addClass( 'loading' );

			var data = {};

			// Fetch changes that are directly added by calling $thisbutton.data( key, value )
			$.each( $thisbutton.data(), function( key, value ) {
				data[ key ] = value;
			});

			// Fetch data attributes in $thisbutton. Give preference to data-attributes because they can be directly modified by javascript
			// while `.data` are jquery specific memory stores.
			$.each( $thisbutton[0].dataset, function( key, value ) {
				data[ key ] = value;
			});

			// Trigger event.
			$( document.body ).trigger( 'adding_to_cart', [ $thisbutton, data ] );

			e.data.addToCartHandler.addRequest({
				type: 'POST',
				url: wcssbDrawersSettings.drawers.add_to_cart.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' ),
				data: data,
				success: function( response ) {
					if ( ! response ) {
						return;
					}

					if ( response.error && response.product_url ) {
						window.location = response.product_url;
						return;
					}

					// Redirect to cart option
					if ( wcssbDrawersSettings.drawers.add_to_cart.cart_redirect_after_add === 'yes' && ! wcssbDrawersSettings.drawers.add_to_cart.is_cart ) {
						window.location = wcssbDrawersSettings.drawers.add_to_cart.cart_url;
						return;
					}

					// Trigger event so themes can refresh other areas.
					$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $thisbutton ] );
				},
				dataType: 'json'
			});
		}
	};

	/**
	 * Update cart page elements after add to cart events.
	 */
	wcssbAddToCartHandler.prototype.updateButton = function( e, fragments, cart_hash, $button ) {
		$button = typeof $button === 'undefined' ? false : $button;

		if ( $button && $button.is( '.js-wcssb-ajax-add-to-cart' ) ) {
			$button.removeClass( 'loading' );

			if ( fragments ) {
				$button.addClass( 'added' );
			}

			// View cart text.
			if ( fragments && $button.parent().find( '.added_to_cart' ).length === 0 ) {
				$button.after( '<a href="' + wcssbDrawersSettings.drawers.add_to_cart.cart_url + '" class="added_to_cart wc-forward js-wcssb-forward" title="' +
					wcssbDrawersSettings.drawers.add_to_cart.i18n_view_cart + '">' + wcssbDrawersSettings.drawers.add_to_cart.i18n_view_cart + '</a>' );
			}

			$( document.body ).trigger( 'wc_cart_button_updated', [ $button ] );

			$( document.body ).trigger( 'wcssb_cart_button_updated', [ $button ] );
		}
	};

	/**
	 * Update fragments after add to cart events.
	 */
	wcssbAddToCartHandler.prototype.updateFragments = function( e, fragments ) {
		if ( fragments ) {
			$.each( fragments, function( key ) {
				$( key )
					.addClass( 'updating' )
					.fadeTo( '400', '0.6' )
					.block({
						message: null,
						overlayCSS: {
							opacity: 0.6
						}
					});
			});

			$.each( fragments, function( key, value ) {
				$( key ).replaceWith( value );
				$( key ).stop( true ).css( 'opacity', '1' ).unblock();
			});

			$( document.body ).trigger( 'wc_fragments_loaded' );
		}
	};

	/**
	 * Init wcssbAddToCartHandler.
	 */
	new wcssbAddToCartHandler();
});
