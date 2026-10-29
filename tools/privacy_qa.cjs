// Complete the public notice in disposable browser sessions before testing account forms.
async function acceptPrivacyPlaywright(page) {
    const checkbox = page.locator('input[name="privacy_read"]');
    if (!await checkbox.count()) return;
    await checkbox.check();
    await Promise.all([
        page.waitForNavigation({waitUntil: 'domcontentloaded'}),
        page.locator('.alpha-privacy-form button[type="submit"]').click(),
    ]);
}

async function acceptPrivacyPuppeteer(page) {
    const checkbox = await page.$('input[name="privacy_read"]');
    if (!checkbox) return;
    if (!await checkbox.evaluate(input => input.checked)) await checkbox.click();
    await Promise.all([
        page.waitForNavigation({waitUntil: 'domcontentloaded'}),
        page.click('.alpha-privacy-form button[type="submit"]'),
    ]);
}

module.exports = {acceptPrivacyPlaywright, acceptPrivacyPuppeteer};
