/**
 * Premium Analytics Dashboard Pro — Frontend Visitor Tracker
 *
 * Koi build step, koi framework nahi — plain vanilla JS. Page load
 * par ek pageview record karta hai, aur page chhodte waqt
 * navigator.sendBeacon se time-on-page bhejta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

( function () {
	'use strict';

	if ( 'undefined' === typeof padTracker ) {
		return;
	}

	var startedAt = Date.now();
	var pageviewId = 0;
	var endSent = false;

	/**
	 * Screen resolution, language aur timezone browser se nikaal kar
	 * server ko pageview start ki soochna deta hai.
	 */
	function trackPageviewStart() {

		var payload = new FormData();
		payload.append( 'action', 'pad_track_pageview' );
		payload.append( 'nonce', padTracker.nonce );
		payload.append( 'url', window.location.href );
		payload.append( 'title', document.title );
		payload.append( 'screen', window.screen ? ( window.screen.width + 'x' + window.screen.height ) : '' );
		payload.append( 'language', navigator.language || '' );

		try {
			payload.append( 'timezone', Intl.DateTimeFormat().resolvedOptions().timeZone || '' );
		} catch ( err ) {
			payload.append( 'timezone', '' );
		}

		fetch( padTracker.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: payload
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( data ) {
				if ( data && data.success && data.data && data.data.pageview_id ) {
					pageviewId = data.data.pageview_id;
				}
			} )
			.catch( function () {
				// Silently ignore — analytics kabhi bhi site ke normal kaam ko block nahi karni chahiye.
			} );
	}

	/**
	 * Page chhodte waqt (tab close/navigate away) time-on-page beacon bhejta hai.
	 * sendBeacon guarantee karta hai ki request page unload hone ke baad bhi jaati hai.
	 */
	function trackPageviewEnd() {

		if ( endSent || ! pageviewId ) {
			return;
		}

		endSent = true;

		var seconds = Math.round( ( Date.now() - startedAt ) / 1000 );

		var payload = new FormData();
		payload.append( 'action', 'pad_track_pageview_end' );
		payload.append( 'nonce', padTracker.nonce );
		payload.append( 'pageview_id', pageviewId );
		payload.append( 'seconds', seconds );

		if ( navigator.sendBeacon ) {
			navigator.sendBeacon( padTracker.ajaxUrl, payload );
		} else {
			fetch( padTracker.ajaxUrl, { method: 'POST', body: payload, keepalive: true } );
		}
	}

	document.addEventListener( 'visibilitychange', function () {
		if ( 'hidden' === document.visibilityState ) {
			trackPageviewEnd();
		}
	} );

	window.addEventListener( 'pagehide', trackPageviewEnd );

	trackPageviewStart();
}() );
