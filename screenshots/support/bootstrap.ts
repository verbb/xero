import { registerPluginBootstrap } from '@verbb/craft-screenshots/api';

import { ensureXeroScreenshotModule } from './fixtures';

export default registerPluginBootstrap({
    id: 'commerce-xero',
    async setup(context) {
        await ensureXeroScreenshotModule(context.installDir);
    },
});
