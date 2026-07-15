/**
 * Premium Analytics Dashboard Pro — Report Generation
 *
 * Period tabs, custom range, AJAX report generation, results
 * rendering (summary cards + tables + trend chart), aur export
 * links ko current period/range ke saath sync karna.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

( function () {
	'use strict';

	if ( 'undefined' === typeof padAdmin ) {
		return;
	}

	var app = document.getElementById( 'pad-reports-app' );

	if ( ! app ) {
		return;
	}

	var currentPeriod = 'daily';
	var trendChart     = null;

	var tabs           = document.querySelectorAll( '.pad-period-tabs [data-period]' );
	var customRangeRow = document.getElementById( 'pad-custom-range-row' );
	var fromInput      = document.getElementById( 'pad-report-from' );
	var toInput        = document.getElementById( 'pad-report-to' );
	var resultsEl      = document.getElementById( 'pad-report-results' );
	var pdfLink        = document.getElementById( 'pad-report-export-pdf' );
	var csvLink        = document.getElementById( 'pad-report-export-csv' );

	tabs.forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {

			tabs.forEach( function ( t ) { t.classList.remove( 'is-active' ); } );
			tab.classList.add( 'is-active' );

			currentPeriod = tab.getAttribute( 'data-period' );
			customRangeRow.hidden = 'custom' !== currentPeriod;
		} );
	} );

	document.getElementById( 'pad-generate-report' ).addEventListener( 'click', generateReport );

	/**
	 * Report AJAX se generate karta hai aur results render karta hai.
	 */
	function generateReport() {

		var params = {
			action: 'pad_generate_report',
			nonce: padAdmin.nonce,
			period: currentPeriod,
			date_from: 'custom' === currentPeriod ? fromInput.value : '',
			date_to: 'custom' === currentPeriod ? toInput.value : ''
		};

		resultsEl.innerHTML = '<p class="pad-table-loading">' + padAdmin.strings.loading + '</p>';

		fetch( padAdmin.ajaxUrl + '?' + new URLSearchParams( params ).toString(), { credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				if ( json && json.success ) {
					renderReport( json.data );
					updateExportLinks( params );
				}
			} );
	}

	/**
	 * Export links ke href me current period/range merge karta hai.
	 *
	 * @param {Object} params Current generate params.
	 */
	function updateExportLinks( params ) {

		[ [ pdfLink, app.getAttribute( 'data-export-pdf-url' ) ], [ csvLink, app.getAttribute( 'data-export-csv-url' ) ] ].forEach( function ( pair ) {

			var el = pair[ 0 ];
			var base = pair[ 1 ];

			if ( ! el || ! base ) {
				return;
			}

			var url = new URL( base, window.location.origin );
			url.searchParams.set( 'period', params.period );
			url.searchParams.set( 'date_from', params.date_from );
			url.searchParams.set( 'date_to', params.date_to );

			el.setAttribute( 'href', url.toString() );
			el.hidden = false;
		} );
	}

	/**
	 * Basic HTML escaping.
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
	 * Ek chhota bar-list HTML banata hai.
	 *
	 * @param {Array}  rows      Row objects.
	 * @param {string} labelProp Label property name.
	 * @param {string} totalProp Total property name.
	 * @return {string}
	 */
	function buildBarList( rows, labelProp, totalProp ) {

		if ( ! rows || ! rows.length ) {
			return '<div class="pad-empty-state pad-empty-state-compact"><p>' + padAdmin.strings.noLeads + '</p></div>';
		}

		var html = '<ul class="pad-bar-list">';

		rows.forEach( function ( row ) {
			html += '<li><span>' + escapeHtml( row[ labelProp ] ) + '</span><strong>' + escapeHtml( row[ totalProp ] ) + '</strong></li>';
		} );

		return html + '</ul>';
	}

	/**
	 * Poora report result render karta hai.
	 *
	 * @param {Object} data PAD_Reports::generate() output.
	 */
	function renderReport( data ) {

		var cards = [
			[ 'Total Leads', data.leads_total ],
			[ 'Total Visitors', data.visitors_total ],
			[ 'Total Sessions', data.sessions_total ],
			[ 'Page Views', data.pageviews_total ],
			[ 'Avg. Session', data.avg_session ],
			[ 'Bounce Rate', data.bounce_rate ],
			[ 'Conversion Rate', data.conversion_rate ]
		];

		var html = '<section class="pad-panel"><div class="pad-panel-head"><h2>' + escapeHtml( data.range_label ) + '</h2></div>';
		html += '<section class="pad-cards-grid pad-cards-grid-compact">';

		cards.forEach( function ( pair ) {
			html += '<div class="pad-card"><div class="pad-card-body"><span class="pad-card-value">' + escapeHtml( pair[ 1 ] ) + '</span><span class="pad-card-label">' + escapeHtml( pair[ 0 ] ) + '</span></div></div>';
		} );

		html += '</section></section>';

		html += '<section class="pad-panel"><div class="pad-panel-head"><h2>' + padAdmin.strings.leadsTrend + '</h2></div><div id="pad-report-trend-chart" style="height:280px;"></div></section>';

		html += '<section class="pad-charts-grid">';
		html += '<div class="pad-panel"><div class="pad-panel-head"><h2>' + padAdmin.strings.leadsByStatus + '</h2></div>' + buildBarList( data.leads_by_status, 'status', 'total' ) + '</div>';
		html += '<div class="pad-panel"><div class="pad-panel-head"><h2>' + padAdmin.strings.leadsByForm + '</h2></div>' + buildBarList( data.leads_by_form, 'form_name', 'total' ) + '</div>';
		html += '<div class="pad-panel"><div class="pad-panel-head"><h2>' + padAdmin.strings.leadsByCountry + '</h2></div>' + buildBarList( data.leads_by_country, 'country', 'total' ) + '</div>';
		html += '<div class="pad-panel"><div class="pad-panel-head"><h2>' + padAdmin.strings.trafficSources + '</h2></div>' + buildBarList( data.traffic_sources, 'referral_source', 'total' ) + '</div>';
		html += '</section>';

		resultsEl.innerHTML = html;

		if ( 'undefined' !== typeof ApexCharts && data.daily_trend ) {

			var wrap = document.querySelector( '.pad-wrap' );
			var mode = wrap && 'dark' === wrap.getAttribute( 'data-theme' ) ? 'dark' : 'light';

			if ( trendChart ) {
				trendChart.destroy();
			}

			trendChart = new ApexCharts( document.getElementById( 'pad-report-trend-chart' ), {
				chart: { type: 'area', height: 280, toolbar: { show: false } },
				stroke: { curve: 'smooth', width: 3 },
				xaxis: { categories: data.daily_trend.categories || [] },
				series: data.daily_trend.series || [],
				theme: { mode: mode },
				fill: { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0.05 } }
			} );

			trendChart.render();
		}
	}
}() );
