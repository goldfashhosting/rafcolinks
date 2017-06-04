//********************************************************************************************************************************
// reusable message function
//********************************************************************************************************************************
function rafcoMessage( msgType, msgText ) {
	jQuery( 'div#wpbody h2:first' ).after( '<div id="message" class="' + msgType + ' below-h2"><p>' + msgText + '</p></div>' );
}

//********************************************************************************************************************************
// clear any admin messages
//********************************************************************************************************************************
function rafcoClearAdmin() {
	jQuery( 'div#wpbody div#message' ).remove();
	jQuery( 'div#wpbody div#setting-error-settings_updated' ).remove();
}

//********************************************************************************************************************************
// button action when disabled
//********************************************************************************************************************************
function rafcoBtnDisable( btnDiv, btnSpin, btnItem ) {
	jQuery( btnDiv ).find( btnSpin ).css( 'visibility', 'visible' );
	jQuery( btnDiv ).find( btnItem ).attr( 'disabled', 'disabled' );
}

//********************************************************************************************************************************
// button action when enabled
//********************************************************************************************************************************
function rafcoBtnEnable( btnDiv, btnSpin, btnItem ) {
	jQuery( btnDiv ).find( btnSpin ).css( 'visibility', 'hidden' );
	jQuery( btnDiv ).find( btnItem ).removeAttr( 'disabled' );
}

