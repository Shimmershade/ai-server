#!/bin/bash

curl -X POST http://localhost:8000/api/llm/ask \
  -H "Content-Type: application/json" \
  -d '{
    "input": "тайны ордена тамплиеров",
    "instructions": "Пиши на русском, кратко"
  }'
