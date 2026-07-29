import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  root: '.',
  base: '/assets/',
  build: {
    outDir: 'public/assets',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, 'assets/app.js')
      },
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: ({name}) => {
          if (name && name.endsWith('.css')) return 'css/[name]';
          return 'assets/[name]';
        }
      }
    }
  }
});
