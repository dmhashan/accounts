import { defineConfig, loadEnv, type ProxyOptions } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import flowbiteReact from "flowbite-react/plugin/vite";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, new URL('.', import.meta.url).pathname, '');
  const apiTarget = env.VITE_API_PROXY_TARGET || 'https://cxfit.beforward.lk';
  const tenantHost = env.VITE_API_PROXY_HOST || new URL(apiTarget).host;

  const tenantProxy: ProxyOptions = {
    target: apiTarget,
    changeOrigin: true,
    cookieDomainRewrite: '',
    headers: { host: tenantHost },
  };
  const tenantRootProxy = {
    ...tenantProxy,
    rewrite: (path: string) => path.replace(/^\/__tenant-root/, '') || '/',
  };

  return {
    plugins: [react(), tailwindcss(), flowbiteReact()],
    server: {
      proxy: {
        '/api': tenantProxy,
        '/login': tenantProxy,
        '/__tenant-root': tenantRootProxy,
      },
    },
  };
});