#!/usr/bin/env bash
# IdleTime hook for Claude Code.
#
#   idletime-hook.sh start   -> run on UserPromptSubmit: tells IdleTime your agent is busy
#                               and opens the "Watch & earn" page in your browser
#   idletime-hook.sh stop    -> run on Stop: tells IdleTime the agent finished
#
# Config lives in ~/.idletime/config (created by the install command on the
# Connect Claude Code page):
#   IDLETIME_URL=https://your-idletime-site.example
#   IDLETIME_TOKEN=1|xxxxxxxx
#   IDLETIME_EXPECTED_MINUTES=5      # how long a typical Claude turn takes you
#   IDLETIME_OPEN_COOLDOWN=20        # minutes before the page is opened again
#   IDLETIME_OPEN_BROWSER=1          # 0 = only report status, never open a tab
#
# The hook never blocks Claude: it forks to the background, prints nothing
# (UserPromptSubmit output would be added to Claude's context) and always exits 0.

set -u
DIR="${IDLETIME_HOME:-$HOME/.idletime}"
# Read KEY=value lines (values may contain "|", so the file is parsed, not sourced).
if [ -f "$DIR/config" ]; then
  while IFS='=' read -r key value || [ -n "$key" ]; do
    value="${value%$'\r'}"; value="${value#[\"\']}"; value="${value%[\"\']}"
    case "$key" in IDLETIME_[A-Z_]*) export "$key=$value" ;; esac
  done < "$DIR/config"
fi
MODE="${1:-start}"

# Claude Code sends hook input as JSON on stdin; we don't need it.
cat >/dev/null 2>&1 || true

[ -n "${IDLETIME_URL:-}" ] && [ -n "${IDLETIME_TOKEN:-}" ] || exit 0
URL="${IDLETIME_URL%/}"

api() { # method path [json]
  curl -fsS --max-time 8 -X "$1" "$URL$2" \
    -H "Authorization: Bearer $IDLETIME_TOKEN" -H "Accept: application/json" \
    -H "Content-Type: application/json" ${3:+--data "$3"} 2>/dev/null
}

open_url() {
  if command -v open >/dev/null 2>&1 && [ "$(uname)" = "Darwin" ]; then open "$1"
  elif command -v wslview >/dev/null 2>&1; then wslview "$1"
  elif command -v cmd.exe >/dev/null 2>&1; then cmd.exe /c start "" "$1"
  elif command -v xdg-open >/dev/null 2>&1; then xdg-open "$1"
  fi >/dev/null 2>&1
}

minutes_since() { # file -> minutes since its mtime (large number if missing)
  [ -f "$1" ] || { echo 99999; return; }
  local now mtime
  now=$(date +%s)
  mtime=$(stat -c %Y "$1" 2>/dev/null || stat -f %m "$1" 2>/dev/null || echo 0)
  echo $(( (now - mtime) / 60 ))
}

start() {
  local body id watch
  body=$(api POST /api/idle-sessions "{\"client\":\"cli\",\"agent\":\"claude-code\",\"expected_minutes\":${IDLETIME_EXPECTED_MINUTES:-5}}") || return
  id=$(printf '%s' "$body" | sed -n 's/.*"session":{"id":\([0-9]*\).*/\1/p')
  watch=$(printf '%s' "$body" | sed -n 's/.*"watch_url":"\([^"]*\)".*/\1/p' | sed 's#\\/#/#g')
  [ -n "$id" ] && printf '%s' "$id" > "$DIR/session"

  if [ "${IDLETIME_OPEN_BROWSER:-1}" = "1" ] && [ -n "$watch" ] \
     && [ "$(minutes_since "$DIR/last_open")" -ge "${IDLETIME_OPEN_COOLDOWN:-20}" ]; then
    touch "$DIR/last_open"
    open_url "$watch"
  fi
}

stop() {
  [ -f "$DIR/session" ] || return
  api POST "/api/idle-sessions/$(cat "$DIR/session")/end" '{}' >/dev/null
  rm -f "$DIR/session"
}

mkdir -p "$DIR"
case "$MODE" in
  start) ( start ) >/dev/null 2>&1 & ;;
  stop)  ( stop )  >/dev/null 2>&1 & ;;
esac
exit 0
