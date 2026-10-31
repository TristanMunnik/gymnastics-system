import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const phpAppUrl = new URL(env.PHP_APP_URL || 'http://localhost/gymnastics/php/public');

  return {
    plugins: [react()],
    base: './',
    build: {
      outDir: '../php/public/app',
      emptyOutDir: true,
    },
    server: {
      // In development, forward /api.php to the PHP server so the browser sees one origin
      // (the session cookie works and no CORS setup is needed).
      proxy: {
        '/api.php': {
          target: phpAppUrl.origin,
          changeOrigin: true,
          rewrite: (path) => {
            const queryIndex = path.indexOf('?');
            const query = queryIndex === -1 ? '' : path.slice(queryIndex);
            return `${phpAppUrl.pathname.replace(/\/$/, '')}/api.php${query}`;
          },
        },
      },
    },
  };
});
