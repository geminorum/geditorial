<?php namespace geminorum\gEditorial\WordPress;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

use geminorum\gEditorial\Core;

class DataType extends Core\Base
{

	const BASE = 'geditorial';
	const TYPE = 'undefined';

	// Date Sub-Types: `datetime`/`date`/`time`
	// Vcard Sub-Types: `Vcard4`/`Vcard3`
	// Contact Sub-Types: `Email`/`URL`/`Phone`
	protected $__sub_type = NULL;

	protected $_value = '';
	protected $_name  = FALSE;
	protected $_field = [];
	protected $_args  = [];

    public function __construct( $value = NULL, $name = NULL, $field = NULL, $args = [] )
    {
		$this->_value = $value;
		$this->_name  = $name;
		$this->_field = $field;
		$this->_args  = $args;
	}

	public function __toString()
	{
		return $this->get( 'raw' );
	}

	private function filters( $filter, ...$args )
	{
		return apply_filters( sprintf( '%s_datatype_%s_%s', static::BASE, static::TYPE, $filter ?: 'default' ), ...$args );
	}

	public function set( $value, $context = '' ) { return $this->_value = $value; }
	public function get( $context = '' )
	{
		$value = $this->_value;

		if ( 'display' === $context )
			$value = $this->prep( $this->_value, $context );

		else if ( 'edit' === $context )
			$value = $this->sanitize( $this->_value, $context );

		return $this->filters( __FUNCTION__, $value, $this->_name, $context );
	}

	public function getName( $context = '' ) { return $this->_name; }
	public function getField( $context = '' ) { return $this->_field; }
	public function getArgs( $context = '' ) { return $this->_args; }
	public function getType( $context = '' ) { return static::TYPE; }
	public function getSubType( $context = '' ) { return $this->__sub_type ?? static::TYPE; }

	public function prep( $value, $context = '' ) { return trim( $value ); }
	public function isEmpty( $value ) { return parent::empty( $value ); }
	public function isValid( $value ) { return $this->validate( $value ); }
	public function validate( $value ) { return TRUE; }
	public function sanitize( $value ) { return TRUE; }

	// TODO: getICon() // define default with override from args
}
