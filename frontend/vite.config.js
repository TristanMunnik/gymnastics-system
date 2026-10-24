import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const phpAppUrl = new URL(env.PHP_APP_URL || 'http://localhost/gymnastics/php');

  return {
    plugins: [react()],
    base: './',
    build: {
      outDir: '../php/dashboard',
      emptyOutDir: true,
    },
    server: {
      proxy: {
        '/api.php': {
          target: phpAppUrl.origin,
          changeOrigin: true,
          rewrite: () => `${phpAppUrl.pathname.replace(/\/$/, '')}/api.php`,
        },
      },
    },
  };
});
