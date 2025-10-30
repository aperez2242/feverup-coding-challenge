#!/bin/bash
set -e

# Colors for logs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # no color

CONTAINER="wordpress-dev"
THEMES_PATH="/var/www/html/wp-content/themes"
PLUGINS_PATH="/var/www/html/wp-content/plugins"

echo -e "${YELLOW}Starting WordPress containers (if not running)...${NC}"
docker-compose up -d wordpress db

echo -e "${YELLOW}Checking for Understrap theme...${NC}"
docker-compose exec -T $CONTAINER bash -c "
  cd $THEMES_PATH &&
  if [ ! -d understrap ]; then
    echo 'Understrap not found. Downloading...'
    wget -q https://github.com/understrap/understrap/releases/latest/download/understrap.zip &&
    unzip -q understrap.zip &&
    rm understrap.zip &&
    echo 'Understrap installed successfully.'
  else
    echo 'Understrap already exists. Skipping download.'
  fi
"

echo -e "${YELLOW}Ensuring child theme directory exists...${NC}"
docker-compose exec -T $CONTAINER bash -c "
  mkdir -p $THEMES_PATH/understrap-child &&
  cd $THEMES_PATH/understrap-child &&
  if [ ! -f style.css ]; then
    cat > style.css <<'EOF'
/*
Theme Name: Understrap Child
Template: understrap
Version: 1.0
*/
EOF
    echo 'Created style.css'
  fi

  if [ ! -f functions.php ]; then
    cat > functions.php <<'EOF'
<?php
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('understrap-parent', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('understrap-child', get_stylesheet_uri(), ['understrap-parent'], wp_get_theme()->get('Version'));
});
EOF
    echo 'Created functions.php'
  fi
"

echo -e "${YELLOW}Ensuring Pokémon plugin directory exists...${NC}"
docker-compose exec -T $CONTAINER bash -c "
  mkdir -p $PLUGINS_PATH/pokemon-cpt/public &&
  touch $PLUGINS_PATH/pokemon-cpt/pokemon-cpt.php &&
  touch $PLUGINS_PATH/pokemon-cpt/public/pokemon-ajax.js
  echo 'Plugin skeleton ready at $PLUGINS_PATH/pokemon-cpt/'
"

echo -e "${YELLOW}Fixing permissions...${NC}"
docker-compose exec -T $CONTAINER bash -c "
  chown -R www-data:www-data $THEMES_PATH $PLUGINS_PATH
"

echo -e "${GREEN}✅ Setup complete!${NC}"
echo -e "${GREEN}Now open WordPress → Appearance → Themes and activate 'Understrap Child'.${NC}"
echo -e "${GREEN}Then go to Plugins → activate 'Pokémon CPT' (once you paste the code).${NC}"
