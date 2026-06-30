#!/usr/bin/env bash
#
# End-to-end API walkthrough against the running stack.
# Creates the data it needs (manager → shop → products → stock), then exercises
# every read endpoint. Doubles as a quick smoke test.
#
# Prerequisites: the stack is up — `make start` is enough (this script creates its own data).
# Usage:   make api-demo      (or)   bash bin/api-demo.sh
# Override the target with:   BASE_URL=http://localhost:9876 bash bin/api-demo.sh
#
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"

# Pretty-print JSON if jq is available, otherwise print as-is.
pp() { if command -v jq >/dev/null 2>&1; then jq .; else cat; echo; fi; }

# First "id" field of a JSON response (the create endpoints return it first).
id_of() { grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4; }

post() { curl -fsS -X POST "$BASE_URL$1" -H 'Content-Type: application/json' -d "$2"; }
put()  { curl -fsS -X PUT  "$BASE_URL$1" -H 'Content-Type: application/json' -d "$2"; }
get()  { curl -fsS "$BASE_URL$1"; }

step() { printf '\n\033[36m== %s ==\033[0m\n' "$1"; }

echo "Target: $BASE_URL"

# --- Arrange: create the data ------------------------------------------------
step "POST /api/managers — create a manager"
manager=$(post /api/managers '{"name":"Jane Cooper"}'); echo "$manager" | pp
managerId=$(echo "$manager" | id_of)

step "POST /api/shops — create a shop (Paris Marais)"
shop=$(post /api/shops "{\"name\":\"Paris Marais\",\"address\":\"12 rue de Rivoli, 75004 Paris\",\"latitude\":48.8559,\"longitude\":2.3601,\"managerId\":\"$managerId\"}")
echo "$shop" | pp
shopId=$(echo "$shop" | id_of)

step "POST /api/products — create two products"
p1=$(post /api/products '{"name":"Wrap dress","pictureUrl":"https://example.com/wrap-dress.jpg"}'); echo "$p1" | pp
p2=$(post /api/products '{"name":"Sandy dress","pictureUrl":"https://example.com/sandy-dress.jpg"}'); echo "$p2" | pp
p1Id=$(echo "$p1" | id_of); p2Id=$(echo "$p2" | id_of)

step "PUT /api/products/{id}/stock — set stock in the shop (one in stock, one out)"
put "/api/products/$p1Id/stock" "[{\"shopId\":\"$shopId\",\"quantity\":12}]" | pp
put "/api/products/$p2Id/stock" "[{\"shopId\":\"$shopId\",\"quantity\":0}]" | pp

# --- Act: exercise the read endpoints ----------------------------------------
step "GET /api/products — list the catalog (search=dress)"
get "/api/products?search=dress&sort=name&direction=asc&page=1&limit=20" | pp

step "GET /api/shops — search shops near Paris"
get "/api/shops?lat=48.8566&lng=2.3522&radius=5000&page=1&limit=20" | pp

step "GET /api/stock — stock of the shop (out-of-stock excluded by default)"
get "/api/stock?shopIds=$shopId" | pp

step "GET /api/stock — same, including out-of-stock"
get "/api/stock?shopIds=$shopId&includeOutOfStock=true" | pp

step "GET /api/shops/{id}/products — products of the shop"
get "/api/shops/$shopId/products" | pp

step "GET /api/products/{id}/availability — where to find the product near Paris"
get "/api/products/$p1Id/availability?lat=48.8566&lng=2.3522&radius=5000" | pp

printf '\n\033[32m✓ Done.\033[0m\n'
