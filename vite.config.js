import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
export default defineConfig(async () => ({ // Make the defineConfig callback async
    plugins: [
        await import('laravel-vite-plugin').then((module) => // Dynamically import
            module.default({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.jsx',
                ],
                refresh: true,
            })
        ),
        // ... other plugins if you have them
        react(),
    ],
}));

// export default defineConfig({
//     plugins: [
//         laravel({
//             input: [
//                 'resources/js/app.jsx',
//                 'resources/css/app.css',
//             ],
//             refresh: true,
//         }),
//         react(),
//     ],
// });
