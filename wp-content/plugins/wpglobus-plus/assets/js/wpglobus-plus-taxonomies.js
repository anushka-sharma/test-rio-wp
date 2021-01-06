/**
 * WPGlobus Plus Multilingual Taxonomies and CTPs.
 * Interface JS functions
 *
 * @since 1.1.33
 *
 * @package WPGlobus Plus
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusPlusTaxonomies*/

jQuery(document).ready(function($) {
	"use strict";
	var api = {
		postType: '',
		spinnerClass: '.spinner',
		dialogBeforeOpen: function(attrs){
			$('.wpglobus-button-save').css({'visibility':'hidden'});
		},
		dialogClose: function(attrs){
			$('.wpglobus-button-save').css({'visibility':''});
		},
		setPostType: function(){
			if ( 'undefined' === typeof WPGlobusPlusTaxonomies.$_GET['post-type'] ) {
				api.postType = 'post';
				return;
			}
			var _pt = WPGlobusPlusTaxonomies.$_GET['post-type'];
			if ( $('#tabs-taxonomies li.tab-post-type-'+_pt).length == 0 ) {
				// reset incorrect post type in URL.
				api.postType = 'post';
				var search = location.search.replace("post-type="+_pt, "post-type=post");
				var url = 'admin.php' + search;
				history.pushState({}, '', url);		
			} else {
				api.postType = _pt;
			}
		},
		getPostType: function(){
			return api.postType;
		},
		init: function() {
			
			if ( 'undefined' !== typeof WPGlobusPlusTaxonomies.pagenow 
					&& 'options-permalink.php' == WPGlobusPlusTaxonomies.pagenow ) {
				api.initPermalinkPage();
			}
			
			if ( 'taxonomies' != WPGlobusPlus.tab ) {
				return;
			}
		
			//if ( 'general' == WPGlobusPlusTaxonomies.currentSection ) {
				$( WPGlobusPlusTaxonomies.setContentId ).html( WPGlobusPlusTaxonomies.contentHtml );
			//} else if ( 'debug' == WPGlobusPlusTaxonomies.currentSection ) {
				//$( WPGlobusPlusTaxonomies.setContentId ).html(' 11111 ' );
			//}
			api.setPostType();
			this.start();
			this.attachListeners();
			
			
		},	
		initPermalinkPage: function() {
			/*
			WPGlobusDialogApp.addElement({
				id: 'category_base',
				dialog: {
					title: 'Category base',
					placeholder: WPGlobusPlusTaxonomies.multilingualSlug['category'],
					formFooter: WPGlobusPlusTaxonomies.formFooter['category'],
					beforeOpen: {'WPGlobusPlusTaxonomies':'dialogBeforeOpen'},
					close: {'WPGlobusPlusTaxonomies':'dialogClose'}
				}
			});
			WPGlobusDialogApp.addElement({
				id: 'tag_base',
				dialog: {
					title: 'Tag base',
					placeholder: WPGlobusPlusTaxonomies.multilingualSlug['post_tag'],
					formFooter: WPGlobusPlusTaxonomies.formFooter['post_tag'],
					beforeOpen: {'WPGlobusPlusTaxonomies':'dialogBeforeOpen'},
					close: {'WPGlobusPlusTaxonomies':'dialogClose'}
				}
			}); //*/
		},
		tabActivate: function(event, ui, tabCreate) {
			tabCreate = tabCreate || false;
			var _oldPostType = $(ui.oldTab).data('post-type') || false;
			var _newPostType = $(ui.newTab).data('post-type') || false;
			if ( ! _newPostType ) {
				_newPostType = api.getPostType();
			}
			
			$('#tabs-taxonomies li').css({'font-weight':''});
			$('#tabs-taxonomies li.tab-post-type-'+_newPostType).css({'font-weight':'bold'});
			
			if ( ! tabCreate ) {
				var search = location.search;
				if ( -1 == search.indexOf('&post-type=') ) {
					var url = 'admin.php' + search + '&post-type='+_newPostType;
					history.pushState({}, '', url);
				} else {
					if ( _oldPostType ) {
						search = search.replace("post-type="+_oldPostType, "post-type="+_newPostType);
						var url = 'admin.php' + search;
						history.pushState({}, '', url);
					}
				}
			}				
		},
		start: function() {
			$( "#tabs-taxonomies" ).tabs({
				create: function(event, ui){api.tabActivate(event, ui, true)},
				activate: function(event, ui){api.tabActivate(event, ui)}
			}).addClass( "ui-tabs-vertical ui-helper-clearfix" );
			$( "#tabs-taxonomies li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );					
			setTimeout( function() { 

				$('.wpglobus-plus-section').removeClass('one-col');
			
				$('#post-type-'+api.postType).click();
				if ( location.hash == '' ) {
					$('.wpglobus-plus-taxonomies-terms-box').addClass('hidden');
				} else {
					if ( 'post' != api.getPostType() ) {
						var container = $('body'),
							scrollTo = $(location.hash);
						if ( scrollTo.length == 1 ) {
							container.animate({
								scrollTop: scrollTo.offset().top - container.offset().top + container.scrollTop()
							});
						}
					}
				}
			}, 300 );
			api.setTooltip();
		},
		setTooltip: function() {
			$('.wpglobus-plus-taxonomies-help').tooltip({
				position: {
					my: "center bottom-20",
					at: "center top",
					using: function( position, feedback ) {
						$( this ).css( position );
						$( "<div>" )
						.addClass( "arrow" )
						.addClass( feedback.vertical )
						.addClass( feedback.horizontal )
						.appendTo( this );
					}
				}
			});
		},
		attachListeners: function() {
			
			$(document).on('keyup', '.wpglobus-plus-taxonomy-field', function(ev){
				/**
				 * Get caret position:
				 *	function f1(el) {
				 *		var val = el.value;
				 *		alert(val.slice(0, el.selectionStart).length);
				 *	}				 
				 */
				var $t = $(this),
					v = $t.val(),
					found = v.match(/\s/);

				if ( null === v.match(/\s/)	) {
					return;
				}
				$t.val( v.replace( /\s/g, '-' ) );
			});
			
			/**
			 * Make multilingual string of slugs for save.
			 */
			$(document).on('change', '.wpglobus-plus-taxonomy-field', function(ev){
				var $t = $(this),
					sourceId = $t.data('source-id'),
					lang = $t.data('language'),
					value = $t.val();
				if ( lang != WPGlobusCoreData.default_language ) { 
					if ( '' == value ) {
						value = $t.data('slug-default');
						$t.val(value);
					}
					$(sourceId).val( WPGlobusCore.getString( $(sourceId).val(), value, lang ) );
				}
			});
			
			/**
			 * Hide/show whole terms list.
			 */
			$(document).on('click', '.wpglobus-plus-taxonomies-toggle', function(ev){
				var $t = $(this);
				if ( $('#wpglobus-plus-taxonomies-'+$t.data('taxonomy')+'-terms-box').hasClass('hidden') ) {
					$('.wpglobus-plus-taxonomies-terms-box').addClass('hidden');
					$('#wpglobus-plus-taxonomies-'+$t.data('taxonomy')+'-terms-box').removeClass('hidden');
				} else {
					$('.wpglobus-plus-taxonomies-terms-box').addClass('hidden');
				}
			});
			
			/**
			 * Hide/show terms without miltilingual slug.
			 */
			$(document).on('click', '.wpglobus-plus-taxonomies-hide-empty-terms', function(ev){
				var $t = $(this),
					hideTerm = $t.prop('checked');

				if ( ! hideTerm ) {
					$('#wpglobus-plus-taxonomies-'+$t.data('taxonomy')+'-terms-box .wpglobus-plus-taxonomies-term').removeClass('hidden');
					return;
				}
				
				$('#wpglobus-plus-taxonomies-'+$t.data('taxonomy')+'-terms-box .wpglobus-taxonomy-term-source').each(function(i,e){
					var $e = $(e);
					if ( '' == $e.val() ) {
						var id = $e.data('term-id');
						$('#wpglobus-plus-taxonomies-'+$t.data('taxonomy')+'-terms-box #taxonomy-term-'+id).addClass('hidden');
					}
				});
			});
			
			/**
			 * God mode. Activates by clicking on Post type: header.
			 */
			$(document).on('dblclick', '.post-type-tab', function(ev){
				$('.wpglobus-taxonomy-source').toggleClass('hidden');
				$('.wpglobus-taxonomy-term-source').toggleClass('hidden');
			});
			
			/**
			 * Save multilingual slug strings in DB.
			 */
			$(document).on('click', '.post-type-save', function(ev){
				
				var $t = $(this);
				
				var data = {}; 
				data['post_type'] = {};
				data['taxonomy']  = {};
				
				$( $t.data('tab-id') + ' .wpglobus-taxonomy-source' ).each(function(i,e){
					var $e = $(e);
					var pt = $e.data('post-type');
					if ( 'undefined' !== typeof pt ) {
						data['post_type'][pt] = {};
						data['post_type'][pt] = $e.val();
					}
					var tax = $e.data('taxonomy');
					if ( 'undefined' !== typeof tax ) {
						data['taxonomy'][tax] = {};
						data['taxonomy'][tax]['slug'] = $e.val();
					}
				});
		
				$( $t.data('tab-id') + ' .wpglobus-taxonomy-term-source' ).each(function(i,e){
					var $e = $(e);
					var tax = $e.data('taxonomy');
					var slug = $e.data('term-slug');
					var termID = 'term_id_'+$e.data('term-id');
					if ( 'undefined' !== typeof data['taxonomy'][tax] ) {
						if ( 'undefined' === typeof data['taxonomy'][tax]['term_slug'] ) {
							data['taxonomy'][tax]['term_slug'] = {};
						}
						if ( 'undefined' === typeof data['taxonomy'][tax]['term_slug'][termID] ) {
							data['taxonomy'][tax]['term_slug'][termID] = {};
						}
						data['taxonomy'][tax]['term_slug'][termID] = $e.val();
					}
				});	
		
				api.ajax({
					action: 'taxonomies-save',
					module: WPGlobusPlusTaxonomies.module,
					post_type: $t.data('post-type'),
					data: data
				}, api.beforeSend)
				.done(function (result) {
					//api.done( result );
				})
				.fail(function (error) {})
				.always(function (jqXHR, status){
					$(api.spinnerClass).css({'visibility':'hidden'});
				});
			});
			
			/**
			 * Listeners for section `Debug`.
			 */
			if ( 'undefined' !== typeof WPGlobusPlusTaxonomies.$_GET.section && 'debug' == WPGlobusPlusTaxonomies.$_GET.section ) { 
				$(document).on('click', '.wpglobus-plus-taxonomies-title', function(ev){
					var id = $(this).data('id');
					if ( $('.wpglobus-plus-taxonomies-debug-data-'+id).hasClass('hidden') ) {
						$('.wpglobus-plus-taxonomies-debug-data').addClass('hidden');
						$('.wpglobus-plus-taxonomies-debug-data-'+id).removeClass('hidden');
					} else {
						$('.wpglobus-plus-taxonomies-debug-data').addClass('hidden');
					}
				});
			}
			
			/**
			 * Listeners for section `Rewrite rules`.
			 * @since 1.3.11
			 */
			if ( 'undefined' !== typeof WPGlobusPlusTaxonomies.$_GET.section && 'rewrite_rules' == WPGlobusPlusTaxonomies.$_GET.section ) { 
				$(document).on('click', '.wpglobus-plus-taxonomies-title', function(ev){
					var id = $(this).data('id');
					if ( $('.wpglobus-plus-taxonomies-rewrite_rules-data-'+id).hasClass('hidden') ) {
						$('.wpglobus-plus-taxonomies-rewrite_rules-data').addClass('hidden');
						$('.wpglobus-plus-taxonomies-rewrite_rules-data-'+id).removeClass('hidden');
					} else {
						$('.wpglobus-plus-taxonomies-rewrite_rules-data').addClass('hidden');
					}
				});
			}			
		},
		beforeSend: function() {
			$(api.spinnerClass).css({'visibility':'visible'});
		},
		ajax: function(order, beforeSend) {
			return $.ajax({beforeSend:function(){
				if ( 'undefined' !== typeof beforeSend ) beforeSend();
			},type:'POST', url:ajaxurl, data:{action:WPGlobusPlusTaxonomies.process_ajax, order:order}, dataType:'json'});
		}
	};
	
	WPGlobusPlusTaxonomies = $.extend({}, WPGlobusPlusTaxonomies, api);
	WPGlobusPlusTaxonomies.init();	
	
});
