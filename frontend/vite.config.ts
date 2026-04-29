import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      '@components': path.resolve(__dirname, './src/components'),
      '@pages': path.resolve(__dirname, './src/pages'),
      '@services': path.resolve(__dirname, './src/services'),
      '@store': path.resolve(__dirname, './src/store'),
      '@hooks': path.resolve(__dirname, './src/hooks'),
      '@types': path.resolve(__dirname, './src/types'),
    },
  },
  server: {
    host: true,
    port: 5173,
    // Windows + Docker: inotify do bind mount quase não dispara; polling faz o HMR refletir alterações
    watch: {
      usePolling: process.env.CHOKIDAR_USEPOLLING === 'true' || process.env.WATCHPACK_POLLING === 'true',
      interval: 300,
    },
    proxy: {
      '/api': {
        target: 'http://nginx',
        changeOrigin: true,
      },
    },
  },
})
