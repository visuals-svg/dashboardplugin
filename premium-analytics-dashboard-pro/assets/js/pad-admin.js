/**
 * Premium Analytics Dashboard Pro — Admin JS
 *
 * Theme toggle (dark/light), color picker aur company logo media
 * uploader ko wire karta hai. Har AJAX call nonce ke saath jaati hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

( function ( $ ) {
	'use strict';

	$( function () {
		var $wrap = $( '.pad-wrap' );

		/**
		 * Dark / Light mode toggle — turant UI update karta hai,
		 * phir background me AJAX se user preference save karta hai.
		 */
		$( '#pad-theme-toggle' ).on( 'click', function () {
			var current = $wrap.attr( 'data-theme' ) === 'dark' ? 'dark' : 'light';
			var next = current === 'dark' ? 'light' : 'dark';

			$wrap.attr( 'data-theme', next );
			document.dispatchEvent( new CustomEvent( 'pad:theme-changed', { detail: { mode: next } } ) );

			$.post( padAdmin.ajaxUrl, {
				action: 'pad_toggle_theme_mode',
				nonce: padAdmin.nonce,
				mode: next
			} );
		} );

		/**
		 * WordPress ka built-in color picker theme color field par.
		 */
		if ( $.fn.wpColorPicker ) {
			$( '.pad-color-field' ).wpColorPicker();
		}

		/**
		 * Company logo ke liye WordPress Media Library uploader.
		 */
		var padMediaFrame;

		$( '#pad-upload-logo-btn' ).on( 'click', function ( event ) {
			event.preventDefault();

			if ( padMediaFrame ) {
				padMediaFrame.open();
				return;
			}

			padMediaFrame = wp.media( {
				title: padAdmin.selectLogoTitle || 'Select Company Logo',
				button: { text: padAdmin.selectLogoTitle || 'Use this logo' },
				multiple: false,
				library: { type: 'image' }
			} );

			padMediaFrame.on( 'select', function () {
				var attachment = padMediaFrame.state().get( 'selection' ).first().toJSON();

				$( '#pad-company-logo' ).val( attachment.url );
				$( '.pad-logo-preview' ).remove();
				$( '#pad-company-logo' ).closest( '.pad-form-field' ).append(
					$( '<img>' ).addClass( 'pad-logo-preview' ).attr( 'src', attachment.url )
				);
			} );

			padMediaFrame.open();
		} );

		/**
		 * Notification bell — dropdown toggle, list fetch, mark-read.
		 */
		var $notifBtn      = $( '#pad-notification-toggle' );
		var $notifDropdown = $( '#pad-notification-dropdown' );
		var $notifBadge    = $( '#pad-notification-badge' );
		var $notifList     = $( '#pad-notification-list' );

		if ( $notifBtn.length ) {

			/**
			 * Basic HTML-escaping (client-side defense in depth).
			 *
			 * @param {*} value Raw value.
			 * @return {string}
			 */
			function escapeHtml( value ) {
				var div = document.createElement( 'div' );
				div.textContent = null === value || 'undefined' === typeof value ? '' : String( value );
				return div.innerHTML;
			}

			/**
			 * href attribute ke liye safe escaping.
			 *
			 * @param {string} value Raw URL.
			 * @return {string}
			 */
			function escapeAttr( value ) {
				return escapeHtml( value ).replace( /"/g, '&quot;' );
			}

			/**
			 * Server se recent notifications + unread count fetch karta hai.
			 */
			function padLoadNotifications() {

				$.getJSON( padAdmin.ajaxUrl, {
					action: 'pad_get_notifications',
					nonce: padAdmin.nonce
				} ).done( function ( response ) {

					if ( ! response || ! response.success ) {
						return;
					}

					var data = response.data;

					if ( data.unread_count > 0 ) {
						$notifBadge.text( data.unread_count > 9 ? '9+' : data.unread_count ).prop( 'hidden', false );
					} else {
						$notifBadge.prop( 'hidden', true );
					}

					if ( ! data.items.length ) {
						$notifList.html( '<p class="pad-empty-state pad-empty-state-compact"><span>' + ( padAdmin.strings.noNotifications || 'No notifications yet.' ) + '</span></p>' );
						return;
					}

					var html = '';

					data.items.forEach( function ( item ) {
						var unreadClass = item.is_read ? '' : ' pad-notification-unread';
						html += '<a href="' + escapeAttr( item.link || '#' ) + '" class="pad-notification-item' + unreadClass + '" data-id="' + item.id + '">';
						html += '<strong>' + escapeHtml( item.title ) + '</strong>';
						html += '<p>' + escapeHtml( item.message ) + '</p>';
						html += '<span>' + escapeHtml( item.created_at ) + '</span>';
						html += '</a>';
					} );

					$notifList.html( html );
				} );
			}

			$notifBtn.on( 'click', function ( event ) {
				event.stopPropagation();
				var isHidden = $notifDropdown.prop( 'hidden' );
				$notifDropdown.prop( 'hidden', ! isHidden );

				if ( isHidden ) {
					padLoadNotifications();
				}
			} );

			$( document ).on( 'click', function ( event ) {
				if ( ! $( event.target ).closest( '.pad-notification-wrap' ).length ) {
					$notifDropdown.prop( 'hidden', true );
				}
			} );

			$notifList.on( 'click', '.pad-notification-item', function () {
				var id = $( this ).data( 'id' );
				$.post( padAdmin.ajaxUrl, { action: 'pad_mark_notification_read', nonce: padAdmin.nonce, id: id } );
			} );

			$( '#pad-mark-all-read' ).on( 'click', function () {
				$.post( padAdmin.ajaxUrl, { action: 'pad_mark_all_notifications_read', nonce: padAdmin.nonce } ).done( function () {
					padLoadNotifications();
				} );
			} );

			padLoadNotifications();
		}
	} );
} )( jQuery );
