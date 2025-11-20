<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

class DropdownUser extends DropdownPost
{
	public function __construct( $directed, $title )
	{
		parent::__construct( $directed, $title );

		add_action( 'pre_user_query', [ __CLASS__, 'massage_query' ], 9 );
		add_action( 'restrict_manage_users', [ $this, 'show_dropdown' ] );
	}

	public static function massage_query( $query )
	{
		if ( isset( $query->_o2o_capture ) )
			return;

		// Don't overwrite existing O2O query
		if ( isset( $query->query_vars['connected_type'] ) )
			return;

		O2O\Utils::append( $query->query_vars, self::get_qv() );
	}

	protected function render_dropdown()
	{
		$html = parent::render_dropdown();
		$html.= Core\HTML::tag( 'input', [
			'type'  => 'submit',
			'class' => 'button',
			'value' => _x( 'Filter', 'O2O', 'geditorial' ),
		] );

		return '<div style="float:right;margin-left:16px">'.$html.'</div>';
	}
}

