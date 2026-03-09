#!/bin/bash
# build and run EvilKnievelnoVNC after setup via setup.sh
shell=/bin/bash
url="---tUrl---"
instances=---inst---

###############################################################
## BUILD & RUN

# 1. build & run controller first
echo "[*] Starting controller..."
cd controller && $shell run.sh &
sleep 15

# 2. build & run EvilnoVNC instances BEFORE haproxy
# Issue #7 fix: HAProxy resolves evil01:8111 etc. at startup via DNS.
# If EvilnoVNC containers are not yet running, HAProxy marks all backends DOWN on boot.
# Strict startup order: controller → EvilnoVNC instances → haproxy
echo "[*] Starting $instances EvilnoVNC instances..."
cd ../EvilnoVNC

for ((i=1; i<=$instances; i++)); do
        [ $i -lt 10 ] && i="0$i"

	$shell run.sh "$url" $i &

	echo "[*] Instance $i started"
	sleep 15
done

echo "[*] EvilnoVNC instances started, pointing to $url"

# 3. build & run haproxy LAST so backends (evil01..N) are resolvable on startup
echo "[*] Starting haproxy..."
cd ../haproxy && $shell run.sh &
sleep 15

cd ..

#jobs
docker ps -a
