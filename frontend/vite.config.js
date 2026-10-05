import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: process.env.PATHYATRA_PHP_URL || 'http://localhost/Yatra',
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
