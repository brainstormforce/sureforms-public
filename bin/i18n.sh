#!/bin/bash

# Run release script
#bash bin/i18n.sh
#
# Generates/updates the .pot, .po, .mo and JS .json translation files for the
# BSF Top 20 languages standard. Run at release time. New locales are created
# automatically via msginit, so the .po files do not need to be committed.

# Always run from the plugin root so relative paths resolve.
cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit 1

# Target locales (BSF Top 20). Format: <wp-locale-filename>:<msginit-locale>
LANGS=(
    "de_DE:de_DE" "es_ES:es_ES" "fr_FR:fr_FR" "it_IT:it_IT" "nl_NL:nl_NL"
    "pl_PL:pl_PL" "pt_PT:pt_PT" "id_ID:id_ID" "pt_BR:pt_BR" "ru_RU:ru_RU"
    "tr_TR:tr_TR" "ja:ja_JP" "zh_CN:zh_CN" "ar:ar" "sv_SE:sv_SE"
    "vi:vi_VN" "he_IL:he_IL" "th:th_TH" "el:el_GR" "cs_CZ:cs_CZ"
)

# Run textdomain updates and generate the POT file
echo "Running textdomain update and POT file generation..."
npm run makepot

# Create a .po for any target locale that does not exist yet.
echo "Ensuring .po files exist for all target locales..."
for entry in "${LANGS[@]}"; do
    file="${entry%%:*}"; loc="${entry##*:}"
    po="languages/sureforms-${file}.po"
    if [ ! -f "$po" ]; then
        echo "  creating $po ($loc)"
        msginit --input=languages/sureforms.pot --output="$po" --locale="$loc" --no-translator
    fi
done

# Update PO files
echo "Updating PO files..."
npm run i18n:po

# Translate using GPT-PO (only empty msgstr entries are filled).
echo "Translating PO files using GPT-PO..."
npm run i18n:gptpo:nl
npm run i18n:gptpo:fr
npm run i18n:gptpo:de
npm run i18n:gptpo:es
npm run i18n:gptpo:it
npm run i18n:gptpo:pt
npm run i18n:gptpo:pl
npm run i18n:gptpo:id
npm run i18n:gptpo:ptbr
npm run i18n:gptpo:ru
npm run i18n:gptpo:tr
npm run i18n:gptpo:ja
npm run i18n:gptpo:zh
npm run i18n:gptpo:ar
npm run i18n:gptpo:sv
npm run i18n:gptpo:vi
npm run i18n:gptpo:he
npm run i18n:gptpo:th
npm run i18n:gptpo:el
npm run i18n:gptpo:cs

# Update PO files again after translation
echo "Updating PO files again..."
npm run i18n:po

# Generate MO files
echo "Generating MO files..."
npm run i18n:mo

# Generate JSON translation files
echo "Generating JSON translation files..."
npm run i18n:json

echo "All commands executed successfully."
