<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEstimated extends gEditorialModuleCore
{

	public $meta_key = '_ge_estimated';
	private $added   = FALSE;

	public static function module()
	{
		return array(
			'name'  => 'estimated',
			'title' => _x( 'Estimated', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'  => _x( 'Calculates an average required time to complete reading a post.', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
			'icon'  => 'clock',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'average',
					'type'        => 'number',
					'title'       => _x( 'Reading Time', 'Estimated Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Average words per minute', 'Estimated Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 250,
				),
				array(
					'field'       => 'prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Estimated Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'String before the estimated time on the content', 'Estimated Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Estimated read time:', 'Estimated Module: Setting Default', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'teaser',
					'type'        => 'select',
					'title'       => _x( 'Content Teaser', 'Estimated Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Calculate teaser text along with rest of the content', 'Estimated Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 'include',
					'values'      => array(
						'ignore'  => _x( 'Ignore', 'Estimated Module: Content Teaser Option', GEDITORIAL_TEXTDOMAIN ),
						'include' => _x( 'Include', 'Estimated Module: Content Teaser Option', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'insert_content',
				'insert_priority',
				array(
					'field'       => 'min_words',
					'type'        => 'number',
					'title'       => _x( 'Minimum Words', 'Estimated Module: Setting Title', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'And above this number of words will show the notice', 'Estimated Module: Setting Description', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 1000,
				),
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	protected function get_global_strings()
	{
		return array(
			'noops' => array(
				'word_count' => _nx_noop( '%s Word', '%s Words', 'Estimated Module: Noop', GEDITORIAL_TEXTDOMAIN ),
			),
		);
	}

	public function init()
	{
		do_action( 'geditorial_estimated_init', $this->module );
		$this->do_globals();

		if ( ! is_admin() ) {

			if ( 'none' != $this->get_setting( 'insert_content', 'none' ) )
				add_filter( 'the_content', array( $this, 'the_content' ),
					$this->get_setting( 'insert_priority', 22 ) );
			else
				add_action( 'gnetwork_themes_content_before', array( $this, 'content_before' ),
					$this->get_setting( 'insert_priority', 60 ) );

			$this->enqueue_styles();
		}

		// TODO: add shortcode
	}

	public function current_screen( $screen )
	{
		if ( in_array( $screen->post_type, $this->post_types() ) ) {

			if ( 'post' == $screen->base ) {

				add_action( 'save_post', array( $this, 'save_post_supported_cpt' ), 20, 3 );

			} else if ( 'edit' == $screen->base ) {

				add_action( 'geditorial_tweaks_column_row', array( $this, 'column_row_wordcount' ), -50 );
			}
		}
	}

	public function column_row_wordcount( $post )
	{
		if ( $wordcount = get_post_meta( $post->ID, $this->meta_key, TRUE ) ) {

			echo '<div class="-row estimated-wordcount">';

				echo '<span class="-icon" title="'
					.esc_attr_x( 'Estimated Time', 'Estimated Module: Row Icon Title', GEDITORIAL_TEXTDOMAIN )
					.'"><span class="dashicons dashicons-'.$this->module->icon.'"></span></span>';

					echo '<span class="-wordcount" title="'
						.esc_attr_x( 'Word Count', 'Estimated Module: Row Title', GEDITORIAL_TEXTDOMAIN ).'">'
						.sprintf( gEditorialHelper::noopedCount( $wordcount, $this->get_noop( 'word_count' ) ), number_format_i18n( $wordcount ) )
						.'</span>';

					echo ' <span class="-estimated-time">('
						.$this->get_time_estimated( $wordcount )
						.')</span>';

			echo '</div>';
		}
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		$this->get_post_wordcount( $post_ID, TRUE );

		return $post_ID;
	}

	public function content_before( $content, $posttypes = NULL )
	{
		if ( ! is_singular( $this->post_types() ) )
			return;

		if ( ! in_the_loop() || ! is_main_query() )
			return;

		$post = get_post();

		if ( ! $wordcount = get_post_meta( $post->ID, $this->meta_key, TRUE ) )
			$wordcount = $this->get_post_wordcount( $post->ID, TRUE );

		if ( $this->get_setting( 'min_words', 250 ) > $wordcount )
			return;

		$pref = $this->get_setting( 'prefix', _x( 'Estimated read time:', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ) );

		echo '<div class="geditorial-wrap estimated -before">';
			echo ( $pref ? $pref.' ' : '' ).$this->get_time_estimated( $wordcount, TRUE );
		echo '</div>';
	}

	public function the_content( $content )
	{
		if ( $this->added )
			return $content;

		if ( ! is_singular( $this->post_types() ) )
			return $content;

		if ( ! in_the_loop() || ! is_main_query() )
			return $content;

		$post = get_post();

		if ( ! $wordcount = get_post_meta( $post->ID, $this->meta_key, TRUE ) )
			$wordcount = $this->get_post_wordcount( $post->ID, TRUE );

		if ( $this->get_setting( 'min_words', 250 ) > $wordcount ) {
			$this->added = TRUE;
			return $content;
		}

		$place = $this->get_setting( 'insert_content', 'none' );
		$pref  = $this->get_setting( 'prefix', _x( 'Estimated read time:', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ) );
		$html  = '<div class="geditorial-wrap estimated -'.$place.'">'.( $pref ? $pref.' ' : '' ).$this->get_time_estimated( $wordcount, TRUE ).'</div>';

		$this->added = TRUE;

		if ( 'before' == $place )
			return $html.$content;

		return $content.$html;
	}

	// FIXME: use wp internal word count
	protected function get_post_wordcount( $post_id, $update = FALSE )
	{
		$content = get_post_field( 'post_content', $post_id, 'raw' );

		if ( 'ignore' == $this->get_setting( 'teaser', 'include' )
			|| strpos( $content, '<!--noteaser-->' ) ) {

				if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
					$content = explode( $matches[0], $content, 2 );
					$content = $content[1];
				}
		}

		$wordcount = self::wordCountUTF8( $content );

		if ( $update )
			update_post_meta( $post_id, $this->meta_key, $wordcount );

		return $wordcount;
	}

	protected function get_time_estimated( $wordcount = 0, $info = TRUE )
	{
		$avgtime = $this->get_setting( 'average', 250 );
		$minutes = floor( (int) $wordcount / (int) $avgtime );

		if ( $minutes < 1 )
			$estimated = _x( 'less than 1 minute', 'Estimated Module', GEDITORIAL_TEXTDOMAIN );
		else
			$estimated = sprintf( _nx( '%s minute', '%s minutes', $minutes, 'Estimated Module', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $minutes ) );

		if ( $info )
			return '<span title="'.esc_attr( sprintf( _x( 'If you try to read %s words per minute', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ), number_format_i18n( $avgtime ) ) ).'">'.$estimated.'</span>';

		return $estimated;
	}
}
