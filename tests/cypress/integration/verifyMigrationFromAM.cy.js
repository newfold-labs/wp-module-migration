import { getPluginId } from './support/platformHelpers';
import { wpLogin } from './support/commonHelpers';
import {
	setMigrationOptions,
	clearMigrationOptions,
	getMigrationRoute,
	assertMigrationRedirect,
} from './support/migrationHelpers';
import { deleteOption } from './support/serverHelpers';

const pluginId = getPluginId();

if ( pluginId === 'bluehost' || pluginId === 'hostgator' ) {
	describe(
		'Redirect to Onboarding Migration Flow from MFE entrypoint',
		{ testIsolation: true },
		() => {
			before( () => {
				wpLogin();
				setMigrationOptions();
				deleteOption( 'nfd_module_onboarding_status' );
				cy.reload();
			} );

			it( 'Redirects to correct migration URL without errors', () => {
				cy.visit( getMigrationRoute( pluginId ) );
				assertMigrationRedirect( pluginId );
			} );

			after( () => {
				clearMigrationOptions();
			} );
		}
	);
} else {
	describe(
		'Redirect to Onboarding Migration Flow from MFE entrypoint',
		{ testIsolation: true },
		() => {
			it( 'is skipped for unsupported plugins', () => {
				cy.log( `Skipping migration test for plugin: ${ pluginId }` );
			} );
		}
	);
}
