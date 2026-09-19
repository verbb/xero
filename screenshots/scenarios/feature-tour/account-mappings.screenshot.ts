import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedXeroFixture } from '../../support/fixtures';

let organisationRoute = '/admin/screenshot-xero/organisation';

export default defineScreenshotScenario({
    id: 'xero-feature-tour-account-mappings',
    output: 'feature-tour/account-mappings.png',
    route: () => organisationRoute,
    viewport: { width: 1240, height: 900, deviceScaleFactor: 2 },
    async setup(context) {
        organisationRoute = (await seedXeroFixture(context)).organisationRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Verbb Studio Pty Ltd' },
        { type: 'text', text: 'Sales Revenue' },
        { type: 'text', text: 'Accounts Receivable' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `(() => {
                document.activeElement?.blur();
                window.scrollTo(0, 0);

                const mainContent = document.querySelector('#main-content');
                if (!(mainContent instanceof HTMLElement)) throw new Error('Unable to locate the Craft main content.');

                mainContent.style.transform = 'translateY(20px)';
                mainContent.style.position = 'relative';
                mainContent.style.zIndex = '1';
                mainContent.style.boxShadow = '0 -20px 0 var(--body-bg)';
            })()`,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 250 } },
    ],
    target: { type: 'anchoredClip', selector: '#main-container', x: 0, y: 0, width: 1010, height: 720 },
    caption: 'Revenue, receivables, freight, rounding, discounts and fees mapped to the right Xero accounts.',
    intent: 'Show the current Craft 5 organisation mapping workflow without exposing or recreating an OAuth connection screen.',
});
