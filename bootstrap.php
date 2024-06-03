<?php

use NewfoldLabs\WP\Module\Migration\Migration;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;
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

						if ( ! defined( 'NFD_MIGRATION_MODULE_VERSION' ) ) {
							define( 'NFD_MIGRATION_MODULE_VERSION', '1.0.2' );
						}
						$brand = $container->plugin()->id;
						if ( 'atomic' === getContext( 'platform' ) ) {
							$brand = 'bh-cloud';
						}
						defined( 'NFD_PROXY_ACCESS_WORKER' ) || define( 'NFD_PROXY_ACCESS_WORKER', 'https://hiive.cloud/workers/migration-token-proxy' );
						if( !defined(  'INSTAWP_API_KEY' ) ){
							$response = UtilityService::get_insta_api_key( $brand );
							define( 'INSTAWP_API_KEY', $response );
						}
						defined( 'INSTAWP_API_DOMAIN' ) || define( 'INSTAWP_API_DOMAIN', 'https://app.instawp.io' );
						defined( 'INSTAWP_MIGRATE_ENDPOINT' ) || define( 'INSTAWP_MIGRATE_ENDPOINT', 'migrate/' . $brand );

						new Migration( $container );
					},
					'isActive' => true,
					'isHidden' => true,
				)
			);
		}
	);
}
