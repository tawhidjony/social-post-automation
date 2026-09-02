#!/usr/bin/env sh
set -e

BIN_DIR="$(cd "$(dirname "$0")/../bin" && pwd)"
MKCERT="${MKCERT_BIN:-$BIN_DIR/mkcert}"

ARCH="$(uname -m)"
OS="$(uname -s | tr '[:upper:]' '[:lower:]')"

case "${ARCH}" in
    x86_64|amd64) ARCH="amd64" ;;
    aarch64|arm64) ARCH="arm64" ;;
    *)
        echo "Unsupported architecture: ${ARCH}" >&2
        exit 1
        ;;
esac

URL="https://dl.filippo.io/mkcert/latest?for=${OS}/${ARCH}"

echo "Downloading mkcert for ${OS}/${ARCH}..."
mkdir -p "${BIN_DIR}"
curl -fsSL "${URL}" -o "${MKCERT}"
chmod +x "${MKCERT}"

echo "Installed mkcert at ${MKCERT}"
echo "Run: make certs"
