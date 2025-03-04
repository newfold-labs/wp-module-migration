import { GetPluginId } from '../wp-module-support/pluginID.cy';
import { wpLogin } from '../wp-module-support/utils.cy';

const COMMAND_TIMEOUT = 120000;
const pluginId = GetPluginId();

if ( pluginId === 'bluehost' ) {
	describe(
		'Migration Flow - Emulating AM Flow',
		{ testIsolation: true },
		() => {
			before( () => {
				wpLogin();
				cy.exec(
					`npx wp-env run cli wp option set nfd_migrate_site "true"`
				);
				cy.reload();
			} );

			it( 'Should load the Migration page successfully', () => {
				// Intercept API call to migration service
				cy.intercept(
					'GET',
					'https://migrate.bluehost.com/api/v2/initial-data'
				).as( 'migrationInit' );

				// Visit the migration page
				cy.visit(
					'/wp-admin/index.php?page=nfd-onboarding#/sitegen/step/migration'
				);

				// Wait for API request to be made
				cy.wait( '@migrationInit', { timeout: COMMAND_TIMEOUT } )
					.its( 'response.statusCode' )
					.should( 'eq', 200 );

				// Ensure the URL is correct
				cy.url().should( 'include', 'migrate/bluehost?d_id=' );
			} );
		}
	);
}
