(function($) {

	"use strict";

	tinymce.create( 'tinymce.plugins.IconizePlugin', {

		init : function( ed, url ) {

			var t = this;

			var iconButton;

			// Allow "wplink" plugin to add links to icons, unlink icon when "unlink" command is called ( since Iconize v1.0.1 ).
			ed.on('BeforeExecCommand', function( e ) {

				var cmd = e.command,
					node = ed.selection.getNode(),
					link = ed.dom.getParent( node, 'A' ),
					iconStackWrapperNode = ed.dom.getParent( node, 'span.iconized-stack' ),
					n, b;

				if ( 'WP_Link' === cmd ) {

					if ( ed.dom.hasClass( node, 'iconized' ) ) {

						// Reference node.
						n = iconStackWrapperNode ? iconStackWrapperNode : node;

						link = ed.dom.getParent( node, 'A' );

						if ( ! link ) {

							// Wrapp icon element with link.
							$( n ).wrap( '<a href="http://">' );
						}

						// Collapse selection and let "wplink" plugin find the link.
						ed.selection.select( n );
						ed.selection.collapse(0);
					}

				} else if ( 'unlink' === cmd ) {

					if ( ( ed.dom.getParent( node, '.iconized' ) || iconStackWrapperNode ) && link ) {

						// Unlink icon.
						b = ed.selection.getBookmark();
						ed.dom.remove( link, 1 );
						ed.selection.moveToBookmark( b );
					}

				}// else if ( tinymce.Env.webkit && ( 'wpFullScreenOn' === cmd ) ) {
				//} else if ( tinymce.Env.webkit && ( 'wpFullScreenOff' === cmd ) ) {
				//}
			});

			// Workaround for WP 3.9 to remove the link inserted above if "wplink" dialog is canceled.
			$( '#wp-link' ).on( 'visibility', function() {

				var $element = $( this );

				var timer = setInterval( function() {

					if( $element.is( ':hidden' ) ) {

						var node = ed.selection.getNode(),
							link = ed.dom.getParent( node, 'A' ),
							b;

						if ( null !== link && ( ! ed.dom.getAttrib( link, 'href' ) || 'http://' === ed.dom.getAttrib( link, 'href' ) ) ) {

							// Unlink icon
							b = ed.selection.getBookmark();
							ed.dom.remove( link, 1 );
							ed.selection.moveToBookmark( b );

							ed.focus();
						}
					}
				}, 300 );

			}).trigger( 'visibility' );


			ed.on('init', function() {

				// When clicked on icon, select "iconized" instead of placeholder, disable drag&drop, disable resizing on IE.
				ed.on( 'mousedown', function( e ) {

					var body = ed.getBody();

					if ( ed.dom.hasClass( e.target, 'iconized' ) || ed.dom.hasClass( e.target, 'iconized-placeholder' ) ) {

						if ( tinymce.Env.ie ) {

							// Disable editing.
							$(body).attr({'contenteditable': false});
						}

						e.preventDefault();
						e.stopPropagation();

						// Select icon.
						t._selectIcon( ed, e.target );

						ed.focus();

						return false;

					} else {

						if ( tinymce.Env.ie ) {

							// Enable editing.
							$(body).attr({'contenteditable': true});
						}
					}
				});

				// on mouseup event, if icon is wrapped with link enable editing in IE ( since Iconize v1.0.1 ).
				ed.on( 'mouseup', function( e ) {

					if ( tinymce.Env.ie ) {

						var body = ed.getBody();

						if ( ( ed.dom.hasClass( e.target, 'iconized' ) || ed.dom.hasClass( e.target, 'iconized-placeholder' ) ) && ed.dom.getParent( e.target, 'A' ) ) {

							$(body).attr({'contenteditable': true});
						}
					}
				});

				// On keydown event, if the cursor is placed on node with "iconized" class, move it before or after the node.
				ed.on( 'keydown', function( e ) {

					var code, node, iconStackWrapperNode, n, el,
						body = ed.getBody(),
						invisibleChar = '\uFEFF';

					code = e.keyCode ? e.keyCode : e.which;

					// Do nothing if user press cmd, ctrl, F#, backspace, delete keys.
					if ( ! e.metaKey || ! e.ctrlKey || ! ( code >= 112 && code <= 123 ) || 8 !== code || 46 !== code ) {

						node = ed.selection.getNode();

						if ( ed.dom.hasClass( node, 'iconized' ) ) {

							if ( tinymce.Env.ie ) {

								$( body ).attr({'contenteditable': true});
							}

							e.preventDefault();

							// Check for "iconized-stack" wrapper.
							iconStackWrapperNode = ed.dom.getParent( node, 'span.iconized-stack' );

							// Reference node.
							n = iconStackWrapperNode ? iconStackWrapperNode : node;

							// Create cursor placeholder.
							el = ed.dom.create( 'span', { 'class': 'iconize-cph', 'data-mce-bogus': 'true' }, invisibleChar );

							// If left or up arrow keys, insert cursor placeholder before reference node.
							if( 37 === code || 38 === code ) {

								n.parentNode.insertBefore( el, n );

							} else {

								// Insert cursor placeholder after reference node.
								ed.dom.insertAfter( el, n );
							}

							// Move the cursor to cursor placeholder element and colapse selection.
							ed.selection.setCursorLocation( el, 0 );
							ed.selection.collapse(1);

							// Remove cursor placeholder.
							ed.dom.remove( el );

							return false;
						}
					}
				});
			});

			// Register commands
			ed.addCommand( 'insertIcon', function() {

				if ( ( ! iconButton || ! iconButton.disabled() ) && typeof window.iconizeTinymceDialog !== 'undefined' ) {

					window.iconizeTinymceDialog.open( ed.id );
				}
			});

			ed.addCommand( 'swapIconsPositions', function() {

				var node = ed.selection.getNode(),
					prevIcon = ed.dom.getPrev( node, '.iconized' ),
					nextIcon = ed.dom.getNext( node, '.iconized' ); // in IE ( and Firefox in some situations ) user can actually select previous icon, cool...

				if ( prevIcon ) {

					ed.dom.insertAfter( prevIcon,node );
					ed.selection.select( prevIcon );

				} else if ( nextIcon ) {

					node.parentNode.insertBefore( nextIcon, node );
					ed.selection.select( nextIcon );
				}
			});

			ed.addCommand( 'swapIconsSizes', function() {

				var node = ed.selection.getNode(),
					prevIcon = ed.dom.getPrev( node, '.iconized' ),
					nextIcon = ed.dom.getNext( node, '.iconized' ),
					siblingIcon = prevIcon || nextIcon,
					stackBaseClass = 'iconized-stack-base';

				if ( siblingIcon ) {

					if ( ed.dom.hasClass( siblingIcon, stackBaseClass ) ) {

						ed.dom.removeClass( siblingIcon, stackBaseClass );
						ed.dom.addClass( node, stackBaseClass );

					} else if ( ed.dom.hasClass( node,stackBaseClass ) ) {

						ed.dom.removeClass( node, stackBaseClass );
						ed.dom.addClass( siblingIcon, stackBaseClass );
					}
				}
			});

			ed.addCommand( 'removeIcon', function() {

				var iconStackWrapperClasses, iconStackWrapperClassesArr, prevIcon, nextIcon, siblingIcon, iconStackSize, iconStackAlign,
					node = ed.selection.getNode(),
					hasI = ed.dom.hasClass( node, 'iconized' ),
					iconStackWrapperNode = ed.dom.getParent( node, 'span.iconized-stack' ),
					siblingIconHTML = '',
					allSizeClasses = iconizeTinymceDialog.getSelectOptions( $('#mce-icon-size') ),
					allAlignClasses = iconizeTinymceDialog.getSelectOptions( $('#mce-icon-align') );

				if ( hasI ) {

					// If there are stacked icons remove only selected icon.
					if ( iconStackWrapperNode ) {

						// We want to take size and align classes from wrapper and add them to remaining icon later.

						// Get all classes from wrapper node and make an array of wrapper node classes.
						iconStackWrapperClasses = ed.dom.getAttrib( iconStackWrapperNode, 'class' );
						iconStackWrapperClassesArr = iconStackWrapperClasses.split(/ /);

						// Take size and align classes.
						for ( var i = 0; i < iconStackWrapperClassesArr.length; i++ ) {

							if ( $.inArray( iconStackWrapperClassesArr[ i ], allSizeClasses ) !== -1 ) {

								iconStackSize = iconStackWrapperClassesArr[ i ];
							}

							if ( $.inArray( iconStackWrapperClassesArr[ i ], allAlignClasses ) !== -1 ) {

								iconStackAlign = iconStackWrapperClassesArr[ i ];
							}
						}

						// Take node sibling ( icon we want to keep ).
						prevIcon = ed.dom.getPrev( node, '.iconized' );
						nextIcon = ed.dom.getNext( node, '.iconized' );

						siblingIcon = prevIcon || nextIcon;

						if ( siblingIcon ) {

							// Remove "iconized-stack-base" class from it.
							if ( ed.dom.hasClass( siblingIcon, 'iconized-stack-base' ) ) {

								ed.dom.removeClass( siblingIcon, 'iconized-stack-base' );
							}

							// Add size and align classes to it.
							if ( iconStackSize ) {

								ed.dom.addClass( siblingIcon, iconStackSize );
							}

							if ( iconStackAlign ) {

								ed.dom.addClass( siblingIcon, iconStackAlign );
							}

							// Take HTML of remaining icon.
							siblingIconHTML = siblingIcon.outerHTML;
						}

						// Remove icon stack.
						ed.dom.remove( iconStackWrapperNode );

						// Insert remaining icon.
						ed.execCommand( 'mceInsertContent', false, siblingIconHTML );

					} else { // No stacked icons, remove node.

						ed.dom.remove( node );
					}

					// Fire nodeChanged() event so that plugin buttons states can be applied immediately.
					ed.nodeChanged();
				}
			});

			// Register buttons.
			ed.addButton( 'insert_icon', {
				title: t._translate( ed, 'insert_icon_title' ),
				cmd: 'insertIcon',
				image: url + '/images/flag.png',
				//icon: 'insert_icon',
				onPostRender: function() {

					var iconButton = this;

					ed.on( 'NodeChange', function( e ) {

						var n = e.element;
						// Active if node ( or parent node ) has "iconized" class and selection is not colapsed
						iconButton.active( ed.dom.getParent( n, '.iconized' ) && ! ed.selection.isCollapsed() );
					});
				}
			});

			ed.addButton( 'swap_icon_positions', {
				title: t._translate( ed, 'swap_pos_title' ),
				cmd: 'swapIconsPositions',
				image: url+'/images/swappos.png',
				onPostRender: function() {

					var ctrl = this;

					ed.on( 'NodeChange', function( e ) {

						var n = e.element;
						// Active if node ( or parent node ) has "iconized" class, is wrapped with "iconized-stack" class, and have sibling with "iconized" class )
						// Disabled otherwise
						ctrl.disabled( ! ( ed.dom.getParent( n, '.iconized' ) && ed.dom.getParent( n, '.iconized-stack' ) && ( ed.dom.getPrev( n, '.iconized' ) || ed.dom.getPrev( n.parentNode, '.iconized' ) || ed.dom.getNext( n, '.iconized' ) || ed.dom.getNext( n.parentNode, '.iconized' ) ) ) );
					});
				}
			});

			ed.addButton( 'swap_icon_sizes', {
				title: t._translate( ed, 'swap_size_title' ),
				cmd: 'swapIconsSizes',
				image: url+'/images/swapsizes.png',
				onPostRender: function() {

					var ctrl = this;

					ed.on( 'NodeChange', function( e ) {

						var n = e.element;
						// Active if node ( or parent node ) has "iconized" class, is wrapped with "iconized-stack" class, and have sibling with "iconized" class )
						// Disabled otherwise
						ctrl.disabled( ! ( ed.dom.getParent( n, '.iconized' ) && ed.dom.getParent( n, '.iconized-stack' ) && ( ed.dom.getPrev( n, '.iconized' ) || ed.dom.getPrev( n.parentNode, '.iconized' ) || ed.dom.getNext( n, '.iconized' ) || ed.dom.getNext( n.parentNode, '.iconized' ) ) ) );
					});
				}
			});

			ed.addButton( 'remove_icon', {
				title: t._translate( ed, 'remove_icon_title' ),
				cmd: 'removeIcon',
				image: url+'/images/unflag.png',
				onPostRender: function() {

					var ctrl = this;

					ed.on( 'NodeChange', function( e ) {

						var n = e.element;
						// Active if node ( or parent node ) has "iconized" class and selection is not colapsed
						// Disabled otherwise
						ctrl.disabled( ! ( ed.dom.getParent( n, '.iconized' ) && ! ed.selection.isCollapsed() ) );
					});
				}
			});

			// Add listeners to handle placeholder.
			t._handlePlaceholder( ed );

			// Select ".iconized" if placehoder is selected on node change event.
			ed.on( 'NodeChange', function( e ) {

				var n = e.element;

				if ( 'SPAN' === n.nodeName && ed.dom.hasClass( n, 'iconized-placeholder' ) ) {

					t._selectIcon( ed, n );
				}
			});
		},

		// Plugin info.
		getInfo: function() {

			return {
				longname: 'Iconize WordPress Plugin',
				author: 'THATplugin',
				authorurl: 'https://thatplugin.com/',
				infourl: 'https://thatplugin.com/',
				version: tinymce.majorVersion + "." + tinymce.minorVersion
			};
		},

		// Internal functions.

		// Select icon element.
		_selectIcon: function( ed, sel ) {

			if ( ed.dom.hasClass( sel, 'iconized-placeholder' ) ) {

				// Workaround for webkit where user can't select floated icon ( in case icon is the only element in editor ).
				if ( tinymce.Env.webkit ) {

					// Remove contenteditable attr, select icon, set contenteditable="false".
					$( sel.parentNode ).removeAttr('contenteditable');
					ed.selection.select( sel.parentNode );
					$( sel.parentNode ).attr({'contenteditable': false});

				} else {

					ed.selection.select( sel.parentNode );
				}

			} else {

				ed.selection.select( sel );
			}
		},

		// Handle placeholders.
		_handlePlaceholder: function( ed ) {

			ed.on( 'ResolveName', function( e ) {
				var dom = ed.dom,
					target = e.target;

				// If  node has "iconized" or "iconized-stack" class, display only that class name in path bar
				if ( ed.dom.hasClass( target, 'iconized' ) ) {

					e.name = ed.selection.isCollapsed() ? 'placeholder' : 'iconized';

				} else if ( ed.dom.hasClass( target, 'iconized-stack' ) ) {

					e.name = ed.selection.isCollapsed() ? 'placeholder' : 'iconized-stack';
				}
			});

			/*
			* Disable editing of icons in visual editor by adding contenteditable="false" attribute.
			* Add hidden absolutely positioned placeholder to each icon to disable removal of empty tags and to ensure proper selection of icons on click event.
			*/
			ed.on( 'BeforeSetContent', function( e ) {

				if ( e.content ) {

					// Create div element so we can manipulate content in it.
					var el = $('<div></div>');

					// Convert content string to html and add it to our element.
					el.html( e.content );

					// Manipulate icons.
					$( el ).find(".iconized").each( function() {

						var $this = $(this);

						// If there is icon stack wrapper, add contenteditable="false" to it too.
						if ( $this.parent(".iconized-stack") ) {

							$this.parent(".iconized-stack").attr({'contenteditable': "false", 'data-mce-resize': "false"});
							$this.empty().attr('data-mce-resize','false').removeClass('mceItemNoResize').append('<span class="iconized-placeholder" data-mce-resize="false">|icon|</span>');

						} else {

							// Add placeholder and contenteditable attr to icon tags
							$this.empty().attr('contenteditable',"false").removeClass('mceItemNoResize').append('<span class="iconized-placeholder" data-mce-resize="false">|icon|</span>');
						}
					});

					// Convert content html back to string, save it, and destroy div element.
					var string = $( el ).html();
					e.content = string;
					$( el ).remove();
				}
			});

			// Remove contenteditable attributes and placeholders from content
			ed.on( 'PostProcess', function( e ) {

				if ( e.get ) {

					// Create div element so we can manipulate content in it.
					var el = $('<div></div>');

					// convert content string to html and add it to our element
					el.html( e.content );

					// Manipulate icons.
					$( el ).find(".iconized").each( function() {

						var $this = $(this);

						// Remove contenteditable attr from icon stack wrapper.
						if ( $this.parent(".iconized-stack") ) {

							$this.parent(".iconized-stack").removeAttr('contenteditable').removeAttr('data-mce-resize').removeClass('mceItemNoResize');
						}

						// Remove placeholder and contenteditable attr.
						$this.empty().removeAttr('contenteditable').removeAttr('data-mce-resize').removeClass('mceItemNoResize');
					});

					// Convert content html back to string, save it, and destroy div element.
					var string = $( el ).html();
					e.content = string;
					$( el ).remove();
				}
			});
		},

		_translate: function( ed, string ) {

			if ( this._isGutenbergActive() ) {
				return tinymce.translate( string );
			} else {
				return ed.getLang( 'iconize_mce.' + string );
			}
		},

		_isGutenbergActive: function() {
			return document.body.classList.contains( 'block-editor-page' );
		}
	});

	// Register plugin
	tinymce.PluginManager.add( 'iconize_mce', tinymce.plugins.IconizePlugin );

})(jQuery);
