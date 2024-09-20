import { getAppId } from './pluginID.cy';

const appId = getAppId();
const customCommandTimeout = 30000;
const longWait = 120000;

export const wpLogin = () => {
	cy.login( Cypress.env( 'wpUsername' ), Cypress.env( 'wpPassword' ) );
};