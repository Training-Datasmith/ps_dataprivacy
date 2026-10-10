#!/usr/bin/env bash
set -euo pipefail

PHAR_URL="https://phar.phpunit.de/phpunit-4.8.36.phar"
ASC_URL="https://phar.phpunit.de/phpunit-4.8.36.phar.asc"
PHAR_PATH="/tmp/phpunit-4.8.36.phar"
ASC_PATH="/tmp/phpunit-4.8.36.phar.asc"
EXPECTED_SHA="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
EXPECTED_FINGERPRINT="D8406D0D82947747293778314AA394086372C20A"

curl -fsSL -o "${PHAR_PATH}" "${PHAR_URL}"
curl -fsSL -o "${ASC_PATH}" "${ASC_URL}"

ACTUAL_SHA="$(sha256sum "${PHAR_PATH}" | awk '{print $1}')"
if [ "${ACTUAL_SHA}" != "${EXPECTED_SHA}" ]; then
  echo "PHPUnit PHAR SHA-256 mismatch: expected ${EXPECTED_SHA}, got ${ACTUAL_SHA}" >&2
  exit 1
fi

GPG_HOME="$(mktemp -d)"
export GNUPGHOME="${GPG_HOME}"
trap 'rm -rf "${GPG_HOME}"' EXIT

curl -fsSL -o /tmp/sebastian-bergmann.asc https://sebastian-bergmann.de/gpg.asc
gpg --batch --import /tmp/sebastian-bergmann.asc >/dev/null 2>&1

FINGERPRINT="$(gpg --batch --with-colons --fingerprint 4AA394086372C20A 2>/dev/null | awk -F: '/^fpr:/ {print $10; exit}')"
if [ "${FINGERPRINT}" != "${EXPECTED_FINGERPRINT}" ]; then
  echo "GPG fingerprint mismatch: expected ${EXPECTED_FINGERPRINT}, got ${FINGERPRINT}" >&2
  exit 1
fi

STATUS_FILE="$(mktemp)"
if ! gpg --batch --status-file "${STATUS_FILE}" --verify "${ASC_PATH}" "${PHAR_PATH}" >/dev/null 2>&1; then
  cat "${STATUS_FILE}" >&2
  exit 1
fi

if ! grep -q "^\\[GNUPG:\\] VALIDSIG ${EXPECTED_FINGERPRINT} " "${STATUS_FILE}"; then
  echo "Missing VALIDSIG for expected fingerprint in GPG status output:" >&2
  cat "${STATUS_FILE}" >&2
  exit 1
fi

echo "PHPUnit PHAR verified at ${PHAR_PATH}"
