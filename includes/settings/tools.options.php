<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

gEditorialWPUser::superAdminOnly();

gEditorialHTML::tableSide( get_option( 'geditorial_options' ) );
