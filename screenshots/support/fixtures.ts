import { readFileSync } from 'node:fs';
import { copyFile, mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type XeroFixture = {
    organisationRoute: string;
    orderRoute: string;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-xero.php'), 'utf8');

/** Seed a completed Commerce order and a screenshot-only route through the real mapping template. */
export async function seedXeroFixture(context: ScreenshotSetupContext): Promise<XeroFixture> {
    await ensureXeroScreenshotModule(context.installDir);
    const output = await context.runCraftScript(seedScript, { label: 'seed-xero' });
    const fixture = JSON.parse(output.trim()) as XeroFixture;

    if (!fixture.organisationRoute || !fixture.orderRoute) {
        throw new Error(`Invalid Xero fixture payload: ${output}`);
    }

    return fixture;
}

export async function ensureXeroScreenshotModule(installDir: string): Promise<void> {
    const moduleDir = join(installDir, 'modules/xeroscreenshots');
    const controllerDir = join(moduleDir, 'controllers');
    await mkdir(controllerDir, { recursive: true });
    await copyFile(join(supportDir, 'module', 'Module.php'), join(moduleDir, 'Module.php'));
    await copyFile(join(supportDir, 'module', 'ScreenshotOrganisation.php'), join(moduleDir, 'ScreenshotOrganisation.php'));
    await copyFile(join(supportDir, 'module', 'controllers', 'OrganisationController.php'), join(controllerDir, 'OrganisationController.php'));

    const appPath = join(installDir, 'config/app.php');
    let contents = await readFile(appPath, 'utf8');

    if (contents.includes("'xeroScreenshots'")) {
        return;
    }

    contents = contents.replace(
        /return\s*\[\s*'id'\s*=>\s*App::env\('CRAFT_APP_ID'\)\s*\?:\s*'CraftCMS',\s*\];/s,
        `require_once dirname(__DIR__) . '/modules/xeroscreenshots/Module.php';
require_once dirname(__DIR__) . '/modules/xeroscreenshots/ScreenshotOrganisation.php';
require_once dirname(__DIR__) . '/modules/xeroscreenshots/controllers/OrganisationController.php';

return [
    'id' => App::env('CRAFT_APP_ID') ?: 'CraftCMS',
    'modules' => [
        'xeroScreenshots' => \\modules\\xeroscreenshots\\Module::class,
    ],
    'bootstrap' => ['xeroScreenshots'],
];`,
    );

    if (!contents.includes("'xeroScreenshots'")) {
        throw new Error('Failed to register the Xero screenshot module.');
    }

    await writeFile(appPath, contents);
}
