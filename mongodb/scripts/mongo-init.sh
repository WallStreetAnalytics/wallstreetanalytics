#!/usr/bin/env bash

mongo $MONGODB_DATABASE --port $MONGODB_PORT -u $MONGO_INITDB_ROOT_USERNAME -p $MONGO_INITDB_ROOT_PASSWORD --authenticationDatabase admin <<EOF
use "$MONGODB_DATABASE";
db.createUser({user: "$MONGODB_USER", pwd: "$MONGODB_PASS", roles:[{ role: "readWrite", db: "$MONGODB_DATABASE"}]})
EOF
