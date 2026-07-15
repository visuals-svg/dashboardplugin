/**
 * Premium Analytics Dashboard Pro — Leads Table
 *
 * Search, sort, filters, pagination, status change, notes, tags,
 * delete/bulk-delete — sab yahan se AJAX ke through chalte hain.
 * Koi build step nahi, plain vanilla JS.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

( function () {
	'use strict';

	if ( 'undefined' === typeof padAdmin ) {
		return;
	}

	var app = document.getElementById( 'pad-leads-app' );

	if ( ! app ) {
		return;
	}

	var canDelete = '1' === app.getAttribute( 'data-can-delete' );

	var state = {
		search: '',
		status: '',
		country: '',
		form_id: '',
		date_from: '',
		date_to: '',
		orderby: 'created_at',
		order: 'DESC',
		page: 1,
		per_page: 20
	};

	var selectedIds = {};

	var tbody       = document.getElementById( 'pad-leads-tbody' );
	var pageInfo     = document.getElementById( 'pad-page-info' );
	var bulkBar      = document.getElementById( 'pad-bulk-bar' );
	var bulkCount    = document.getElementById( 'pad-bulk-count' );

	/**
	 * Server ko current state ke saath list request bhejta hai.
	 */
	function fetchList() {

		var params = new URLSearchParams( Object.assign( { action: 'pad_leads_list', nonce: padAdmin.nonce }, state ) );

		tbody.innerHTML = '<tr><td colspan="9" class="pad-table-loading">' + padAdmin.strings.loading + '</td></tr>';

		fetch( padAdmin.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				if ( json && json.success ) {
					renderRows( json.data );
				}
			} );

		updateExportLinks();
	}

	/**
	 * Table rows render karta hai.
	 *
	 * @param {Object} data Server response (rows, total, page, per_page, total_pages).
	 */
	function renderRows( data ) {

		tbody.innerHTML = '';
		selectedIds = {};
		updateBulkBar();

		if ( ! data.rows.length ) {
			var emptyCols = canDelete ? 9 : 8;
			tbody.innerHTML = '<tr><td colspan="' + emptyCols + '" class="pad-table-loading">' + padAdmin.strings.noLeads + '</td></tr>';
		}

		data.rows.forEach( function ( lead ) {

			var tr = document.createElement( 'tr' );
			var checkboxCell = canDelete ? '<td><input type="checkbox" class="pad-row-check" data-id="' + lead.id + '" /></td>' : '';

			tr.innerHTML =
				checkboxCell +
				'<td>' + escapeHtml( lead.form_name ) + '</td>' +
				'<td>' + escapeHtml( lead.name ) + '</td>' +
				'<td>' + escapeHtml( lead.email ) + '</td>' +
				'<td>' + escapeHtml( lead.phone ) + '</td>' +
				'<td>' + escapeHtml( lead.country ) + '</td>' +
				'<td>' + buildStatusSelect( lead ) + '</td>' +
				'<td>' + escapeHtml( lead.date ) + '</td>' +
				'<td>' +
					'<button type="button" class="button pad-view-lead" data-id="' + lead.id + '">' + padAdmin.strings.view + '</button> ' +
					( canDelete ? '<button type="button" class="button pad-button-danger pad-delete-lead" data-id="' + lead.id + '">' + padAdmin.strings.deleteLabel + '</button>' : '' ) +
				'</td>';

			tbody.appendChild( tr );
		} );

		pageInfo.textContent = padAdmin.strings.page + ' ' + data.page + ' / ' + Math.max( 1, data.total_pages ) + ' (' + data.total + ')';

		document.getElementById( 'pad-page-prev' ).disabled = data.page <= 1;
		document.getElementById( 'pad-page-next' ).disabled = data.page >= data.total_pages;
	}

	/**
	 * Ek lead ke liye status <select> HTML banata hai.
	 *
	 * @param {Object} lead Row data.
	 * @return {string}
	 */
	function buildStatusSelect( lead ) {

		var statuses = [ 'new', 'contacted', 'qualified', 'converted', 'lost' ];
		var html = '<select class="pad-status-select" data-id="' + lead.id + '">';

		statuses.forEach( function ( status ) {
			var selected = status === lead.status ? ' selected' : '';
			html += '<option value="' + status + '"' + selected + '>' + status.charAt( 0 ).toUpperCase() + status.slice( 1 ) + '</option>';
		} );

		html += '</select>';

		return html;
	}

	/**
	 * Basic HTML-escaping (XSS-safe rendering client side).
	 *
	 * @param {string} value Raw value.
	 * @return {string}
	 */
	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = null === value || 'undefined' === typeof value ? '' : String( value );
		return div.innerHTML;
	}

	/**
	 * Export/Print links ke href me current filter state merge karta hai.
	 */
	function updateExportLinks() {

		var map = {
			'pad-export-csv': app.getAttribute( 'data-export-csv-url' ),
			'pad-export-excel': app.getAttribute( 'data-export-excel-url' ),
			'pad-export-pdf': app.getAttribute( 'data-export-pdf-url' ),
			'pad-print-leads': app.getAttribute( 'data-print-url' )
		};

		Object.keys( map ).forEach( function ( id ) {

			var el = document.getElementById( id );

			if ( ! el || ! map[ id ] ) {
				return;
			}

			var url = new URL( map[ id ], window.location.origin );

			[ 'search', 'status', 'country', 'form_id', 'date_from', 'date_to' ].forEach( function ( key ) {
				url.searchParams.set( key, state[ key ] );
			} );

			el.setAttribute( 'href', url.toString() );
		} );
	}

	/**
	 * Bulk-select bar dikhaata/chhupata hai selection count ke hisaab se.
	 */
	function updateBulkBar() {

		if ( ! bulkBar ) {
			return;
		}

		var count = Object.keys( selectedIds ).length;

		if ( count > 0 ) {
			bulkBar.hidden = false;
			bulkCount.textContent = count + ' ' + padAdmin.strings.selected;
		} else {
			bulkBar.hidden = true;
		}
	}

	/**
	 * Lead detail slide-over panel kholta hai aur data load karta hai.
	 *
	 * @param {number} leadId Lead ID.
	 */
	function openDetail( leadId ) {

		var panel   = document.getElementById( 'pad-lead-detail' );
		var content = document.getElementById( 'pad-lead-detail-content' );

		panel.hidden = false;
		content.innerHTML = '<p class="pad-table-loading">' + padAdmin.strings.loading + '</p>';

		var params = new URLSearchParams( { action: 'pad_lead_detail', nonce: padAdmin.nonce, lead_id: leadId } );

		fetch( padAdmin.ajaxUrl + '?' + params.toString(), { credentials: 'same-origin' } )
			.then( function ( response ) { return response.json(); } )
			.then( function ( json ) {
				if ( json && json.success ) {
					renderDetail( leadId, json.data );
				}
			} );
	}

	/**
	 * Detail panel ka content render karta hai.
	 *
	 * @param {number} leadId Lead ID.
	 * @param {Object} data   { lead, meta, notes, tags }.
	 */
	function renderDetail( leadId, data ) {

		var content = document.getElementById( 'pad-lead-detail-content' );
		var lead     = data.lead;

		var fields = [
			[ 'Form', lead.form_name ],
			[ 'Name', ( lead.first_name + ' ' + lead.last_name ).trim() ],
			[ 'Email', lead.email ],
			[ 'Phone', lead.phone ],
			[ 'Country', lead.country ],
			[ 'City', lead.city ],
			[ 'State', lead.state ],
			[ 'Message', lead.message ],
			[ 'IP Address', lead.ip_address ],
			[ 'Browser', lead.browser ],
			[ 'OS', lead.os ],
			[ 'Device', lead.device ],
			[ 'Referrer', lead.referrer ],
			[ 'Landing Page', lead.landing_page ],
			[ 'UTM Source', lead.utm_source ],
			[ 'UTM Medium', lead.utm_medium ],
			[ 'UTM Campaign', lead.utm_campaign ]
		];

		var html = '<h2>' + padAdmin.strings.leadDetails + ' #' + leadId + '</h2><dl class="pad-detail-list">';

		fields.forEach( function ( pair ) {
			if ( pair[ 1 ] ) {
				html += '<dt>' + escapeHtml( pair[ 0 ] ) + '</dt><dd>' + escapeHtml( pair[ 1 ] ) + '</dd>';
			}
		} );

		html += '</dl>';

		if ( data.meta && data.meta.length ) {
			html += '<h3>' + padAdmin.strings.customFields + '</h3><dl class="pad-detail-list">';
			data.meta.forEach( function ( row ) {
				html += '<dt>' + escapeHtml( row.meta_key ) + '</dt><dd>' + escapeHtml( row.meta_value ) + '</dd>';
			} );
			html += '</dl>';
		}

		html += '<h3>' + padAdmin.strings.tags + '</h3><div class="pad-tag-list" id="pad-tag-list"></div>';
		html += '<div class="pad-inline-form"><input type="text" id="pad-new-tag" placeholder="' + padAdmin.strings.addTag + '" /><button type="button" class="button" id="pad-add-tag">+</button></div>';

		html += '<h3>' + padAdmin.strings.notes + '</h3><div id="pad-notes-list"></div>';
		html += '<div class="pad-inline-form"><textarea id="pad-new-note" placeholder="' + padAdmin.strings.addNote + '"></textarea><button type="button" class="button button-primary" id="pad-add-note">' + padAdmin.strings.save + '</button></div>';

		content.innerHTML = html;

		renderTags( leadId, data.tags );
		renderNotes( data.notes );

		document.getElementById( 'pad-add-tag' ).addEventListener( 'click', function () {
			var input = document.getElementById( 'pad-new-tag' );
			if ( ! input.value.trim() ) {
				return;
			}
			postAction( 'pad_lead_add_tag', { lead_id: leadId, tag_name: input.value.trim() } ).then( function ( json ) {
				if ( json && json.success ) {
					renderTags( leadId, json.data.tags );
					input.value = '';
				}
			} );
		} );

		document.getElementById( 'pad-add-note' ).addEventListener( 'click', function () {
			var textarea = document.getElementById( 'pad-new-note' );
			if ( ! textarea.value.trim() ) {
				return;
			}
			postAction( 'pad_lead_add_note', { lead_id: leadId, note: textarea.value.trim() } ).then( function ( json ) {
				if ( json && json.success ) {
					renderNotes( json.data.notes );
					textarea.value = '';
				}
			} );
		} );
	}

	/**
	 * Tag chips render karta hai.
	 *
	 * @param {number} leadId Lead ID.
	 * @param {Array}  tags   Tag objects.
	 */
	function renderTags( leadId, tags ) {

		var list = document.getElementById( 'pad-tag-list' );
		list.innerHTML = '';

		( tags || [] ).forEach( function ( tag ) {
			var chip = document.createElement( 'span' );
			chip.className = 'pad-tag-chip';
			chip.textContent = tag.name;

			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.textContent = '×';
			remove.addEventListener( 'click', function () {
				postAction( 'pad_lead_remove_tag', { lead_id: leadId, tag_id: tag.id } ).then( function ( json ) {
					if ( json && json.success ) {
						renderTags( leadId, json.data.tags );
					}
				} );
			} );

			chip.appendChild( remove );
			list.appendChild( chip );
		} );
	}

	/**
	 * Notes list render karta hai.
	 *
	 * @param {Array} notes Note objects.
	 */
	function renderNotes( notes ) {

		var list = document.getElementById( 'pad-notes-list' );
		list.innerHTML = '';

		if ( ! notes || ! notes.length ) {
			list.innerHTML = '<p class="pad-field-hint">' + padAdmin.strings.noNotes + '</p>';
			return;
		}

		notes.forEach( function ( note ) {
			var div = document.createElement( 'div' );
			div.className = 'pad-note-item';
			div.innerHTML = '<strong>' + escapeHtml( note.author ) + '</strong> <span>' + escapeHtml( note.created_at ) + '</span><p>' + escapeHtml( note.note ) + '</p>';
			list.appendChild( div );
		} );
	}

	/**
	 * Ek AJAX POST action bhejta hai (nonce already merged).
	 *
	 * @param {string} action POST action name.
	 * @param {Object} data   Additional POST fields.
	 * @return {Promise<Object>}
	 */
	function postAction( action, data ) {

		var body = new URLSearchParams( Object.assign( { action: action, nonce: padAdmin.nonce }, data ) );

		return fetch( padAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( response ) { return response.json(); } );
	}

	// --- Event wiring -------------------------------------------------

	var searchTimer;
	document.getElementById( 'pad-leads-search' ).addEventListener( 'input', function ( e ) {
		clearTimeout( searchTimer );
		var value = e.target.value;
		searchTimer = setTimeout( function () {
			state.search = value;
			state.page = 1;
			fetchList();
		}, 400 );
	} );

	[ [ 'pad-leads-filter-status', 'status' ], [ 'pad-leads-filter-country', 'country' ], [ 'pad-leads-filter-form', 'form_id' ] ].forEach( function ( pair ) {
		document.getElementById( pair[ 0 ] ).addEventListener( 'change', function ( e ) {
			state[ pair[ 1 ] ] = e.target.value;
			state.page = 1;
			fetchList();
		} );
	} );

	[ [ 'pad-leads-filter-date-from', 'date_from' ], [ 'pad-leads-filter-date-to', 'date_to' ] ].forEach( function ( pair ) {
		document.getElementById( pair[ 0 ] ).addEventListener( 'change', function ( e ) {
			state[ pair[ 1 ] ] = e.target.value;
			state.page = 1;
			fetchList();
		} );
	} );

	document.getElementById( 'pad-leads-reset' ).addEventListener( 'click', function () {
		state = Object.assign( state, { search: '', status: '', country: '', form_id: '', date_from: '', date_to: '', page: 1 } );
		document.getElementById( 'pad-leads-search' ).value = '';
		document.getElementById( 'pad-leads-filter-status' ).value = '';
		document.getElementById( 'pad-leads-filter-country' ).value = '';
		document.getElementById( 'pad-leads-filter-form' ).value = '';
		document.getElementById( 'pad-leads-filter-date-from' ).value = '';
		document.getElementById( 'pad-leads-filter-date-to' ).value = '';
		fetchList();
	} );

	document.getElementById( 'pad-leads-per-page' ).addEventListener( 'change', function ( e ) {
		state.per_page = parseInt( e.target.value, 10 );
		state.page = 1;
		fetchList();
	} );

	document.getElementById( 'pad-page-prev' ).addEventListener( 'click', function () {
		if ( state.page > 1 ) {
			state.page -= 1;
			fetchList();
		}
	} );

	document.getElementById( 'pad-page-next' ).addEventListener( 'click', function () {
		state.page += 1;
		fetchList();
	} );

	document.querySelectorAll( '#pad-leads-table th[data-sort]' ).forEach( function ( th ) {
		th.style.cursor = 'pointer';
		th.addEventListener( 'click', function () {
			var column = th.getAttribute( 'data-sort' );
			if ( state.orderby === column ) {
				state.order = 'ASC' === state.order ? 'DESC' : 'ASC';
			} else {
				state.orderby = column;
				state.order = 'ASC';
			}
			fetchList();
		} );
	} );

	tbody.addEventListener( 'change', function ( e ) {

		if ( e.target.classList.contains( 'pad-row-check' ) ) {
			var id = e.target.getAttribute( 'data-id' );
			if ( e.target.checked ) {
				selectedIds[ id ] = true;
			} else {
				delete selectedIds[ id ];
			}
			updateBulkBar();
			return;
		}

		if ( e.target.classList.contains( 'pad-status-select' ) ) {
			postAction( 'pad_lead_update_status', { lead_id: e.target.getAttribute( 'data-id' ), status: e.target.value } );
		}
	} );

	tbody.addEventListener( 'click', function ( e ) {

		if ( e.target.classList.contains( 'pad-view-lead' ) ) {
			openDetail( e.target.getAttribute( 'data-id' ) );
			return;
		}

		if ( e.target.classList.contains( 'pad-delete-lead' ) ) {
			if ( ! window.confirm( padAdmin.strings.confirmDelete ) ) {
				return;
			}
			postAction( 'pad_lead_delete', { lead_id: e.target.getAttribute( 'data-id' ) } ).then( function ( json ) {
				if ( json && json.success ) {
					fetchList();
				}
			} );
		}
	} );

	var selectAll = document.getElementById( 'pad-select-all' );

	if ( selectAll ) {
		selectAll.addEventListener( 'change', function ( e ) {
			document.querySelectorAll( '.pad-row-check' ).forEach( function ( checkbox ) {
				checkbox.checked = e.target.checked;
				var id = checkbox.getAttribute( 'data-id' );
				if ( e.target.checked ) {
					selectedIds[ id ] = true;
				} else {
					delete selectedIds[ id ];
				}
			} );
			updateBulkBar();
		} );
	}

	var bulkDeleteBtn = document.getElementById( 'pad-bulk-delete' );

	if ( bulkDeleteBtn ) {
		bulkDeleteBtn.addEventListener( 'click', function () {

			var ids = Object.keys( selectedIds );

			if ( ! ids.length || ! window.confirm( padAdmin.strings.confirmBulkDelete ) ) {
				return;
			}

			var body = new URLSearchParams();
			body.append( 'action', 'pad_lead_bulk_delete' );
			body.append( 'nonce', padAdmin.nonce );
			ids.forEach( function ( id ) { body.append( 'lead_ids[]', id ); } );

			fetch( padAdmin.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			} )
				.then( function ( response ) { return response.json(); } )
				.then( function ( json ) {
					if ( json && json.success ) {
						fetchList();
					}
				} );
		} );
	}

	document.getElementById( 'pad-lead-detail-close' ).addEventListener( 'click', closeDetail );
	document.getElementById( 'pad-lead-detail-backdrop' ).addEventListener( 'click', closeDetail );

	/**
	 * Detail slide-over band karta hai.
	 */
	function closeDetail() {
		document.getElementById( 'pad-lead-detail' ).hidden = true;
	}

	fetchList();
}() );
