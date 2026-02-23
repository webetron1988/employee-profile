import { resolve } from 'path';
import { defineConfig } from 'vite';
import handlebars from 'vite-plugin-handlebars';

export default defineConfig({
  root: 'src',
  // Serve the original assets/ directory as static files during development.
  // Files in publicDir are served at / and copied to dist/ on build.
  publicDir: '../public',

  // Treat references to assets/* as external during build (they are legacy
  // static files that will be deployed alongside the built output).
  // Full asset migration into the Vite pipeline happens in later phases.

  plugins: [
    handlebars({
      partialDirectory: resolve(__dirname, 'src/partials'),
      context: {
        title: 'Workforce Profile',
        employee: {
          fullName: 'John David Mitchell',
          jobTitle: 'Senior Data Scientist',
          department: 'Data Science & AI Division',
          employeeId: 'EMP-2022-0847',
          avatar: 'assets/media/users/300_21.jpg',
          status: 'Active',
        },
      },
    }),
  ],

  resolve: {
    alias: {
      '@assets': resolve(__dirname, 'src/assets'),
    },
  },

  server: {
    port: 3000,
    open: false,
  },

  build: {
    outDir: '../dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'src/index.html'),
      },
    },
    // Use esbuild for minification (built-in, no extra dependency)
    minify: 'esbuild',
    cssMinify: true,
  },
});
