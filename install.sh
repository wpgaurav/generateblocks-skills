#!/usr/bin/env bash
#
# install.sh — Install GenerateBlocks skills for AI coding assistants
#
# Usage:
#   ./install.sh              # Interactive mode (choose tools)
#   ./install.sh --all        # Install for all detected tools
#   ./install.sh claude       # Install for specific tool(s)
#   ./install.sh cursor codex # Multiple tools at once
#
# Supported tools:
#   claude    — Claude Code (~/.claude/skills/)
#   cursor    — Cursor IDE (~/.cursor/skills/ + .cursor/rules/)
#   windsurf  — Windsurf IDE (~/.windsurf/rules/)
#   codex     — OpenAI Codex CLI (~/.codex/instructions.md)
#   gemini    — Gemini CLI (GEMINI.md)
#   copilot   — GitHub Copilot (.github/copilot-instructions.md)
#   cline     — Cline / Roo Code (.clinerules)
#   aider     — Aider (.aider.conf.yml + conventions)

set -euo pipefail

# ─── Config ──────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILLS_DIR="$SCRIPT_DIR/skills"
AGENTS_FILE="$SCRIPT_DIR/AGENTS.md"

SKILLS=(
  "generateblocks-layouts"
  "html-to-generateblocks"
  "elementor-to-generateblocks"
  "figma-to-generateblocks"
)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

# ─── Helpers ─────────────────────────────────────────────────────────

info()    { echo -e "${BLUE}→${NC} $*"; }
success() { echo -e "${GREEN}✓${NC} $*"; }
warn()    { echo -e "${YELLOW}!${NC} $*"; }
error()   { echo -e "${RED}✗${NC} $*"; }

copy_skill_dir() {
  local src="$1" dest="$2"
  mkdir -p "$dest"
  # Preserve the routed references and executable validation dependencies.
  python3 - "$src" "$dest" <<'PYCOPY'
from pathlib import Path
import shutil
import sys
source, target = map(Path, sys.argv[1:])
if source.resolve() != target.resolve():
    shutil.copy2(source / 'SKILL.md', target / 'SKILL.md')
    for name in ('references', 'examples', 'scripts', 'assets', 'agents'):
        if (source / name).is_dir():
            shutil.copytree(source / name, target / name, dirs_exist_ok=True,
                            ignore=shutil.ignore_patterns('__pycache__', '*.pyc', '.DS_Store'))
PYCOPY
}

build_combined_instructions() {
  # Combine AGENTS.md + all SKILL.md files into one markdown document
  local output="$1"
  {
    cat "$AGENTS_FILE"
    echo ""
    echo "## Reference roots for this installation"
    echo "Resolve skill-relative references against these source folders:"
    for skill in "${SKILLS[@]}"; do
      echo "- $skill: $SKILLS_DIR/$skill"
    done
    echo "Keep this checkout available; the combined file is a routed entrypoint, not an embedded manual."
    echo ""
    echo "---"
    echo ""
    for skill in "${SKILLS[@]}"; do
      local skill_file="$SKILLS_DIR/$skill/SKILL.md"
      if [ -f "$skill_file" ]; then
        # Strip YAML frontmatter
        sed '1{/^---$/!q;};1,/^---$/d' "$skill_file"
        echo ""
        echo "---"
        echo ""
      fi
    done
  } > "$output"
}

# ─── Installers ──────────────────────────────────────────────────────

install_claude() {
  local target="$HOME/.claude/skills"
  info "Installing for ${BOLD}Claude Code${NC} → $target"
  mkdir -p "$target"

  for skill in "${SKILLS[@]}"; do
    local src="$SKILLS_DIR/$skill"
    local dest="$target/$skill"
    if [ -d "$src" ]; then
      copy_skill_dir "$src" "$dest"
      success "  $skill"
    fi
  done

  success "Claude Code: ${#SKILLS[@]} skills installed"
  echo "  Run ${BOLD}claude${NC} in any project to use them."
}

install_cursor() {
  local target="$HOME/.cursor/skills"
  info "Installing for ${BOLD}Cursor${NC} → $target"
  mkdir -p "$target"

  for skill in "${SKILLS[@]}"; do
    local src="$SKILLS_DIR/$skill"
    local dest="$target/$skill"
    if [ -d "$src" ]; then
      copy_skill_dir "$src" "$dest"
      success "  $skill"
    fi
  done

  success "Cursor: ${#SKILLS[@]} skills installed to personal skills"
  echo ""
  info "Optional: Install project-level rule"
  echo "  To add to a specific project, run:"
  echo "  ${BOLD}mkdir -p .cursor/rules && cp $AGENTS_FILE .cursor/rules/generateblocks.md${NC}"
}

install_windsurf() {
  local target="$HOME/.windsurf/rules"
  info "Installing for ${BOLD}Windsurf${NC} → $target"
  mkdir -p "$target"

  local combined="$target/generateblocks.md"
  build_combined_instructions "$combined"
  success "Windsurf: Combined instructions → $combined"
  echo "  Windsurf loads rules from ~/.windsurf/rules/ automatically."
}

install_codex() {
  local target="$HOME/.codex"
  info "Installing for ${BOLD}OpenAI Codex CLI${NC} → $target"
  mkdir -p "$target"

  local instructions="$target/instructions.md"
  build_combined_instructions "$instructions"
  success "Codex CLI: Instructions → $instructions"
  echo "  Codex loads ~/.codex/instructions.md as system prompt."
}

