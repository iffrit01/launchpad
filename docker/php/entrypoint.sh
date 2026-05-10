#!/usr/bin/env bash
set -e

mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

exec "$@"
