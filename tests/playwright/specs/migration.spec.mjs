import { test } from '@playwright/test';
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

  test('Redirects to correct migration URL without errors', async ({ page }) => {
    await auth.loginToWordPress(page);
    
    // Navigate to migration page - this will trigger redirect to external migration service
    await navigateToMigrationPage(page);
    
    // Verify we were redirected to the migration service with correct parameters
    await assertMigrationRedirect(page);
  });
});
