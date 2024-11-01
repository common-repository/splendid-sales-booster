/* global wcssbProductCrossSells */

jQuery(document).ready(function($){

	var product_template 	= wp.template( 'wcssb-product-row' ),
		products 			= wcssbProductCrossSells.products;

	var wcssbReinitWcElements = function(){

		$( document.body )
			.trigger( 'wc-enhanced-select-init' )
			.trigger( 'init_tooltips' );

	}

	if ( Object.keys( products ).length ) {
		$.each( products, function( parent_key, parent_products ) {

			$.each( parent_products, function( i, product_data ){

				$('.js-wcssb-products[data-parent-key="' + parent_key + '"]').append( product_template( product_data ) );

			});

		});
	}

	$(document.body)
		.on( 'click', '.js-wcssb-add-product-row-button', function(e){

			e.preventDefault();

			var $button = $(this);

			var $products = $(this).siblings('.js-wcssb-products');

			var row_key = new Date().getTime() + ( ( ( 1 + Math.random() ) * 0x10000 ) | 0 ).toString(8);

			var new_product_row_data = {
				row_key: row_key,
				parent_key: $products.attr( 'data-parent-key' ),
				product_id: '',
				product_name: '',
	            product_cta: '',
	            is_new_row: true,
			};

		   $products
		    	.append( product_template( new_product_row_data ) )
		    	.sortable( 'refresh' );

		    $( document.body ).trigger( 'wcssb_reinit_wc_elements' );

		})
		.on( 'click', '.js-wcssb-remove-product-row', function(e){

			e.preventDefault();

			if ( window.confirm( wcssbProductCrossSells.strings.i18n_remove_product_row ) ) {
				$(this).parents('.js-wcssb-product-row').remove();
			}

		})
		.on( 'wcssb_reinit_wc_elements', function(e){

			wcssbReinitWcElements();

		});

	$('.js-wcssb-products').sortable({
		axis: 'y',
		handle: '.js-wcssb-product-row-head',
		create: function(){
			$( document.body ).trigger( 'wcssb_reinit_wc_elements' );
		}
	});

});
