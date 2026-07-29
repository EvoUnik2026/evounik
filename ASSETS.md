Asset build and publish instructions

Quick start (host has Node/npm):

1. Install dependencies:

```bash
npm install
```

2. Build production assets (Vite):

```bash
npm run build
```

3. Publish assets and run Asset Mapper compile:

```bash
composer assets:build
```

Alternative: build inside a Node container (no Node on host):

```bash
docker run --rm -v "%CD%":/app -w /app node:18-alpine sh -lc "npm install && npm run build"
composer assets:build
```

Development watch mode (rebuild on change):

```bash
npm run dev
```

Notes:
- `npm run build` runs Vite to bundle/minify JS and CSS from `assets/` into `public/assets/`.
 - `composer assets:build` runs `npm run build` and then `php bin/console asset-map:compile` to let Symfony publish mapped assets.
- If you prefer Vite or a different toolchain, I can scaffold that instead.
