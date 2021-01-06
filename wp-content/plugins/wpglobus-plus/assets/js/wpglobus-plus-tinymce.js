/**
 * WPGlobus Plus TinyMCE.
 * Interface JS functions
 *
 * @since 1.1.25
 *
 * @package WPGlobus Plus
 */
/*jslint browser:true*/
/*global tinymce:true */
/*global jQuery, console, WPGlobusCore, WPGlobusCoreData, WPGlobusAdminApp, WPGlobusPlusTinyMCE*/

// http://www.wpbeginner.com/wp-tutorials/how-to-create-a-wordpress-tinymce-plugin/
// https://www.gavick.com/blog/wordpress-tinymce-custom-buttons

jQuery(document).ready(function($){
	"use strict";

	if ( 'undefined' === typeof WPGlobusPlusTinyMCE ) {
		return;
	}

	if ( WPGlobusPlusTinyMCE.page == 'wpglobus-plus-options' && WPGlobusPlusTinyMCE.tab == 'tinymce' ) {
		/* do nothing */
	} else {

		if ( 'undefined' === typeof WPGlobusCoreData ) {
			return;
		}
		if ( 'undefined' === typeof WPGlobusAdminApp ) {
			return;
		}

	}

	var api = {
		dLang  				: WPGlobusCoreData.default_language,
		languageButtonClass : 'widget btn wpglobus-plus-language-button',
		activeButtonClass   : 'active',
		currentLanguage		: {},
		content				: {},
		savedStatus			: false,
		debugMode			: false,
		settingsTab			: WPGlobusPlusTinyMCE.page == 'wpglobus-plus-options' && WPGlobusPlusTinyMCE.tab == 'tinymce',
		startInitEditors	: false,
		submitElements		: [],
		init: function() {
			if ( api.settingsTab ) {
				api.initSettingsTab();
			} else {
				api.debugMode = WPGlobusAdminApp.App.prototype.parseBool( WPGlobusCoreData.page_custom_data.debugMode );
				if ( 'undefined' !== typeof tinymce ) {
					api.checkSubmitElements();
					api.addButtons();
					api.initEditors();
					api.attachListeners();
				}
			}
		},
		initSettingsTab: function() {
			/**
			 * Add new item.
			 */
			$(document).on('click', '#wpglobus-plus-add-item', function(ev){
				var $item = $('#wpglobus-plus-skeleton tbody tr').clone();
				$('#wpglobus-plus-tinymce-items tbody .no-items').hide();
				$('#wpglobus-plus-tinymce-items tbody').append($item);
			});

			/**
			 * onclick.
			 */
			$(document).on('click', '.wpglobus-plus-action-ajaxify', function(ev){
				ev.preventDefault();
				var $t = $(this);
				if ( 'remove' == $t.data('action') ) {
					$t.parents('tr').fadeToggle('slow');
				}
				api.order = {};
				api.order['action'] 	= WPGlobusPlusTinyMCE.module + '-' + $t.data('action');
				api.order['module'] 	= WPGlobusPlusTinyMCE.module;
				api.order['page'] 		= $t.data('page');
				api.order['key'] 		= $t.data('key');
				api.order['settings'] 	= WPGlobusPlusTinyMCE.settings;
				api.ajax(api.order)
				.done(function (data) {
				})
				.fail(function (error) {})
				.always(function (jqXHR, status){});
			});

			/**
			 * onchange.
			 */
			$(document).on('change', '.wpglobus-plus-ajaxify', function(ev){
				var $t = $(this),
					$p = $t.parents('tr');

				api.order = {};
				api.order['action'] 	= WPGlobusPlusTinyMCE.module + '-' + $t.data('action');
				api.order['module'] 	= WPGlobusPlusTinyMCE.module;
				api.order['page']   	= $p.find('input.page').val();
				api.order['element']   	= $p.find('input.element').val();
				api.order['key'] 		= $t.data('key');
				api.order['settings'] 	= WPGlobusPlusTinyMCE.settings;

				if ( '' == api.order['page'] || '' == api.order['element'] ) {
					return;
				}
				api.ajax(api.order)
				.done(function (data) {
				})
				.fail(function (error) {})
				.always(function (jqXHR, status){});
			});
		},
		ajax: function(order, beforeSend) {
			return $.ajax({
				beforeSend:function(){
					if ( 'undefined' !== typeof beforeSend ) beforeSend();
			},type:'POST', url:ajaxurl, data:{action:WPGlobusPlusTinyMCE.process_ajax, order:order}, dataType:'json'});
		},
		initEditors: function() {
			if ( ! api.startInitEditors ) {
				return;
			}
			$(document).on( 'tinymce-editor-init', function( event, editor ) {

				if ( api.isExcluded(editor.id) ) {
					return;
				}

				/** tinymce */
				editor.on( 'nodechange keyup', _.debounce( api.update, 300 ) );

				/** textarea */
				$( '#' + editor.id ).on( 'input keyup', _.debounce( api.update, 300 ) );

			} );
		},
		checkSubmitElements: function() {

			var submitElements = null;
			if ( 'undefined' !== typeof WPGlobusCoreData.page_custom_data.submitElements[ WPGlobusPlusTinyMCE.pagenow ] ) {
				submitElements = WPGlobusCoreData.page_custom_data.submitElements[ WPGlobusPlusTinyMCE.pagenow ];
			} else {
				if ( 'undefined' !== typeof WPGlobusCoreData.page_custom_data.submitElements[ WPGlobusPlusTinyMCE.page ] ) {
					submitElements = WPGlobusCoreData.page_custom_data.submitElements[ WPGlobusPlusTinyMCE.page ];
				}
			}

			if ( submitElements === null ) {
				/** do nothing */
			} else {

				$.each( submitElements, function( i, element ) {
					if ( ! /^[#]|^[.]|^input\[name=/.test(element) ) {
						submitElements[i] = 'input[name="'+element+'"]';
					}
					if ( $( submitElements[i] ).length > 0 )  {
						api.startInitEditors = true;
					}
				});
				api.submitElements = submitElements;
			}

		},
		addButtons: function() {
			tinymce.PluginManager.add('wpglobus_globe', function( editor, url ) {

				if ( api.isExcluded(editor.id) ) {
					return;
				}

				if ( api.startInitEditors ) {
					/**
					 * Init content.
					 */
					api.content[editor.id]  			= $('#'+editor.id).val();
					api.currentLanguage[editor.id]		= api.dLang;
					var content = WPGlobusCore.getTranslations( api.content[editor.id] );
					$( '#'+editor.id ).val( content[api.dLang] );

					/**
					 * Add WPGlobus translatable class.
					 */
					_.delay( function () {
						$( editor.iframeElement ).addClass( 'wpglobus-translatable' ).css({'width':'99%'});
						$( '#' + editor.id ).addClass( 'wpglobus-translatable' );
					}, 2000 );
				}

				/**
				 * Tooltip.
				 */
				var tooltip, icon;
				if ( api.startInitEditors ) {
					tooltip = api.debugMode ? 'Get editor ID' : '';
					icon = 'wpglobus-plus-globe';
				} else {
					tooltip = WPGlobusPlusTinyMCE.i18n.warning;
					icon = 'wpglobus-plus-red-globe';
				}
				editor.addButton( 'wpglobus_globe', {
					text: '',
					icon: icon,
					tooltip: tooltip,
					onclick: function() {
						if ( 'wpglobus-plus-red-globe' == $(this)[0].settings.icon ) {
							window.open( WPGlobusPlusTinyMCE.settings.page, '_blank' );
						} else {
							if ( api.debugMode && api.startInitEditors ) {
								editor.insertContent( editor.id );
							}
						}
					}
				});
			});

			if ( ! api.startInitEditors ) {
				return;
			}

			$.each( WPGlobusCoreData.enabled_languages, function(indx,language) {
				tinymce.PluginManager.add('wpglobus_language_button_' + language, function( editor, url ) {
					if ( api.isExcluded(editor.id) ) {
						return;
					}
					var activeButtonClass = language == api.dLang ? api.activeButtonClass : '';
					editor.addButton( 'wpglobus_language_button_' + language, {
						text: language,
						icon: false,
						tooltip: 'Select '+WPGlobusCoreData.en_language_name[language]+' language',
						classes: api.languageButtonClass + ' ' + activeButtonClass + ' wpglobus-plus-language-button-'+editor.id+' wpglobus-plus-language-button-'+language,
						onclick: function() {
							/** editor.insertContent( editor.id ); */
							editor.execCommand( 'mceNewDocument' );
							editor.execCommand( 'mceInsertContent', false, WPGlobusCore.getTranslations(api.content[editor.id])[language] );
							api.currentLanguage[editor.id] = language;
							api.toggleButtonClass(editor.id, language)
						}
					});
				});
			});
		},
		toggleButtonClass: function( edID, language ) {
			$( '.mce-wpglobus-plus-language-button-'+edID ).removeClass( 'mce-active' );
			$( '.mce-wpglobus-plus-language-button-'+edID+'.mce-wpglobus-plus-language-button-'+language ).addClass( 'mce-active' );
		},
		attachListeners: function() {
			if ( ! api.startInitEditors ) {
				return;
			}

			$.each( api.submitElements, function(i, submitElement) {
				if ( submitElement.length == 0 ) {
					return true;
				}
				$(document).on( 'mouseenter', submitElement, function(evnt){
					$.each( api.content, function(id, content){
						if ( tinymce.get( id ) == null || tinymce.get( id ).isHidden() ) {
							$( '#' + id ).val( content );
							$( '#' + id + '-tmce' ).click();
						} else {
							tinymce.get( id ).setContent( content, { format:'raw' } );
						}
					});
				}).on( 'mouseleave', submitElement, function( event ) {
					if ( api.savedStatus ) {
						return;
					}
					$.each( api.content, function(id, content){
						var content = WPGlobusCore.getTranslations(api.content[id])[ api.currentLanguage[id] ];
						if ( tinymce.get( id ) == null || tinymce.get( id ).isHidden() ) {
							$( '#' + id ).val( content );
						} else {
							tinymce.get( id ).setContent( content, { format:'raw' } );
						}
					});
				}).on( 'click', submitElement, function( event ) {
					api.savedStatus = true;
				});
			});
		},
		update: function( event ) {
			/**
			 * Need for testing.
			 * console.log( 'UPDATE' );
			 */
			var id, text;

			if ( typeof event.target !== 'undefined' ) {
				id = event.target.id;
			} else {
				return;
			}

			if ( id == 'tinymce' ) {
				id = event.target.dataset.id;
			}

			if ( 'undefined' === typeof api.content[ id ] ) {
				return;
			}

			if ( tinymce.get( id ) == null || tinymce.get( id ).isHidden() ) {
				text = $( '#' + id ).val();
			} else {
				text = tinymce.get( id ).getContent( { format: 'raw' } );
			}
			api.content[ id ] = WPGlobusCore.getString( api.content[ id ], text, api.currentLanguage[ id ] );
		},
		isExcluded: function(id) {
			if ( 'undefined' === typeof id ) {
				return false;
			}

			/**
			 * Check exact match.
			 */
			if ( -1 != $.inArray( id, WPGlobusPlusTinyMCE.excluded ) ) {
				return true;
			}
			/**
			 * Check the matching by mask.
			 */
			var result = false;
			$.each( WPGlobusPlusTinyMCE.excludedMask, function(i,mask){
				 if ( -1 != id.indexOf( mask ) ) {
					result = true;
					return false; // break loop.
				 }
			});

			return result;

		}
	};

	WPGlobusPlusTinyMCE = $.extend({}, WPGlobusPlusTinyMCE, api);
	WPGlobusPlusTinyMCE.init();

});

