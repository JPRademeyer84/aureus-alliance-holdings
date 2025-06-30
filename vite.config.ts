import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";

// https://vitejs.dev/config/
export default defineConfig({
  server: {
    host: "localhost",
    open: true,
    strictPort: false,
    // Proxy disabled - all components use full API URLs
    // proxy: {
    //   '/api': {
    //     target: 'http://localhost:80/aureus-angel-alliance',
    //     changeOrigin: true,
    //     secure: false,
    //   }
    // }
  },
  plugins: [
    react(),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
      // EMERGENCY FIX: Redirect all lucide-react imports to safe stub
      "lucide-react": path.resolve(__dirname, "./src/components/LucideStub.tsx"),
    },
  },
});