install_gemini() {
  info "Installing for ${BOLD}Gemini CLI${NC} → GEMINI.md"
  local target="$SCRIPT_DIR/GEMINI.md"
  build_combined_instructions "$target"
  success "Gemini CLI: Instructions → $target"
  echo "  Gemini CLI loads GEMINI.md from the project root."
  echo "  Copy to your project: ${BOLD}cp $target /path/to/your/project/${NC}"
}

install_copilot() {
  info "Installing for ${BOLD}GitHub Copilot${NC}"
  local target="$SCRIPT_DIR/.github"
  mkdir -p "$target"

  local instructions="$target/copilot-instructions.md"
  build_combined_instructions "$instructions"
  success "Copilot: Instructions → $instructions"
  echo "  Copy .github/copilot-instructions.md to your project root."
  echo "  ${BOLD}cp -r $target /path/to/your/project/${NC}"
}

install_cline() {
  info "Installing for ${BOLD}Cline / Roo Code${NC}"
  local target="$SCRIPT_DIR/.clinerules"
  build_combined_instructions "$target"
  success "Cline: Rules → $target"
  echo "  Copy .clinerules to your project root."
  echo "  ${BOLD}cp $target /path/to/your/project/${NC}"
}

install_aider() {
  info "Installing for ${BOLD}Aider${NC}"
  local conventions="$SCRIPT_DIR/CONVENTIONS.md"
  build_combined_instructions "$conventions"
  success "Aider: Conventions → $conventions"
  echo "  Copy CONVENTIONS.md to your project root."
  echo "  Aider reads CONVENTIONS.md automatically."
  echo "  Or add to .aider.conf.yml: ${BOLD}read: [CONVENTIONS.md]${NC}"
}

# ─── Interactive Mode ────────────────────────────────────────────────

show_menu() {
  echo ""
  echo -e "${BOLD}GenerateBlocks Skills Installer${NC}"
  echo ""
  echo "Select AI tools to install for:"
  echo ""
  echo "  1) Claude Code        ~/.claude/skills/ (skill directories)"
  echo "  2) Cursor             ~/.cursor/skills/ (skill directories)"
  echo "  3) Windsurf           ~/.windsurf/rules/ (combined markdown)"
  echo "  4) OpenAI Codex CLI   ~/.codex/instructions.md"
  echo "  5) Gemini CLI         GEMINI.md (project root)"
  echo "  6) GitHub Copilot     .github/copilot-instructions.md"
  echo "  7) Cline / Roo Code   .clinerules (project root)"
  echo "  8) Aider              CONVENTIONS.md (project root)"
  echo ""
  echo "  a) All of the above"
  echo "  q) Quit"
  echo ""
  echo -n "Choose (comma-separated, e.g. 1,2,5): "
}

run_interactive() {
  show_menu
  read -r choices

  if [[ "$choices" == "q" ]]; then
    echo "Bye!"
    exit 0
  fi

  echo ""

  if [[ "$choices" == "a" ]]; then
    choices="1,2,3,4,5,6,7,8"
  fi

  IFS=',' read -ra selected <<< "$choices"
  for choice in "${selected[@]}"; do
    choice="$(echo "$choice" | tr -d ' ')"
    case "$choice" in
      1) install_claude ;;
      2) install_cursor ;;
      3) install_windsurf ;;
      4) install_codex ;;
      5) install_gemini ;;
      6) install_copilot ;;
      7) install_cline ;;
      8) install_aider ;;
      *) warn "Unknown option: $choice" ;;
    esac
    echo ""
  done

  echo -e "${GREEN}${BOLD}Done!${NC} Skills installed."
  echo ""
  echo "Skills included:"
  for skill in "${SKILLS[@]}"; do
    echo "  • $skill"
  done
  echo ""
  echo "Next steps:"
  echo "  • Open your AI tool and start building GenerateBlocks layouts"
  echo "  • Try: \"Create a hero section with headline, stats bar, and two CTA buttons\""
}

# ─── CLI Mode ────────────────────────────────────────────────────────

run_cli() {
  echo ""
  echo -e "${BOLD}GenerateBlocks Skills Installer${NC}"
  echo ""

  for tool in "$@"; do
    case "$tool" in
      --all)
        install_claude; echo ""
        install_cursor; echo ""
        install_windsurf; echo ""
        install_codex; echo ""
        install_gemini; echo ""
        install_copilot; echo ""
        install_cline; echo ""
        install_aider; echo ""
        ;;
      claude)   install_claude ;;
      cursor)   install_cursor ;;
      windsurf) install_windsurf ;;
      codex)    install_codex ;;
      gemini)   install_gemini ;;
      copilot)  install_copilot ;;
      cline)    install_cline ;;
      aider)    install_aider ;;
      -h|--help)
        echo "Usage: ./install.sh [--all] [tool ...]"
        echo ""
        echo "Tools: claude cursor windsurf codex gemini copilot cline aider"
        echo ""
        echo "Examples:"
        echo "  ./install.sh              # Interactive mode"
        echo "  ./install.sh --all        # Install for all tools"
        echo "  ./install.sh claude       # Claude Code only"
        echo "  ./install.sh cursor codex # Multiple tools"
        exit 0
        ;;
      *)
        error "Unknown tool: $tool"
        echo "Available: claude cursor windsurf codex gemini copilot cline aider"
        exit 1
        ;;
    esac
    echo ""
  done

  echo -e "${GREEN}${BOLD}Done!${NC}"
}

# ─── Main ────────────────────────────────────────────────────────────

if [ $# -eq 0 ]; then
  run_interactive
else
  run_cli "$@"
fi
