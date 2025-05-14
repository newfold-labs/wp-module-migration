import { GetPluginId } from './wp-module-support/pluginID.cy';
import { wpLogin } from './wp-module-support/utils.cy';

const COMMAND_TIMEOUT = 60000;
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
				cy.exec(
					`npx wp-env run cli wp option update _transient_nfd_site_capabilities '{"canMigrateSite": true}' --format=json`
				);
				cy.reload();
			} );

			it( 'Should redirect to correct Migration URL without errors', () => {
				const migrationDomain = `migrate.${ pluginId }.com`;

				// Visit the migration page
				cy.visit(
					'/wp-admin/index.php?page=nfd-onboarding#/sitegen/step/migration'
				);

				// Confirm the redirect worked and URL is correct
				cy.location( 'href', { timeout: COMMAND_TIMEOUT } ).should(
					( href ) => {
						const url = new URL( href );
						expect( url.hostname ).to.eq( migrationDomain );
						expect( url.searchParams.has( 'd_id' ) ).to.eq( true );
						expect( url.searchParams.has( 'locale' ) ).to.eq(
							true
						);
					}
				);

				// Check that the page did not show a 404 or major error
				cy.document()
					.its( 'contentType' )
					.should( 'include', 'text/html' );
				cy.get( 'body' ).should( 'not.contain', '404' );
				cy.get( 'body' ).should( 'not.contain', 'Error' );
			} );

			after( () => {
				// Cleanup options and transients
				cy.exec(
					`npx wp-env run cli wp option delete nfd_migrate_site`
				);
				cy.exec(
					`npx wp-env run cli wp option delete _transient_nfd_site_capabilities`
				);
			} );
		}
	);
}
