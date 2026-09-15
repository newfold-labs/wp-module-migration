import { test, expect } from '@playwright/test';
import {
  auth,
  setMigrationOptions,
  clearMigrationOptions,
  deleteOption,
  navigateToMigrationPage,
  assertMigrationRedirect,
} from '../helpers/index.mjs';

test.describe('Redirect to Onboarding Migration Flow from MFE entrypoint', () => {
  test.beforeAll(async () => {
    // Set up migration options before tests
    await setMigrationOptions();
    await deleteOption('nfd_module_onboarding_status');
  });

  test.afterAll(async () => {
    // Clean up migration options after tests
    await clearMigrationOptions();
  });

  test('Loads the migration entrypoint in wp-admin', async ({ page }) => {
    await auth.loginToWordPress(page);

    const response = await navigateToMigrationPage(page);

    expect(response, 'Expected a response from the migration entrypoint').toBeTruthy();
    expect(
      response.status(),
      'Migration entrypoint should not return an error status',
    ).toBeLessThan(400);
  });

  // The v4 engine asks InstaWP to call the destination site to start the migration
  // agent. The wp-env site is not reachable from InstaWP, so connect fails with
  // "destination plugin not reachable or api_key invalid" and no migration URL is
  // issued. Re-enable once CI can expose the test site to InstaWP.
  test.skip('Redirects to correct migration URL without errors', async ({ page }) => {
    test.setTimeout(120000);

    await auth.loginToWordPress(page);

    // Navigate to migration page - this will trigger redirect to external migration service
    await navigateToMigrationPage(page);

    // Verify we were redirected to the migration service with correct parameters
    await assertMigrationRedirect(page);
  });
});
