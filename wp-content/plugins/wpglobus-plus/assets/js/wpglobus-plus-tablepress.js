/**
 * WPGlobusPlus for TablePress
 * Interface JS functions
 *
 * @since 1.1.1
 *
 * @package WPGlobusPlus
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusCoreData*/
jQuery(document).ready(function($){
    "use strict";
	
	var api = {
		startX: 0,
		startY: 0,
		endX: 0,
		endY: 0,
		option: {
			tableLanguage: WPGlobusCoreData.language	
		},
		selector: [
			'<div class="wpglobus-selector">',
				'<span class="language">{{language}}</span>',
				'<div class="wpglobus-language-box">',
					'<ul>',
					'{{language_list}}',
					'</ul>',
				'</div>',
			'</div>'
		].join(''),
		init: function(args) {
			api.option = $.extend( api.option, args );
			
			if ( WPGlobusPlusTablePress.action == 'tablepress-all-tables' ) {
				api.setAllTables();
			} else {
				api.addSelector();
				api.setTable(true);
				api.addElements();
				api.addListeners();
			}
		},
		setAllTables: function() {
			var cols = [
				'.tablepress-all-tables .column-table_name a',
				'.tablepress-all-tables .column-table_description'
				];
			$(cols).each(function(indx,col){	
				$(col).each(function(i,e){
					var $t = $(this);
					$t.text( WPGlobusCore.TextFilter( $t.text(), WPGlobusCoreData.language ) );
				});
			});
		},	
		setMenuItems: function() {
			$('.wpglobus-selector .language').text( WPGlobusCoreData.en_language_name[api.option.tableLanguage] );
			$('.wpglobus-language-box ul').html( api.getMenuItems() );
			api.addMenuListeners();
		},	
		getMenuItems: function() {
			var list = '';
			$.each(WPGlobusCoreData.enabled_languages, function(i,l){
				if ( l != api.option.tableLanguage ) {
					list += '<li><a href="" onclick="return false;" class="item" data-language="'+l+'">'+WPGlobusCoreData.en_language_name[l]+'</a></li>';	
				}	
			});
			return list;	
		},	
		addSelector: function() {
			api.selector = api.selector.replace( '{{language}}', WPGlobusCoreData.en_language_name[WPGlobusCoreData.language] );
			api.selector = api.selector.replace( '{{language_list}}', api.getMenuItems );
			$('#tablepress_edit-table-data .handlediv').before(api.selector);
		},	
		setTable: function(setData) {
			$('#edit-form-body textarea').each( function(i,e){
				var $t = $(this),
					val;
				
				if ( setData ) {
					val = $t.val();
					$t.attr('data-wpglobus-source', val);
				} else {
					val = $t.data( 'wpglobus-source' );
				}
				$t.val( WPGlobusCore.TextFilter( val, api.option.tableLanguage, 'RETURN_EMPTY' ) );	
			});
		},	
		addTableListeners: function() {
			$('#edit-form-body textarea').each( function(i,e){
				var $t = $(this);
				$t.off('change');
				$t.on('change',function(event){
					var t = $(this);
					t.data( 'wpglobus-source', WPGlobusCore.getString( t.data('wpglobus-source'), t.val(), api.option.tableLanguage ) );
				});	
			});	
		},
		addAppendListeners: function() {
			var ids = [
				'#rows-append',
				'#columns-append',
				'#rows-duplicate',
				'#columns-duplicate',
				'#rows-insert',
				'#columns-insert'
			].join(',');
			$(ids).on('click', function(event){
				$('#edit-form-body textarea').each( function(i,e){
					var $t = $(this);
					if ( $t.data( 'wpglobus-source' ) == undefined ) {
						$t.attr('data-wpglobus-source', '');
					}	
				});
				api.addTableListeners();
			});
		},
		addListeners: function() {
			api.addTableListeners();
			$('.save-changes-button').on('mouseenter', function(event){
				$('#edit-form-body textarea').each( function(i,e){
					var t = $(this);
					if ( t.data('wpglobus-source') != 'undefined' ) {
						t.val( t.data('wpglobus-source') );
					}	
				});
				var b = $(this);
				api.startX = b.offset().left;
				api.startY = b.offset().top;
				api.endX   = api.startX + b.width();
				api.endY   = api.startY + b.height();
				
			}).on('mouseleave', function(event) {
				api.setTable(false);
			});	
			$(document).ajaxComplete(function(event, jqxhr, settings){
				if (settings.data.indexOf('action=tablepress_save_table&') >= 0) {
					$(document).one('mousemove', function(event){
						if ( event.pageX < api.startX-5 || event.pageX > api.endX + 20
							|| event.pageY < api.startY || event.pageY > api.endY + 5 ) {
							
							api.setTable(false);
						}
						
					});
				}
			});	
			$('.wpglobus-selector').on('mouseenter', function(event){
				$('.wpglobus-language-box').css({'display':'block'});
			}).on('mouseleave', function(event) {
				$('.wpglobus-language-box').css({'display':'none'});
			});
			api.addMenuListeners();
			api.addAppendListeners();
		},	
		addMenuListeners: function() {
			$('.wpglobus-selector .item').on('click', function(event){
				$('.wpglobus-language-box').css({'display':'none'});
				api.option.tableLanguage = $(this).data('language');
				api.setTable(false);
				api.setMenuItems();
			});
		},
		addElements: function() {
			WPGlobusDialogApp.addElement({
				id: 'table-name',
				dialogTitle: 'Edit Table name',
				sbTitle: 'Click for edit',
				style: 'width:95%;float:left;'
			});
			WPGlobusDialogApp.addElement({
				id: 'table-description',
				dialogTitle: 'Edit Table description',
				sbTitle: 'Click for edit'
			});				
		}
	};
	
	WPGlobusPlusTablePress = $.extend({}, WPGlobusPlusTablePress, api);
	
	WPGlobusPlusTablePress.init();	
});
