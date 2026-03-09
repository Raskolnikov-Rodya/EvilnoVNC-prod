#!/bin/bash

# Issue #4 fix: abort early if port 443 is already occupied on the host
# (prevents silent HAProxy bind failure due to stale containers or host services)
if ss -tlnp 2>/dev/null | grep -q ':443 ' || netstat -tlnp 2>/dev/null | grep -q ':443 '; then
	echo "[!] ERROR: Port 443 is already in use on the host."
	echo "    Identify conflicting process: netstat -tulpn | grep :443"
	echo "    Stop stale containers:       docker ps | grep 443   then   docker stop <name>"
	exit 1
fi

# build
docker build --rm -t haproxy .

# run
# Issue #1/#2 fix: cert path hardcoded to absolute /opt/certificates/certandkey.pem (staged by setup.sh)
# and mounted :ro to prevent container writes and avoid ambiguous colon syntax
docker run --rm --name hap --network evil -p 443:443 -p 1300:1300 \
	-v /opt/certificates/certandkey.pem:/etc/certandkey.pem:ro \
	-v ./whitelist.acl:/etc/whitelist.acl \
	-v ./blacklist.acl:/etc/blacklist.acl \
	-v ./503.http:/etc/haproxy/503.http \
	haproxy 

	#-v /root/haproxy/cors.lua:/etc/cors.lua \

# certandkey.pem built from cert.pem and privatekey.pem
