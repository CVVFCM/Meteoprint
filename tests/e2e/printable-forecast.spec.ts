import {test, expect} from '@playwright/test';


test.describe('Printable forecast view', () => {
    test('CVVFCM page is printable', async ({page}) => {
        await page.goto('https://localhost/forecast/cvvfcm');
        await page.emulateMedia({media: 'print'});

        await page.waitForSelector('.slot__icon');

        await expect(page).toHaveScreenshot(
            'forecast-print-cvvfcm.png',
            {
                maxDiffPixelRatio: .01,
                mask: [
                    page.locator('.slot'),
                ],
            },
        );
    });
});
