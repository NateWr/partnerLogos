#!/bin/bash

set -e

npx cypress run --headless --browser chrome --config '{"specPattern":["plugins/generic/partnerLogos/cypress/tests/functional/*.cy.{js,jsx,ts,tsx}"]}'
