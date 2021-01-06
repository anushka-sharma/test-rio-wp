/**
 * WPGlobus Plus Multilingual Taxonomies and CTPs.
 * Interface JS functions
 *
 * @since 1.1.54
 *
 * @package WPGlobus Plus
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusPlusTaxonomies*/

jQuery(document).ready(function($) {
	"use strict";
	
    if ('undefined' === typeof WPGlobusPlusTaxonomies) {
        return;
    }
	
	var api = {
		is_builder: false,
		parseBool: function(b)  {
			return !(/^(false|0)$/i).test(b) && !!b;
		},
		isBuilder: function() {
			return api.is_builder;
		},
		getBuilderID: function() {
			return WPGlobusPlusTaxonomies.builderID;
		},
		init: function() {
			api.is_builder = api.parseBool(WPGlobusPlusTaxonomies.builderID);
			api.setPostTypeSlug();
			api.attachListeners();
		},
		replaceSlug: function(language) {
			if ( language == WPGlobusCoreData.default_language ) {
				return false;
			}	
			var newSlug = WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow][language];
			if ( 'undefined' === typeof newSlug || '' == newSlug ) {
				return false;
			}
			var _permID = '#sample-permalink-'+language;
			var html = $(_permID).html();
			$(_permID).html( html.replace('/'+WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow][WPGlobusCoreData.default_language]+'/', '/'+newSlug+'/') );

			var href = $('#view-post-btn-'+language+' a').attr('href');
			$('#view-post-btn-'+language+' a').attr('href', href.replace('/'+WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow][WPGlobusCoreData.default_language]+'/', '/'+newSlug+'/'));
			return true;			
		},
		replaceSlugBuilder: function(language) {
			if ( language == WPGlobusCoreData.default_language ) {
				return false;
			}
			if ( 'undefined' === typeof WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow] ) {
				/** for CPTs */
				return false;
			}			
			var newSlug = WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow][WPGlobusPlusTaxonomies._GET.language];
			if ( 'undefined' === typeof newSlug || '' == newSlug ) {
				return false;
			}			
			var html = $('#sample-permalink a').html();
			$('#sample-permalink a').html( html.replace('/'+WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow][WPGlobusCoreData.default_language]+'/', '/'+newSlug+'/') );
			var href = $('#sample-permalink a').attr('href');
			$('#sample-permalink a').attr('href', href.replace('/'+WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow][WPGlobusCoreData.default_language]+'/', '/'+newSlug+'/'));
			return true;
		},
		attachListeners: function() {
			$(document).on('wpglobus-sample-permalink:done', function(event, result, language){
				if ( api.isBuilder() ) {
					api.replaceSlugBuilder(language);
				} else {
					api.replaceSlug(language);
				}
			});
		},
		setPostTypeSlug: function() {
			if ( 'undefined' === typeof WPGlobusPlusTaxonomies.post_type[WPGlobusPlusTaxonomies.typenow] ) {
				return;
			}

			if ( api.isBuilder() ) {
				
				var language = '';
				if ( 'undefined' === typeof WPGlobusPlusTaxonomies._GET.language ) {
					language = WPGlobusCoreData.default_language;
				} else {
					language = WPGlobusPlusTaxonomies._GET.language;
				}
				if ( language == WPGlobusCoreData.default_language ) {
					return;
				}
				
				api.replaceSlugBuilder(language);
				
			} else {
				
				$.each(WPGlobusCoreData.enabled_languages, function(i, language) {
					
					if ( language == WPGlobusCoreData.default_language ) {
						return true;
					}
					api.replaceSlug(language);
				});
				
			}			
		}
	};
	
	WPGlobusPlusTaxonomies = $.extend({}, WPGlobusPlusTaxonomies, api);
	WPGlobusPlusTaxonomies.init();	
	
});
