<?php

use NewfoldLabs\WP\Module\Migration\Migration;
use NewfoldLabs\WP\ModuleLoader\Container;
use function NewfoldLabs\WP\Context\getContext;

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
						$brand = $container->plugin()->id;
						if ( 'atomic' === getContext( 'platform' ) ) {
							$brand = 'bh-cloud';
						}
						define( 'NFD_MIGRATION_PLUGIN_URL', $container->plugin()->url );

						defined( 'NFD_PROXY_ACCESS_WORKER' ) || define( 'NFD_PROXY_ACCESS_WORKER', 'https://hiive.cloud/workers/migration-token-proxy' );
						defined( 'NFD_MIGRATION_PROXY_WORKER' ) || define( 'NFD_MIGRATION_PROXY_WORKER', 'https://migrate.bluehost.com' );

						defined( 'BRAND_PLUGIN' ) || define( 'BRAND_PLUGIN', $brand );

						defined( 'INSTAWP_MIGRATE_ENDPOINT' ) || define( 'INSTAWP_MIGRATE_ENDPOINT', 'migrate/' . $brand );

						if ( ! defined( 'NFD_MIGRATION_DIR' ) ) {
							define( 'NFD_MIGRATION_DIR', __DIR__ );
						}

						new Migration( $container );
					},
					'isActive' => true,
					'isHidden' => true,
				)
			);
		}
	);
}
