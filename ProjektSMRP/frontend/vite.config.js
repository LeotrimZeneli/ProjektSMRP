import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  base: './',
  publicDir: false,
  server: {
    proxy: {
      '/api/v1': {
        target: 'http://localhost/ProjektSMRP/backend',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'public/app-react',
    emptyOutDir: true,
  },
});
