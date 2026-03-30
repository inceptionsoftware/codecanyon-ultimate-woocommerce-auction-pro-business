/**
 * UWA License Server – Admin JavaScript
 *
 * Handles:
 *  - Purchase code UUID format validation
 *  - "Verify with Envato" AJAX auto-fill
 *  - "Test Envato Connection" AJAX
 *  - Regenerate secret key (with confirmation)
 *  - Toggle show/hide for the Envato token field
 *  - Delete / ban / deactivate-all row actions (AJAX)
 *  - Clear logs (AJAX)
 *  - License type ↔ max-domains sync
 *
 * Requires: jQuery (loaded by WordPress core)
 */

/* global ulsAdminData */

( function ( $ ) {
	'use strict';

	/* ------------------------------------------------------------------ */
	/* Helpers                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * UUID v4 regex (Envato purchase code format).
	 * xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx (all hex digits)
	 */
	var UUID_REGEX = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

	/**
	 * Show an inline notice next to a target element.
	 *
	 * @param {jQuery}  $el      Container element.
	 * @param {string}  message  Text to display.
	 * @param {string}  type     'success' or 'error'.
	 */
	function showNotice( $el, message, type ) {
		$el
			.removeClass( 'success error' )
			.addClass( 'uls-inline-notice ' + type )
			.text( message )
			.show();
	}

	/**
	 * Show a loading spinner in an element.
	 *
	 * @param {jQuery}  $el  Target element.
	 * @param {string}  msg  Optional loading message.
	 */
	function showSpinner( $el, msg ) {
		$el
			.removeClass( 'uls-inline-notice success error' )
			.html( '<span class="uls-spinner"></span> ' + ( msg || '' ) )
			.show();
	}

	/* ------------------------------------------------------------------ */
	/* Purchase-code format validation (Add License form)                  */
	/* ------------------------------------------------------------------ */

	var $codeInput  = $( '#purchase_code' );
	var $codeError  = $( '#uls-code-format-error' );
	var $addForm    = $( '#uls-add-license-form' );

	if ( $codeInput.length ) {
		$codeInput.on( 'input blur', function () {
			var val = $( this ).val().trim();
			if ( val.length === 0 ) {
				$codeError.hide();
				return;
			}
			if ( ! UUID_REGEX.test( val ) ) {
				$codeError.show();
				$( this ).addClass( 'uls-input-error' );
			} else {
				$codeError.hide();
				$( this ).removeClass( 'uls-input-error' );
			}
		} );
	}

	if ( $addForm.length ) {
		$addForm.on( 'submit', function ( e ) {
			var code = $codeInput.val().trim();
			if ( code && ! UUID_REGEX.test( code ) ) {
				e.preventDefault();
				$codeError.show();
				$codeInput.focus();
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* License type ↔ max domains sync                                     */
	/* ------------------------------------------------------------------ */

	var $licenseType = $( '#license_type' );
	var $maxDomains  = $( '#max_domains' );

	if ( $licenseType.length && $maxDomains.length ) {
		$licenseType.on( 'change', function () {
			if ( 'extended' === $( this ).val() ) {
				$maxDomains.val( 999 );
			} else {
				$maxDomains.val( 1 );
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Verify with Envato (Add License)                                    */
	/* ------------------------------------------------------------------ */

	$( '#uls-verify-envato' ).on( 'click', function () {
		var code = $codeInput.val().trim();

		if ( ! UUID_REGEX.test( code ) ) {
			$codeError.show();
			$codeInput.focus();
			return;
		}

		$codeError.hide();

		var $btn    = $( this );
		var $status = $( '#uls-verify-status' );

		$btn.prop( 'disabled', true );
		showSpinner( $status, ulsAdminData.i18n.verifying );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action:        'uls_verify_envato_purchase',
				nonce:         ulsAdminData.nonce,
				purchase_code: code,
			},
			function ( response ) {
				$btn.prop( 'disabled', false );

				if ( response.success && response.data ) {
					var d = response.data;

					// Auto-fill the form fields.
					$( '#buyer_name' ).val( d.buyer_name  || '' );
					$( '#buyer_email' ).val( d.buyer_email || '' );

					if ( d.license_type ) {
						$( '#license_type' ).val( d.license_type ).trigger( 'change' );
					}

					if ( d.support_until ) {
						$( '#support_until' ).val( d.support_until );
					}

					if ( d.purchase_date ) {
						$( '#purchase_date' ).val( d.purchase_date );
					}

					showNotice( $status, '✓ ' + ( response.data.item_name || 'Verified!' ), 'success' );
				} else {
					var errMsg = ( response.data && response.data.message )
						? response.data.message
						: 'Verification failed.';
					showNotice( $status, '✗ ' + errMsg, 'error' );
				}
			}
		).fail( function () {
			$btn.prop( 'disabled', false );
			showNotice( $status, '✗ Request failed. Check your connection.', 'error' );
		} );
	} );

	/* ------------------------------------------------------------------ */
	/* Test Envato Connection (Settings page)                              */
	/* ------------------------------------------------------------------ */

	$( '#uls-test-envato' ).on( 'click', function () {
		var $btn    = $( this );
		var $result = $( '#uls-envato-test-result' );

		$btn.prop( 'disabled', true );
		showSpinner( $result, ulsAdminData.i18n.testing );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action: 'uls_test_envato_connection',
				nonce:  ulsAdminData.nonce,
			},
			function ( response ) {
				$btn.prop( 'disabled', false );
				if ( response.success ) {
					showNotice( $result, '✓ ' + response.data.message, 'success' );
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : 'Connection failed.';
					showNotice( $result, '✗ ' + msg, 'error' );
				}
			}
		).fail( function () {
			$btn.prop( 'disabled', false );
			showNotice( $result, '✗ Request failed.', 'error' );
		} );
	} );

	/* ------------------------------------------------------------------ */
	/* Toggle show/hide Envato token field                                 */
	/* ------------------------------------------------------------------ */

	$( '.uls-toggle-token' ).on( 'click', function () {
		var targetId = $( this ).data( 'target' );
		var $field   = $( '#' + targetId );
		var $icon    = $( this ).find( '.dashicons' );

		if ( 'password' === $field.attr( 'type' ) ) {
			$field.attr( 'type', 'text' );
			$icon.removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
		} else {
			$field.attr( 'type', 'password' );
			$icon.removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
		}
	} );

	/* ------------------------------------------------------------------ */
	/* Regenerate Secret Key                                               */
	/* ------------------------------------------------------------------ */

	$( '#uls-regenerate-key' ).on( 'click', function () {
		if ( ! window.confirm( ulsAdminData.confirmReg ) ) {
			return;
		}

		var $btn    = $( this );
		var $status = $( '#uls-regenerate-status' );

		$btn.prop( 'disabled', true );
		showSpinner( $status, ulsAdminData.i18n.regenerating );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action: 'uls_regenerate_secret_key',
				nonce:  ulsAdminData.nonce,
			},
			function ( response ) {
				$btn.prop( 'disabled', false );
				if ( response.success ) {
					$( '#uls_secret_key_display' ).val( response.data.key );
					showNotice( $status, '✓ ' + response.data.message, 'success' );
				} else {
					showNotice( $status, '✗ Failed to regenerate key.', 'error' );
				}
			}
		).fail( function () {
			$btn.prop( 'disabled', false );
			showNotice( $status, '✗ Request failed.', 'error' );
		} );
	} );

	/* ------------------------------------------------------------------ */
	/* Row actions: Ban license                                            */
	/* ------------------------------------------------------------------ */

	$( document ).on( 'click', '.uls-ban-license', function ( e ) {
		e.preventDefault();

		if ( ! window.confirm( ulsAdminData.confirmBan ) ) {
			return;
		}

		var $link      = $( this );
		var licenseId  = $link.data( 'id' );
		var nonce      = $link.data( 'nonce' );
		var $row       = $( '#license-row-' + licenseId );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action:     'uls_ban_license',
				nonce:      nonce,
				license_id: licenseId,
			},
			function ( response ) {
				if ( response.success ) {
					// Update the status badge in-place.
					$row.find( '.uls-badge' )
						.removeClass( 'uls-badge-active uls-badge-inactive uls-badge-expired' )
						.addClass( 'uls-badge-banned' )
						.text( 'Banned' );

					// Remove the ban link since it's already banned.
					$link.closest( 'span.ban' ).remove();
				} else {
					alert( response.data ? response.data.message : 'Action failed.' );
				}
			}
		);
	} );

	/* ------------------------------------------------------------------ */
	/* Row actions: Delete license (soft delete)                           */
	/* ------------------------------------------------------------------ */

	$( document ).on( 'click', '.uls-delete-license', function ( e ) {
		e.preventDefault();

		if ( ! window.confirm( ulsAdminData.confirmDel ) ) {
			return;
		}

		var $link      = $( this );
		var licenseId  = $link.data( 'id' );
		var nonce      = $link.data( 'nonce' );
		var $row       = $( '#license-row-' + licenseId );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action:     'uls_delete_license',
				nonce:      nonce,
				license_id: licenseId,
			},
			function ( response ) {
				if ( response.success ) {
					// Fade out and remove the row.
					$row.fadeOut( 400, function () {
						$( this ).remove();
					} );
				} else {
					alert( response.data ? response.data.message : 'Delete failed.' );
				}
			}
		);
	} );

	/* ------------------------------------------------------------------ */
	/* Row actions: Deactivate all domains                                 */
	/* ------------------------------------------------------------------ */

	$( document ).on( 'click', '.uls-deactivate-all', function ( e ) {
		e.preventDefault();

		var $link      = $( this );
		var licenseId  = $link.data( 'id' );
		var nonce      = $link.data( 'nonce' );
		var $row       = $( '#license-row-' + licenseId );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action:     'uls_deactivate_all',
				nonce:      nonce,
				license_id: licenseId,
			},
			function ( response ) {
				if ( response.success ) {
					// Reset the domains counter to 0/max.
					var $domainsCell = $row.find( '.column-domains span' );
					var text         = $domainsCell.text();
					var parts        = text.split( '/' );
					if ( parts.length === 2 ) {
						$domainsCell.text( '0 / ' + parts[ 1 ].trim() );
					}
					$domainsCell.removeClass( 'uls-domains-full' );

					// Brief visual confirmation.
					$row.addClass( 'updated' );
					setTimeout( function () { $row.removeClass( 'updated' ); }, 1500 );
				} else {
					alert( response.data ? response.data.message : 'Action failed.' );
				}
			}
		);
	} );

	/* ------------------------------------------------------------------ */
	/* Clear Logs                                                          */
	/* ------------------------------------------------------------------ */

	$( '#uls-clear-logs' ).on( 'click', function () {
		if ( ! window.confirm( ulsAdminData.confirmLog ) ) {
			return;
		}

		var $btn    = $( this );
		var $result = $( '#uls-clear-logs-result' );
		var nonce   = $btn.data( 'nonce' );

		$btn.prop( 'disabled', true );

		$.post(
			ulsAdminData.ajaxUrl,
			{
				action: 'uls_clear_logs',
				nonce:  nonce,
			},
			function ( response ) {
				$btn.prop( 'disabled', false );
				if ( response.success ) {
					showNotice( $result, '✓ ' + response.data.message, 'success' );
				} else {
					showNotice( $result, '✗ Failed to clear logs.', 'error' );
				}
			}
		).fail( function () {
			$btn.prop( 'disabled', false );
			showNotice( $result, '✗ Request failed.', 'error' );
		} );
	} );

	/* ------------------------------------------------------------------ */
	/* Auto-dismiss admin notices after 4 seconds                          */
	/* ------------------------------------------------------------------ */

	setTimeout( function () {
		$( '.is-dismissible.notice' ).not( '.notice-error' ).fadeOut( 600 );
	}, 4000 );

} )( jQuery );
