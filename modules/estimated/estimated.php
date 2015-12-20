<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialEstimated extends gEditorialModuleCore
{

	public $meta_key = '_ge_estimated';
	private $added   = FALSE;

	public static function module()
	{
		return array(
			'name'     => 'estimated',
			'title'    => _x( 'Estimated', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
			'desc'     => _x( 'Calculates an average required time to complete reading a post.', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
			'dashicon' => 'clock',
		);
	}

	protected function get_global_settings()
	{
		return array(
			'_general' => array(
				array(
					'field'       => 'average',
					'type'        => 'number',
					'title'       => _x( 'Reading Time', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Average words per minute', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 250,
				),
				array(
					'field'       => 'prefix',
					'type'        => 'text',
					'title'       => _x( 'Content Prefix', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'String before the estimated time on the content', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
					'default'     => _x( 'Estimated read time:', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
				),
				array(
					'field'       => 'teaser',
					'type'        => 'select',
					'title'       => _x( 'Content Teaser', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
					'description' => _x( 'Calculate teaser text along with rest of the content', 'Estimated Module', GEDITORIAL_TEXTDOMAIN ),
					'default'     => 'include',
					'values'      => array(
						'ignore'  => _x( 'Ignore', 'Estimated Module: Content Teaser Option', GEDITORIAL_TEXTDOMAIN ),
						'include' => _x( 'Include', 'Estimated Module: Content Teaser Option', GEDITORIAL_TEXTDOMAIN ),
					),
				),
				'insert_content',
			),
			'posttypes_option' => 'posttypes_option',
		);
	}

	public function init()
	{
		do_action( 'geditorial_estimated_init', $this->module );
		$this->do_globals();

		if ( 'none' != $this->get_setting( 'insert_content', 'none' ) )
			add_filter( 'the_content', array( $this, 'the_content' ), 22 );

		// TODO: add shortcode
	}

	public function admin_init()
	{
		add_action( 'save_post', array( $this, 'save_post_supported_cpt' ), 20, 3 );
	}

	public function save_post_supported_cpt( $post_ID, $post, $update )
	{
		if ( ! $this->is_save_post( $post, $this->post_types() ) )
			return $post_ID;

		$this->get_post_wordcount( $post_ID, TRUE );

		return $post_ID;
	}

	public function the_content( $content )
	{
		if ( ! is_singular() )
			return $content;

		if ( $this->added )
			return $content;

		global $post;

		if ( ! in_array( $post->post_type, $this->post_types() ) )
			return $content;

		if ( ! $wordcount = get_post_meta( $post->ID, $this->meta_key, TRUE ) )
			$wordcount = $this->get_post_wordcount( $post->ID, TRUE );

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

		// $wordcount = self::wordCount( $content );
		// $wordcount = str_word_count( strip_tags( $content ) );
		// $wordcount = self::wordCountUTF8( strip_tags( $content ) );
		$wordcount = self::wordCountUTF8alt( strip_tags( $content ) );

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
