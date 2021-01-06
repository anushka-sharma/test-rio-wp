/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusCoreData, WPGlobusBSWidget*/
(function($) {
	"use strict";
	if ( typeof WPGlobusBSWidget == 'undefined' ) {
		return;	
	}
	
	var api = {	
		option : {
			language: WPGlobusCoreData.default_language,
			content: '',
			activeClass: 'mce-active',
			button_separator: WPGlobusBSWidget.data.button_separator,
			text_separator: WPGlobusBSWidget.data.text_separator,
			icon: WPGlobusBSWidget.data.icon,
			button: WPGlobusBSWidget.data.button,
			button_class: WPGlobusBSWidget.data.button_class,
			button_classes: WPGlobusBSWidget.data.button_classes,
			pagenow: WPGlobusBSWidget.data.pagenow
		},
		language: {},
		content: {},
		ajaxActionId: undefined,
		editor: {},
		editorKeep: false,
		preventChangeEditor: false,
		saveCustomizeEditors: false, // click Save & Publish
		init: function(args) {
			api.option = $.extend( api.option, args );
			
			if ( api.option.pagenow == 'customize.php' ) {
				api.onCustomize();	
			} else if ( api.option.pagenow == 'widgets.php' ) { 	
				api.addButtons();
				api.addListeners();
				if ( 'tinymce' != getUserSetting('editor') ) {
					setUserSetting('editor','tinymce');
				}
			}	
		},
		onCustomizeElementListeners: function( element ) {

			tinymce.get( element.id ).on( 'change', function(e){
				
				if ( api.preventChangeEditor ) {
					return;
				}	
				var w = $( e.target.iframeElement ).data( 'widget' );

				/** in html mode we need use text filter */
				var content = WPGlobusCore.TextFilter( e.level.content, WPGlobusCoreData.language );
				
				WPGlobusCustomize.controlWidgets[ w ].element[ e.target.id ].value = WPGlobusCore.getString(
					WPGlobusCustomize.controlWidgets[ w ].element[ e.target.id ].value,
					content,
					WPGlobusCoreData.language
				);	

			});
			
			$( '#' + element.id ).on( 'change', function( ev ){
				/**
				 * html mode of editor
				 */
				if ( api.preventChangeEditor ) {
					return;
				}				
				var $t = $( this ),
					w = $t.data( 'widget' ),
					id = $t.attr( 'id' );
				
				if ( ! tinymce.get( id ).isHidden() ) {
					return;	
				}	
				
				var content = WPGlobusCore.TextFilter( $t.html(), WPGlobusCoreData.language );
	
			});
			
			var sId = '#' + WPGlobusCustomize.controlWidgets[ element.widget ].submit[0].id;
			
			$( sId ).attr( 'data-editor-id', element.id );

			$( document ).off( 'mouseenter', sId, WPGlobusCustomize.onSubmitEvents )
			.off( 'mouseleave', sId, WPGlobusCustomize.onSubmitEvents )
			.off( 'click', sId, WPGlobusCustomize.onSubmitEvents );
			
			$( document ).on( 'mouseenter', sId, function(ev){
				api.preventChangeEditor = true;
				var $t = $(this),
					edId = $t.data( 'editor-id' );
				
				if ( tinymce.get( edId ).isHidden() ) {
					/** html mode */
					$( '#'+edId ).val( 
						WPGlobusCustomize.controlWidgets[ $t.data('widget') ].element[ edId ].value
					);	
				
				} else {
					tinymce.get( edId ).setContent(
						WPGlobusCustomize.controlWidgets[ $t.data('widget') ].element[ edId ].value
					);
				}	

				$.each( WPGlobusCustomize.controlWidgets[ $t.data('widget') ]['element'], function(id,e) {
					if ( id == $t.data('editor-id') ) {
						/** don't set value for editor */	
					} else {	
						$( '#' + id ).val( e.value );
					}	
				});				
				
			}).on( 'mouseleave', sId, function(ev) {
				api.preventChangeEditor = false;

				if ( false === api.editorKeep ) {
					/** restore data for current language when submit button wasn't clicked */
					var $t = $(this),
						edId = $t.data('editor-id');
					
					if ( tinymce.get( edId ).isHidden() ) {
						$( '#'+edId ).val( 
							WPGlobusCore.TextFilter( 
								WPGlobusCustomize.controlWidgets[ $t.data('widget') ].element[ edId ].value,
								WPGlobusCoreData.language,
								'RETURN_EMPTY'
							)
						);	
					} else {	
						tinymce.get( edId ).setContent(
							WPGlobusCore.TextFilter( 
								WPGlobusCustomize.controlWidgets[ $t.data('widget') ].element[ edId ].value,
								WPGlobusCoreData.language,
								'RETURN_EMPTY'
							)	
						);
					}	

					$.each( WPGlobusCustomize.controlWidgets[ $t.data('widget') ]['element'], function(id,e) {
						$( '#' + id ).val( WPGlobusCore.TextFilter( e.value, WPGlobusCoreData.language, 'RETURN_EMPTY' ) );
					});					
					
				}
				
			}).on( 'click', sId, function(ev){
				api.editorKeep = {}
				api.editorKeep['id'] = element.id;
				api.editorKeep['widget'] = element.widget;
				api.editorKeep['language'] = WPGlobusCoreData.language;
			});
			
		},	
		onCustomize: function() {

			$( document ).on( 'wpglobus_customize_control_added_widget', function( e, widget ) {
				
				if ( widget.indexOf( 'black-studio' ) >= 0 ) {
					setTimeout( function() {
						$.each( WPGlobusCustomize.controlWidgets[ widget ].element, function( element, obj ) {
							if ( tinymce.get( element ) !== null ) {
								/** work with tinymce editor */
								if ( typeof api.editor[ element ] === 'undefined' ) {
									api.editor[ element ] = {};
								}	
								api.editor[ element ]['id']  	 = element;
								api.editor[ element ]['widget']  = widget;
								api.editor[ element ]['iframe']  = '#' + element + '_ifr';
	
								$( api.editor[ element ]['iframe'] )
									.addClass( 'wpglobus-customize-translatable-element' )
									.css({ 'width':'99%' })
									.attr( 'data-widget', widget );
									
								api.onCustomizeElementListeners( api.editor[ element ] );	
							}	
						}); 
					},
					1000 );
				}	
					
			});
			
			/**
			 * ajaxComplete event handler
			 */
			$(document).on( 'ajaxComplete', function( ev, response ) {
				if ( typeof response.responseText === 'undefined' ) {
					return;	
				}
				
				if ( api.saveCustomizeEditors ) {
					$.each( WPGlobusBSWidget.editor, function( editorID, obj ) {
						tinymce.get( editorID ).setContent(
							WPGlobusCore.TextFilter( 
								WPGlobusCustomize.controlWidgets[ obj.widget ].element[ editorID ].value,
								WPGlobusCoreData.language,
								'RETURN_EMPTY' 
							)
						);	
					});
					api.saveCustomizeEditors = false;		
				}
				
				if ( response.responseText.indexOf( 'WP_CUSTOMIZER_SIGNATURE' ) >= 0 ) {
					
					if ( api.editorKeep ) {
						
						$( '.bstw-loading' ).css( 'display', 'none' );
							
						if ( tinymce.get( api.editorKeep['id'] ).isHidden() ) {
							
							var t = WPGlobusCore.TextFilter( 
								WPGlobusCustomize.controlWidgets[ api.editorKeep['widget'] ].element[ api.editorKeep['id'] ].value,
								WPGlobusCoreData.language,
								'RETURN_EMPTY'
							);
							
							$( '#'+api.editorKeep['id'] ).val( 
								WPGlobusCore.TextFilter( 
									WPGlobusCustomize.controlWidgets[ api.editorKeep['widget'] ].element[ api.editorKeep['id'] ].value,
									WPGlobusCoreData.language,
									'RETURN_EMPTY'
								)
							);								
						} else {	
						
							tinymce.get( api.editorKeep['id'] ).setContent(
								WPGlobusCore.TextFilter( 
									WPGlobusCustomize.controlWidgets[ api.editorKeep['widget'] ].element[ api.editorKeep['id'] ].value,
									api.editorKeep['language'],
									'RETURN_EMPTY' 
								)
							);	
						
						}
						
						setTimeout( function() {
							api.editorKeep = false;
						}, 2000 );	
						api.preventChangeEditor = false;

					}
				
				}
			});
			
			$( document ).on( 'wpglobus_customize_control_language', function( e, newLang ) {

				$.each( WPGlobusBSWidget.editor, function( editorID, obj ) {
					tinymce.get( editorID ).setContent(
						WPGlobusCore.TextFilter( 
							WPGlobusCustomize.controlWidgets[ obj.widget ].element[ editorID ].value,
							newLang,
							'RETURN_EMPTY' 
						)
					);	
				});	
				
			});
			
			/** Save&Publish button */
			$( '#save' ).on( 'mouseenter', function( event ) {
				$.each( WPGlobusBSWidget.editor, function( editorID, obj ) {
					tinymce.get( editorID ).setContent(
						WPGlobusCustomize.controlWidgets[ obj.widget ].element[ editorID ].value
					);	
				});	
			}).on( 'mouseleave', function( event ) {
				if ( api.saveCustomizeEditors ) {
					return;	
				}	
				$.each( WPGlobusBSWidget.editor, function( editorID, obj ) {
					tinymce.get( editorID ).setContent(
						WPGlobusCore.TextFilter( 
							WPGlobusCustomize.controlWidgets[ obj.widget ].element[ editorID ].value,
							WPGlobusCoreData.language,
							'RETURN_EMPTY' 
						)
					);	
				});	
			}).on( 'click', function(event){
				api.saveCustomizeEditors = true;
			});					
			
			
		},	
		saveContent: function( editor, language ) {
			var c, id;
			if ( typeof editor == 'object' ) {
				//c = editor.getContent().replace(/<p>\s*<\/p>/g, '' ); // remove empty p
				c = editor.getContent();
				id = editor.id;
			} else {
				/** string */
				id = editor;	
				c = $('#'+id).val();
			}	
			api.content[id] = WPGlobusCore.getString( api.content[id], c, language );
		},
		getTranslation: function( editor, language ) {
			return WPGlobusCore.getTranslations( api.content[editor.id] )[language];	
		},
		removeClass: function( id ) {
			$('.mce-'+api.option.button_class+id).removeClass( api.option.activeClass );
		},
		fixDialogStartIcon: function( id ) {
			var p;
			if ( typeof id === 'undefined' ) {
				$('.widget .wp-editor-area').each(function(i,e){
					id = $(e).attr('id');
					p = $('#'+id).parents('.widget').attr('id');
					if ( typeof p !== 'undefined' ) {
						$('#'+p+' .wpglobus_dialog_start.wpglobus_dialog_icon').css('margin-right','20px');
					}	
				});					
			} else {	
				p = $('#'+id).parents('.widget').attr('id');
				if ( typeof p != 'undefined' ) {
					$('#'+p+' .wpglobus_dialog_start.wpglobus_dialog_icon').css('margin-right','20px');
				}
			}			
		},
		addAjaxListener: function( id ) {
			$(document).ajaxComplete(function(event, jqxhr, settings){
				if ( -1 != settings.data.indexOf( 'action=save-widget') ) {
					if ( -1 != settings.data.indexOf( 'delete_widget=1' ) ) {
						// deleted widget
					} else {
						// update or added new widget
						api.fixDialogStartIcon( api.ajaxActionId );
						if ( typeof api.ajaxActionId !== 'undefined' ) {
							window.switchEditors.go( api.ajaxActionId, 'tmce' );
							api.ajaxActionId = undefined;
						}	
					}	
				}	
			});			
		},	
		addEditorListener: function( id ) {
			var p = $( '#'+id ).parents( '.widget' ).attr( 'id' );
			if ( typeof p != 'undefined' ) {
				$( '#'+p+' .widget-control-save' ).on( 'click', function( ev ){
					ev.preventDefault();
					if ( tinymce.get(id) == null || tinymce.get(id).isHidden() ) {
						// html mode
						$('#'+id).val( api.content[id] );
					} else {						
						tinymce.get(id).setContent( api.content[id] );
						/**
						 * @todo remove
						 */
						//tinymce.triggerSave();
					}	
					api.ajaxActionId = id;
				});
			}
		},	
		addListeners: function() {
			api.addAjaxListener();
			$(document).on( 'click', '.widget-title, .widget-title-action',function( ev ) {
				ev.preventDefault();
				var p = $(this).parents('.widget').attr('id');
				window.switchEditors.go( $('#'+p).find('.wp-editor-area').attr('id'), 'tmce' );
			});		
		},	
		addButtons: function() {
			tinymce.PluginManager.add(api.option.button_separator, function( editor, url ) {
				editor.addButton(api.option.button_separator, {
					text: api.option.text_separator,
					icon: api.option.icon
				});			
			});
			$.each( WPGlobusCoreData.enabled_languages, function(i,language) {	
				tinymce.PluginManager.add( api.option.button+language, function( editor, url ) {
					var active_class = '';
					/** ex. widget-black-studio-tinymce-3-text */
					if ( editor.id.indexOf('widget-black-studio-') >= 0 ) {
						if ( language == WPGlobusCoreData.default_language ) {
							api.fixDialogStartIcon(editor.id);
							api.addEditorListener(editor.id);
							api.content[ editor.id ]  = $( '#'+editor.id ).text();
							api.language[ editor.id ] = api.option.language;
							$('#'+editor.id).val( api.getTranslation(editor,language) );
							
							active_class = ' active';
							editor.on( 'keyup', function( event, l ){
								api.saveContent( editor, api.language[editor.id] );
							});
							$(document).on( 'change', '#'+editor.id, function(event){
								var id = $(this).attr('id');
								if ( tinymce.get(id).isHidden() ) {
									api.saveContent( id, api.language[id] );
								}
							});
						}	
					}	

					editor.addButton(api.option.button+language, {
						text: WPGlobusCoreData.en_language_name[language],
						icon: false,
						tooltip: 'Select '+WPGlobusCoreData.en_language_name[language]+' language',
						value: language,
						classes: api.option.button_classes + active_class + ' ' + api.option.button_class+language + ' ' + api.option.button_class+editor.id,
						onclick: function() {
							var t = $( this ),
								id = t[0]['_id'],
								l = WPGlobusCoreData.default_language;
							
							if ( typeof t[0]['_value'] != 'undefined' ) {
								l = t[0]['_value'];
							} else if ( typeof t[0].settings.value != 'undefined' ) {
								l = t[0].settings.value;
							} else {
								/** console.log('Language value not defined. It was set to default.');	*/
							}
							
							api.removeClass( editor.id );
							$('#'+id).addClass( api.option.activeClass );
							api.saveContent( editor, api.language[editor.id] );
							api.language[editor.id] = l;
							editor.setContent( api.getTranslation( editor, api.language[editor.id] ) );
						}	
					});
				});	
				
			});

		}	
	
	}
	
	WPGlobusBSWidget = $.extend({}, WPGlobusBSWidget, api);
	WPGlobusBSWidget.init();
	
})(jQuery);