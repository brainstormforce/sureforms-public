#!/usr/bin/env bash

# Exit if any command fails.
set -e

BASE_SHA="${1:?Usage: check-test-coverage.sh <base-sha> [<head-sha>]}"
HEAD_SHA="${2:-HEAD}"

# Step 1 — Extract diff for PHP files in source directories (skip third-party).
DIFF=$(git diff "$BASE_SHA"..."$HEAD_SHA" --unified=0 --diff-filter=AM -- 'inc/*.php' 'admin/*.php' 'modules/*.php' ':!inc/lib/')

if [ -z "$DIFF" ]; then
	echo "No PHP source changes detected. Skipping."
	exit 0
fi

MISSING=()
TOTAL=0
declare -A SEEN

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

		# Mark as seen so Step 2b doesn't re-process this function.
		KEY="${CURRENT_FILE}:${FUNC_NAME}"
		SEEN[$KEY]=1

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

# Step 2b — Detect modified function bodies (functions whose body changed but
#           whose signature was not newly added).
CURRENT_FILE=""
declare -A FILE_LINES # file -> space-separated changed line numbers

while IFS= read -r line; do
	if [[ "$line" =~ ^diff\ --git\ a/(.+)\ b/ ]]; then
		CURRENT_FILE="${BASH_REMATCH[1]}"
		continue
	fi
	# Parse @@ hunk headers to extract new-file line ranges.
	if [[ "$line" =~ ^@@.*\+([0-9]+)(,([0-9]+))?\ @@ ]]; then
		START="${BASH_REMATCH[1]}"
		COUNT="${BASH_REMATCH[3]:-1}"
		for (( i=START; i<START+COUNT; i++ )); do
			FILE_LINES["$CURRENT_FILE"]+="$i "
		done
	fi
done <<< "$DIFF"

for FILE in "${!FILE_LINES[@]}"; do
	[ -f "$FILE" ] || continue

	# Get all public/protected function declarations with their line numbers.
	FUNC_LINES=$(grep -nE '^\s*(public|protected)\s+(static\s+)?function\s+[a-zA-Z_]' "$FILE" | \
		sed -E 's/^([0-9]+):.*function[[:space:]]+([a-zA-Z_][a-zA-Z0-9_]*).*/\1 \2/')

	[ -z "$FUNC_LINES" ] && continue

	# For each changed line, find the enclosing function.
	for LINE_NO in ${FILE_LINES[$FILE]}; do
		ENCLOSING=""
		while IFS=' ' read -r FLINE FNAME; do
			[ "$FLINE" -le "$LINE_NO" ] && ENCLOSING="$FNAME"
		done <<< "$FUNC_LINES"

		[ -z "$ENCLOSING" ] && continue

		# Skip magic methods.
		[[ "$ENCLOSING" =~ ^__ ]] && continue
		# Skip singleton boilerplate.
		[[ "$ENCLOSING" == "get_instance" ]] && continue

		# De-duplicate against functions already found in Step 2.
		KEY="${FILE}:${ENCLOSING}"
		[[ -n "${SEEN[$KEY]+x}" ]] && continue
		SEEN[$KEY]=1

		TOTAL=$((TOTAL + 1))

		# Map source file to test file (same logic as Step 3).
		DIR=$(dirname "$FILE")
		BASENAME=$(basename "$FILE")
		TEST_FILE="tests/unit/${DIR}/test-${BASENAME}"

		# Check if a matching test method exists.
		if [ -f "$TEST_FILE" ] && grep -qE "function test_${ENCLOSING}(_|\b)" "$TEST_FILE"; then
			continue
		fi

		MISSING+=("  ${FILE}:${ENCLOSING}() → expected: ${TEST_FILE} → function test_${ENCLOSING}()")
	done
done

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
