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
 * Delete a WordPress option
 * @param {string} key - Option name
 */
export async function deleteOption(key) {
  await wordpress.wpCli(`option delete ${key}`, { failOnNonZeroExit: false });
}

/**
 * Required capabilities for migration tests to function.
 * canMigrateSite is required for migration access; hasAISiteGen and canAccessAI
 * are required when running migration through the onboarding flow.
 */
export const MIGRATION_CAPABILITIES = {
  canAccessAI: true,
  canMigrateSite: true,
  hasAISiteGen: true,
};

/**
 * Set migration options required for the test.
 * Sets required site capabilities and nfd_migrate_site option.
 */
export async function setMigrationOptions() {
  await newfold.setCapability(MIGRATION_CAPABILITIES);
  await setOption('nfd_migrate_site', 'true');
}

/**
 * Clear all migration-related options
 */
export async function clearMigrationOptions() {
  await deleteOption('nfd_migrate_site');
  await newfold.clearCapabilities();
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
 * Detect migration redirect flow from the brand-proxy URL.
 * v3: InstaWP connect flow — g_id + locale (rebuilt by InstaMigrateService).
 * v4: InstaWP e2e-mig flow — token in ?t= (URL from InstaWP; we only swap host).
 *
 * @param {URL} url Redirect URL.
 * @returns {'v3'|'v4'|'unknown'}
 */
function getMigrationRedirectFlow(url) {
  if (url.searchParams.has('g_id') && url.searchParams.has('locale')) {
    return 'v3';
  }

  if (url.searchParams.has('t')) {
    return 'v4';
  }

  return 'unknown';
}

/**
 * Assert that page redirected to the migration service.
 * Accepts v3 (g_id + locale) or v4 (?t= token) brand-proxy URLs.
 *
 * @param {import('@playwright/test').Page} page
 */
export async function assertMigrationRedirect(page) {
  const domain = getMigrationDomain();

  // waitForURL defaults to "load"; external migration pages may never fire it in CI.
  await page.waitForURL(`**://${domain}/**`, {
    timeout: 60000,
    waitUntil: 'domcontentloaded',
  });

  const url = new URL(page.url());
  expect(url.hostname, 'Expected redirect to migration domain').toBe(domain);

  const flow = getMigrationRedirectFlow(url);
  expect(
    flow,
    `Expected v3 (g_id + locale) or v4 (?t=) migration URL, got: ${url.href}`,
  ).not.toBe('unknown');

  if (flow === 'v3') {
    expect(url.searchParams.get('g_id'), 'Expected g_id for v3 migration URL').toBeTruthy();
    expect(url.searchParams.get('locale'), 'Expected locale for v3 migration URL').toBeTruthy();
  } else {
    expect(url.searchParams.get('t'), 'Expected migration token (t) for v4 migration URL').toBeTruthy();
  }

  // Verify page loaded correctly (check for specific error patterns, not generic "error")
  const bodyText = await page.locator('body').textContent();
  expect(bodyText, 'Page should not show 404 error').not.toMatch(/404\s*(not found)?/i);
  expect(bodyText, 'Page should not show server error').not.toMatch(/500\s*(internal server error)?/i);
  expect(bodyText, 'Page should not show bad gateway error').not.toMatch(/502\s*(bad gateway)?/i);
  expect(bodyText, 'Page should not show service unavailable error').not.toMatch(/503\s*(service unavailable)?/i);
}
