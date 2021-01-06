/**
 * WPGlobus Plus for YoastSeo 3.3.0
 * Interface JS functions
 *
 * @since 1.5.7
 *
 * @package WPGlobus Plus
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusYoastSeo, WPGlobusYoastSeoPlugin*/
jQuery(document).ready(function($){
    "use strict";
	
	if ( typeof WPGlobusYoastSeo === 'undefined' ) {
		return;	
	}	
	if ( typeof WPGlobusYoastSeoPlugin === 'undefined' ) {
		return;	
	}	
	
	var api = {
		classes: [ '.wpglobus-yoast_wpseo_focuskw_text_input', '.wpglobus-wpseo-pageanalysis' ],
		__canonicalUrl: '',
		__useCanonicalUrl: true,
		init: function(args) {
			api.__canonicalUrl = WPGlobusPlusYoastSeo.canonicalUrl;
			api.__useCanonicalUrl = api.parseBool(WPGlobusPlusYoastSeo.canonicalUrl);
			api.setCanonicalUrl();
			$.each( api.classes, function(i,e){
				$(e).removeClass('hidden').css({'display':'block'});
			});
			api.addClass();
			$( '.wpglobus-suggest' ).addClass( 'hidden' );
			api.addListeners();
		},
		parseBool: function(b)  {
			return !(/^(false|0)$/i).test(b) && !!b;
		},	
		setCanonicalUrl: function() {
			if ( api.__useCanonicalUrl ) {
				setInterval(function(){
					WPGlobusDialogApp.addElement(api.__canonicalUrl);
				},1000);
			}
		},
		addClass: function( l ) {
			$('.wpglobus-yoast_wpseo_focuskw_text_input').addClass('wpglobus-translatable');
			$('.wpglobus-metakeywords').addClass('wpglobus-translatable');
			$('.wpglobus-snippet-editor-title').addClass('wpglobus-translatable');
			$('.wpglobus-snippet-editor-meta-description').addClass('wpglobus-translatable');
		},
		setCite: function( l ) {
			// @see citeModification() in WPGlobusYoastSeoPlugin
			$( '#snippet_cite_' + l ).text( $( '#editable-post-name-full-' + l ).text() );
		},	
		setSnippetSlug: function( l ) {
			var ss = $( '#snippet-editor-slug_' + l );
			if ( ss.size() == 1 ) {
				ss.val( $( '#editable-post-name-full-' + l ).text() );	
			}	
		},	
		addListeners: function() {

			$( WPGlobusYoastSeo.wpseoTabSelector ).on( 'tabsactivate', function(event, ui){
				var l = ui.newPanel.attr( 'data-language' );
				if ( l === 'undefined' || l == WPGlobusCoreData.default_language ) {
					return;
				}
				// set cite
				api.setCite( l );
				
				/**
				 * @since 1.1.10
				 * for YoastSEO 3.1
				 */
				api.setSnippetSlug( l );
				
				// set url @see YoastSEO.Analyzer.prototype.urlKeyword
				WPGlobusYoastSeo.url = $( WPGlobusYoastSeoPlugin.post_slug + '-' + l ).text();
				
				// set keyword
				var k = $( '#yoast_wpseo_focuskw_text_input_' + l ).val();
				YoastSEO.app.rawData.keyword = k ;
				WPGlobusYoastSeoPlugin.focuskw.val( k );
				WPGlobusYoastSeoPlugin.focuskw_hidden.val( k );

				YoastSEO.app.analyzeTimer(YoastSEO.app);
			});
			
			$(document).on( 'wpglobus_yoast_analysis', function( i,e ) {
				return true;
			});

			/**
			 * Meta keywords.
			 * @since 1.8.8
			 */			
			$(document).on('wpglobus_meta_keywords', function(event) {
				$(document).on('keyup', '.wpglobus-metakeywords', function(ev){
					var $t = $(this),
						l = $t.data('language');

					var s = WPGlobusCore.getString( $('#yoast_wpseo_metakeywords').val(), $t.val(), l );
					$('#yoast_wpseo_metakeywords').val(s);				
				});
				return true;
			});
		}	
	};
	
	WPGlobusPlusYoastSeo = $.extend({}, WPGlobusPlusYoastSeo, api);
	
	WPGlobusPlusYoastSeo.init();	
});