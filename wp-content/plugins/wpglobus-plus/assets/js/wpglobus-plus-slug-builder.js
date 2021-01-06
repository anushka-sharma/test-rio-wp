/**
 * WPGlobus Plus Slug Admin
 * Interface JS functions
 *
 * @since 1.1.42
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusPlusSlug */

(function($) {
    "use strict";
	var api = {
		option: {},
		real_slug: $('#post_name'),
		revert_slug: $('#post_name').val(),
		currentExtraLanguage: undefined,
		promise: $.when(),
		init: function(args) {
			api.option = $.extend(api.option, args);
			api.localizePermalink(true);
			this.attachListeners();
			api.yoastSettings();
		},
		yoastSettings: function() {
			if ( WPGlobusAdmin.builder.language == WPGlobusCoreData.default_language ) {
				return;
			}
			if ( 'YoastSEO' == WPGlobusPlusSlug.yoastSeo ) {
				setTimeout(function() {
					$('.wpseo-metabox-root button').each(function(i,e){
						var classes = $(e).attr('class');
						if ( -1 != classes.indexOf('Button__BaseButton-') ) {
							$(e).addClass('wpglobus-plus-base-button');
						}
					});
					$(document).on('click', '.wpglobus-plus-base-button', function(ev){
						setTimeout(function() {
							$('#snippet-editor-field-slug').prop('disabled', true);
						}, 500);
					});
				}, 1500);
			}
		},
		localizePermalink: function(init) {
			if ( 'undefined' === typeof init ) {
				init = false;
			}
			if ( 'gutenberg' == WPGlobusPlusSlug.builderID ) {
				//
			} else {
				if ( WPGlobusCoreData.default_language != WPGlobusPlusSlug.builder.language ) {
					/**
					 * Modify permalink data.
					 */
					var homeURL = WPGlobusPlusSlug.homeURL;
					var permalink = $('#sample-permalink').html();
					var regexp = new RegExp(WPGlobusPlusSlug.homeURL, 'g');
					var localized = permalink.replace( regexp, WPGlobusPlusSlug.homeLocalizedURL );
					
					if ( init ) {
						if ( null !== WPGlobusPlusSlug.slug ) {
							localized = localized.replace(new RegExp($('#post_name').val()), WPGlobusPlusSlug.slug );
						}
						$('#sample-permalink').html(localized);
						if ( null === WPGlobusPlusSlug.slug && null === WPGlobusPlusSlug.shortenSlug ) {
							// do nothing.
						} else {
							$('#editable-post-name-full').text(WPGlobusPlusSlug.slug);
							$('#editable-post-name').text(WPGlobusPlusSlug.shortenSlug);
						}
					} else {
						localized = localized.replace(new RegExp($('#post_name').val()), $('#editable-post-name-full').text() );
						$('#sample-permalink').html(localized);
						
					}

				}
			}
		},
		attachListeners: function() {
			
			if ( 'gutenberg' == WPGlobusPlusSlug.builderID ) {
				$(document).on('blur', '.wpglobus-editable-post-name', function(ev) {
					// @todo may be 'focusout' event.
					var $t = $(this);
					var newName = $t.val();
							
					if ( newName == $t.data('old-name') ) {
						return;
					}

					if ( newName.length > 0 ) {
						api.getSamplePermalink($t.data('post-id'), $t.data('language'), newName);
					} else {
						api.remove($t.data('post-id'), $t.data('language'));
					}
				});
			} else {
				
				$(document).ajaxSend(function(event, jqxhr, settings){
					if ( 'undefined' === typeof settings.data ) {
						return;	
					}
					var lang;
					if ( 'undefined' === typeof api.currentExtraLanguage ) {
						lang = $('input[name="wpglobus_language"]').val();
						if ( 'undefined' === typeof lang || lang == WPGlobusCoreData.default_language ) {
							return;
						}
					}

					/**
					 * action=sample-permalink
					 * @see Save permalink changes in wp-admin\js\post.js
					 */
					if ( -1 != settings.data.indexOf('action=sample-permalink') ) {
						api.currentExtraLanguage = lang;
					}
					
				});
				$(document).ajaxComplete(function(event, jqxhr, settings){
					if ( 'undefined' === typeof api.currentExtraLanguage ) {
						return;
					}
					/**
					 * Check `action=sample-permalink` again @see ajaxSend()
					 * This is important for slow internet connection.
					 * @see Save permalink changes in wp-admin\js\post.js
					 * @since 1.2.2
					 */
					if ( -1 == settings.data.indexOf('action=sample-permalink') ) {
						return;
					}	
					api.save( WPGlobusAdmin.$_get.post, api.currentExtraLanguage, $('#editable-post-name-full').text() );
					/**
					 * Restore slug for default language.
					 */
					api.real_slug.val( api.revert_slug );
					api.localizePermalink(false);
					api.currentExtraLanguage = undefined;
				});
			}
		},
		getSamplePermalink: function(postId, language, new_slug) {
		
			$.post(
				ajaxurl,
				{
					action: 'sample-permalink',
					post_id: postId,
					new_slug: new_slug,
					new_title: $('#title').val(),
					samplepermalinknonce: $('#samplepermalinknonce').val()
				},
				function(data) {
					var match = data.match(/<span id="editable-post-name-full">.*<\/span>/);
					if ( 'undefined' !== typeof match[0] ) {
						var __name = match[0].replace('<span id="editable-post-name-full">', '');
						__name = __name.replace('</span>', '');
						api.save(postId, language, __name);
						$('#editable-post-name-'+language).val(__name);
					}
				}
			);
			
		},
		remove: function(post_id, language) {
			
			api.promise = api.promise.then( function() {

				var order = {};
				order['action'] 	= 'wpglobus-remove-name';
				order['post_id'] 	= post_id;
				order['language'] 	= language;
	
				return $.ajax({
					beforeSend:function(){
						if ( 'undefined' !== typeof api.beforeSend ) api.beforeSend(order);
					},
					type: 'POST',
					url: WPGlobusAdmin.ajaxurl,
					data: {action:WPGlobusPlusSlug.process_ajax, order:order},
					dataType: 'json' 
				});					
			}, function(){
				/* error in promise */
				/* return $.ajax( ); */
			}).then( function( result ) {
				if ( 'success' == result.status ) {
					$('#editable-post-name-'+result.language).data('old-name', result.new_name);
				}
			});			
			
		},
		save: function(post_id, language, newName) {
			
			api.promise = api.promise.then( function() {

				var order = {};
				order['action'] 	= 'wpglobus-save-name';
				order['post_id'] 	= post_id;
				order['language'] 	= language;
				order['new_name'] 	= newName;
	
				return $.ajax({
					beforeSend:function(){
						if ( 'undefined' !== typeof api.beforeSend ) api.beforeSend(order);
					},
					type: 'POST',
					url: WPGlobusAdmin.ajaxurl,
					data: {action:WPGlobusPlusSlug.process_ajax, order:order},
					dataType: 'json' 
				});					
			}, function(){
				/* error in promise */
				/* return $.ajax( ); */
			}).then( function( result ) {
				if ( 'success' == result.status ) {
					$('#editable-post-name-'+result.language).data('old-name', result.new_name);
					/**
					 * @since 1.1.54
					 */
					$(document).trigger('wpglobus-sample-permalink:done', [result, result.language] );
				}	
			});

		}
	
	};
	
	WPGlobusPlusSlug = $.extend({}, WPGlobusPlusSlug, api);
	WPGlobusPlusSlug.init();
	
})(jQuery);