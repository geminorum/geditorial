<?php namespace geminorum\gEditorial\Services\O2O\Admin;

use geminorum\gEditorial\Core;
use geminorum\gEditorial\Services\O2O;

// An O2O admin meta-box is composed of several "fields"
interface Field {
	function get_title();
	function render( $o2o_id, $item );
}
