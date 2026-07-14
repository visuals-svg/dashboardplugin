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
	} );
} )( jQuery );
