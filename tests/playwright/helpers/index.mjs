/**
 * Migration Module Test Helpers for Playwright
 * 
 * - Plugin Helpers (re-exported)
 * - Constants
 * - Navigation Helpers
 * - WP-CLI / Option Helpers
 * - Assertion Helpers
 */
import { expect } from '@playwright/test';
import { join, dirname } from 'path';
import { fileURLToPath, pathToFileURL } from 'url';

// ES module equivalent of __dirname
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// ============================================================================
// PLUGIN HELPERS (re-exported from plugin-level helpers)
// ============================================================================

const pluginDir = process.env.PLUGIN_DIR || process.cwd();
const finalHelpersPath = join(pluginDir, 'tests/playwright/helpers/index.mjs');
const helpersUrl = pathToFileURL(finalHelpersPath).href;
const pluginHelpers = await import(helpersUrl);

export const { auth, wordpress, newfold, a11y, utils } = pluginHelpers;

// ============================================================================
// CONSTANTS
// ============================================================================

/** Plugin ID from environment */
export const pluginId = process.env.PLUGIN_ID || 'bluehost';

/** Migration domains by plugin */
export const MIGRATION_DOMAINS = {
  bluehost: 'migrate.bluehost.com',
  hostgator: 'migrate.hostgator.com',
};

/** Migration routes by plugin */
export const MIGRATION_ROUTES = {
  bluehost: '/wp-admin/index.php?page=nfd-onboarding#/migration',
  hostgator: '/wp-admin/index.php?page=nfd-onboarding#/sitegen/step/migration',
};

// ============================================================================
// WP-CLI / OPTION HELPERS
// ============================================================================

/**
 * Set a WordPress option
 * @param {string} key - Option name
 * @param {string} value - Option value
 */
export async function setOption(key, value) {
  await wordpress.wpCli(`option set ${key} '${value}'`);
}

/**
 * Update a WordPress transient/option with JSON value
 * @param {string} key - Option/transient name
 * @param {Object} jsonValue - Value to store as JSON
 */
export async function updateTransient(key, jsonValue) {
  const stringified = JSON.stringify(jsonValue).replace(/"/g, '\\"');
  await wordpress.wpCli(`option update ${key} "${stringified}" --format=json`);
}

/**
 * Delete a WordPress option
 * @param {string} key - Option name
 */
export async function deleteOption(key) {
  await wordpress.wpCli(`option delete ${key}`, { failOnNonZeroExit: false });
}

/**
 * Set migration options required for the test
 * Sets canMigrateSite capability and nfd_migrate_site option
 */
export async function setMigrationOptions() {
  await updateTransient('_transient_nfd_site_capabilities', {
    canMigrateSite: true,
    hasAISiteGen: true,
  });
  await setOption('nfd_migrate_site', 'true');
}

/**
 * Clear all migration-related options
 */
export async function clearMigrationOptions() {
  await deleteOption('nfd_migrate_site');
  await deleteOption('_transient_nfd_site_capabilities');
  await deleteOption('nfd_module_onboarding_status');
}

// ============================================================================
// NAVIGATION HELPERS
// ============================================================================

/**
 * Get migration route for the current plugin
 * @returns {string} Migration route URL path
 */
export function getMigrationRoute() {
  return MIGRATION_ROUTES[pluginId] || MIGRATION_ROUTES.bluehost;
}

/**
 * Get expected migration domain for the current plugin
 * @returns {string} Migration domain hostname
 */
export function getMigrationDomain() {
  return MIGRATION_DOMAINS[pluginId] || MIGRATION_DOMAINS.bluehost;
}

/**
 * Navigate to migration page
 * @param {import('@playwright/test').Page} page
 */
export async function navigateToMigrationPage(page) {
  await page.goto(getMigrationRoute());
}

// ============================================================================
// ASSERTION HELPERS
// ============================================================================

/**
 * Assert that page redirected to the migration service
 * Verifies:
 * - Hostname matches expected migration domain
 * - URL has required g_id parameter
 * - URL has required locale parameter
 * - Page is valid HTML (not 404 or error)
 * 
 * @param {import('@playwright/test').Page} page
 */
export async function assertMigrationRedirect(page) {
  const domain = getMigrationDomain();
  
  // Wait for external redirect with generous timeout
  await page.waitForURL(`**://${domain}/**`, { timeout: 60000 });
  
  // Verify URL structure
  const url = new URL(page.url());
  expect(url.hostname).toBe(domain);
  expect(url.searchParams.has('g_id')).toBe(true);
  expect(url.searchParams.has('locale')).toBe(true);
  
  // Verify page loaded correctly (not 404 or error)
  const bodyText = await page.locator('body').textContent();
  expect(bodyText).not.toContain('404');
  expect(bodyText.toLowerCase()).not.toContain('error');
}
