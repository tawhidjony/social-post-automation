#!/usr/bin/env sh
set -e

DOMAIN="${LOCAL_DOMAIN:-social-post-automation.test}"
DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_BIN="$(cd "${DIR}/../../bin" && pwd)"
CERT_FILE="${DIR}/${DOMAIN}.crt"
KEY_FILE="${DIR}/${DOMAIN}.key"

resolve_mkcert() {
    if [ -n "${MKCERT_BIN:-}" ] && [ -x "${MKCERT_BIN}" ]; then
        echo "${MKCERT_BIN}"
        return 0
    fi

    if command -v mkcert >/dev/null 2>&1; then
        command -v mkcert
        return 0
    fi

    if [ -x "${PROJECT_BIN}/mkcert" ]; then
        echo "${PROJECT_BIN}/mkcert"
        return 0
    fi

    return 1
}

generate_with_mkcert() {
    MKCERT="$(resolve_mkcert)" || return 1

    echo "Generating browser-trusted certificates with mkcert for ${DOMAIN}..."
    "${MKCERT}" -install
    "${MKCERT}" -cert-file "${CERT_FILE}" -key-file "${KEY_FILE}" "${DOMAIN}" "*.${DOMAIN}"
    echo "Generated trusted certificates:"
    echo "  ${CERT_FILE}"
    echo "  ${KEY_FILE}"
}

generate_with_openssl() {
    echo "mkcert not found. Generating self-signed certificate with OpenSSL..."
    echo "For a trusted padlock in your browser, run: make mkcert-install && make certs"
    echo ""

    openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
        -keyout "${KEY_FILE}" \
        -out "${CERT_FILE}" \
        -subj "/CN=${DOMAIN}/O=Local Development/C=US" \
        -addext "subjectAltName=DNS:${DOMAIN},DNS:*.${DOMAIN}"

    echo "Generated self-signed certificates:"
    echo "  ${CERT_FILE}"
    echo "  ${KEY_FILE}"
}

case "${CERT_METHOD:-auto}" in
    mkcert)
        generate_with_mkcert
        ;;
    openssl)
        generate_with_openssl
        ;;
    auto)
        if generate_with_mkcert; then
            :
        else
            generate_with_openssl
        fi
        ;;
    *)
        echo "Unknown CERT_METHOD: ${CERT_METHOD} (use auto, mkcert, or openssl)" >&2
        exit 1
        ;;
esac