//********************************************************************************************************************************
// start the engine
//********************************************************************************************************************************
jQuery(document).ready( function($) {

//********************************************************************************************************************************
// quick helper to check for an existance of an element
//********************************************************************************************************************************
	$.fn.divExists = function(callback) {
		// slice some args
		var args = [].slice.call( arguments, 1 );
		// check for length
		if ( this.length ) {
			callback.call( this, args );
		}
		// return it
		return this;
	};

//********************************************************************************************************************************
// set some vars
//********************************************************************************************************************************
	var yrlSocial   = '';
	var yrlAdminBox = '';
	var yrlClickBox = '';
	var yrlClickRow = '';
	var yrlKeyword  = '';
	var yrlPostID   = '';
	var yrlNonce    = '';

//********************************************************************************************************************************
// social link in a new window because FANCY
//********************************************************************************************************************************
	$( 'div.rafco-sidebox' ).on( 'click', 'a.admin-twitter-link', function() {
		// only do this on larger screens
		if ( $( window ).width() > 765 ) {
			// get our link
			yrlSocial = $( this ).attr( 'href' );
			// open our fancy window
			window.open( yrlSocial, 'social-share-dialog', 'width=626,height=436' );
			// and finish
			return false;
		}
	});

//********************************************************************************************************************************
// do the password magic
//********************************************************************************************************************************
	$( 'td.apikey-field-wrapper' ).divExists( function() {

		// hide it on load
		$( 'input#rafco-api' ).hidePassword( false );

		// now check for clicks
		$( 'td.apikey-field-wrapper' ).on( 'click', 'span.password-toggle', function () {

			// if our password is not visible
			if ( ! $( this ).hasClass( 'password-visible' ) ) {
				$( this ).addClass( 'password-visible' );
				$( 'input#rafco-api' ).showPassword( false );
			} else {
				$( this ).removeClass( 'password-visible' );
				$( 'input#rafco-api' ).hidePassword( false );
			}

		});
	});

//********************************************************************************************************************************
// other external links in new tab
//********************************************************************************************************************************
	$( 'div.rafco-sidebox' ).find( 'a.external' ).attr( 'target', '_blank' );

//********************************************************************************************************************************
// show / hide post types on admin
//********************************************************************************************************************************
	$( 'tr.setting-item-types' ).divExists( function() {

		// see if our box is checked
		yrlAdminBox = $( this ).find( 'input#rafco-cpt' ).is( ':checked' );

		// if it is, show it
		if ( yrlAdminBox === true ) {
			$( 'tr.secondary' ).show();
		}

		// if not, hide it and make sure boxes are not checked
		if ( yrlAdminBox === false ) {
			$( 'tr.secondary' ).hide();
			$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
		}

		// now the check for clicking
		$( 'tr.setting-item-types' ).on( 'change', 'input#rafco-cpt', function() {

			// check the box (again)
			yrlAdminBox = $( this ).is( ':checked' );

			// if it is, show it
			if ( yrlAdminBox === true ) {
				$( 'tr.secondary' ).fadeIn( 700 );
			}

			// if not, hide it and make sure boxes are not checked
			if ( yrlAdminBox === false ) {
				$( 'tr.secondary' ).fadeOut( 700 );
				$( 'tr.secondary' ).find( 'input:checkbox' ).prop( 'checked', false );
			}
		});

	});

//********************************************************************************************************************************
// create RAFCO on call
//********************************************************************************************************************************
	$( 'div#rafco-post-display').on( 'click', 'input.rafco-api', function () {

		// get my post ID and my nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// adjust buttons
		$( 'div#rafco-post-display' ).find( 'span.rafco-spinner' ).css( 'visibility', 'visible' );
		$( 'div#rafco-post-display' ).find( 'input.rafco-api').attr( 'disabled', 'disabled' );

		// get my optional keyword
		yrlKeyword  = $( 'div#rafco-post-display' ).find( 'input.rafco-keyw' ).val();

		// set my data array
		var data = {
			action:  'create_rafco',
			keyword: yrlKeyword,
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			$( 'div#rafco-post-display' ).find( 'span.rafco-spinner' ).css( 'visibility', 'hidden' );
			$( 'div#rafco-post-display' ).find( 'input.rafco-api').removeAttr( 'disabled' );

			var obj;

			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			if( obj.success === true ) {
				rafcoMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				rafcoMessage( 'error', obj.message );
			}

			else {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			// add in the new RAFCO box if it comes back
			if( obj.success === true && obj.linkbox !== null ) {

				// remove the submit box
				$( 'div#rafco-post-display' ).find( 'p.rafco-submit-block' ).remove();

				// swap out our boxes
				$( 'div#rafco-post-display' ).find( 'p.rafco-input-block' ).replaceWith( obj.linkbox );

				// add our shortlink button
				$( 'div#edit-slug-box' ).append( '<input type="hidden" value="' + obj.linkurl + '" id="shortlink">' );
				$( 'div#edit-slug-box' ).append( rafcoAdmin.shortSubmit );
			}
		});

	});

//********************************************************************************************************************************
// delete RAFCO on call
//********************************************************************************************************************************
	$( 'div#rafco-post-display' ).on( 'click', 'span.rafco-delete', function () {

		// get my post ID and nonce
		yrlPostID   = $( this ).data( 'post-id' );
		yrlNonce    = $( this ).data( 'nonce' );

		// bail without post ID or nonce
		if ( yrlPostID === '' || yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		$( 'div#wpbody div#message' ).remove();
		$( 'div#wpbody div#setting-error-settings_updated' ).remove();

		// set my data array
		var data = {
			action:  'delete_rafco',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			if( obj.success === true ) {
				rafcoMessage( 'updated', obj.message );
			}

			else if( obj.success === false && obj.message !== null ) {
				rafcoMessage( 'error', obj.message );
			}
			else {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			// add in the new RAFCO box if it comes back
			if( obj.success === true && obj.rafcobox !== null ) {

				$( 'div#rafco-post-display' ).find( 'p.howto' ).remove();
				$( 'div#rafco-post-display' ).find( 'p.rafco-exist-block' ).replaceWith( obj.linkbox );

				$( 'div#edit-slug-box' ).find( 'input#shortlink' ).remove();
				$( 'div#edit-slug-box' ).find( 'a:contains("Get Shortlink")' ).remove();
			}
		});

	});

//********************************************************************************************************************************
// update RAFCO click count
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.rafco-admin-update', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'tr.entry' ).find( 'td.rafco-click' );

		// set my data array
		var data = {
			action:  'stats_rafco',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new number box if it comes back
			if( obj.success === true && obj.clicknm !== null ) {
				yrlClickBox.find( 'span' ).text( obj.clicknm );
			}
		});
	});

//********************************************************************************************************************************
// create RAFCO inline
//********************************************************************************************************************************
	$( 'div.row-actions' ).on( 'click', 'a.rafco-admin-create', function (e) {

		// stop the hash
		e.preventDefault();

		// get my nonce
		yrlNonce    = $( this ).data( 'nonce' );

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// get my post ID
		yrlPostID   = $( this ).data( 'post-id' );

		// bail without post ID
		if ( yrlPostID === '' ) {
			return;
		}

		// set my row and box as a variable for later
		yrlClickRow = $( this ).parents( 'div.row-actions' );
		yrlClickBox = $( this ).parents( 'div.row-actions' ).find( 'span.create-rafco' );

		// set my data array
		var data = {
			action:  'inline_rafco',
			post_id: yrlPostID,
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// hide the row actions
			yrlClickRow.removeClass( 'visible' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				return false;
			}

			// add in the new click box if it comes back
			if( obj.success === true && obj.rowactn !== null ) {
				yrlClickBox.replaceWith( obj.rowactn );
			}
		});
	});

