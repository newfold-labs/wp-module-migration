// support/platformHelpers.js
export const getPluginId = () => Cypress.env( 'pluginId' );
export const getAppId = () => Cypress.env( 'appId' );
export const isBluehost = () => Cypress.env( 'pluginId' ) === 'bluehost';
export const isHostgator = () => Cypress.env( 'pluginId' ) === 'hostgator';
