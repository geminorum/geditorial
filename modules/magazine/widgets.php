<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gEditorialMagazineWidget_IssueCover extends gEditorialWidgetCore
{

	protected function setup()
	{
		return array(
			'module' => 'magazine',
			'name'   => 'magazine_issue_cover',
			'class'  => 'magazine-issue-cover',
			'title'  => __( 'Editorial Magazine: Issue Cover', GEDITORIAL_TEXTDOMAIN ),
			'desc'   => __( 'Displays selected issue cover', GEDITORIAL_TEXTDOMAIN ),
		);
	}

	public function widget_NEW( $args, $instance )
	{
		$context = empty( $instance['context'] ) ? '' : $instance['context'];

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			get_template_part( 'searchform', $context );
		$this->after_widget( $args, $instance );
	}

	public function widget( $args, $instance )
	{
		if ( ! $instance['latest_issue'] && empty( $instance['issue'] ) && ! is_singular() )
			return;

		if ( ! empty( $instance['latest_issue'] ) ) {
			$prefix = 'latest_issue';
		} else if ( ! empty ( $instance['issue_id'] ) ) {
			$prefix = 'issue_'.$instance['issue_id'];
		} else {
			$prefix = 'queried_'.get_queried_object_id();
		}

		$this->widget_cache( $args, $instance, '_'.$prefix );
	}


	public function widget_html( $args, $instance )
	{
		global $gEditorial;

		$issue_cpt = $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' );
		$func      = array( 'gEditorialMagazineTemplates', 'issue_cover' );
		$id        = get_queried_object_id();

		if ( ! empty( $instance['latest_issue'] ) ) {
			$id = gEditorialHelper::getLastPostOrder( $issue_cpt, '', 'ID', 'publish' );

		} else if ( ! empty ( $instance['issue_id'] ) ) {
			$id = $instance['issue_id'];

		} else {
			if ( $issue_cpt != get_post_type( $id ) )
				$func = array( 'gEditorialMagazineTemplates', 'the_issue_cover' );
		}

		if ( FALSE === $id || ! is_callable( $func ) )
			return FALSE;

		// FIXME: write better callback!!
		$func_args = array( '', '',
			( empty( $instance['image_size'] ) ? 'issue-thumbnail' : $instance['image_size'] ),
			( $instance['link_issue'] ? 'parent' : FALSE ), // WHATIF? : custom link?
			array(
				'id'    => $id,
				'echo'  => FALSE,
				'cb'    => apply_filters( 'geditorial_magazine_widget_issue_cover_cb',
					array( 'gEditorialMagazineTemplates', 'issue_cover_callback' ), $instance, $this->id_base ),
				'title' => ( $instance['number_line'] ? 'number' : FALSE ), // or 'title'
			),
		);

		$result = call_user_func_array( $func, $func_args );

		if ( ! $result )
			return FALSE;

		$this->before_widget( $args, $instance );
		$this->widget_title( $args, $instance );
			echo $result;
		$this->after_widget( $args, $instance );

		return TRUE;
	}

	public function form( $instance )
	{
		global $gEditorial;

		$issue_cpt = $gEditorial->get_module_constant( 'magazine', 'issue_cpt', 'issue' );

		echo '<div class="geditorial-admin-wrap-widgetform">';

		$this->form_title( $instance );
		$this->form_title_link( $instance );

		$this->form_post_id( $instance, '0', 'issue_id', 'posttype', $issue_cpt, _x( 'The Issue:', '[Magazine Module] Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_image_size( $instance, $issue_cpt.'-thumbnail', 'image_size', $issue_cpt );

		$this->form_checkbox( $instance, FALSE, 'latest_issue', _x( 'Always the latest issue', '[Magazine Module] Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'link_issue', _x( 'Link to the issue', '[Magazine Module] Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );
		$this->form_checkbox( $instance, FALSE, 'number_line', _x( 'Display the Number Meta', '[Magazine Module] Widget: Issue Cover', GEDITORIAL_TEXTDOMAIN ) );

		$this->form_context( $instance );
		$this->form_class( $instance );

		echo '</div>';
	}

	public function update( $new_instance, $old_instance )
	{
		$instance                 = $old_instance;
		$instance['title']        = strip_tags( $new_instance['title'] );
		$instance['title_link']   = strip_tags( $new_instance['title_link'] );
		$instance['issue_id']     = intval( $new_instance['issue_id'] );
		$instance['image_size']   = strip_tags( $new_instance['image_size'] );
		$instance['latest_issue'] = isset( $new_instance['latest_issue'] );
		$instance['link_issue']   = isset( $new_instance['link_issue'] );
		$instance['number_line']  = isset( $new_instance['number_line'] );
		$instance['context']      = strip_tags( $new_instance['context'] );
		$instance['class']        = strip_tags( $new_instance['class'] );

		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions[$this->alt_option_name] ) )
			delete_option( $this->alt_option_name );

		return $instance;
	}
}
