import { defineConfig, loadEnv, type ProxyOptions } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const apiTarget = env.VITE_API_PROXY_TARGET || 'https://cxfit.beforward.lk';
  const tenantHost = env.VITE_API_PROXY_HOST || new URL(apiTarget).host;

  const tenantProxy: ProxyOptions = {
    target: apiTarget,
    changeOrigin: true,
    cookieDomainRewrite: '',
    configure: (proxy) => {
      proxy.on('proxyReq', (request) => {
        request.setHeader('host', tenantHost);
      });
    },
  };
  const tenantRootProxy = {
    ...tenantProxy,
    rewrite: (path: string) => path.replace(/^\/__tenant-root/, '') || '/',
  };

  return {
    plugins: [react()],
    server: {
      proxy: {
        '/api': tenantProxy,
        '/login': tenantProxy,
        '/__tenant-root': tenantRootProxy,
      },
    },
  };
});