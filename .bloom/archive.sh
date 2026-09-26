#!/usr/bin/env bash
# .bloom/archive.sh
set -euo pipefail

# Keep in sync with setup.sh
SITE="mfm-$(printf '%s' "$BLOOM_WORKSPACE_ID" | tr -cd '[:alnum:]' | tr '[:upper:]' '[:lower:]' | cut -c1-10)"

# Tolerate a site that was never linked or is already gone,
# or Bloom will refuse to remove the worktree.
herd unsecure "$SITE" || true
herd unlink "$SITE" || true
