var iconizeTinymceDialog;

(function( $ ) {

	"use strict";

	var editor, node, hasI, iconStackWrapperNode, tdIconLists, tdTitle, iconSizeLabel, iconAlignLabel, tdHoverColorWrapper,
		tdInputs = {},
		autocompleteData = [],
		nodeClasses = '', nodeClassesArr = [],
		iconStackWrapperClasses = '', iconStackWrapperClassesArr = [],
		allNames = [], allEffects = [], allSetClasses = [], allNameClasses = [], allSizeClasses = [], allAlignClasses = [], allTransformClasses = [], allAnimateClasses = [], allHoverClasses = [],
		requiredClassesArr = [ 'iconized','iconized-stack-base' ], reservedClasses = ['iconized-stack'],
		tdColorHoverClass = 'hover-color-change';

	iconizeTinymceDialog = {

		textarea: '',

		init: function() {

			// Our dialog.
			tdInputs.dialog = $('#iconize-mce-modal');

			// Dialog action buttons.
			tdInputs.submit = $('#iconize-mce-update');
			tdInputs.stack  = $('#iconize-mce-stack');

			// Dialog inputs.
			tdInputs.set        = $('#mce-icon-set');
			tdInputs.name       = $('#mce-icon-name');
			tdInputs.effect     = $('#mce-icon-effect');
			tdInputs.transform  = $('#mce-icon-transform');
			tdInputs.animate    = $('#mce-icon-animate');
			tdInputs.hover      = $('#mce-icon-hover');
			tdInputs.color      = $('#mce-icon-color');
			tdInputs.hovercolor = $('#mce-icon-color-hover');
			tdInputs.size       = $('#mce-icon-size');
			tdInputs.customsize = $('#mce-icon-custom-size');
			tdInputs.align      = $('#mce-icon-align');
			tdInputs.custom     = $('#mce-icon-custom-classes');

			// Dialog icons lists wrapper.
			tdIconLists = $('#iconize-mce-icons');

			// Dialog title, size and align labels.
			tdTitle        = $('#iconize-mce-title');
			iconSizeLabel  = $('#mce-icon-size-howto');
			iconAlignLabel = $('#mce-icon-align-howto');

			// Icon color hover checkbox.
			tdHoverColorWrapper = $('#mce-color-hover-checkbox');

			// Make an arrays of all possible classes for set, transform, size, align.
			allSetClasses       = iconizeTinymceDialog.getSelectOptions( tdInputs.set );
			allTransformClasses = iconizeTinymceDialog.getSelectOptions( tdInputs.transform );
			allAnimateClasses   = iconizeTinymceDialog.getSelectOptions( tdInputs.animate );
			allHoverClasses     = iconizeTinymceDialog.getSelectOptions( tdInputs.hover );
			allSizeClasses      = iconizeTinymceDialog.getSelectOptions( tdInputs.size );
			allAlignClasses     = iconizeTinymceDialog.getSelectOptions( tdInputs.align );

			allEffects = iconizeTinymceDialog.getSelectOptions( tdInputs.effect );

			// Render icons lists, and generate array for autocomplete and array of possible name classes.
			if ( iconizeDialogParams.icons ) {

				for ( var i = 0; i < allSetClasses.length; i++ ) {

					var set = allSetClasses[ i ],
						setIconNames = iconizeDialogParams.icons[ set ],
						$ul = $('<ul id="'+set+'-icons-list">').addClass('icons-list');

					tdIconLists.append( $ul );

					$.merge( allNames, setIconNames );

					for ( var j = 0; j < setIconNames.length; j++ ) {

						var name = setIconNames[ j ],
							nameClass = 'glyph-'+name,
							listItem;

						listItem  = '<li class="icons-list-item">';
							listItem += '<a href="#" class="icons-list-icon" title="'+name+'" data-set="'+set+'">';
								listItem += '<span class="iconized '+set+' '+nameClass+'"></span>';
							listItem += '</a>';
						listItem += '</li>';

						$ul.append( listItem );

						autocompleteData.push({ label: name, set: set });
						allNameClasses.push( nameClass );
					}
				}
			}

			// Make an array of reserved classes - it will be used by tagit plugin to disable user to type this classes.
			reservedClasses.push( tdColorHoverClass );
			$.merge( reservedClasses, requiredClassesArr );
			$.merge( reservedClasses, allNameClasses );
			$.merge( reservedClasses, allSetClasses );
			$.merge( reservedClasses, allTransformClasses );
			$.merge( reservedClasses, allAnimateClasses );
			$.merge( reservedClasses, allHoverClasses );
			$.merge( reservedClasses, allSizeClasses );
			$.merge( reservedClasses, allAlignClasses );

			// Show/Hide icon lists on set select.
			tdInputs.set.change( function() {

				var value = $(this).val(),
					listSel = '#'+value+'-icons-list';

				tdIconLists.find('.icons-list').hide();
				tdIconLists.find(listSel).show();
				tdIconLists.scrollTop(0);
			});

			// Add autocomplete plugin on icon name input to enable search.
			tdInputs.name.iconizeautocomplete({
				appendTo: tdInputs.dialog,
				source: autocompleteData,
				minLength: 1,
				change: function( event, ui ) {

					if ( null === ui.item ) {

						var selectedIcon = tdIconLists.find('.selected-icon'),
							selectedName = selectedIcon.attr('title'),
							selectedSet = selectedIcon.data('set');

						if ( selectedIcon.length ) {

							$(this).val( selectedName );
							tdInputs.set.val( selectedSet );

						} else {

							$(this).val('');
							tdInputs.set.val( allSetClasses[0] );
						}
					}
				},
				select: function( event, ui ) {

					iconizeTinymceDialog.unselectIcon();
					iconizeTinymceDialog.selectIcon( ui.item.set,ui.item.value );
					iconizeTinymceDialog.scrollToIcon();
				}
			}).bind( 'focus', function() {

				$(this).iconizeautocomplete('search');
			});

			// Add color picker plugin on icon color input.
			tdInputs.color.wpColorPicker({
				change: function () {

					iconizeTinymceDialog.setColor( $(this).wpColorPicker('color') );
					tdHoverColorWrapper.removeClass('hidden');
				},
				clear: function () {

					iconizeTinymceDialog.setColor('');
					tdHoverColorWrapper.addClass('hidden');
				}
			});

			// Add Tag-it plugin on custom classes input.
			tdInputs.custom.tagit({
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
					if ( $.inArray( ui.tagLabel, reservedClasses ) !== -1 ) {

						alert( iconizeDialogParams.l10n.reserved_class );
						tdInputs.custom.tagit( 'removeTag', ui.tag );
					}
				}
			});

			// Handle action buttons.
			tdInputs.submit.click( function(e) {

				e.preventDefault();

				iconizeTinymceDialog.update();
			});

			tdInputs.stack.click( function(e) {

				e.preventDefault();

				iconizeTinymceDialog.stack();
			});

			// Selecting icons.
			$('#iconize-mce-modal .icons-list-icon').click( function(e) {

				e.preventDefault();

				var $this = $(this);

				if ( false === $this.hasClass('selected-icon') ) {

					tdIconLists.find('.icons-list-icon.selected-icon').removeClass('selected-icon');
					$this.addClass('selected-icon');

					tdInputs.name.val( $this.attr('title') );
				}
			});

			tdInputs.dialog.on( 'show.wpbs.modal', iconizeTinymceDialog.refresh );

			tdInputs.dialog.on( 'shown.wpbs.modal', function() {

				iconizeTinymceDialog.scrollToIcon( true );
			});

			tdInputs.dialog.on( 'hide.wpbs.modal', iconizeTinymceDialog.onClose );

			tdInputs.dialog.on( 'hidden.wpbs.modal', iconizeTinymceDialog.setDefaultValues );

			// Add "icon" button to quicktags toolbar if iconize quicktags plugin is enabled and the quicktags api is available.
			if ( iconizeSettings.iconizeQuicktags && typeof( QTags ) !== 'undefined' ) {

				// WordPress quicktags API
				QTags.addButton(
					'icon',
					'icon',
					function() {

						iconizeTinymceDialog.open();
					},
					'',
					'',
					iconizeDialogParams.l10n.insert,
					130
				);
			}
		},

		isMCE : function() {

			return editor && ! editor.isHidden();
		},

		open: function(editorId) {

			var ed;

			iconizeTinymceDialog.range = null;

			if ( editorId ) {

				window.wpActiveEditor = editorId;
			}

			if ( ! window.wpActiveEditor ) {

				return;
			}

			iconizeTinymceDialog.textarea = $( '#' + window.wpActiveEditor ).get( 0 );

			if ( typeof tinymce !== 'undefined' ) {

				ed = tinymce.get( wpActiveEditor );

				if ( ed && ! ed.isHidden() ) {

					editor = ed;

				} else {

					editor = null;
				}

				if ( editor && tinymce.Env.ie ) {

					editor.windowManager.bookmark = editor.selection.getBookmark();
				}
			}

			if ( ! iconizeTinymceDialog.isMCE() && document.selection ) {

				iconizeTinymceDialog.textarea.focus();
				iconizeTinymceDialog.range = document.selection.createRange();
			}

			tdInputs.dialog.wpbsmodal('show');
		},

		refresh : function() {

			if ( ! allNameClasses.length ) {

				alert( iconizeDialogParams.l10n.no_icons_defined );
				return false;
			}

			tdIconLists.addClass('loading-overlay');

			if ( iconizeTinymceDialog.isMCE() ) {

				if ( tinymce.Env.ie ) {
					editor.selection.moveToBookmark( editor.windowManager.bookmark );
				}

				iconizeTinymceDialog.mceRefresh();

			} else {

				// HTML mode
				iconizeTinymceDialog.range = null;

				if ( document.selection ) {

					iconizeTinymceDialog.textarea.focus();
					iconizeTinymceDialog.range = document.selection.createRange();
				}

				iconizeTinymceDialog.setDefaultValues();
			}
		},

		mceRefresh: function() {

			var i, index, ed,
				node = '',
				iSet = '', iNameClass = '', iName = '', iSize = '', iCustomSize = '', iAlign = '', iEffectType = '', iTransform = '', iAnimate = '', iHover = '', iColor = '', iColorHover = false;

			ed = editor;

			// Selected node.
			node = ed.selection.getNode();

			// Check for icon.
			hasI = ed.dom.hasClass( node, 'iconized' );

			// Check for "iconized-stack" wrapper.
			iconStackWrapperNode = ed.dom.getParent( node, 'span.iconized-stack' );

			if ( hasI ) { // Node is iconized

				tdTitle.text( iconizeDialogParams.l10n.edit );

				if ( iconStackWrapperNode ) {

					// Hide stack button.
					tdInputs.stack.hide();

					// Change size and align labels.
					iconSizeLabel.text( iconizeDialogParams.l10n.stack_size_label );
					iconAlignLabel.text( iconizeDialogParams.l10n.stack_align_label );

					// Get all classes from wrapper node and make an array of wrapper node classes.
					iconStackWrapperClasses = ed.dom.getAttrib( iconStackWrapperNode, 'class' );
					iconStackWrapperClassesArr = iconStackWrapperClasses.split(/ /);

					// Take size and align classes.
					for ( i = 0; i < iconStackWrapperClassesArr.length; i++ ) {

						if ( $.inArray( iconStackWrapperClassesArr[ i ], allSizeClasses ) !== -1 ) {

							iSize = iconStackWrapperClassesArr[ i ];
						}

						if ( $.inArray( iconStackWrapperClassesArr[ i ], allAlignClasses ) !== -1 ) {

							iAlign = iconStackWrapperClassesArr[ i ];
						}
					}

					if ( ed.dom.getStyle( iconStackWrapperNode, 'font-size' ) ) {

						iCustomSize = ed.dom.getStyle( iconStackWrapperNode, 'font-size' );
					}

				} else {

					// Show stack action button.
					tdInputs.stack.show();

					// Change size and align labels.
					iconSizeLabel.text( iconizeDialogParams.l10n.icon_size_label );
					iconAlignLabel.text( iconizeDialogParams.l10n.icon_align_label );
				}

				// Get all classes from node and make an array of node classes.
				nodeClasses = ed.dom.getAttrib( node, 'class' );
				nodeClassesArr = nodeClasses.split(/ /);

				// Remove required classes from node classes array.
				for ( i = 0; i < requiredClassesArr.length; i++ ) {

					index = $.inArray( requiredClassesArr[ i ], nodeClassesArr );

					if ( index !== -1 ) {

						nodeClassesArr.splice( index, 1 );
					}
				}

				// Organise remaining node classes.
				for ( i = 0; i < nodeClassesArr.length; i++ ) {

					if ( $.inArray( nodeClassesArr[ i ], allSetClasses ) !== -1 ) {

						iSet = nodeClassesArr[ i ];

					} else if ( $.inArray( nodeClassesArr[ i ], allNameClasses ) !== -1 ) {

						iNameClass = nodeClassesArr[ i ];

					} else if ( ! iconStackWrapperNode && $.inArray( nodeClassesArr[ i ], allSizeClasses ) !== -1 ) {

						iSize = nodeClassesArr[ i ];

					} else if ( ! iconStackWrapperNode && $.inArray( nodeClassesArr[ i ], allAlignClasses ) !== -1 ) {

						iAlign = nodeClassesArr[ i ];

					} else if ( $.inArray( nodeClassesArr[ i ], allTransformClasses ) !== -1 ) {

						iTransform = nodeClassesArr[ i ];
						iEffectType = 'transform';

					} else if ( $.inArray( nodeClassesArr[ i ], allAnimateClasses ) !== -1 ) {

						iAnimate = nodeClassesArr[ i ];
						iEffectType = 'animate';

					} else if ( $.inArray( nodeClassesArr[ i ], allHoverClasses ) !== -1 ) {

						iHover = nodeClassesArr[ i ];
						iEffectType = 'hover';

					} else if ( tdColorHoverClass === nodeClassesArr[ i ] ) {

						iColorHover = true;

					} else {

						// Put all other classes in custom classes input.
						tdInputs.custom.tagit( 'createTag', nodeClassesArr[ i ] );
					}
				}

				if ( iNameClass && iSet ) { // Everything is fine.

					iName = iNameClass.replace( 'glyph-', '' );

					// Select icon and update name and set inputs.
					iconizeTinymceDialog.selectIcon( iSet, iName );

					// Set values of other classes inputs.
					tdInputs.transform.val( iTransform );
					tdInputs.animate.val( iAnimate );
					tdInputs.hover.val( iHover );

					if ( iEffectType ) {

						tdInputs.effect.val( iEffectType );

					} else {

						tdInputs.effect.val( allEffects[0] );
					}

					iconizeTinymceDialog.showChildOptions( tdInputs.effect );

					// Size
					if ( ed.dom.getStyle( node, 'font-size' ) ) {

						iCustomSize = ed.dom.getStyle( node, 'font-size' );
					}

					if ( iCustomSize ) {

						tdInputs.size.val( 'custom-size' );
						tdInputs.customsize.val( iCustomSize );

					} else {

						tdInputs.size.val( iSize );
						tdInputs.customsize.val('');
					}

					iconizeTinymceDialog.showChildOptions( tdInputs.size );

					// Align
					tdInputs.align.val( iAlign );

					// Take color from node and update colorpicker and color hover checkbox.
					if ( ed.dom.getStyle( node, 'color' ) ) {

						iColor = ed.dom.getStyle( node, 'color' );
						tdHoverColorWrapper.removeClass('hidden');

					} else {

						iColor = '';
						tdHoverColorWrapper.addClass('hidden');
					}

					tdInputs.color.val( iColor );
					tdInputs.color.wpColorPicker('color', iColor );

					tdInputs.hovercolor.prop( 'checked', iColorHover );

					// Update save button text.
					tdInputs.submit.text( iconizeDialogParams.l10n.update );

				} else {

					// Something is wrong, notify user about it and set default values to dialog inputs.
					alert( iconizeDialogParams.l10n.no_icon_found );
					iconizeTinymceDialog.setDefaultValues();
				}

			} else {

				// No icon, clear all dialog inputs
				iconizeTinymceDialog.setDefaultValues();
			}
		},

		close: function() {

			tdInputs.dialog.wpbsmodal('hide');
		},

		onClose: function() {

			if ( ! iconizeTinymceDialog.isMCE() ) {

				if ( iconizeTinymceDialog.range ) {

					iconizeTinymceDialog.range.moveToBookmark( iconizeTinymceDialog.range.getBookmark() );
					iconizeTinymceDialog.range.select();
				}
			}
		},

		update: function() {

			if ( iconizeTinymceDialog.isMCE() ) {

				iconizeTinymceDialog.mceUpdate();

			} else {

				iconizeTinymceDialog.htmlUpdate();
			}
		},

		htmlUpdate: function() {

			var begin, end, cursor,
				textarea = iconizeTinymceDialog.textarea,
				iconset = '', iconname = '', iconsize = '',iconcustomsize = '', iconalign = '', iconeffecttype = '', iconeffect = '', iconcolor = '', iconcolorhover = '', iconcustomclasses = '',
				iconHTML = '', iconInlineStyles='';

			if ( ! textarea ) {

				return;
			}

			// Take icon name from input.
			iconname = tdInputs.name.val();

			// If there's no selected icon, display notice.
			if ( ! iconname ) {

				alert( iconizeDialogParams.l10n.no_icon_selected );

			} else {

				// Take all other input values.
				iconset           = tdInputs.set.val();
				iconsize          = tdInputs.size.val();
				iconcustomsize    = tdInputs.customsize.val();
				iconalign         = tdInputs.align.val();
				iconeffecttype    = tdInputs.effect.val();
				iconcolor         = tdInputs.color.val();
				iconcolorhover    = tdInputs.hovercolor.prop('checked');
				iconcustomclasses = tdInputs.custom.tagit('assignedTags');
				iconcustomclasses = iconcustomclasses.toString();
				iconcustomclasses = iconcustomclasses.replace( /,/g, ' ' );

				if ( 'transform' ===  iconeffecttype ) {

					iconeffect = tdInputs.transform.val();

				} else if ( 'animate' ===  iconeffecttype ) {

					iconeffect = tdInputs.animate.val();

				} else if ( 'hover' ===  iconeffecttype ) {

					iconeffect = tdInputs.hover.val();
				}

				if ( iconcolor || 'custom-size' === iconsize ) {

					iconInlineStyles = ' style="';
					iconInlineStyles += iconcolor ? 'color:' + iconcolor + ';': '';
					iconInlineStyles += iconcustomsize ? 'font-size:' + iconcustomsize + ';': '';
					iconInlineStyles += '"';
				}

				// Generate icon html
				iconHTML  = '<span class="iconized '+iconset+' glyph-'+ iconname;
				iconHTML += ( iconsize && 'custom-size' !== iconsize ) ? ' ' + iconsize : '';
				iconHTML += iconalign ? ' ' + iconalign : '';
				iconHTML += iconeffect ? ' ' + iconeffect : '';
				iconHTML += ( iconcolor && iconcolorhover ) ? ' '+tdColorHoverClass : '';
				iconHTML += iconcustomclasses ? ' ' + iconcustomclasses : '';
				iconHTML += '"';
				iconHTML += iconInlineStyles;
				iconHTML += '></span>';

				if ( document.selection && iconizeTinymceDialog.range ) { // IE

					textarea.focus();
					iconizeTinymceDialog.range.text = iconHTML;
					iconizeTinymceDialog.range.moveToBookmark( iconizeTinymceDialog.range.getBookmark() );
					iconizeTinymceDialog.range.select();

					iconizeTinymceDialog.range = null;

				} else if ( typeof textarea.selectionStart !== 'undefined' ) {

					begin  = textarea.selectionStart;
					end    = textarea.selectionEnd;
					cursor = begin + iconHTML.length;

					textarea.value = textarea.value.substring( 0, begin ) + iconHTML + textarea.value.substring( end, textarea.value.length );

					// Update cursor position
					textarea.selectionStart = textarea.selectionEnd = cursor;
				}

				// Close the dialog
				textarea.focus();
				iconizeTinymceDialog.close();
			}
		},

		mceUpdate: function( ) {

			var i, index, ed,
				foundRequiredClasses = [],
				iconset = '', iconname = '', iconsize = '', iconcustomsize ='', iconalign = '', iconeffecttype = '', iconeffect = '', iconcolor = '', iconcolorhover = '', iconcustomclasses = '',
				icon = '', classes='';

			ed = editor;

			if ( tinymce.Env.ie ) {

				ed.selection.moveToBookmark( ed.updatedSelectionBookmark );
			}

			// Take icon name from input.
			iconname = tdInputs.name.val();

			// If there's no selected icon, display notice.
			if ( ! iconname ) {

				alert( iconizeDialogParams.l10n.no_icon_selected );

			} else {

				// Take all input values.
				iconset           = tdInputs.set.val();
				iconsize          = tdInputs.size.val();
				iconcustomsize    = tdInputs.customsize.val();
				iconalign         = tdInputs.align.val();
				iconeffecttype    = tdInputs.effect.val();
				iconcolor         = tdInputs.color.val();
				iconcolorhover    = tdInputs.hovercolor.prop('checked');
				iconcustomclasses = tdInputs.custom.tagit('assignedTags');
				iconcustomclasses = iconcustomclasses.toString();
				iconcustomclasses = iconcustomclasses.replace( /,/g, ' ' );

				// Take effect
				if ( 'transform' ===  iconeffecttype ) {

					iconeffect = tdInputs.transform.val();

				} else if ( 'animate' ===  iconeffecttype ) {

					iconeffect = tdInputs.animate.val();

				} else if ( 'hover' ===  iconeffecttype ) {

					iconeffect = tdInputs.hover.val();
				}

				// Get our node.
				node = ed.selection.getNode();

				// Check for icon.
				hasI = ed.dom.hasClass( node, 'iconized' );

				// Check for "iconized-stack" wrapper.
				iconStackWrapperNode = ed.dom.getParent(node, 'span.iconized-stack');

				// Get all classes from node and make an array of node classes.
				nodeClasses    = ed.dom.getAttrib( node, 'class' );
				nodeClassesArr = nodeClasses.split(/ /);

				// Take required classes from node.
				for ( i = 0; i<requiredClassesArr.length; i++ ) {

					index = $.inArray( requiredClassesArr[ i ], nodeClassesArr );

					if ( index !== -1 ) {

						foundRequiredClasses.push( requiredClassesArr[ i ] );
					}
				}

				// If node has an icon update it, otherwise make new icon and insert it to editor.
				if ( hasI ) {

					// If node is in icon stack, remove size and align classes from wrapper.
					if ( iconStackWrapperNode ) {

						// Get all classes from wrapper node and make an array of wrapper node classes.
						iconStackWrapperClasses    = ed.dom.getAttrib( iconStackWrapperNode, 'class' );
						iconStackWrapperClassesArr = iconStackWrapperClasses.split(/ /);

						// Remove size and align classes from wrapper.
						for ( i = 0; i < iconStackWrapperClassesArr.length; i++ ) {

							if ( $.inArray( iconStackWrapperClassesArr[ i ], allSizeClasses ) !== -1 ) {

								ed.dom.removeClass( iconStackWrapperNode, iconStackWrapperClassesArr[ i ] );

							} else if ( $.inArray( iconStackWrapperClassesArr[ i ], allAlignClasses ) !== -1 ) {

								ed.dom.removeClass( iconStackWrapperNode, iconStackWrapperClassesArr[ i ] );
							}
						}

						// Remove "data-mce-style" attr from wrapper so that we can update inline styles ( color, font-size ).
						$( iconStackWrapperNode ).removeAttr('data-mce-style');
					}

					// Remove all classes from node.
					$( node ).removeAttr('class');

					// Remove "data-mce-style" attr from node so that we can update inline styles ( color, font-size ).
					$( node ).removeAttr('data-mce-style');

					// Return back required classes.
					for ( i = 0; i < foundRequiredClasses.length; i++ ) {

						ed.dom.addClass( node, foundRequiredClasses[ i ] );
					}

					// Add icon set and icon name classes.
					ed.dom.addClass( node, iconset );
					ed.dom.addClass( node, 'glyph-'+iconname );

					// Add size and align classes to wrapper if there is one, otherwise add them to node.
					if ( iconStackWrapperNode ) {

						if ( 'custom-size' === iconsize ) {

							// Add custom font size
							ed.dom.setStyles( iconStackWrapperNode, { 'font-size' : iconcustomsize } );

						} else {

							// Remove font size, if any
							ed.dom.setStyles( iconStackWrapperNode, { 'font-size' : '' } );
							// Add predefined size class
							ed.dom.addClass( iconStackWrapperNode, iconsize );
						}

						if ( iconalign ) {

							ed.dom.addClass( iconStackWrapperNode, iconalign );
						}

					} else {

						if ( 'custom-size' === iconsize ) {

							// Add custom font size
							ed.dom.setStyles( node, { 'font-size' : iconcustomsize } );

						} else {

							// Remove font size, if any
							ed.dom.setStyles( node, { 'font-size' : '' } );
							// Add predefined size class
							ed.dom.addClass( node, iconsize );
						}

						if ( iconalign ) {

							ed.dom.addClass( node, iconalign );
						}
					}

					// Add other classes
					if ( iconeffect ) {

						ed.dom.addClass( node, iconeffect );
					}

					if ( iconcustomclasses ) {

						ed.dom.addClass( node, iconcustomclasses );
					}

					// Add "hover-color-change" class if needed
					if ( iconcolor && iconcolorhover ) {

						ed.dom.addClass( node, tdColorHoverClass );
					}

					// Update color
					ed.dom.setStyles( node, { 'color' : iconcolor } );

				} else {// No icon, make one.

					// Generate class value
					classes  = 'iconized '+iconset+' glyph-'+ iconname;
					classes += ( iconsize && 'custom-size' !== iconsize ) ? ' ' + iconsize : '';
					classes += iconalign ? ' ' + iconalign : '';
					classes += iconeffect ? ' ' + iconeffect : '';
					classes += iconcustomclasses ? ' ' + iconcustomclasses : '';

					// Create new icon tag.
					icon = ed.dom.create(
						'span',
						{ 'class': classes, 'contenteditable': 'false', 'data-mce-resize': 'false'},
						'<span class="iconized-placeholder" data-mce-resize="false">|icon|</span>'
					);

					// Add inline font-size style to it if needed.
					if ( 'custom-size' === iconsize ) {

						ed.dom.setStyle( icon, 'font-size', iconcustomsize );
					}

					// Add inline color style to it if needed.
					if ( iconcolor ) {

						ed.dom.setStyle( icon, 'color', iconcolor );

						if ( iconcolorhover ) {

							ed.dom.addClass( icon, tdColorHoverClass );
						}
					}

					// Insert newly created icon where caret position is.
					ed.selection.setNode( icon );
				}

				// Close the dialog and focus the editor.

				ed.focus();

				iconizeTinymceDialog.close();
			}
		},

		stack: function() {

			var i, ed,
				iconset = '', iconname = '', iconsize = '', iconcustomsize ='', iconalign = '',  iconeffecttype = '', iconeffect = '', iconcolor = '', iconcolorhover = '', iconcustomclasses = '',
				iconStacked = '', iconStackWrapper = '', iconCustomSize = '', classes = '';

			ed = editor;

			// Take our input values.
			iconset           = tdInputs.set.val();
			iconname          = tdInputs.name.val();
			iconsize          = tdInputs.size.val();
			iconcustomsize    = tdInputs.customsize.val();
			iconalign         = tdInputs.align.val();
			iconeffecttype    = tdInputs.effect.val();
			iconcolor         = tdInputs.color.val();
			iconcolorhover    = tdInputs.hovercolor.prop('checked');
			iconcustomclasses = tdInputs.custom.tagit('assignedTags');
			iconcustomclasses = iconcustomclasses.toString();
			iconcustomclasses = iconcustomclasses.replace(/,/g,' ');

			if ( 'transform' ===  iconeffecttype ) {

				iconeffect = tdInputs.transform.val();

			} else if ( 'animate' ===  iconeffecttype ) {

				iconeffect = tdInputs.animate.val();

			} else if ( 'hover' ===  iconeffecttype ) {

				iconeffect = tdInputs.hover.val();
			}

			// Get our node, get all classes from it and make an array of node classes
			node           = ed.selection.getNode();
			nodeClasses    = ed.dom.getAttrib( node, 'class' );
			nodeClassesArr = nodeClasses.split(/ /);

			// Remove size and align classes from existing icon and add "iconized-stack-base" class to it.
			for ( i = 0; i < nodeClassesArr.length; i++ ) {

				if ( $.inArray( nodeClassesArr[ i ], allSizeClasses ) !== -1 ) {

					ed.dom.removeClass( node, nodeClassesArr[ i ] );

				} else if ( $.inArray( nodeClassesArr[ i ], allAlignClasses ) !== -1 ) {

					ed.dom.removeClass( node, nodeClassesArr[ i ] );
				}

				if ( ed.dom.getStyle( node, 'font-size' ) ) {

					iconCustomSize = ed.dom.getStyle( node, 'font-size' );
				}
			}

			ed.dom.addClass( node, 'iconized-stack-base' );

			// Generate wrapper and wrap existing icon with it.
			iconStackWrapper  = '<span contenteditable="false" class="iconized-stack';
			iconStackWrapper += ( iconsize && 'custom-size' !== iconsize ) ? ' ' + iconsize : '';
			iconStackWrapper += iconalign ? ' ' + iconalign : '';
			iconStackWrapper += '"/>';

			$( node ).wrap( iconStackWrapper ).parent().css( 'font-size', iconCustomSize );

			// Generate class value for new icon.
			classes  = 'iconized ' + iconset + ' glyph-' + iconname;
			classes += iconeffect ? ' ' + iconeffect : '';
			classes += iconcustomclasses ? ' ' + iconcustomclasses : '';

			// Create new icon tag.
			iconStacked = ed.dom.create(
				'span',
				{ 'class': classes, 'contenteditable': 'false', 'data-mce-resize': 'false'},
				'<span class="iconized-placeholder" data-mce-resize="false">|icon|</span>'
			);

			// Add inline color style to it if needed.
			if ( iconcolor ) {

				ed.dom.setStyle( iconStacked, 'color', iconcolor );

				if ( iconcolorhover ) {

					ed.dom.addClass( iconStacked, tdColorHoverClass );
				}
			}

			// Insert newly created icon after existing and put cursor on it.
			ed.dom.insertAfter( iconStacked, node );
			ed.selection.setCursorLocation( iconStacked, 0 );

			// Return
			ed.focus();
			iconizeTinymceDialog.close();
		},

		setDefaultValues: function() {

			iconizeTinymceDialog.unselectIcon();
			iconizeTinymceDialog.clearColorPicker();
			iconizeTinymceDialog.clearCustomClasses();

			tdInputs.stack.hide();

			tdInputs.effect.val( allEffects[0] );
			tdInputs.transform.val('');
			tdInputs.animate.val('');
			tdInputs.hover.val('');

			iconizeTinymceDialog.showChildOptions( tdInputs.effect );

			tdInputs.size.val('');
			tdInputs.customsize.val('');
			iconizeTinymceDialog.showChildOptions( tdInputs.size );

			tdInputs.align.val('');
			tdInputs.color.val('');
			tdInputs.hovercolor.prop( 'checked', false );
			tdHoverColorWrapper.addClass('hidden');

			// Update title, size/align labels, and submit text
			tdTitle.text( iconizeDialogParams.l10n.insert );
			iconSizeLabel.text( iconizeDialogParams.l10n.icon_size_label );
			iconAlignLabel.text( iconizeDialogParams.l10n.icon_align_label );
			tdInputs.submit.text( iconizeDialogParams.l10n.insert );
		},

		selectIcon: function( iconSet, iconName ) {

			// Show correct list.
			tdInputs.set.val( iconSet );
			tdIconLists.find('.icons-list').hide();
			tdIconLists.find('#'+iconSet+'-icons-list').show();

			var iClassSelector = '.' + iconSet + '.glyph-' + iconName;

			// Highlight icon and update name input
			tdIconLists.find( iClassSelector ).parent().addClass('selected-icon');
			tdInputs.name.val( iconName );
		},

		unselectIcon: function() {

			tdIconLists.find('.icons-list-icon.selected-icon').removeClass('selected-icon');
			tdInputs.name.val('');
			tdInputs.set.val( allSetClasses[0] );
			tdIconLists.find('.icons-list').hide();
			tdIconLists.find('#'+allSetClasses[0]+'-icons-list').show();
		},

		setColor: function( color ) {

			tdInputs.color.val( color );
		},

		clearColorPicker: function() {

			tdInputs.color.wpColorPicker( 'color', '' );

			// remove prev color styles from picker
			$('#iconize-mce-modal .wp-color-result').removeAttr('style');
		},

		clearCustomClasses: function() {

			tdInputs.custom.tagit('removeAll');
		},

		scrollToIcon: function( loading ) {

			var	load = loading || false,
				toIcon = '',
				position = 0;

			toIcon = tdInputs.dialog.find('.selected-icon');

			if ( toIcon.length ) {

				position = toIcon.position().top - tdIconLists.position().top + tdIconLists.scrollTop() - 92;
			}

			if ( true === load ) {

				tdIconLists.animate( { scrollTop: position }, '500', 'swing', function() {

					tdIconLists.removeClass('loading-overlay');
				});

			} else {

				tdIconLists.scrollTop( position );
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

	$(document).ready( iconizeTinymceDialog.init );

})( jQuery );
