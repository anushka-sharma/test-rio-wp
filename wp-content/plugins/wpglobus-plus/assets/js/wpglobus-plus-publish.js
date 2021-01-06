/**
 * WPGlobus Plus Publish
 * Interface JS functions
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
/*jslint browser: true*/
/*global jQuery, console, WPGlobusCore, WPGlobusPlusPublish */

(function($) {
    "use strict";
	var api = {
		option : {
		},
		statusBoxDelta : 0,
		init : function(args) {
			api.option = $.extend(api.option, args);
			//this.attachListener();
			this.postStatus();
		},
		postStatus: function() {
			var status_classes = [],
				order = {};
			
			$.each( WPGlobusPlusPublish.data.statuses, function(i,s) {
				status_classes[i] = 'wpglobus-status-' + s;
			});
			status_classes = status_classes.join(' ');
		
			$('.wpglobus-pub-language').each(function(i,e){
				var l = $(this).data('language'),
					s = $(this).data('status');
				$('#wpglobus-pub-'+l+' .wpglobus-status-'+s).addClass('wpglobus-pub-current-status');	
			});
			
			$(document).on('mouseenter mouseleave', '.wpglobus-pub-language', function(event) {
				var $this = $(this),
					l = $this.data('language');
				if ( 'mouseenter' == event.type ) {
					$('.wpglobus-pub-status').addClass('hidden');
					$('#wpglobus-pub-'+l).removeClass('hidden');
					api.statusBoxDelta = event.screenY;
				} else if ( 'mouseleave' == event.type ) {	
					if ( api.statusBoxDelta != 0 && event.screenY - api.statusBoxDelta < 0) {
						$('.wpglobus-pub-status').addClass('hidden');
					}	
				}	
			});
			$(document).on('mouseleave', '.wpglobus-pub-status', function(event) {
				$('.wpglobus-pub-status').addClass('hidden');
			});
			$(document).on('click', '.wpglobus-pub-status li', function(event){
				var $t = $(this),
				    $s = $t.find('.wpglobus-spinner'),
					l = $t.data('language'),
					beforeSend = function(){
						$s.css('visibility','visible');	
					};
				if ( ! $t.hasClass('wpglobus-pub-current-status') ) {
					var order = {};
					order['action'] = 'set_status';
					order['post_id'] = WPGlobusPlusPublish.data.post_id;
					order['language'] = l;
					order['status'] = $t.data('status');
					api.ajax(order, beforeSend).done(function(result) {
						if ( 'ok' == result['result'] ) {
							$('#wpglobus-pub-'+l+' li').removeClass('wpglobus-pub-current-status');
							$t.addClass('wpglobus-pub-current-status');
							$('.wpglobus-pub-selector-'+l).removeClass(status_classes).addClass('wpglobus-status-'+$t.data('status'));
							$('.wpglobus-pub-selector-'+l).data('status',result['status']);
						}	
                    })
                    .fail(function(error){})
                    .always(function(jqXHR, status){$s.css('visibility','hidden');});
				}
			});
		},	
		attachListener : function() {},
		ajax : function(order, beforeSend) {
			return $.ajax({
				beforeSend:function(){
					if ( typeof beforeSend != 'undefined' ) beforeSend();
			},type:'POST', url:ajaxurl, data:{action:WPGlobusPlusPublish.data.process_ajax, order:order}, dataType:'json'});
		}	
	};
	
	WPGlobusPlusPublish = $.extend({}, WPGlobusPlusPublish, api);
	
	WPGlobusPlusPublish.init();
	
})(jQuery);