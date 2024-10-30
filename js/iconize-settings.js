jQuery(document).ready(function($) {

		function progress_bar( max, time ) {
			$( '.iconize-upload-progress .bar' ).stop().animate({"width": max + '%'}, time );
		}

		function ajaxSend( request, callback ) {

			$.ajax( {
				url: iconizeOptionsParams.ajaxurl,
				data: request,
				cache: false,
				contentType: false,
				dataType: 'json',
				processData: false,
				type: 'POST',
				success: function( response ) {
					callback( response, this );
				}
			} );
		}

		/* New Font */
		$( '#iconize-upload-files' ).on( 'change', function( evt ) {

			var reader = new FileReader(),
				files = evt.target.files,
				files_one = files[ 0 ];

			progress_bar( 100, 200 );

			// Closure to capture the file information.
			reader.onload = (function( theFile ) {
				return function( e ) {

					var file_name = escape( theFile.name );

					$( '.iconize-upload-progress' ).addClass( 'show' );

					var request = new FormData();

					request.append( "file_name", file_name );
					request.append( "source_file", theFile, file_name );
					request.append( "action", "iconize_icons_save_font" );
					request.append( "_wpnonce", $( '.iconize-upload-field' ).find( '#_wpnonce' ).val() );

					ajaxSend( request, function( response ) {

						if ( response.status_save === 'updated' ) {
							document.location.reload();
						}
						else if ( response.status_save === 'exist' ) {
							alert( iconizeOptionsParams.exist );
						} 
						else if ( response.status_save === 'failedopen' ) {
							alert( iconizeOptionsParams.failedopen );
						} 
						else if ( response.status_save === 'failedextract' ) {
							alert( iconizeOptionsParams.failedextract );
						} 
						else if ( response.status_save === 'emptyfile' ) {
							alert( iconizeOptionsParams.emptyfile );
						}
						else if ( response.status_save === 'updatefailed' ) {
							alert( iconizeOptionsParams.updatefailed );
						}

						progress_bar( 0, 5 );
						$( '.iconize-upload-progress' ).removeClass( 'show' );
					} );

				}
			})( files_one );

			reader.readAsDataURL( files_one );
		} );

		/* Delete Font */
		$( document ).on( 'click', '.iconize-option-remove-button', function( e ) {
			e.preventDefault();

			var conf = confirm( iconizeOptionsParams.delete );

			if ( conf == true ) {

				var request = new FormData(),
					$this = $( this ),
					fontKey = $this.data( 'remove' );

				$this.prop( 'disabled', true );

				request.append( "font_key", fontKey );
				request.append( "action", "iconize_icons_delete_font" );
				request.append( "_wpnonce", $( '.iconize-upload-field' ).find( '#_wpnonce' ).val() );

				ajaxSend( request, function( response ) {

					if ( 'remove' === response.status_save ) {

						document.location.reload();

					} else {
						
						alert( iconizeOptionsParams.deletefailed );
						$this.prop( 'disabled', false );
					}

				}, this );

			}
		} );
	}
);