#!/usr/bin/env sh
set -eu

CONFIG="Content/build/tailwind.config.js"
INPUT="Inc/Template/frontend/default/assets/css/tailwind-src.css"
OUTPUT="Inc/Template/frontend/default/assets/css/tailwind.css"

# npx: panel ayarı (NPX_BIN), PATH, veya yaygın konumlar. Linux hostingte web sunucusu PATH'inde npx olmayabilir.
if [ -n "${NPX_BIN:-}" ] && [ -x "${NPX_BIN}" ]; then
  NPX_CMD="${NPX_BIN}"
elif command -v npx >/dev/null 2>&1; then
  NPX_CMD="npx"
else
  NPX_CMD=""
  for candidate in /usr/bin/npx /usr/local/bin/npx /opt/node/bin/npx; do
    if [ -x "$candidate" ]; then
      NPX_CMD="$candidate"
      break
    fi
  done
  if [ -z "$NPX_CMD" ] && [ -n "${HOME:-}" ] && [ -d "$HOME/.nvm/versions/node" ]; then
    for npx_cand in "$HOME"/.nvm/versions/node/*/bin/npx; do
      if [ -x "$npx_cand" ]; then
        NPX_CMD="$npx_cand"
        break
      fi
    done
  fi
  if [ -z "$NPX_CMD" ]; then
    echo "npx not found. Set npx path in Admin > Settings > General (npx path), or build Tailwind on your PC and upload Inc/Template/frontend/default/assets/css/tailwind.css."
    exit 1
  fi
fi

"$NPX_CMD" tailwindcss --config "$CONFIG" --input "$INPUT" --output "$OUTPUT" --minify
