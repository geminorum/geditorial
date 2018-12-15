<?php namespace geminorum\gEditorial\O2O;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class DropdownUser extends DropdownPost
{

	public function __construct( $directed, $title )
	{
		parent::__construct( $directed, $title );

		add_action( 'pre_user_query', [ __CLASS__, 'massage_query' ], 9 );

		add_action( 'restrict_manage_users', [ $this, 'show_dropdown' ] );
	}

	static function massage_query( $query )
	{
		if ( isset( $query->_o2o_capture ) )
			return;

		// Don't overwrite existing O2O query
		if ( isset( $query->query_vars['connected_type'] ) )
			return;

		Utils::append( $query->query_vars, self::get_qv() );
	}

	protected function render_dropdown()
	{
		return html( 'div', [
			'style' => 'float:right;margin-left:16px'
		],
			parent::render_dropdown(),
			html( 'input', [
				'type'  => 'submit',
				'class' => 'button',
				'value' => _x( 'Filter', 'O2O', GEDITORIAL_TEXTDOMAIN ),
			] )
		);
	}
}
