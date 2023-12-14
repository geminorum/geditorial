<?php echo '</div>';

if ( ! empty( $foot_callback ) && is_callable( $foot_callback ) ) call_user_func( $foot_callback, $profile ?? FALSE );

echo '</body></html>';
