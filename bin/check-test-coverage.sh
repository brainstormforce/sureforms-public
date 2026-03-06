#!/usr/bin/env bash

# Exit if any command fails.
set -e

BASE_SHA="${1:?Usage: check-test-coverage.sh <base-sha>}"

# Step 1 — Extract diff for PHP files in source directories (skip third-party).
DIFF=$(git diff "$BASE_SHA"...HEAD --unified=0 --diff-filter=AM -- 'inc/*.php' 'admin/*.php' 'modules/*.php' ':!inc/lib/')

if [ -z "$DIFF" ]; then
	echo "No PHP source changes detected. Skipping."
	exit 0
fi

MISSING=()
TOTAL=0

# Step 2 — Parse each changed file and its added lines.
CURRENT_FILE=""

while IFS= read -r line; do
	# Track current file from diff headers.
	if [[ "$line" =~ ^diff\ --git\ a/(.+)\ b/ ]]; then
		CURRENT_FILE="${BASH_REMATCH[1]}"
		continue
	fi

	# Only look at added lines.
	[[ "$line" =~ ^\+ ]] || continue

	# Step 2a — Match public/protected function declarations.
	# Skip: private, abstract, magic methods (__*), get_instance().
	if [[ "$line" =~ ^\+[[:space:]]*(public|protected)[[:space:]]+(static[[:space:]]+)?function[[:space:]]+([a-zA-Z_][a-zA-Z0-9_]*)[[:space:]]*\( ]]; then
		FUNC_NAME="${BASH_REMATCH[3]}"

		# Skip magic methods.
		[[ "$FUNC_NAME" =~ ^__ ]] && continue
		# Skip singleton boilerplate.
		[[ "$FUNC_NAME" == "get_instance" ]] && continue

		TOTAL=$((TOTAL + 1))

		# Step 3 — Map source file to test file.
		# inc/form-submit.php -> tests/unit/inc/test-form-submit.php
		DIR=$(dirname "$CURRENT_FILE")
		BASENAME=$(basename "$CURRENT_FILE")
		TEST_FILE="tests/unit/${DIR}/test-${BASENAME}"

		# Step 4 — Check if a matching test method exists.
		if [ -f "$TEST_FILE" ] && grep -qE "function test_${FUNC_NAME}(_|\b)" "$TEST_FILE"; then
			continue
		fi

		MISSING+=("  ${CURRENT_FILE}:${FUNC_NAME}() → expected: ${TEST_FILE} → function test_${FUNC_NAME}()")
	fi
done <<< "$DIFF"

# Step 5 — Report results.
if [ ${#MISSING[@]} -eq 0 ]; then
	echo "✓ All ${TOTAL} new/modified public/protected functions have corresponding tests."
	exit 0
fi

echo "✗ ${#MISSING[@]} of ${TOTAL} new/modified functions are missing test coverage:"
echo ""
for entry in "${MISSING[@]}"; do
	echo "$entry"
done
echo ""
echo "Add test methods or apply the 'skip-test-check' label to bypass."
exit 1
