import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import path from 'node:path'

// https://vite.dev/config/
export default defineConfig({
  base: '/v2/',
  plugins: [react(), tailwindcss()],
  build: {
    outDir: '../v2',
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/api': {
        target: 'https://kevs.my.id',
        changeOrigin: true,
        secure: true,
      },
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
})