//********************************************************************************************************************************
// run API status update update from admin
//********************************************************************************************************************************
	$( 'div#rafco-admin-status' ).on( 'click', 'input.rafco-click-status', function () {

		// get my nonce first
		yrlNonce    = $( 'input#rafco_status' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		rafcoClearAdmin();

		// adjust buttons
		rafcoBtnDisable( 'div#rafco-admin-status', 'span.rafco-status-spinner', 'input.rafco-click-status' );

		// set my data array
		var data = {
			action:  'status_rafco',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			rafcoBtnEnable( 'div#rafco-admin-status', 'span.rafco-status-spinner', 'input.rafco-click-status' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			// we got a status back
			if( obj.success === true ) {

				// check the icon
				if( obj.baricon !== '' ) {
					$( 'div#rafco-admin-status' ).find( 'span.api-status-icon' ).replaceWith( obj.baricon );
				}

				// check the text return
				if( obj.message !== '' ) {
					$( 'div#rafco-admin-status' ).find( 'p.api-status-text' ).text( obj.message );
				}

				// check the checkmark return
				if( obj.stcheck !== '' ) {
					// add the checkmark
					$( 'div#rafco-admin-status' ).find( 'p.api-status-actions' ).append( obj.stcheck );
					// delay then fade out
					$( 'span.api-status-checkmark' ).delay( 3000 ).fadeOut( 1000 );
				}

			}
			else if( obj.success === false && obj.message !== null ) {
				rafcoMessage( 'error', obj.message );
			}
			else {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// run click update from admin
//********************************************************************************************************************************
	$( 'div#rafco-data-refresh' ).on( 'click', 'input.rafco-click-updates', function () {

		// get my nonce first
		yrlNonce    = $( 'input#rafco_refresh' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		rafcoClearAdmin();

		// adjust buttons
		rafcoBtnDisable( 'div#rafco-data-refresh', 'span.rafco-refresh-spinner', 'input.rafco-click-updates' );

		// set my data array
		var data = {
			action:  'refresh_rafco',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			rafcoBtnEnable( 'div#rafco-data-refresh', 'span.rafco-refresh-spinner', 'input.rafco-click-updates' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				rafcoMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				rafcoMessage( 'error', obj.message );
			}
			else {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// attempt data import
//********************************************************************************************************************************
	$( 'div#rafco-data-refresh' ).on( 'click', 'input.rafco-click-import', function () {

		// get my nonce first
		yrlNonce    = $( 'input#rafco_import' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		rafcoClearAdmin();

		// adjust buttons
		rafcoBtnDisable( 'div#rafco-data-refresh', 'span.rafco-import-spinner', 'input.rafco-click-import' );

		// set my data array
		var data = {
			action:  'import_rafco',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			rafcoBtnEnable( 'div#rafco-data-refresh', 'span.rafco-import-spinner', 'input.rafco-click-import' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				rafcoMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				rafcoMessage( 'error', obj.message );
			}
			else {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// change meta key from old plugin
//********************************************************************************************************************************
	$( 'div#rafco-data-refresh' ).on( 'click', 'input.rafco-convert', function () {

		// get my nonce first
		yrlNonce    = $( 'input#rafco_convert' ).val();

		// bail if no nonce
		if ( yrlNonce === '' ) {
			return;
		}

		// remove any existing messages
		rafcoClearAdmin();

		// adjust buttons
		rafcoBtnDisable( 'div#rafco-data-refresh', 'span.rafco-convert-spinner', 'input.rafco-convert' );

		// set my data array
		var data = {
			action:  'convert_rafco',
			nonce:   yrlNonce
		};

		// my ajax return check
		jQuery.post( ajaxurl, data, function( response ) {

			// adjust buttons
			rafcoBtnEnable( 'div#rafco-data-refresh', 'span.rafco-convert-spinner', 'input.rafco-convert' );

			var obj;
			try {
				obj = jQuery.parseJSON(response);
			}
			catch(e) {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}

			if( obj.success === true && obj.message !== '' ) {
				rafcoMessage( 'updated', obj.message );
			}
			else if( obj.success === false && obj.message !== null ) {
				rafcoMessage( 'error', obj.message );
			}
			else {
				rafcoMessage( 'error', rafcoAdmin.defaultError );
			}
		});
	});

//********************************************************************************************************************************
// you're still here? it's over. go home.
//********************************************************************************************************************************
});
