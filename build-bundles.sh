#!/usr/bin/env bash
# Regenerate the importable skill bundles from skills/.
# Each skill produces importable/<name>.zip and an identical .skill copy.
# Run after editing anything under skills/.
set -euo pipefail

cd "$(dirname "$0")"

SKILLS=(
    generateblocks-layouts
    html-to-generateblocks
    elementor-to-generateblocks
    figma-to-generateblocks
)

mkdir -p importable

for skill in "${SKILLS[@]}"; do
    if [ ! -d "skills/$skill" ]; then
        echo "skip: skills/$skill not found" >&2
        continue
    fi
    rm -f "importable/$skill.zip" "importable/$skill.skill"
    (cd skills && zip -rq "../importable/$skill.zip" "$skill" \
        -x "*.DS_Store" "*/__pycache__/*" "*.pyc")
    cp "importable/$skill.zip" "importable/$skill.skill"
    echo "built importable/$skill.zip + .skill"
done
