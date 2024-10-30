var iconizeAdminDialog;

(function( $ ){
	
	"use strict";
	
	var preview, adIconLists, adTitle, adHoverColorWrapper,
		adInputs = {},
		adAutocompleteData = [],
		adAllNames = [], adAllEffects = [], adAllSetClasses = [], adAllNameClasses=[], adAllSizeClasses = [], adAllAlignClasses = [], adAllTransformClasses = [], adAllAnimateClasses = [], adAllHoverClasses = [],
		adReservedClasses = ['iconized','iconized-stack','iconized-stack-base'],
		adColorHoverClass = 'hover-color-change',
		inputNameID = '', inputSetID = '', inputTransformID = '', inputColorID = '', inputSizeID = '', inputAlignID = '', inputCustomClassesID = '',
		inputNameSelector = '', inputSetSelector = '', inputTransformSelector = '', inputColorSelector = '', inputSizeSelector = '', inputAlignSelector = '', inputCustomClassesSelector = '',
		currentNameValue = '', currentSetValue = '', currentTransformValue = '', currentColorValue = '', currentSizeValue = '', currentAlignValue = '', currentCustomClassesValue = '', currentIconSelector = '', addTagClicked = '';

	iconizeAdminDialog = {

		init: function() {

			// Our dialog.
			adInputs.dialog = $('#iconize-admin-modal');

			// Dialog action buttons.
			adInputs.submit = $('#iconize-admin-update');
			adInputs.remove = $('#iconize-admin-remove');

			// Inputs.
			adInputs.set        = $('#admin-icon-set');
			adInputs.name       = $('#admin-icon-name');
			adInputs.effect     = $('#admin-icon-effect');
			adInputs.transform  = $('#admin-icon-transform');
			adInputs.animate    = $('#admin-icon-animate');
			adInputs.hover      = $('#admin-icon-hover');
			adInputs.color      = $('#admin-icon-color');
			adInputs.hovercolor = $('#admin-icon-color-hover');
			adInputs.size       = $('#admin-icon-size');
			adInputs.align      = $('#admin-icon-align');
			adInputs.custom     = $('#admin-icon-custom-classes');

			// Icons lists wrapper.
			adIconLists = $('#iconize-admin-icons');

			// Dialog title.
			adTitle = $('#iconize-admin-title');

			// Icon color hover checkbox.
			adHoverColorWrapper = $('#admin-color-hover-checkbox');

			// Make an arrays of all possible classes for set, effects, size, align.
			adAllSetClasses       = iconizeAdminDialog.getSelectOptions( adInputs.set );
			adAllTransformClasses = iconizeAdminDialog.getSelectOptions( adInputs.transform );
			adAllAnimateClasses   = iconizeAdminDialog.getSelectOptions( adInputs.animate );
			adAllHoverClasses     = iconizeAdminDialog.getSelectOptions( adInputs.hover );
			adAllSizeClasses      = iconizeAdminDialog.getSelectOptions( adInputs.size );
			adAllAlignClasses     = iconizeAdminDialog.getSelectOptions( adInputs.align );

			adAllEffects = iconizeAdminDialog.getSelectOptions( adInputs.effect );

			// Render icons lists, and generate array for autocomplete and array of possible name classes.
			if ( iconizeDialogParams.icons ) {

				for ( var i = 0; i < adAllSetClasses.length; i++ ) {

					var set = adAllSetClasses[ i ],
						setIconNames = iconizeDialogParams.icons[ set ],
						$ul = $('<ul id="'+set+'-icons-list">').addClass('icons-list');

					adIconLists.append( $ul );

					$.merge( adAllNames, setIconNames );
					
					for ( var j = 0; j < setIconNames.length; j++ ) {

						var name = setIconNames[ j ],
							nameClass = "glyph-"+name,
							listItem = '';

						listItem  = '<li class="icons-list-item">';
							listItem += '<a href="#" class="icons-list-icon" title="'+name+'" data-set="'+set+'">';
								listItem += '<span class="iconized '+set+' '+nameClass+'"></span>';
							listItem += '</a>';
						listItem += '</li>';

						$ul.append( listItem );

						adAutocompleteData.push({ label: name, set: set });
						adAllNameClasses.push( nameClass );
					}
				}
			}

			// Make an array of reserved classes - it will be used by tagit plugin to disable user to type this classes in custom classes input.
			adReservedClasses.push( adColorHoverClass );
			$.merge( adReservedClasses, adAllNameClasses );
			$.merge( adReservedClasses, adAllSetClasses );
			$.merge( adReservedClasses, adAllTransformClasses );
			$.merge( adReservedClasses, adAllAnimateClasses );
			$.merge( adReservedClasses, adAllHoverClasses );
			$.merge( adReservedClasses, adAllSizeClasses );
			$.merge( adReservedClasses, adAllAlignClasses );

			// Add color picker plugin on icon color input.
			adInputs.color.wpColorPicker({
				change: function() {

					iconizeAdminDialog.setColor( $(this).wpColorPicker('color') );
					adHoverColorWrapper.removeClass('hidden');
				},
				clear: function() {

					iconizeAdminDialog.setColor('');
					adHoverColorWrapper.addClass('hidden');
					adInputs.hovercolor.prop( 'checked', false );
				}
			});

			// Add autocomplete plugin on icon name input to enable search.
			adInputs.name.iconizeautocomplete({
				appendTo: adInputs.dialog,
				source: adAutocompleteData,
				minLength: 1,
				change: function( event, ui ) {

					if ( null === ui.item  ) {

						var selectedIcon = adIconLists.find('.selected-icon'),
							selectedName = selectedIcon.attr('title'),
							selectedSet = selectedIcon.data('set');

						if ( selectedIcon.length ) {

							$(this).val( selectedName );
							adInputs.set.val( selectedSet );

						} else {

							$(this).val('');
							adInputs.set.val( adAllSetClasses[0] );
						}
					}
				},
				select: function( event, ui ) {

					// Remove "selected-icon" class if any, hide all lists, show selected icons list.
					adIconLists.find('.icons-list-icon.selected-icon').removeClass('selected-icon');
					adInputs.set.val( ui.item.set );
					adIconLists.find('.icons-list').hide();
					adIconLists.find('#'+ui.item.set+'-icons-list').show();

					// Select icon, add name value to input and scroll to icon.
					adIconLists.find('.'+ui.item.set+'.glyph-'+ui.item.value).parent().addClass('selected-icon');
					adInputs.name.val( ui.item.value );
					iconizeAdminDialog.scrollToIcon();
				}
			}).bind( 'focus', function() {

				$(this).iconizeautocomplete('search');
			});

			// Handle selecting of icons.
			$('#iconize-admin-modal .icons-list-icon').click( function(e) {

				e.preventDefault();

				var $this = $(this);

				if ( false === $this.hasClass('selected-icon') ) {

					adIconLists.find('.icons-list-icon.selected-icon').removeClass('selected-icon');
					$this.addClass('selected-icon');

					adInputs.name.val( $this.attr('title') );
				}
			});

			// Add tag-it plugin on custom classes input.
			adInputs.custom.tagit({
				autocomplete: {
					disabled: true
				},
				preprocessTag: function( val ) {

					if ( ! val ) {

						return '';
					}

					// Do some validation for CSS class names ( http://stackoverflow.com/a/19670342 ).
					var validation1 = val.replace(/^[^-_a-zA-Z]+/, '').replace(/^-(?:[-0-9]+)/, '-');
					var validation  = validation1 && validation1.replace(/[^-_a-zA-Z0-9]+/g, '-');

					return validation;
				},
				afterTagAdded: function( event, ui ) {

					// Disable adding reserved CSS class names.
					if ( $.inArray( ui.tagLabel, adReservedClasses ) !== -1 ) {

						alert( iconizeDialogParams.l10n.reserved_class );
						adInputs.custom.tagit( 'removeTag', ui.tag );
					}
				}
			});

			// Handle icon set lists change.
			adInputs.set.change( function(){

				var value = $(this).val(),
					listSel = '#'+value+'-icons-list';

				adIconLists.find('.icons-list').hide();
				adIconLists.find( listSel ).show();

				adIconLists.scrollTop(0);
			});

			// Handle action buttons.
			adInputs.submit.click( function(e) {

				e.preventDefault();

				iconizeAdminDialog.update();
			});

			adInputs.remove.click( function(e) {

				e.preventDefault();

				iconizeAdminDialog.remove();
			});

			// When preview button is clicked, take all needed parameters and show the dialog.
			$(document).on(
				'click',
				'.preview-icon',
				function(e) {

					e.preventDefault();

					preview = $(this);

					inputNameID          = preview.parent().next().children('.iconize-input-name').attr('id');
					inputSetID           = preview.parent().next().children('.iconize-input-set').attr('id');
					inputTransformID     = preview.parent().next().children('.iconize-input-transform').attr('id');
					inputColorID         = preview.parent().next().children('.iconize-input-color').attr('id');
					inputSizeID          = preview.parent().next().children('.iconize-input-size').attr('id');
					inputAlignID         = preview.parent().next().children('.iconize-input-align').attr('id');
					inputCustomClassesID = preview.parent().next().children('.iconize-input-custom-classes').attr('id');

					inputNameSelector          = '#'+inputNameID;
					inputSetSelector           = '#'+inputSetID;
					inputTransformSelector     = '#'+inputTransformID;
					inputColorSelector         = '#'+inputColorID;
					inputSizeSelector          = '#'+inputSizeID;
					inputAlignSelector         = '#'+inputAlignID;
					inputCustomClassesSelector = '#'+inputCustomClassesID;

					currentNameValue          = $( inputNameSelector ).val();
					currentSetValue           = $( inputSetSelector ).val();
					currentTransformValue     = $( inputTransformSelector ).val();
					currentColorValue         = $( inputColorSelector ).val();
					currentSizeValue          = $( inputSizeSelector ).val();
					currentAlignValue         = $( inputAlignSelector ).val();
					currentCustomClassesValue = $( inputCustomClassesSelector ).val();

					currentIconSelector = '.'+currentSetValue+'.'+currentNameValue;

					iconizeAdminDialog.show();
				}
			);
			
			// Update color on preview button icon.
			iconizeAdminDialog.updatePreviewIconColors();
			// For widget and menu systems.
			$(document).on( 'ajaxStop', function() {
				
				iconizeAdminDialog.updatePreviewIconColors();
			});

			// For taxonomy systems.
			$('form#addtag input#submit').click( function(e) {

				var form = $(this).parents('form');

				if ( validateForm( form ) ) {
					
					addTagClicked = 'clicked';
				}
			});

			$(document).on( 'ajaxComplete', function() {

				if ( $('body').hasClass('edit-tags-php') ) {

					if ( 'clicked' === addTagClicked ) {

						iconizeAdminDialog.remove();
					}

					addTagClicked = '';
				}
			});

			// When modal dialog is shown, scroll to icon and remove loading image.
			adInputs.dialog.on( 'shown.wpbs.modal', function() {

				iconizeAdminDialog.scrollToIcon( true );
			});

			// When modal dialog is closed focus preview button.
			adInputs.dialog.on( 'hidden.wpbs.modal', iconizeAdminDialog.closed );
		},
		
		show: function() {

			var i, index, currentCustomClassesArr = [], colorHover = false;

			if ( ! adAllNameClasses.length ) {

				alert( iconizeDialogParams.l10n.no_icons_defined );
				return;
			}

			adIconLists.addClass('loading-overlay');
			iconizeAdminDialog.clearColorPicker();
			iconizeAdminDialog.clearCustomClasses();

			// There is an icon defined, populate dialog inputs and select icon.
			if ( '' !== currentNameValue && '' !== currentSetValue ) {

				// Enable "Remove icon" button
				adInputs.remove.show();

				if ( ( $.inArray( currentNameValue, adAllNameClasses ) !== -1 ) && ( $.inArray( currentSetValue, adAllSetClasses ) !== -1 ) ) {

					// Change dialog title and submit button text.
					adTitle.text( iconizeDialogParams.l10n.edit );
					adInputs.submit.text( iconizeDialogParams.l10n.update );
					
					// Show correct icon set and select defined icon.
					adInputs.set.val( currentSetValue );
					adIconLists.find('.icons-list').hide();
					adIconLists.find('#'+currentSetValue+'-icons-list').show();

					adIconLists.find('.icons-list-icon.selected-icon').removeClass('selected-icon');
					adIconLists.find( currentIconSelector ).parent().addClass('selected-icon');
					adInputs.name.val( currentNameValue.replace( 'glyph-', '' ) );

					// Populate effects inputs.
					if ( ! currentTransformValue ) {

						adInputs.effect.val( adAllEffects[0] );
						adInputs.transform.val('');
						adInputs.animate.val('');
						adInputs.hover.val('');

					} else if ( $.inArray( currentTransformValue, adAllTransformClasses ) !== -1 ) {

						adInputs.effect.val('transform');
						adInputs.transform.val( currentTransformValue );

					} else if ( $.inArray( currentTransformValue, adAllAnimateClasses ) !== -1 ) {

						adInputs.effect.val('animate');
						adInputs.animate.val( currentTransformValue );

					} else if ( $.inArray( currentTransformValue, adAllHoverClasses ) !== -1 ) {

						adInputs.effect.val('hover');
						adInputs.hover.val( currentTransformValue );
					}

					iconizeAdminDialog.showChildOptions( adInputs.effect );

					// Populate other inputs.
					adInputs.color.val( currentColorValue );
					adInputs.color.wpColorPicker( 'color', currentColorValue );

					adInputs.size.val( currentSizeValue );
					adInputs.align.val( currentAlignValue );

					// Custom classes.
					currentCustomClassesArr = currentCustomClassesValue.split(',');

					// If there is color, check if color hover class is in custom classes input and update checkbox
					if ( currentColorValue ) {

						if ( currentCustomClassesArr.length ) {

							// Check for color hover class
							index = $.inArray( adColorHoverClass, currentCustomClassesArr );

							if ( index !== -1 ) {

								colorHover = true;

								// Remove it from custom classes array
								currentCustomClassesArr.splice( index, 1 );
							}
						}

						// Show checkbox.
						adHoverColorWrapper.removeClass('hidden');

					} else {
						
						// Hide checkbox.
						adHoverColorWrapper.addClass('hidden');
					}

					// Update checkbox.
					adInputs.hovercolor.prop( 'checked', colorHover );

					// Update custom classes dialog input
					for ( i = 0; i < currentCustomClassesArr.length; i++ ) {

						adInputs.custom.tagit( 'createTag', currentCustomClassesArr[ i ] );
					}

				} else {

					alert( iconizeDialogParams.l10n.no_icon_found_admin );

					iconizeAdminDialog.setDefaultValues();
				}

			} else { // No icon defined.

				// Disable "Remove icon" button.
				adInputs.remove.hide();

				iconizeAdminDialog.setDefaultValues();
			}

			adInputs.dialog.wpbsmodal('show');
		},

		close: function() {

			adInputs.dialog.wpbsmodal('hide');

			var updateWidgetButton = preview.parent().parent().parent().parent().find('input.widget-control-save');
			// Trigger preview update inside Customizer.
			if ( $('body').hasClass('wp-customizer') ) {

				// Force soft widget update inside customizer UI by clicking on hidden widget update button.
				if ( updateWidgetButton.length ) {
					updateWidgetButton.click();
				}
				
				var updateMenuButton = preview.parent().parent().find( 'button.customizer-preview-icon-trigger' );
				if ( updateMenuButton.length ) {
					updateMenuButton.click();
				}
			
			}

			$( inputNameSelector ).trigger( 'change' );
		},

		closed: function() {

			preview.focus();
		},

		update: function() {

			var selectedSet = '', selectedIconName = '', selectedEffectType = '', selectedTransform = '', selectedColor = '', selectedColorHover = '', selectedSize = '', selectedAlign = '', customClasses = '';

			// If there's no selected icon, show message to user.
			if ( ! adInputs.name.val() ) {

				alert( iconizeDialogParams.l10n.no_icon_selected );

			} else {

				selectedSet       = adInputs.set.val();
				selectedIconName  = 'glyph-'+adInputs.name.val();

				selectedEffectType = adInputs.effect.val();
				if ( 'transform' ===  selectedEffectType ) {

					selectedTransform = adInputs.transform.val();

				} else if ( 'animate' ===  selectedEffectType ) {

					selectedTransform = adInputs.animate.val();

				} else if ( 'hover' ===  selectedEffectType ) {
					
					selectedTransform = adInputs.hover.val();
				}
				
				selectedColor      = adInputs.color.val();
				selectedColorHover = adInputs.hovercolor.prop('checked');
				selectedSize       = adInputs.size.val();
				selectedAlign      = adInputs.align.val();
				customClasses      = adInputs.custom.tagit('assignedTags');
				customClasses      = customClasses.toString();

				if ( selectedColorHover ) {

					if ( customClasses ) {

						customClasses += ','+adColorHoverClass;

					} else {

						customClasses = adColorHoverClass;
					}
				}

				$( inputNameSelector ).val( selectedIconName );
				$( inputSetSelector ).val( selectedSet );
				$( inputTransformSelector ).val( selectedTransform );
				$( inputColorSelector ).val( selectedColor );
				$( inputSizeSelector ).val( selectedSize );
				$( inputAlignSelector ).val( selectedAlign );
				$( inputCustomClassesSelector ).val( customClasses );

				preview.children().removeClass().addClass('iconized').addClass( selectedSet ).addClass( selectedIconName ).addClass( selectedTransform ).css( 'color', selectedColor );

				if ( selectedColorHover ) {

					preview.children().addClass( adColorHoverClass );
				}

				iconizeAdminDialog.close();
			}
		},

		remove: function() {

			adInputs.set.val('');
			adInputs.name.val('');
			adInputs.effect.val( adAllEffects[0] );
			adInputs.transform.val('');
			adInputs.animate.val('');
			adInputs.hover.val('');
			adInputs.color.val('');
			adInputs.hovercolor.prop( 'checked', false );
			adInputs.size.val('');
			adInputs.align.val('');
			iconizeAdminDialog.clearColorPicker();
			iconizeAdminDialog.clearCustomClasses();

			$( inputNameSelector ).val('');
			$( inputSetSelector ).val('');
			$( inputTransformSelector ).val('');
			$( inputColorSelector ).val('');
			$( inputSizeSelector ).val('');
			$( inputAlignSelector ).val('');
			$( inputCustomClassesSelector ).val('');

			preview.children().removeClass().css( 'color', '' );

			iconizeAdminDialog.close();
		},

		setDefaultValues: function() {

			adTitle.text( iconizeDialogParams.l10n.add );
			adInputs.submit.text( iconizeDialogParams.l10n.add );

			// Show first icon set and deselect icon.
			adInputs.set.val( adAllSetClasses[0] );
			adIconLists.find('.icons-list').hide();
			adIconLists.find('#'+adAllSetClasses[0]+'-icons-list').show();

			adInputs.name.val('');
			adIconLists.find('.icons-list-icon.selected-icon').removeClass('selected-icon');

			// Effects defaults
			adInputs.effect.val( adAllEffects[0] );
			adInputs.transform.val('');
			adInputs.animate.val('');
			adInputs.hover.val('');

			iconizeAdminDialog.showChildOptions( adInputs.effect );
			
			adInputs.color.val('');
			adInputs.hovercolor.prop( 'checked', false );
			adHoverColorWrapper.addClass('hidden');

			adInputs.size.val('');
			adInputs.align.val('');
		},

		updatePreviewIconColors: function() {

			$('.preview-icon').each( function() {

				var $this = $(this), currentcolor = '', currentcc = '', currentccarr = [];

				currentcolor = $this.parent().next().children('.iconize-input-color').val();
				currentcc    = $this.parent().next().children('.iconize-input-custom-classes').val();

				if ( '' !== currentcolor ) {

					// Update color
					$this.children().css( 'color', currentcolor );

					// Add hover color class if needed.
					currentccarr = currentcc.split(',');

					if ( currentccarr.length && ( $.inArray( adColorHoverClass, currentccarr ) !== -1  ) ) {

						$this.children().addClass( adColorHoverClass );
					}
				}
			});
		},

		setColor: function( color ) {

			adInputs.color.val( color );
		},

		clearColorPicker: function() {

			adInputs.color.wpColorPicker( 'color', '' );

			// Remove prev color styles from picker.
			$('#iconize-admin-modal .wp-color-result').removeAttr('style');
		},

		clearCustomClasses: function() {

			adInputs.custom.tagit('removeAll');
		},

		scrollToIcon: function( loading ) {

			var	load = loading || false,
				icon = '',
				position = 0;

			icon = adInputs.dialog.find('.selected-icon');

			if ( icon.length ) {

				position = icon.position().top - adIconLists.position().top + adIconLists.scrollTop() - 92;
			}

			if ( true === load ) {

				adIconLists.animate( { scrollTop: position }, '500', 'swing', function() {

					adIconLists.removeClass('loading-overlay');
				});

			} else {

				adIconLists.scrollTop( position );
			}
		},

		getSelectOptions: function( select ) {

			var allValues = [];

			select.find('option').each( function() {
				
				var val = $(this).val();
				
				allValues.push( val );
			});

			return allValues;
		},

		showChildOptions: function( select ) {

			var $this = select,
				tempValue = $this.val(),
				id = $this.attr('id'),
				childOptClass = '.mother-opt-'+id,
				showOptClass = '.mother-val-'+tempValue;

			$( childOptClass ).addClass('hidden');
			$( childOptClass+showOptClass ).removeClass('hidden');
		}
	};

	$(document).ready( iconizeAdminDialog.init );

})( jQuery );