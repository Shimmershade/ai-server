#!/bin/bash

docker run -d \
  -p 8000:8000 \
  -e YANDEX_API_KEY="${YANDEX_API_KEY:-test}" \
  -e YANDEX_FOLDER_ID="${YANDEX_FOLDER_ID:-test}" \
  --name llm-service \
  llm-service