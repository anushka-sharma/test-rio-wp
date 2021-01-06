<?php

if ( is_admin() ) {

	/**
	 *
	 */
	add_action( 'wpseo_tab_content', 'action__wpseo_tab_analysis');
	function action__wpseo_tab_analysis() {

		if ( ! isset( $_GET['post'] ) ) {
			return;
		}

		global	$WPGlobus_WPSEO_Metabox;

		$analysis = array();
		foreach ( WPGlobus::Config()->open_languages as $language ) {
			$WPGlobus_WPSEO_Metabox->language = $language;
			$post = get_post( $_GET['post'] );
			WPGlobus_Core::translate_wp_post( $post, $language, WPGlobus::RETURN_EMPTY );
			$analysis[$language] = $WPGlobus_WPSEO_Metabox->linkdex_output( $post );
		}

		?>
		<div id="wpglobus-wpseo-page-analysis-tabs" class="wpglobus-wpseo-tabs">
			<ul class="wpglobus-wpseo-tabs-list">    <?php
				$order = 0;
				foreach ( WPGlobus::Config()->open_languages as $language ) { ?>
					<li id="wpseo-analysis-link-tab-<?php echo $language; ?>"
						data-language="<?php echo $language; ?>"
						data-order="<?php echo $order; ?>"
						class="wpglobus-wpseo-analysis-tab"><a
							href="#wpseo-analysis-tab-<?php echo $language; ?>"><?php echo WPGlobus::Config()->en_language_name[ $language ]; ?></a>
					</li> <?php
					$order ++;
				} ?>
			</ul>	<?php
			foreach ( WPGlobus::Config()->open_languages as $language ) :
				#$url = WPGlobus_Utils::localize_url( $permalink['url'], $language ); 	?>
				<div id="wpseo-analysis-tab-<?php echo $language; ?>" class="wpglobus-wpseo-page-analysis"
					 data-language="<?php echo $language; ?>"
					 data-url-<?php #echo $language; ?>="<?php #echo $url; ?>"
					 data-permalink="<?php #echo $permalink['action']; ?>"
					 data-metadesc="<?php #echo esc_html( WPGlobus_Core::text_filter( $metadesc, $language, WPGlobus::RETURN_EMPTY ) ); ?>"
					 data-wpseotitle="<?php #echo esc_html( WPGlobus_Core::text_filter( $wpseotitle, $language, WPGlobus::RETURN_EMPTY ) ); ?>"
					 data-focuskw="<?php #echo WPGlobus_Core::text_filter( $focuskw, $language, WPGlobus::RETURN_EMPTY ); ?>">

					<?php
					if ( ! empty( $analysis[$language] ) ) {
						echo $analysis[$language];
					}
					$WPGlobus_WPSEO_Metabox->language = $language;
					$WPGlobus_WPSEO_Metabox->wpglobus_publish_box( $WPGlobus_WPSEO_Metabox->results[$language] ); 	?>

				</div> <?php
			endforeach;	?>
		</div>
		<?php
	}

	if ( class_exists('WPSEO_Metabox') ) {

		/**
		 * Class WPGlobus_WPSEO_Metabox
		 * @since 1.0.0
		 */

		class WPGlobus_WPSEO_Metabox extends WPSEO_Metabox {

			/**
			 * @val string
			 */
			public $language = '';

			/**
			 * @val boolean
			 */
			public $post_noindex = false;

			/**
			 * @var array Store results for language
			 */
			public $results = array();

			public function __construct() {

				add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts') );
				add_filter( 'wpseo_replacements', array( $this, 'wpseo_replacements' ) );
				add_action( 'post_submitbox_misc_actions', array( $this, 'wpglobus_score_publish_box' ) );

			}

			/**
			 * Print scripts
			 */
			public function admin_print_scripts() {

				wp_register_script(
					'wpglobus-plus-wpseo',
					WPGlobusPlus_Asset::url_js( 'wpglobus-plus-wpseo23' ),
					array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-tabs', 'wpglobus-admin' ),
					WPGLOBUS_PLUS_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-plus-wpseo' );

			}

			/**
			 * @see WPSEO_Replace_Vars->replace() in class-wpseo-replace-vars.php file
			 */
			public function wpseo_replacements( $replacements ) {

				foreach($replacements as $key=>$value) {
					if ( WPGlobus_Core::has_translations($value) ) {
						$replacements[$key] = WPGlobus_Core::text_filter( $value, $this->language, WPGlobus::RETURN_EMPTY );
					}
				}
				return $replacements;

			}

			/**
			 * Calculate the page analysis results for post.
			 * @see original calculate_results()
			 *
			 * @param  object $post Post to calculate the results for.
			 *
			 * @return  array|WP_Error
			 */
			public function calculate_results( $post ) {
				$options = WPSEO_Options::get_all();

				if ( ! class_exists( 'DOMDocument' ) ) {
					$result = new WP_Error( 'no-domdocument', sprintf( __( "Your hosting environment does not support PHP's %sDocument Object Model%s.", 'wordpress-seo' ), '<a href="http://php.net/manual/en/book.dom.php">', '</a>' ) . ' ' . __( "To enjoy all the benefits of the page analysis feature, you'll need to (get your host to) install it.", 'wordpress-seo' ) );

					$this->results[$this->language] = $result;
					return $result;
				}

				if ( ! is_array( $post ) && ! is_object( $post ) ) {
					$result = new WP_Error( 'no-post', __( 'No post content to analyse.', 'wordpress-seo' ) );

					$this->results[$this->language] = $result;
					return $result;
				}
				/* elseif ( self::get_value( 'focuskw', $post->ID ) === '' ) { */
				elseif ( WPGlobus_Core::text_filter( self::get_value( 'focuskw', $post->ID ), $this->language, WPGlobus::RETURN_EMPTY ) === '' ) {
					$result = new WP_Error( 'no-focuskw', sprintf( __( 'No focus keyword was set for this %s. If you do not set a focus keyword, no score can be calculated.', 'wordpress-seo' ), $post->post_type ) );

					self::set_value( 'linkdex', 0, $post->ID );

					$this->results[$this->language] = $result;
					return $result;
				}
				elseif ( apply_filters( 'wpseo_use_page_analysis', true ) !== true ) {
					$result = new WP_Error( 'page-analysis-disabled', sprintf( __( 'Page Analysis has been disabled.', 'wordpress-seo' ), $post->post_type ) );

					$this->results[$this->language] = $result;
					return $result;
				}

				$results = array();
				$job     = array();

				$sampleurl             = $this->get_sample_permalink( $post );
				if ( WPGlobus::Config()->default_language != $this->language ) {
					$sampleurl[0] = WPGlobus_Utils::localize_url( $sampleurl[0], $this->language );

					/**
					 * Filter to get meta_key.
					 *
					 * @see class-wpglobus-plus-slug.php
					 * @since 1.0.0
					 * @param string      $return    Sample permalink HTML markup.
					 */
					$key  = apply_filters( 'wpglobus_plus_slug_meta_key', '' );
					$slug = '';
					if ( ! empty( $key ) ) {
						$slug = get_post_meta( $post->ID, $key . $this->language, true );
					}
					if ( ! empty( $slug ) ) {
						$sampleurl[1] = $slug;
					}

				}

				$job['pageUrl']        = preg_replace( '`%(?:post|page)name%`', $sampleurl[1], $sampleurl[0] );
				/** @todo check for $job['pageSlug'] */
				$job['pageSlug']       = urldecode( $post->post_name );
				$job['keyword']        = self::get_value( 'focuskw', $post->ID );
				$job['keyword']        = WPGlobus_Core::text_filter( $job['keyword'], $this->language, WPGlobus::RETURN_EMPTY );
				$job['keyword_folded'] = $this->strip_separators_and_fold( $job['keyword'] );
				$job['post_id']        = $post->ID;
				$job['post_type']      = $post->post_type;

				$dom                      = new domDocument;
				$dom->strictErrorChecking = false;
				$dom->preserveWhiteSpace  = false;

				/**
				 * Filter: 'wpseo_pre_analysis_post_content' - Make the post content filterable before calculating the page analysis
				 *
				 * @api string $post_content The post content
				 *
				 * @param object $post The post.
				 */
				//$post_content = apply_filters( 'wpseo_pre_analysis_post_content', $post->post_content, $post );
				$post_content = $post->post_content;

				// Check if the post content is not empty.
				if ( ! empty( $post_content ) ) {
					@$dom->loadHTML( $post_content );
				}

				unset( $post_content );

				$xpath = new DOMXPath( $dom );

				// Check if this focus keyword has been used already.
				$this->check_double_focus_keyword( $job, $results );

				// Keyword.
				$this->score_keyword( $job['keyword'], $results );

				// Title.
				$title = self::get_value( 'title', $post->ID );
				$title   = WPGlobus_Core::text_filter( $title, $this->language, WPGlobus::RETURN_EMPTY );
				if ( $title !== '' ) {
					$job['title'] = $title;
				}
				else {
					if ( isset( $options[ 'title-' . $post->post_type ] ) && $options[ 'title-' . $post->post_type ] !== '' ) {
						$title_template = $options[ 'title-' . $post->post_type ];
					}
					else {
						$title_template = '%%title%% - %%sitename%%';
					}
					$job['title'] = wpseo_replace_vars( $title_template, $post );
				}
				unset( $title );
				$this->score_title( $job, $results );

				// Meta description.
				$description = '';
				$desc_meta   = self::get_value( 'metadesc', $post->ID );
				$desc_meta   = WPGlobus_Core::text_filter( $desc_meta, $this->language, WPGlobus::RETURN_EMPTY );
				if ( $desc_meta !== '' ) {
					$description = $desc_meta;
				}
				elseif ( isset( $options[ 'metadesc-' . $post->post_type ] ) && $options[ 'metadesc-' . $post->post_type ] !== '' ) {
					$description = wpseo_replace_vars( $options[ 'metadesc-' . $post->post_type ], $post );
				}
				unset( $desc_meta );

				self::$meta_length = apply_filters( 'wpseo_metadesc_length', self::$meta_length, $post );

				$this->score_description( $job, $results, $description, self::$meta_length );
				unset( $description );

				// Body.
				$body   = $this->get_body( $post );
				$firstp = $this->get_first_paragraph( $body );
				$this->score_body( $job, $results, $body, $firstp );
				unset( $firstp );

				// URL.
				$this->score_url( $job, $results );

				// Headings.
				$headings = $this->get_headings( $body );
				$this->score_headings( $job, $results, $headings );
				unset( $headings );

				// Images.
				$imgs          = array();
				$imgs['count'] = substr_count( $body, '<img' );
				$imgs          = $this->get_images_alt_text( $post->ID, $body, $imgs );

				// Check featured image.
				if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() ) {
					$imgs['count'] += 1;

					if ( empty( $imgs['alts'] ) ) {
						$imgs['alts'] = array();
					}

					$imgs['alts'][] = $this->strtolower_utf8( get_post_meta( get_post_thumbnail_id( $post->ID ), '_wp_attachment_image_alt', true ) );
				}

				$this->score_images_alt_text( $job, $results, $imgs );
				unset( $imgs );
				unset( $body );

				// Anchors.
				$anchors = $this->get_anchor_texts( $xpath );
				$count   = $this->get_anchor_count( $xpath );

				$this->score_anchor_texts( $job, $results, $anchors, $count );
				unset( $anchors, $count, $dom );

				//$results = apply_filters( 'wpseo_linkdex_results', $results, $job, $post );

				$this->aasort( $results, 'val' );

				$overall     = 0;
				$overall_max = 0;

				foreach ( $results as $result ) {
					$overall     += $result['val'];
					$overall_max += 9;
				}
				unset( $result );

				if ( $overall < 1 ) {
					$overall = 1;
				}
				$score = WPSEO_Utils::calc( WPSEO_Utils::calc( $overall, '/', $overall_max ), '*', 100, true );

				if ( ! is_wp_error( $score ) ) {
					self::set_value( 'linkdex', absint( $score ), $post->ID );

					$results['total'] = $score;
				}

				$this->results[$this->language] = $results;

				return $results;
			}

			/**
			* Output the page analysis results.
			* @see original linkdex_output()
			*
			* @param object $post Post to output the page analysis results for.
			*
			* @return string
			*/
			public function linkdex_output( $post ) {
				$results = $this->calculate_results( $post );

				if ( is_wp_error( $results ) ) {
					$error = $results->get_error_messages();

					return '<tr><td><div class="wpseo_msg"><p><strong>' . esc_html( $error[0] ) . '</strong></p></div></td></tr>';
				}
				$output = '';

				if ( is_array( $results ) && $results !== array() ) {

					$output     = '<table class="wpseoanalysis">';
					$perc_score = absint( $results['total'] );
					unset( $results['total'] ); // Unset to prevent echoing it.

					foreach ( $results as $result ) {
						if ( is_array( $result ) ) {
							$score = WPSEO_Utils::translate_score( $result['val'] );
							$output .= '<tr><td class="score"><div class="' . esc_attr( 'wpseo-score-icon ' . $score ) . '"></div></td><td>' . $result['msg'] . '</td></tr>';
						}
					}
					unset( $result, $score );
					$output .= '</table>';

					if ( WP_DEBUG === true || ( defined( 'WPSEO_DEBUG' ) && WPSEO_DEBUG === true ) ) {
						$output .= '<p><small>(' . $perc_score . '%)</small></p>';
					}
				}

				$output = '<div class="wpseo_msg hidden"><p>' . __( 'To update this page analysis, save as draft or update and check this tab again', 'wordpress-seo' ) . '.</p></div>' . $output;

				unset( $results );

				return $output;
			}

			/**
			 * Check whether the keyword is contained in the title.
			 *
			 * @param array $job     The job array holding both the keyword versions.
			 * @param array $results The results array.
			 */
			public function score_title( $job, &$results ) {
				$scoreTitleMinLength    = 40;
				$scoreTitleMaxLength    = 70;
				$scoreTitleKeywordLimit = 0;

				$scoreTitleMissing          = __( 'Please create a page title.', 'wordpress-seo' );
				$scoreTitleCorrectLength    = __( 'The page title is more than 40 characters and less than the recommended 70 character limit.', 'wordpress-seo' );
				$scoreTitleTooShort         = __( 'The page title contains %d characters, which is less than the recommended minimum of 40 characters. Use the space to add keyword variations or create compelling call-to-action copy.', 'wordpress-seo' );
				$scoreTitleTooLong          = __( 'The page title contains %d characters, which is more than the viewable limit of 70 characters; some words will not be visible to users in your listing.', 'wordpress-seo' );
				$scoreTitleKeywordMissing   = __( 'The keyword / phrase %s does not appear in the page title.', 'wordpress-seo' );
				$scoreTitleKeywordBeginning = __( 'The page title contains keyword / phrase, at the beginning which is considered to improve rankings.', 'wordpress-seo' );
				$scoreTitleKeywordEnd       = __( 'The page title contains keyword / phrase, but it does not appear at the beginning; try and move it to the beginning.', 'wordpress-seo' );

				if ( $job['title'] == '' ) {
					$this->save_score_result( $results, 1, $scoreTitleMissing, 'title' );
				}
				else {
					$job['title'] = wp_strip_all_tags( $job['title'] );

					$statistics = new Yoast_TextStatistics( get_bloginfo( 'charset' ) );
					$length = $statistics->text_length( $job['title'] );

					if ( $length < $scoreTitleMinLength ) {
						$this->save_score_result( $results, 6, sprintf( $scoreTitleTooShort, $length ), 'title_length' );
					}
					elseif ( $length > $scoreTitleMaxLength ) {
						$this->save_score_result( $results, 6, sprintf( $scoreTitleTooLong, $length ), 'title_length' );
					}
					else {
						$this->save_score_result( $results, 9, $scoreTitleCorrectLength, 'title_length' );
					}

					// @todo MA Keyword/Title matching is exact match with separators removed, but should extend to distributed match.
					if ( empty($job['keyword_folded']) ) {
						$needle_position = false;
					} else {
						$needle_position = mb_stripos( $job['title'], $job['keyword_folded'] );
					}

					if ( $needle_position === false ) {
						if ( empty($job['keyword']) ) {
							$needle_position = false;
						} else {
							$needle_position = mb_stripos( $job['title'], $job['keyword'] );
						}
					}

					if ( $needle_position === false ) {
						$this->save_score_result( $results, 2, sprintf( $scoreTitleKeywordMissing, '<span style="color:red;background:#ddd">'.$job['keyword_folded'].'</span>' ), 'title_keyword' );
					}
					elseif ( $needle_position <= $scoreTitleKeywordLimit ) {
						$this->save_score_result( $results, 9, $scoreTitleKeywordBeginning, 'title_keyword' );
					}
					else {
						$this->save_score_result( $results, 6, $scoreTitleKeywordEnd, 'title_keyword' );
					}
				}
			}

			/**
			 *
			 */
			public function wpglobus_score_publish_box($result) {
				echo '<div class="wpglobus-misc-score-box"></div>';
			}

			/**
			 * Outputs the page analysis score.
			 * @see original publish_box()
			 */
			public function wpglobus_publish_box($result) {
				if ( $this->is_metabox_hidden() === true ) {
					return;
				}

				$post = $this->wpglobus_get_metabox_post();

				if ( self::get_value( 'meta-robots-noindex', $post->ID ) === '1' ) {
					$score_label = 'noindex';
					$title       = __( 'Post is set to noindex.', 'wordpress-seo' );
					$score_title = $title;
					$this->post_noindex = true;
				}
				else {

					$score   = '';
					//$results = $this->calculate_results( $post );
					$results = $result;
					if ( ! is_wp_error( $results ) && isset( $results['total'] ) ) {
						$score = $results['total'];
						unset( $results );
					}

					if ( $score === '' ) {
						$score_label = 'na';
						$title       = __( 'No focus keyword set.', 'wordpress-seo' );
					}
					else {
						$score_label = WPSEO_Utils::translate_score( $score );
					}

					$score_title = WPSEO_Utils::translate_score( $score, false );
					if ( ! isset( $title ) ) {
						$title = $score_title;
					}
				}

				printf( '
				<div id="%1s" class="misc-pub-section wpglobus-misc-pub-section misc-yoast">
					<div title="%2$s" class="%3$s"></div>
					%4$s <span class="wpseo-score-title">%5$s</span>
					<a class="wpseo_tablink scroll" href="#wpseo_linkdex">%6$s</a>
				</div>',
					'wpglobus-wpseo-score-result-' . $this->language,
					esc_attr( $title ),
					esc_attr( 'wpseo-score-icon ' . $score_label ),
					WPGlobus::Config()->en_language_name[$this->language] . ' ' . __( 'SEO:', 'wordpress-seo' ),
					$score_title,
					__( 'Check', 'wordpress-seo' )
				);
			}

			/**
			 * Returns post in metabox context
			 * @see get_metabox_post() in class-metabox.php
			 *
			 * @returns WP_Post
			 */
			private function wpglobus_get_metabox_post() {
				if ( isset( $_GET['post'] ) ) {
					$post_id = (int) WPSEO_Utils::validate_int( $_GET['post'] );
					$post    = get_post( $post_id );
				}
				else {
					$post = $GLOBALS['post'];
				}

				return $post;
			}


		}

	}

}
