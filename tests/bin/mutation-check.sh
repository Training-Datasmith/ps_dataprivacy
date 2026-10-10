#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TARGET="${ROOT}/ps_dataprivacy.php"
UPGRADE="${ROOT}/upgrade/upgrade-2.1.0.php"
RUNNER="${ROOT}/tests/bin/run-suite-in-container.sh"
BACKUP="$(mktemp)"
UPGRADE_BACKUP="$(mktemp)"

cp "${TARGET}" "${BACKUP}"
cp "${UPGRADE}" "${UPGRADE_BACKUP}"
trap 'cp "${BACKUP}" "${TARGET}"; cp "${UPGRADE_BACKUP}" "${UPGRADE}"; rm -f "${BACKUP}" "${UPGRADE_BACKUP}"' EXIT

run_gate_expect_fail() {
  local label="$1"
  if "${RUNNER}" >/dev/null 2>&1; then
    echo "Mutation check failed: ${label} did not break the suite" >&2
    exit 1
  fi
  echo "Mutation detected by suite: ${label}"
}

# 1. Wrong hook registration
sed -i "s/registerHook('additionalCustomerFormFields')/registerHook('wrongHookName')/" "${TARGET}"
run_gate_expect_fail "wrong hook name"
cp "${BACKUP}" "${TARGET}"

# 2. Privacy checkbox field renamed
sed -i "s/setName('customer_privacy')/setName('customer_privacy_broken')/" "${TARGET}"
run_gate_expect_fail "checkbox name"
cp "${BACKUP}" "${TARGET}"

# 3. Stop persisting HTML-allowed saves
sed -i "s/Configuration::updateValue('CUSTPRIV_MSG_AUTH', \$message_trads\['auth'\], true)/Configuration::updateValue('CUSTPRIV_MSG_AUTH', \$message_trads['auth'], false)/" "${TARGET}"
run_gate_expect_fail "html save flag"
cp "${BACKUP}" "${TARGET}"

# 4. Upgrade removes wrong hook
sed -i "s/unregisterHook('actionSubmitAccountBefore')/unregisterHook('additionalCustomerFormFields')/" "${UPGRADE}"
run_gate_expect_fail "upgrade hook target"
cp "${UPGRADE_BACKUP}" "${UPGRADE}"

# 5. Module guard becomes return (sentinel should emit)
sed -i 's/^    exit;$/    return;/' "${TARGET}"
run_gate_expect_fail "version guard return"
cp "${BACKUP}" "${TARGET}"

echo "All mutation checks passed."
