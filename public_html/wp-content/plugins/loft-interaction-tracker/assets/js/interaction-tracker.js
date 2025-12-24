( function () {
	'use strict';

	const settings = window.LoftInteractionTrackerData || {};

	if ( ! settings.ajaxUrl || ! settings.nonce ) {
		return;
	}

	const buildPayload = ( eventType, status, details ) => {
		const data = new URLSearchParams();

		data.append( 'action', 'loft_it_track' );
		data.append( 'nonce', settings.nonce );
		data.append( 'eventType', eventType );

		if ( status ) {
			data.append( 'status', status );
		}

		if ( details ) {
			data.append( 'details', JSON.stringify( details ) );
		}

		return data;
	};

	const sendEvent = ( eventType, status, details ) => {
		const payload = buildPayload( eventType, status, details );

		if ( navigator.sendBeacon ) {
			const blob = new Blob( [ payload.toString() ], {
				type: 'application/x-www-form-urlencoded',
			} );

			if ( navigator.sendBeacon( settings.ajaxUrl, blob ) ) {
				return;
			}
		}

		fetch( settings.ajaxUrl, {
			method: 'POST',
			body: payload,
			keepalive: true,
			credentials: 'same-origin',
		} ).catch( () => {} );
	};

	const getValue = ( form, selector ) => {
		const field = form.querySelector( selector );
		return field && 'value' in field ? field.value : '';
	};

	const handleSearchForms = () => {
		document
			.querySelectorAll( 'form.mphb_sc_search-form' )
			.forEach( ( form ) => {
				form.addEventListener( 'submit', () => {
					const details = {
						check_in: getValue( form, 'input[name="mphb_check_in_date"]' ),
						check_out: getValue(
							form,
							'input[name="mphb_check_out_date"]'
						),
						adults: getValue( form, '[name="mphb_adults"]' ),
						children: getValue( form, '[name="mphb_children"]' ),
						page: window.location.href,
					};

					sendEvent( 'search_click', 'submitted', details );
				} );
			} );
	};

	const handleCheckoutForms = () => {
		document
			.querySelectorAll( 'form.mphb_sc_checkout-form' )
			.forEach( ( form ) => {
				form.addEventListener( 'submit', () => {
					const details = {
						check_in: getValue( form, 'input[name="mphb_check_in_date"]' ),
						check_out: getValue(
							form,
							'input[name="mphb_check_out_date"]'
						),
						rooms: form.querySelectorAll( '.mphb-room-details' ).length,
						booking_cid: getValue(
							form,
							'input[name="mphb-booking-checkout-id"]'
						),
						page: window.location.href,
					};

					sendEvent( 'checkout_submit', 'submitted', details );
				} );
			} );
	};

	document.addEventListener( 'DOMContentLoaded', () => {
		handleSearchForms();
		handleCheckoutForms();
	} );
} )();
