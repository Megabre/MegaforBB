@echo off
setlocal

set CONFIG=Content/build/tailwind.config.js
set INPUT=Inc/Template/frontend/default/assets/css/tailwind-src.css
set OUTPUT=Inc/Template/frontend/default/assets/css/tailwind.css

if defined NPX_BIN (set "NPX_CMD=%NPX_BIN%") else (set "NPX_CMD=npx")
"%NPX_CMD%" tailwindcss --config "%CONFIG%" --input "%INPUT%" --output "%OUTPUT%" --minify

endlocal
