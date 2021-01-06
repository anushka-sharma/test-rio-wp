/**
 * WPGlobus Plus Publish Tab
 * Interface JS functions
 *
 * @since 1.3.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery,console,WPGlobusPlusPublishTab*/

(function($) {
    "use strict";
	if ( 'undefined' === typeof WPGlobusPlusPublishTab ) {
		return;
	}	
	var api = {
		selectSelector: '#wpglobus-plus-publish-languages',
		selectID: 'wpglobus-plus-publish-languages',
		bulkStartButtonSelector: '.wpglobus-plus-publish-bulk-start',
		languageIDs: [],
		selectedLanguageIDs: [],
		init: function() {
			if ( 'bulk-actions' == WPGlobusPlusPublishTab.data.section ) {
				api.bulkActions();
			}
		},
		bulkActions: function() {
			$(api.selectSelector).select2({
				placeholder: 'Select language(s)',
				width: '600px',
				maximumSelectionLength: -1,
				escapeMarkup: function (m) {
					return m;
				}
			});
			setTimeout(function(){
				var options = document.getElementById(api.selectID).options;
				api.languageIDs = $.map(options ,function(option) {
					return option.value;
				});
			}, 500);
			api.addListeners();			
		},
		getLanguageIDs: function() {
			return api.languageIDs;
		},
		getSelectedLanguageIDs: function() {
			return api.selectedLanguageIDs;
		},
		addListeners: function() {
			
			$(document).on('click', '.wpglobus-plus-publish-languages-add-all', function(evnt){
				$(api.selectSelector).val(api.languageIDs).trigger('select').trigger('change');
			});
			
			$(document).on('click', '.wpglobus-plus-publish-languages-delete-all', function(evnt){
				$(api.selectSelector).val([]).trigger('change');
			});
			
			$(api.selectSelector).on('change', function(evnt) {
				if ( 'undefined' === typeof evnt.val ) {
					/** It may be `select all` or `delete all` languages. */
					var options = $(api.selectSelector+' option:selected');
					api.selectedLanguageIDs = $.map(options, function(option) {
						return option.value;
					});
				} else {
					api.selectedLanguageIDs = evnt.val;
				}
				if ( api.selectedLanguageIDs.length == 0 ) {
					$(api.bulkStartButtonSelector).addClass('hidden');
				} else {
					$(api.bulkStartButtonSelector).removeClass('hidden');
				}
			});
			
			$('form[name="wpglobus-publish-form"]').submit( function(evnt){
				$(api.bulkStartButtonSelector).addClass('hidden');
				var postID = $(this).find('input[name="post-id"]').val();
				var ids = api.getSelectedLanguageIDs();
				var link = $('form[name="wpglobus-publish-form"] input[name="action-link"]').val();
				link = link.replace( '{{post_id}}', postID );
				link = link.replace( '{{language}}', ids );
				location = link;
				return false;
			});			
		}
	};
	
	WPGlobusPlusPublishTab = $.extend({}, WPGlobusPlusPublishTab, api);
	WPGlobusPlusPublishTab.init();
})(jQuery);		