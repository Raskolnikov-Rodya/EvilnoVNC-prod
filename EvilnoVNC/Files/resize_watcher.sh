#!/bin/bash
# resize_watcher.sh - Monitors X display and resizes Chromium to match
export DISPLAY=:0
LAST_RES=""

# Enforce minimum resolution
MIN_W=800
MIN_H=600

while true; do
    CURRENT_RES=$(xdpyinfo -display $DISPLAY 2>/dev/null | grep dimensions | awk '{print $2}')

    if [ -n "$CURRENT_RES" ] && [ "$CURRENT_RES" != "$LAST_RES" ] && [ -n "$LAST_RES" ]; then
        WIDTH=$(echo "$CURRENT_RES" | cut -d'x' -f1)
        HEIGHT=$(echo "$CURRENT_RES" | cut -d'x' -f2)

        [ "$WIDTH" -lt "$MIN_W" ] && WIDTH=$MIN_W
        [ "$HEIGHT" -lt "$MIN_H" ] && HEIGHT=$MIN_H

        WINID=$(xdotool search --onlyvisible --name "" 2>/dev/null | head -1)
        if [ -n "$WINID" ]; then
            xdotool windowmove "$WINID" 0 0
            xdotool windowsize "$WINID" "$WIDTH" "$HEIGHT"
        fi
    fi

    LAST_RES="$CURRENT_RES"
    sleep 1
done
