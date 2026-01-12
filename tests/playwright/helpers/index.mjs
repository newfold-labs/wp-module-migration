/**
 * Migration Module Test Helpers for Playwright
 * 
 * - Plugin Helpers (re-exported)
 * - WP-CLI / Option Helpers
 * - Navigation Helpers
 * - Assertion Helpers
 */
import { expect } from '@playwright/test';
import { join } from 'path';
import { pathToFileURL } from 'url';

// ============================================================================
// PLUGIN HELPERS (re-exported from plugin-level helpers)
// ============================================================================

const pluginDir = process.env.PLUGIN_DIR || process.cwd();
const finalHelpersPath = join(pluginDir, 'tests/playwright/helpers/index.mjs');
const helpersUrl = pathToFileURL(finalHelpersPath).href;
const pluginHelpers = await import(helpersUrl);

export const { auth, wordpress, newfold, a11y, utils } = pluginHelpers;

// ============================================================================
// INTERNAL CONSTANTS (not exported - use helper functions instead)
// ============================================================================

/** Plugin ID from environment */
const pluginId = process.env.PLUGIN_ID || 'bluehost';

/** Migration domains by plugin */
const MIGRATION_DOMAINS = {
  bluehost: 'migrate.bluehost.com',
  hostgator: 'migrate.hostgator.com',
};

/** Migration routes by plugin */
const MIGRATION_ROUTES = {
  bluehost: '/wp-admin/index.php?page=nfd-onboarding#/migration',
  hostgator: '/wp-admin/index.php?page=nfd-onboarding#/sitegen/step/migration',
};

// ============================================================================
// WP-CLI / OPTION HELPERS
// ============================================================================

/**
 * Set a WordPress option (internal helper)
 * @param {string} key - Option name
 * @param {string} value - Option value
 */
async function setOption(key, value) {
  await wordpress.wpCli(`option set ${key} '${value}'`);
}

/**
 * Update a WordPress transient/option with JSON value (internal helper)
 * @param {string} key - Option/transient name
 * @param {Object} jsonValue - Value to store as JSON
 */
async function updateTransient(key, jsonValue) {
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
function getMigrationRoute() {
  const route = MIGRATION_ROUTES[pluginId];
  if (!route) {
    console.warn(`Unknown pluginId "${pluginId}" for migration route, defaulting to bluehost`);
  }
  return route || MIGRATION_ROUTES.bluehost;
}

/**
 * Get expected migration domain for the current plugin
 * @returns {string} Migration domain hostname
 */
function getMigrationDomain() {
  const domain = MIGRATION_DOMAINS[pluginId];
  if (!domain) {
    console.warn(`Unknown pluginId "${pluginId}" for migration domain, defaulting to bluehost`);
  }
  return domain || MIGRATION_DOMAINS.bluehost;
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
 * - Page is valid HTML (not a server error page)
 * 
 * @param {import('@playwright/test').Page} page
 */
export async function assertMigrationRedirect(page) {
  const domain = getMigrationDomain();
  
  // Wait for external redirect with generous timeout
  await page.waitForURL(`**://${domain}/**`, { timeout: 60000 });
  
  // Wait for page to be ready after redirect
  await page.waitForLoadState('domcontentloaded');
  
  // Verify URL structure with soft assertions for better debugging
  const url = new URL(page.url());
  expect(url.hostname, 'Expected redirect to migration domain').toBe(domain);
  expect.soft(url.searchParams.has('g_id'), 'Expected g_id parameter in URL').toBe(true);
  expect.soft(url.searchParams.has('locale'), 'Expected locale parameter in URL').toBe(true);
  
  // Verify page loaded correctly (check for specific error patterns, not generic "error")
  const bodyText = await page.locator('body').textContent();
  expect(bodyText, 'Page should not show 404 error').not.toMatch(/404\s*(not found)?/i);
  expect(bodyText, 'Page should not show server error').not.toMatch(/500\s*(internal server error)?/i);
  expect(bodyText, 'Page should not show bad gateway error').not.toMatch(/502\s*(bad gateway)?/i);
  expect(bodyText, 'Page should not show service unavailable error').not.toMatch(/503\s*(service unavailable)?/i);
}
