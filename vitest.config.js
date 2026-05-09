import { defineConfig, mergeConfig } from 'vite';
import baseConfig from './vite.config.js';

export default mergeConfig(
    baseConfig,
    defineConfig({
        test: {
            environment: 'jsdom',
            globals: true,
            setupFiles: ['./resources/js/__tests__/setup.js'],
            include: ['./resources/js/__tests__/**/*.test.js'],
            css: true,
        },
        resolve: {
            alias: {
                '@': '/resources/js',
            },
        },
    }),
);