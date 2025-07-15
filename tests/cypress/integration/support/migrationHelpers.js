import { setOption, updateTransient, deleteOption } from './serverHelpers';

export const setMigrationOptions = () => {
	updateTransient( '_transient_nfd_site_capabilities', {
		canMigrateSite: true,
		hasAISiteGen: true,
	} );
	setOption( 'nfd_migrate_site', 'true' );
};

export const clearMigrationOptions = () => {
	deleteOption( 'nfd_migrate_site' );
	deleteOption( '_transient_nfd_site_capabilities' );
	deleteOption( 'nfd_module_onboarding_status' );
};

export const getMigrationRoute = ( pluginId ) => {
	return pluginId === 'bluehost'
		? '/wp-admin/index.php?page=nfd-onboarding#/migration'
		: '/wp-admin/index.php?page=nfd-onboarding#/sitegen/step/migration';
};

export const assertMigrationRedirect = ( pluginId ) => {
	const domain = `migrate.${ pluginId }.com`;

	cy.location( 'href', { timeout: 60000 } ).should( ( href ) => {
		const url = new URL( href );
		expect( url.hostname ).to.eq( domain );
		expect( url.searchParams.has( 'g_id' ) ).to.eq( true );
		expect( url.searchParams.has( 'locale' ) ).to.eq( true );
	} );

	cy.document().its( 'contentType' ).should( 'include', 'text/html' );
	cy.get( 'body' ).should( 'not.contain', '404' );
	cy.get( 'body' ).should( 'not.contain', 'Error' );
};
