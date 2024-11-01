/*global wcssbAddToCartVariationSettings */

/**
 * Handle adding variable Cross-sell products to Cart
 * based on code from `add-to-cart-variation.js` included in WooCommerce plugin.
 */
;(function ( $, window, document, undefined ) {
	/**
	 * wcssbVariationForm class which handles variation forms and attributes.
	 */
	var wcssbVariationForm = function( $form ) {
		var self = this;

		self.$form                 = $form;
		self.$attributeFields      = $form.find( '.js-wcssb-variations select' );
		self.$resetVariations      = $form.find( '.js-wcssb-reset-variations' );
		self.$wcssbItem            = $form.closest( '.js-wcssb-cross-sell-item' );
		self.$wcssbProduct         = self.$wcssbItem.find( '.js-wcssb-cross-sell-item-product' );
		self.$wcssbCheckbox        = self.$wcssbItem.find( 'input[name="wcssb-add-to-cart[]"]' );
		self.$wcssbAddToCartButton = self.$wcssbItem.find( '.add_to_cart_button' );
		self.variationData         = $form.data( 'product_variations' );
		self.loading               = true;

		// Initial state.
		self.$form.off( '.wcssb-variation-form' );

		// Methods.
		self.getChosenAttributes    = self.getChosenAttributes.bind( self );
		self.findMatchingVariations = self.findMatchingVariations.bind( self );
		self.isMatch                = self.isMatch.bind( self );
		self.toggleResetLink        = self.toggleResetLink.bind( self );

		// Events.
		$form.on( 'click.wcssb-variation-form', '.js-wcssb-reset-variations a', { variationForm: self }, self.onReset );
		$form.on( 'wcssb_hide_variation', { variationForm: self }, self.onHide );
		$form.on( 'wcssb_show_variation', { variationForm: self }, self.onShow );
		$form.on( 'wcssb_reset_data', { variationForm: self }, self.onResetDisplayedVariation );
		$form.on( 'change.wcssb-variation-form', '.js-wcssb-variations select', { variationForm: self }, self.onChange );
		$form.on( 'wcssb_found_variation.wcssb-variation-form', { variationForm: self }, self.onFoundVariation );
		$form.on( 'wcssb_check_variations.wcssb-variation-form', { variationForm: self }, self.onFindVariation );
		$form.on( 'wcssb_update_variation_values.wcssb-variation-form', { variationForm: self }, self.onUpdateAttributes );
		self.$wcssbCheckbox.on( 'change', { variationForm: self }, self.onCheckboxChange );
		self.$wcssbAddToCartButton.on( 'click', { variationForm: self }, self.onAddToCart );

		// Init after gallery.
		setTimeout( function() {
			$form.trigger( 'wcssb_check_variations' );
			self.loading = false;
		}, 100 );
	};

	/**
	 * Reset all fields.
	 */
	wcssbVariationForm.prototype.onReset = function( event ) {
		event.preventDefault();
		event.data.variationForm.$attributeFields.val( '' ).trigger( 'change' );
		event.data.variationForm.$form.trigger( 'wcssb_reset_data' );
	};

	/**
	 * When a variation is hidden.
	 */
	wcssbVariationForm.prototype.onHide = function( event ) {
		event.preventDefault();

		event.data.variationForm.$wcssbItem
			.find( '.add_to_cart_button' )
			.addClass( 'disabled' );

		if ( event.data.variationForm.$wcssbCheckbox.is( ':checked' ) ) {
			event.data.variationForm.$wcssbItem.addClass( 'wcssb-invalid' );
		}
	};

	/**
	 * When a variation is shown.
	 */
	wcssbVariationForm.prototype.onShow = function( event, variation ) {
		event.preventDefault();

		event.data.variationForm.$wcssbItem
			.find( '.add_to_cart_button' )
			.removeClass( 'disabled' );

		event.data.variationForm.$wcssbItem.removeClass( 'wcssb-invalid' );
	};

	/**
	 * When the cart button is pressed.
	 */
	wcssbVariationForm.prototype.onAddToCart = function( event ) {
		if ( $( this ).is('.disabled') ) {
			event.preventDefault();

			if ( ! $( this ).val() ) {
				window.alert( wcssbAddToCartVariationSettings.i18n_make_a_selection_text );

				return false;
			}
		}
	};

	/**
	 * When the product checkbox is clicked.
	 */
	wcssbVariationForm.prototype.onCheckboxChange = function( event ) {
		var $checkbox  = $( this ),
			$wcssbItem = event.data.variationForm.$wcssbItem;

		if ( $checkbox.is( ':checked' ) ) {
			if ( parseInt( $checkbox.val() ) > 0 ) {
				$wcssbItem.removeClass( 'wcssb-invalid' );
			} else {
				$wcssbItem.addClass( 'wcssb-invalid' );
			}
		} else {
			$wcssbItem.removeClass( 'wcssb-invalid' );
		}
	};

	/**
	 * When displayed variation data is reset.
	 */
	wcssbVariationForm.prototype.onResetDisplayedVariation = function( event ) {
		var form = event.data.variationForm;
		form.$wcssbItem.find( '.js-wcssb-cross-sell-item-price' ).wcssb_reset_content();
		form.$wcssbProduct.find( '.stock' ).slideUp( 200, function() {
			$( this ).remove();
		});
		form.$form.trigger( 'wcssb_hide_variation' );
	};

	/**
	 * Looks for matching variations for current selected attributes.
	 */
	wcssbVariationForm.prototype.onFindVariation = function( event, chosenAttributes ) {
		var form              = event.data.variationForm,
			attributes        = 'undefined' !== typeof chosenAttributes ? chosenAttributes : form.getChosenAttributes(),
			currentAttributes = attributes.data;

		if ( attributes.count && attributes.count === attributes.chosenCount ) {
			form.$form.trigger( 'wcssb_update_variation_values' );

			var matching_variations = form.findMatchingVariations( form.variationData, currentAttributes ),
				variation           = matching_variations.shift();

			if ( variation ) {
				form.$form.trigger( 'wcssb_found_variation', [ variation ] );
			} else {
				form.$form.trigger( 'wcssb_reset_data' );
				attributes.chosenCount = 0;
			}
		} else {
			form.$form.trigger( 'wcssb_update_variation_values' );
			form.$form.trigger( 'wcssb_reset_data' );
		}

		// Show reset link.
		form.toggleResetLink( attributes.chosenCount > 0 );
	};

	/**
	 * Triggered when a variation has been found which matches all attributes.
	 */
	wcssbVariationForm.prototype.onFoundVariation = function( event, variation ) {
		var form   = event.data.variationForm,
			$price = form.$wcssbItem.find( '.js-wcssb-cross-sell-item-price' );

		if ( variation.discounted_price_html ) {
			$price.wcssb_set_content( variation.discounted_price_html );
		} else {
			$price.wcssb_reset_content();
		}

		form.$wcssbProduct.find( '.stock' ).slideUp( 200, function() {
			$( this ).remove();
		});

		if ( 'onbackorder' == variation.stock_status ) {
			$( variation.stock_html ).hide().insertAfter( $price ).slideDown( 200 );
		}

		form.$wcssbCheckbox.val( variation.variation_id ).prop( 'checked', ! form.loading ).trigger( 'change' );

		form.$wcssbAddToCartButton.attr( 'data-product_id', variation.variation_id ).attr( 'data-product_sku', variation.sku ).trigger( 'change' );

		form.$form.trigger( 'wcssb_show_variation', [ variation ] );
	};

	/**
	 * Triggered when an attribute field changes.
	 */
	wcssbVariationForm.prototype.onChange = function( event ) {
		var form = event.data.variationForm;

		form.$wcssbCheckbox.val( '' ).trigger( 'change' );

		form.$wcssbAddToCartButton.attr( 'data-product_id', '' ).attr( 'data-product_sku', '' ).trigger( 'change' );

		form.$form.trigger( 'wcssb_check_variations' );
	};

	/**
	 * Updates attributes in the DOM to show valid values.
	 */
	wcssbVariationForm.prototype.onUpdateAttributes = function( event ) {
		var form              = event.data.variationForm,
			attributes        = form.getChosenAttributes(),
			currentAttributes = attributes.data;

		// Loop through selects and disable/enable options based on selections.
		form.$attributeFields.each( function( index, el ) {
			var current_attr_select     = $( el ),
				current_attr_name       = current_attr_select.data( 'attribute_name' ),
				option_gt_filter        = ':gt(0)',
				attached_options_count  = 0,
				new_attr_select         = $( '<select/>' ),
				selected_attr_val       = current_attr_select.val() || '',
				selected_attr_val_valid = true;

			// Reference options set at first.
			if ( ! current_attr_select.data( 'attribute_html' ) ) {
				var refSelect = current_attr_select.clone();

				refSelect.find( 'option' ).removeAttr( 'attached' ).prop( 'disabled', false ).prop( 'selected', false );

				// Legacy data attribute.
				current_attr_select.data(
					'attribute_options',
					refSelect.find( 'option' + option_gt_filter ).get()
				);
				current_attr_select.data( 'attribute_html', refSelect.html() );
			}

			new_attr_select.html( current_attr_select.data( 'attribute_html' ) );

			// The attribute of this select field should not be taken into account when calculating its matching variations:
			// The constraints of this attribute are shaped by the values of the other attributes.
			var checkAttributes = $.extend( true, {}, currentAttributes );

			checkAttributes[ current_attr_name ] = '';

			var variations = form.findMatchingVariations( form.variationData, checkAttributes );

			// Loop through variations.
			for ( var num in variations ) {
				if ( typeof( variations[ num ] ) !== 'undefined' ) {
					var variationAttributes = variations[ num ].attributes;

					for ( var attr_name in variationAttributes ) {
						if ( variationAttributes.hasOwnProperty( attr_name ) ) {
							var attr_val         = variationAttributes[ attr_name ],
								variation_active = '';

							if ( attr_name === current_attr_name ) {
								variation_active = 'enabled';

								if ( attr_val ) {
									// Decode entities.
									attr_val = $( '<div/>' ).html( attr_val ).text();

									// Attach to matching options by value. This is done to compare
									// TEXT values rather than any HTML entities.
									var $option_elements = new_attr_select.find( 'option' );
									if ( $option_elements.length ) {
										for (var i = 0, len = $option_elements.length; i < len; i++) {
											var $option_element = $( $option_elements[i] ),
												option_value = $option_element.val();

											if ( attr_val === option_value ) {
												$option_element.addClass( 'attached ' + variation_active );
												break;
											}
										}
									}
								} else {
									// Attach all apart from placeholder.
									new_attr_select.find( 'option:gt(0)' ).addClass( 'attached ' + variation_active );
								}
							}
						}
					}
				}
			}

			// Count available options.
			attached_options_count = new_attr_select.find( 'option.attached' ).length;

			// Check if current selection is in attached options.
			if ( selected_attr_val ) {
				selected_attr_val_valid = false;

				if ( 0 !== attached_options_count ) {
					new_attr_select.find( 'option.attached.enabled' ).each( function() {
						var option_value = $( this ).val();

						if ( selected_attr_val === option_value ) {
							selected_attr_val_valid = true;
							return false; // break.
						}
					});
				}
			}

			// Detach unattached.
			new_attr_select.find( 'option' + option_gt_filter + ':not(.attached)' ).remove();

			// Finally, copy to DOM and set value.
			current_attr_select.html( new_attr_select.html() );
			current_attr_select.find( 'option' + option_gt_filter + ':not(.enabled)' ).prop( 'disabled', true );

			// Choose selected value.
			if ( selected_attr_val ) {
				// If the previously selected value is no longer available, fall back to the placeholder (it's going to be there).
				if ( selected_attr_val_valid ) {
					current_attr_select.val( selected_attr_val );
				} else {
					current_attr_select.val( '' ).trigger( 'change' );
				}
			} else {
				current_attr_select.val( '' ); // No change event to prevent infinite loop.
			}
		});
	};

	/**
	 * Get chosen attributes from form.
	 * @return array
	 */
	wcssbVariationForm.prototype.getChosenAttributes = function() {
		var data   = {};
		var count  = 0;
		var chosen = 0;

		this.$attributeFields.each( function() {
			var attribute_name = $( this ).data( 'attribute_name' );
			var value          = $( this ).val() || '';

			if ( value.length > 0 ) {
				chosen ++;
			}

			count ++;
			data[ attribute_name ] = value;
		});

		return {
			'count'      : count,
			'chosenCount': chosen,
			'data'       : data
		};
	};

	/**
	 * Find matching variations for attributes.
	 */
	wcssbVariationForm.prototype.findMatchingVariations = function( variations, attributes ) {
		var matching = [];
		for ( var i = 0; i < variations.length; i++ ) {
			var variation = variations[i];

			if ( this.isMatch( variation.attributes, attributes ) ) {
				matching.push( variation );
			}
		}
		return matching;
	};

	/**
	 * See if attributes match.
	 * @return {Boolean}
	 */
	wcssbVariationForm.prototype.isMatch = function( variation_attributes, attributes ) {
		var match = true;
		for ( var attr_name in variation_attributes ) {
			if ( variation_attributes.hasOwnProperty( attr_name ) ) {
				var val1 = variation_attributes[ attr_name ];
				var val2 = attributes[ attr_name ];
				if ( val1 !== undefined && val2 !== undefined && val1.length !== 0 && val2.length !== 0 && val1 !== val2 ) {
					match = false;
				}
			}
		}
		return match;
	};

	/**
	 * Show or hide the reset link.
	 */
	wcssbVariationForm.prototype.toggleResetLink = function( on ) {
		if ( on ) {
			this.$resetVariations.removeClass( 'wcssb-hidden' ).slideDown( 200 );
		} else {
			this.$resetVariations.slideUp( 200 );
		}
	};

	/**
	 * Function to call wcssb_variation_form on jquery selector.
	 */
	$.fn.wcssb_variation_form = function() {
		new wcssbVariationForm( this );
		return this;
	};

	/**
	 * Stores the default text for an element so it can be reset later
	 */
	$.fn.wcssb_set_content = function( content ) {
		if ( undefined === this.attr( 'data-wcssb_o_content' ) ) {
			this.attr( 'data-wcssb_o_content', this.text() );
		}
		this.html( content );
	};

	/**
	 * Stores the default text for an element so it can be reset later
	 */
	$.fn.wcssb_reset_content = function() {
		if ( undefined !== this.attr( 'data-wcssb_o_content' ) ) {
			this.html( this.attr( 'data-wcssb_o_content' ) );
		}
	};

	$(function() {
		if ( typeof wcssbAddToCartVariationSettings !== 'undefined' ) {
			$( '.js-wcssb-variations-form' ).each( function() {
				$( this ).wcssb_variation_form();
			});

			$( document.body ).on( 'wcssb_add_to_cart_drawer_section_processed', function( e, $section ) {
				$section.find( '.js-wcssb-variations-form' ).each( function() {
					$( this ).wcssb_variation_form();
				});
			});
		}
	});

})( jQuery, window, document );
