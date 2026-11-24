import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');

// `cp` is the classic script loaded on Commerce order pages. `plugin-kit` is a module with the Plugin Kit
// components, loaded only on the organisations page.
const builds = {
    cp: {
        input: 'src/xero.js',
        clean: true,
        format: 'iife',
        target: 'es2015',
    },
    'plugin-kit': {
        input: 'src/plugin-kit.js',
        clean: false,
        format: 'es',
        target: 'es2022',
    },
};

export default defineConfig(({ mode }) => {
    const config = builds[mode];

    if (!config) {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    return {
        root,
        build: {
            outDir: resolve(root, 'dist'),
            emptyOutDir: config.clean,
            assetsDir: '',
            minify: 'oxc',
            sourcemap: true,
            target: config.target,
            rolldownOptions: {
                input: resolve(root, config.input),
                output: {
                    codeSplitting: false,
                    entryFileNames: '[name].js',
                    assetFileNames: '[name][extname]',
                    format: config.format,
                },
            },
        },
    };
});
