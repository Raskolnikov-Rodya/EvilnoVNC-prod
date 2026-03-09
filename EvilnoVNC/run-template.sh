#!/bin/bash
if [ -z "$1" ]
then
        echo "Error: missing instance parameter"
	echo "Usage:"
        echo -e "\t $0 (<custom-URL>) <two digit instance number>"
        echo -e "\t\t $0 01"
        echo -e "\t\t $0 02"
	echo -e "\t\t ..."
        echo -e "\t\t custom-URL is optional, configure URL with setup.sh"
        exit 1
fi

docker build --rm -t evilnovnc .

# Issue #5 fix: derive INSTANCE_ID explicitly and strip any accidental 'evil' prefix.
# Without this, passing a full container name (e.g. "evil01") as the instance argument
# results in start.sh constructing --name evil${SID} → "evilevilO1" → "evilevilevil01" ...
# Two-arg form:  ./run.sh <url> <instance_id>   → INSTANCE_ID = $2
# One-arg form:  ./run.sh <instance_id>          → INSTANCE_ID = $1
if [ -n "$2" ]; then
	INSTANCE_ID=$2
else
	INSTANCE_ID=$1
fi
INSTANCE_ID=${INSTANCE_ID#evil}   # strip recursive 'evil' prefix if accidentally present
CONTAINER_NAME="evil${INSTANCE_ID}"

# start.sh dynamic "url" instance-id
# e.g. start.sh dynamic "https://example.com" 02
if [ -n "$2" ]
then
        ./start.sh dynamic "$1" "$INSTANCE_ID"
else
	./start.sh dynamic "---tUrl---" "$INSTANCE_ID"
fi
