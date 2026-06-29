import {test, expect, devices} from '@playwright/test';

test.use({...devices['Pixel 5']});

test.describe('Mobile spot forecast view', () => {
    test('CVVFCM page fits a phone viewport', async ({page}) => {
        await page.goto('https://localhost/forecast/cvvfcm');

        await page.waitForSelector('.slot__icon');
        await expect(page.locator('h1')).toBeVisible();

        // The whole page must fit the phone width — no horizontal scroll.
        const overflow = await page.evaluate(
            () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
        );
        expect(overflow).toBeLessThanOrEqual(1);

        // Hide the third-party consent widget and the dev toolbar so the snapshot is stable
        // across environments (the consent script may not load in CI).
        await page.addStyleTag({content: 'uzu-consent, .sf-toolbar { display: none !important; }'});

        await expect(page).toHaveScreenshot(
            'forecast-mobile-cvvfcm.png',
            {
                maxDiffPixelRatio: .01,
                // Mask the dynamic forecast values (same as the print test).
                mask: [
                    page.locator('.slot'),
                ],
            },
        );
    });
});
