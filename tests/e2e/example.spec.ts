import {test, expect} from '@playwright/test';


test.describe('Printable forecast view', () => {
    test('CVVFCM page is printable', async ({page}) => {
        await page.goto('https://localhost/forecast/cvvfcm');
        await page.emulateMedia({media: 'print'});

        await expect(page).toHaveScreenshot(
            'forecast-print-cvvfcm.png',
            {
                mask: [
                    page.locator('.slot__weather'),
                    page.locator('.slot__temp'),
                    page.locator('.slot__wind'),
                ]
            },
        );
    });
});
