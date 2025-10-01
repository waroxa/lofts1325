<?php
/**
 * Disable SiteGround Security IP blocking to allow clearing of visitor data.
 */
add_action(
    'plugins_loaded',
    static function () {
        global $sg_security_loader;

        if ( empty( $sg_security_loader ) || empty( $sg_security_loader->block_service ) ) {
            return;
        }

        remove_action( 'init', array( $sg_security_loader->block_service, 'block_user_by_ip' ) );
    }
);
