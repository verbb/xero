import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedXeroFixture } from '../../support/fixtures';

let orderRoute = '/admin/commerce/orders';

export default defineScreenshotScenario({
    id: 'xero-feature-tour-send-order',
    output: 'feature-tour/send-order.png',
    route: () => orderRoute,
    viewport: { width: 1240, height: 760, deviceScaleFactor: 2 },
    async setup(context) {
        orderRoute = (await seedXeroFixture(context)).orderRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.menubtn[data-icon="settings"]', state: 'visible', timeout: 30000 },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                new Promise((resolve, reject) => {
                    document.activeElement?.blur();
                    window.scrollTo(0, 0);
                    const timeout = window.setTimeout(() => reject(new Error('Craft order actions menu did not initialise.')), 10000);
                    const showAction = () => {
                        const menuButton = window.jQuery('.menubtn[data-icon="settings"]').data('menubtn');

                        if (!menuButton) {
                            window.setTimeout(showAction, 100);
                            return;
                        }

                        if (!document.querySelector('[data-action="send-to-xero"]')) {
                            new Craft.Xero.CpSendOrderToXero('1');
                        }

                        menuButton.showMenu();
                        window.clearTimeout(timeout);
                        resolve(true);
                    };

                    showAction();
                });
            `,
        },
        { type: 'wait', waitFor: { type: 'selector', selector: '[data-action="send-to-xero"]', state: 'visible' } },
    ],
    target: { type: 'anchoredClip', selector: '#main-container', x: 0, y: 0, width: 1010, height: 560 },
    caption: 'A completed Commerce order ready to be sent to Xero from the order actions menu.',
    intent: 'Show the current Xero action inside the genuine Craft Commerce order screen.',
});
