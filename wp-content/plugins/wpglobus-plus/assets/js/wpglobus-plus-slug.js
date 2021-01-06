/**
 * WPGlobus Plus Admin
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusSlug */

(function($) {
    "use strict";
	var api = {
		option: {
		},
		init: function(args) {
			api.option = $.extend(api.option, args);
			this.attachListeners();
		},
		attachListeners: function() {
			$(document).on('wpglobus_post_name_full', function(event, args){
				if ( args.language == WPGlobusCoreData.default_language ) {
					return args.postnamefull;
				}
				return args.postnamefull+'-'+args.language
			});
		},
		ajax: function(order, callback) {
			return $.ajax({beforeSend:function(){callback()},type:'POST', url:ajaxurl, data:{action:WPGlobusSlug.data.process_ajax, order:order}, dataType:'json'});
		},
		editPermalink: function( id, language ) {
			// permalink
			var i, slug_value,
				c = 0,
				e = $('#editable-post-name'+'-'+language),
				revert_e = e.html(),
				//real_slug = $('#post_name'),
				real_slug = $('#post_name'+'_'+language),
				revert_slug = real_slug.val() || '',
				b = $('#edit-slug-buttons'+'-'+language),
				revert_b = b.html(),
				full = $('#editable-post-name-full'+'-'+language);

			// Deal with Twemoji in the post-name
			full.find( 'img' ).replaceWith( function() { return this.alt; } );
			full = full.html();

			$('#view-post-btn'+'-'+language).hide();
			//b.html('<a href="#" class="save button button-small">'+postL10n.ok+'</a> <a class="cancel" href="#">'+postL10n.cancel+'</a>');
			b.html('<a href="#" class="wpglobus-save button button-small" data-language="'+language+'">'+postL10n.ok+'</a> <a class="wpglobus-cancel" href="#">'+postL10n.cancel+'</a>');
			b.children('.wpglobus-save').click(function() {
				var new_slug = e.children('input').val();
				if ( new_slug == $('#editable-post-name-full'+'-'+language).text() ) {
					b.children('.wpglobus-cancel').click();
					return false;
				}
				/*
				$.post(ajaxurl, {
					action: 'wpglobus-sample-permalink',
					post_id: postId,
					new_slug: new_slug,
					new_title: $('#title'+'_'+language).val(),
					samplepermalinknonce: $('#samplepermalinknonce').val()
				}, function(data) {
					var box = $('#edit-slug-box'+'-'+language);
					box.html(data);
					if (box.hasClass('hidden')) {
						box.fadeIn('fast', function () {
							box.removeClass('hidden');
						});
					}

					b.html(revert_b);
					real_slug.val(new_slug);
					$('#view-post-btn'+'-'+language).show();
				}); // */
				
				api.ajax({
					language: language,
					action: 'wpglobus-sample-permalink',
					post_id: id,
					new_title: $('#title'+'_'+language).val(),
					new_slug: new_slug,
					samplepermalinknonce: $('#samplepermalinknonce').val(),
					post_name_full: $('#editable-post-name-full').text(),
					slug_type: WPGlobusSlug.data.slug_type
				}, function(){})
				.done(function (data) {
					var box = $('#edit-slug-box'+'-'+language);
					box.html(data);
					if (box.hasClass('hidden')) {
						box.fadeIn('fast', function () {
							box.removeClass('hidden');
						});
					}

					b.html(revert_b);
					/**
					 * @todo make real_slug
					 */
					//real_slug.val(new_slug);
					$('#view-post-btn'+'-'+language).show();
					
					/**
					 * @since 1.1.54
					 */
					$(document).trigger('wpglobus-sample-permalink:done', [data, language] );
				})
				.fail(function (error) {})
				.always(function (jqXHR, status){});
				
				return false;
			});

			b.children('.wpglobus-cancel').click(function() {
				$('#view-post-btn'+'-'+language).show();
				e.html(revert_e);
				b.html(revert_b);
				real_slug.val(revert_slug);
				return false;
			});

			for ( i = 0; i < full.length; ++i ) {
				if ( '%' == full.charAt(i) )
					c++;
			}

			slug_value = ( c > full.length / 4 ) ? '' : full;
			e.html('<input type="text" id="new-post-slug'+'-'+language+'" value="'+slug_value+'" />').children('input').keypress(function(e) {
				var key = e.keyCode || 0;
				// on enter, just save the new slug, don't save the post
				if ( 13 == key ) {
					b.children('.wpglobus-save').click();
					return false;
				}
				if ( 27 == key ) {
					b.children('.wpglobus-cancel').click();
					return false;
				}
			} ).keyup( function() {
				real_slug.val(this.value);
			}).focus();
		}		
	};
	
	WPGlobusSlug = $.extend({}, WPGlobusSlug, api);
	WPGlobusSlug.init();
	
})(jQuery);