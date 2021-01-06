/**
 * WPGlobus Plus WPGlobEditor
 * Interface JS functions
 *
 * @since 1.1.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusDialogApp, WPGlobusPlusEditor*/

(function($) {
    "use strict";
	var api = {
		option : {
			mode: 'table'
		},
		order : {},
		filterClass: '',
		init : function(args) {
			api.option = $.extend(api.option, args);

			if ( typeof WPGlobusPlusEditor.mode !== 'undefined' && WPGlobusPlusEditor.mode == 'ueditor' ) {
				api.option.mode = WPGlobusPlusEditor.mode;
			}

			if ( api.option.mode == 'table' ) {
				api.filterClass = $('input[name="wpglobeditor-page-filter-class"]').val();
				this.attachListeners();
			} else {
				if ( 'undefined' !== typeof WPGlobusDialogApp ) {
					setTimeout( function() {
						$.each(WPGlobusPlusEditor.elements, function(i,el) {
							WPGlobusDialogApp.addElement(el);
						});
					}, 3000);
				}
			}
		},
		attachListeners : function() {
			
			/**
			 * Page filter.
			 * @since 1.1.29
			 */
			$(document).on('click', '.wpglobus-plus-wpglobeditor-filter', function(ev){
				var $this = $(this),
					filter = $this.data('filter');
					
				if ( '' == filter ) {
					return;
				}
				if ( $('.'+filter).hasClass('filtered') ) {
					$('.wpglobeditor-item').removeClass('hidden filtered');
				} else {
					$('.wpglobeditor-item').addClass('hidden');
					$('.'+filter).removeClass('hidden').addClass('filtered');
				}
			})
			
			$(document).on('click', '#wpglobus-plus-add-item', function(ev){
				var $item = $('#wpglobus-plus-skeleton tbody tr').clone();
				$item.removeClass('hidden');
				$('#wpglobus-plus-editor-items tbody .no-items').hide();
				$('#wpglobus-plus-editor-items tbody').append($item);
			});

			$(document).on('click', '.wpglobus-plus-action-ajaxify', function(ev){
				ev.preventDefault();
				var $t = $(this);
				if ( 'remove' == $t.data('action') ) {
					$t.parents('tr').fadeToggle('slow');
				}
				api.order = {};
				api.order['action'] = WPGlobusPlusEditor.module + '-' + $t.data('action');
				api.order['module'] = WPGlobusPlusEditor.module;
				api.order['page'] 	= $t.data('page');
				api.order['key'] 	= $t.data('key');
				api.ajax(api.order)
					.done(function (data) {
						//console.log(data);
					})
					.fail(function (error) {})
					.always(function (jqXHR, status){});
				});

			$(document).on('change', '.wpglobus-plus-ajaxify', function(ev){
				var $t = $(this),
					$p = $t.parents('tr');

				api.order = {};
				api.order['action'] 	= WPGlobusPlusEditor.module + '-' + $t.data('action');
				api.order['module'] 	= WPGlobusPlusEditor.module;
				api.order['page']   	= $p.find('input.page').val();
				api.order['element']   	= $p.find('input.element').val();
				api.order['key'] 		= $t.data('key');

				if ( '' == api.order['page'] || '' == api.order['element'] ) {
					return;
				}
				api.ajax(api.order)
				.done(function (data) {
					//console.log(data);
					var row 		= $t.parents('tr.wpglobeditor-item');
					var cl 			= api.filterClass.replace( '{{page}}', data.order.page.replace('.','-') );
					var filtered 	= row.hasClass('filtered') ? ' filtered' : '';
					if ( '' == filtered && $('.filtered').length > 0 ) {
						filtered = ' filtered';
					}
					row.removeClass().addClass('wpglobeditor-item ' + cl + filtered);
					row.find('.wpglobus-plus-wpglobeditor-filter').attr('data-filter', cl);
				})
				.fail(function (error) {})
				.always(function (jqXHR, status){});
			});
		},
		ajax : function(order, beforeSend) {
			return $.ajax({
				beforeSend:function(){
					if ( typeof beforeSend !== 'undefined' ) beforeSend();
			},type:'POST', url:ajaxurl, data:{action:WPGlobusPlusEditor.process_ajax, order:order}, dataType:'json'});
		}
	};

	WPGlobusPlusEditor = $.extend({}, WPGlobusPlusEditor, api);

	WPGlobusPlusEditor.init();

})(jQuery);
