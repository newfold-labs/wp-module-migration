import { GetPluginId } from '../wp-module-support/pluginID.cy';
import { wpLogin } from '../wp-module-support/utils.cy';
const customCommandTimeout = 120000;
const pluginId = GetPluginId();
const helpCenter = JSON.stringify( {
	canAccessAI: true,
	canAccessHelpCenter: true,
} );
if ( pluginId == 'bluehost' ) {
	describe(
		'Verify Migration- emulating AM flow',
		{ testIsolation: true },
		() => {
			before( () => {
				wpLogin();

				cy.exec(
					`npx wp-env run cli wp option set nfd_migrate_site "true"`
				);

				cy.reload();
			} );

			it( 'Verify Migration page is loaded', () => {
				cy.intercept(
					'GET',
					'https://migrate.bluehost.com/api/v2/initial-data'
				).as( 'Migration-initialise' );
				cy.visit(
					'/wp-admin/?page=nfd-onboarding#/sitegen/step/migration'
				);
				cy.wait( '@Migration-initialise', {
					timeout: customCommandTimeout,
				} )
					.then( ( interception ) => {
						expect( interception.response.statusCode ).to.eq( 200 );
					} )
					.then( () => {
						cy.url().should( 'contain', 'migrate/bluehost?d_id=' );
					} );
			} );
		}
	);
}
