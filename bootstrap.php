<?php

use NewfoldLabs\WP\Module\Migration\Migration;
use NewfoldLabs\WP\ModuleLoader\Container;

use function NewfoldLabs\WP\ModuleLoader\register;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}


if ( function_exists( 'add_action' ) ) {
	add_action(
		'plugins_loaded',
		function () {
			register(
				array(
					'name'     => 'migration',
					'label'    => __( 'Migration', 'wp-module-migration' ),
					'callback' => function ( Container $container ) {

						if ( ! defined( 'NFD_MIGRATION_MODULE_VERSION' ) ) {
							define( 'NFD_MIGRATION_MODULE_VERSION', '1.0.0' );
						}
						defined( 'INSTAWP_API_KEY' ) || define( 'INSTAWP_API_KEY', 'Tja4XscZD4l4zNwHD8y7j3kAffKs8edJatS0axvt' );
						defined( 'INSTAWP_API_DOMAIN' ) || define( 'INSTAWP_API_DOMAIN', 'https://app.instawp.io' );
						defined( 'INSTAWP_MIGRATE_ENDPOINT' ) || define( 'INSTAWP_MIGRATE_ENDPOINT', 'migrate/bluehost' );
						new Migration( $container );
					},
					'isActive' => true,
					'isHidden' => true,
				)
			);
		}
	);
}
