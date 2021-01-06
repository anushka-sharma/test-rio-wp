<?php
/**
 * @package WPGlobusPlus
 *
 * @since 1.1.29
 */

if ( ! class_exists( 'WPGlobusPlus_Sections' ) ) :

	/**
	 * Class WPGlobusPlus_Sections
	 */
	class WPGlobusPlus_Sections {

		protected $args;
		
		protected $html;
		
		protected $sections;
		
		protected $section;
		
		/** 
		 * Constructor.
		 */
		public function __construct($args) {
			
			$default = array(
				'tab' => '',
				'sections' => array(
					'general'	=> array(
						'caption' 	=> 'General',
						'link'		=> '#'
					)
				)
			);
			
			$this->args = array_merge($default, $args);
			
			$this->get_sections();
			
			add_action( 'admin_footer', array( $this, 'on__footer' ), 999 );
		}

		/**
		 * Footer action.
		 */
		public function on__footer() {	
		?>
<script type='text/javascript'>
/* <![CDATA[ */
var WPGlobusPlusSections = {};
WPGlobusPlusSections.html = '<?php echo $this->html; ?>';
WPGlobusPlusSections.sections = {};
<?php foreach( $this->sections as $k=>$section ) {	?>
	WPGlobusPlusSections.sections[<?php echo $k; ?>] = '<?php echo $section; ?>';
<?php } ?>
WPGlobusPlusSections.section  = '<?php echo $this->section; ?>';
WPGlobusPlusSections.tab = '<?php echo $this->args['tab']; ?>';
/* ]]> */		
</script><?php
		}
		
		/**
		 * Get sections html.
		 *
		 * @param array $sections
		 * @return array
		 */
		public function get_sections() {

			$size = count( $this->args['sections'] );
			
			$i = 0;
			$this->sections = array();
			$this->section  = '';
			
			$s = '<ul class="subsubsub">';
			foreach( $this->args['sections'] as $section_id=>$section ) :
				
				$class = "";
				
				if ( empty( $_GET['section'] ) ) {
					if ( $i == 0 ) {
						$class = 'current';
						$this->section = $section_id;
					}
				} else {
					if ( $section_id == $_GET['section'] ) {
						$class = 'current';
						$this->section = $section_id;
					}
				}
				
				$this->sections[] = $section_id;
				
				$s .= '<li>'; 
				$s .= '<a class="'.$class.'" href="'.$section['link'].'">'; 
				$s .= $section['caption'];
				$s .= '</a>'; 
				if ( $i < $size - 1 ) {
					$s .= '&nbsp;|&nbsp;'; 
				}
				$s .= '</li>'; 
				
				$i++;
				
			endforeach;

			$s .= '</ul><!-- .subsubsub -->';
			
			$this->html = $s;

		}

	} // WPGlobusPlus_Sections

endif;

# --- EOF
