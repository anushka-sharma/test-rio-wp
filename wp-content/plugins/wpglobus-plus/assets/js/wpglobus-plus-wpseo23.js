(function($) {
	// tabs on
	$('#wpglobus-wpseo-page-analysis-tabs').tabs();
	
	var t = $('.wpseotab.linkdex .wpseoanalysis'),
		percentage = t.next('p'),
		$ft = $('.wpseotab.linkdex .form-table');

	if ( t.size() > 0 ) {
		$('#wpglobus-wpseo-page-analysis-tabs').insertBefore(t);
		t.addClass( 'hidden' );
		percentage.addClass( 'hidden' );
	} else if ( $ft.size() > 0 ) {
		$('#wpglobus-wpseo-page-analysis-tabs').insertAfter( $ft );
		$ft.addClass( 'hidden' );
	}
	
	$('.misc-pub-section.misc-yoast.misc-pub-section-last').addClass('hidden');
	
	$.each( WPGlobusCoreData.open_languages, function(i,l){
		var r = $('#wpglobus-wpseo-score-result-'+l);
		if ( r.size() > 0 ) {
			r.appendTo('.wpglobus-misc-score-box');
		}	
	});	
	
	// listen post body tabs & wpseo tabs
	$('body').on('click', '.wpglobus-post-body-tabs-list li, #wpglobus-wpseo-tabs li', function(event){
		var $t = $(this);
		if ( $t.hasClass('wpglobus-post-tab') || $t.hasClass('wpglobus-wpseo-tab') ) {
			$('#wpglobus-wpseo-page-analysis-tabs').tabs('option','active',$t.data('order'));
		}	
	});	
	
})(jQuery);	