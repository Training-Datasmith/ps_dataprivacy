#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
IMAGE="php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143"

"${ROOT}/tests/bin/ensure-phpunit-phar.sh"

DOCKER=(docker)
if ! docker info >/dev/null 2>&1; then
  DOCKER=(sudo docker)
fi

"${DOCKER[@]}" run --rm \
  -v "${ROOT}:/workspace" \
  -v /tmp:/tmp \
  -w /workspace \
  "${IMAGE}" \
  php -v

"${DOCKER[@]}" run --rm \
  -v "${ROOT}:/workspace" \
  -v /tmp:/tmp \
  -w /workspace \
  "${IMAGE}" \
  php tests/run-suite-gate.php
