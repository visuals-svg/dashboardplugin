/**
 * Premium Analytics Dashboard Pro — Chart Rendering
 *
 * Har `[data-chart]` container ke liye AJAX se real data laata hai
 * aur locally-vendored ApexCharts (assets/js/vendor/apexcharts.min.js)
 * se render karta hai. Dark/Light theme toggle hone par charts bhi
 * turant apna theme badalte hain.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

( function () {
	'use strict';

	if ( 'undefined' === typeof padAdmin || 'undefined' === typeof ApexCharts ) {
		return;
	}

	var DONUT_CHARTS = [ 'traffic-sources' ];
	var BAR_CHARTS    = [ 'country-analytics', 'city-analytics', 'browser-analytics', 'device-analytics', 'os-analytics' ];

	var chartInstances = {};

	/**
	 * Current theme mode ('light' ya 'dark') .pad-wrap se padhta hai.
	 *
	 * @return {string}
	 */
	function getThemeMode() {
		var wrap = document.querySelector( '.pad-wrap' );
		return wrap && 'dark' === wrap.getAttribute( 'data-theme' ) ? 'dark' : 'light';
	}

	/**
	 * Chart data AJAX se fetch karta hai.
	 *
	 * @param {string} chartKey    Chart identifier (data-chart value).
	 * @param {Object} extraParams Additional query params (jaise granularity).
	 * @return {Promise<Object>}
	 */
	function fetchChartData( chartKey, extraParams ) {

		var params = new URLSearchParams( Object.assign( {
			action: 'pad_get_chart_data',
			nonce: padAdmin.nonce,
			chart: chartKey
		}, extraParams || {} ) );

		return fetch( padAdmin.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) { return json && json.success ? json.data : null; } );
	}

	/**
	 * ApexCharts options object banata hai chart type ke hisaab se.
	 *
	 * @param {string} chartKey Chart identifier.
	 * @param {Object} data     Server se mila data.
	 * @return {Object}
	 */
	function buildOptions( chartKey, data ) {

		var baseTheme = { mode: getThemeMode() };
		var baseChart = { toolbar: { show: false }, animations: { enabled: true }, fontFamily: 'inherit' };

		if ( -1 !== DONUT_CHARTS.indexOf( chartKey ) ) {
			return {
				chart: Object.assign( {}, baseChart, { type: 'donut', height: 260 } ),
				labels: data.labels || [],
				series: data.series || [],
				theme: baseTheme,
				legend: { position: 'bottom' }
			};
		}

		if ( -1 !== BAR_CHARTS.indexOf( chartKey ) ) {
			return {
				chart: Object.assign( {}, baseChart, { type: 'bar', height: 260 } ),
				plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
				xaxis: { categories: data.categories || [] },
				series: data.series || [],
				theme: baseTheme,
				dataLabels: { enabled: false }
			};
		}

		if ( 'lead-conversion' === chartKey ) {
			return {
				chart: Object.assign( {}, baseChart, { type: 'line', height: 300 } ),
				stroke: { curve: 'smooth', width: 3 },
				xaxis: { categories: data.categories || [] },
				series: data.series || [],
				theme: baseTheme
			};
		}

		if ( 'hourly-heatmap' === chartKey ) {
			return {
				chart: Object.assign( {}, baseChart, { type: 'heatmap', height: 300 } ),
				series: data.series || [],
				theme: baseTheme,
				dataLabels: { enabled: false }
			};
		}

		// Default: leads-over-time (area chart).
		return {
			chart: Object.assign( {}, baseChart, { type: 'area', height: 300 } ),
			stroke: { curve: 'smooth', width: 3 },
			xaxis: { categories: data.categories || [] },
			series: data.series || [],
			theme: baseTheme,
			fill: { type: 'gradient', gradient: { opacityFrom: 0.45, opacityTo: 0.05 } }
		};
	}

	/**
	 * Ek container ke liye chart fetch + render karta hai.
	 *
	 * @param {HTMLElement} el        Chart container element.
	 * @param {Object}      [extra]   Extra query params (granularity, etc.).
	 */
	function renderChart( el, extra ) {

		var chartKey = el.getAttribute( 'data-chart' );

		fetchChartData( chartKey, extra ).then( function ( data ) {

			if ( ! data ) {
				return;
			}

			el.classList.remove( 'pad-chart-placeholder' );

			var options = buildOptions( chartKey, data );

			if ( chartInstances[ el.id ] ) {
				chartInstances[ el.id ].updateOptions( options, true, true );
				return;
			}

			var chart = new ApexCharts( el, options );
			chartInstances[ el.id ] = chart;
			chart.render();
		} );
	}

	/**
	 * Leads Over Time panel ke granularity tabs (Daily/Weekly/Monthly/Yearly) wire karta hai.
	 */
	function wireGranularityTabs() {

		var tabs = document.querySelectorAll( '.pad-chart-tabs [data-granularity]' );

		if ( ! tabs.length ) {
			return;
		}

		var target = document.getElementById( 'pad-chart-leads' );

		if ( ! target ) {
			return;
		}

		Array.prototype.forEach.call( tabs, function ( tab ) {
			tab.addEventListener( 'click', function () {

				Array.prototype.forEach.call( tabs, function ( t ) { t.classList.remove( 'is-active' ); } );
				tab.classList.add( 'is-active' );

				renderChart( target, { granularity: tab.getAttribute( 'data-granularity' ) } );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {

		var containers = document.querySelectorAll( '[data-chart]' );

		Array.prototype.forEach.call( containers, function ( el ) {
			var extra = 'pad-chart-leads' === el.id ? { granularity: 'daily' } : {};
			renderChart( el, extra );
		} );

		wireGranularityTabs();

		document.addEventListener( 'pad:theme-changed', function () {
			Object.keys( chartInstances ).forEach( function ( id ) {
				chartInstances[ id ].updateOptions( { theme: { mode: getThemeMode() } } );
			} );
		} );
	} );
}() );
