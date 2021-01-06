/**
 * WPGlobus Plus for YoastSeo 3.0.0, 3.1.0, 3.2.0
 * Interface JS functions
 *
 * @since 1.1.5
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
		init: function(args) {
			$.each( api.classes, function(i,e){
				$(e).removeClass('hidden');
			});
			$( '.wpglobus-suggest' ).addClass( 'hidden' );
			api.addListeners();
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
			
		}	
	};
	
	WPGlobusPlusYoastSeo = $.extend({}, WPGlobusPlusYoastSeo, api);
	
	WPGlobusPlusYoastSeo.init();	
});