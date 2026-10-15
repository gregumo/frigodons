import {cloudflare} from '@cloudflare/vite-plugin'
import {reactRouter} from '@react-router/dev/vite'
import tailwindcss from '@tailwindcss/vite'
import {defineConfig} from 'vite'

export default defineConfig({
  server: {
    port: 5173,
  },
  resolve: {
    tsconfigPaths: true,
  },
  plugins: [cloudflare({viteEnvironment: {name: 'ssr'}}), tailwindcss(), reactRouter()],
})
