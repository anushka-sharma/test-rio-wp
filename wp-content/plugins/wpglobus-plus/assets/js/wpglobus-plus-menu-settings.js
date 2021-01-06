/**
 * WPGlobus Plus Menu Settings
 * Interface JS functions
 *
 * @since 1.1.17
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCoreData, WPGlobusPlusMenuSettings*/
jQuery(document).ready( function($) {
    "use strict";

	if ( typeof WPGlobusCoreData === 'undefined' ) {
		return;
	}

	if ( typeof WPGlobusPlusMenuSettings === 'undefined' ) {
		return;
	}

	var api =  {
		locationTitle: '(<b>{{locat}}</b>)',
		menuID: 		'#wpglobus_plus_menu_id_',
		defaultMenuID: 	'',
		locationID: 	'#wpglobus_plus_menu_location',
		init : function() {
			if ( 'nav-menus.php' == WPGlobusPlusMenuSettings.pagenow ) {
				api.addTab();
			} else {
				api.defaultMenuID = api.menuID + WPGlobusCoreData.default_language,
				this.setLocation();
				this.addListeners();
			}
		},
		addTab: function() {
			var tab = '<a href="'+WPGlobusPlusMenuSettings.redirect+'" class="nav-tab">WPGlobus Menu Settings</a>';
			$( tab ).insertAfter( '.nav-tab-wrapper a:last' );
		},
		setLocation: function() {
			if ( WPGlobusPlusMenuSettings.options.length == 0 ) {
				return;
			}
			var opts  = WPGlobusPlusMenuSettings.options[ WPGlobusPlusMenuSettings.currentMenuLocation ];
			var locat = WPGlobusPlusMenuSettings.currentMenuLocation;
			$.each( opts, function( lang, id ) {
				var selector = '.' + locat + '-' + lang;
				$( selector ).css( {'background-color':'#e1e1e1'} ).html('');
				$( '#' + locat + '-' + lang + '-' + id ).css( {'background-color':'#0f0'} ).html( api.locationTitle.replace( '{{locat}}', WPGlobusPlusMenuSettings.locations[ locat ] ) );
			});
		},
		selectLocation: function( newLocation ) {
			if ( typeof( newLocation ) === 'undefined' ) {
				return;
			}
			window.location = WPGlobusPlusMenuSettings.redirect + '&location=' + newLocation;
		},
		addListeners: function() {
			$( '#wpglobus_menu_settings_save' ).on( 'click', function(ev){
				var order = {};
				order.action 	= 'save';
				order.location 	= $( api.locationID ).val();
				order.menuID	= {};
				$.each( WPGlobusCoreData.enabled_languages, function( i, l ) {
					order.menuID[l] = $( api.menuID + l ).val();
				});
				api.ajax( order );
			});

			$( '.cell-selector-enabled' ).on( 'click', function(ev){
				var cell = $(this),
					locat  = cell.data( 'current-menu-location' ),
					lang   = cell.data( 'language' ),
					menuID = cell.data( 'menu-id' );

				var selector = '.' + locat + '-' + cell.data( 'language' );
				var cells = $( selector );
				cells.css( {'background-color':'#e1e1e1'} ).html('');
				cell.css( {'background-color':'#0f0'} ).html( api.locationTitle.replace( '{{locat}}', WPGlobusPlusMenuSettings.locations[ locat ] ) );

				if ( menuID == $( api.defaultMenuID ).val() ) {
					$( api.menuID + lang ).val( '' );
				} else {
					$( api.menuID + lang ).val( menuID );
				}
			});
		},
		ajax: function(order, beforeSend) {
			return $.ajax({beforeSend:function(){
				if ( typeof beforeSend !== 'undefined' ) beforeSend();
			},type:'POST', url:ajaxurl, data:{action:WPGlobusPlusMenuSettings.process_ajax, order:order}, dataType:'json'});
		}
	};
	WPGlobusPlusMenuSettings = $.extend({}, WPGlobusPlusMenuSettings, api);
	WPGlobusPlusMenuSettings.init();

});
