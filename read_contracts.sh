#!/bin/sh
set -eu
cd /Applications/MAMP/htdocs/stayin

echo "====== READING CONTROLLERS, VIEW CONTRACT, HELPERS, CONFIG ======"
for f in \
  app/Controllers/HomeController.php \
  app/Controllers/PropertyController.php \
  app/Controllers/SearchController.php \
  app/Controllers/ApiController.php \
  app/Controllers/PageController.php \
  app/Controllers/SeoController.php \
  app/Controllers/DatabaseSitemap.php \
  app/Controllers/HealthController.php \
  app/Controllers/AuthController.php \
  app/Core/View.php \
  app/Core/Controller.php \
  app/Support/helpers.php \
  config/seo.php \
  config/features.php \
  config/display.php \
  config/support.php \
  config/amp.php \
  config/payments.php \
  config/pricing.php \
  config/booking.php \
  app/Models/User.php \
  app/Models/Property.php \
  app/Models/HeroSlide.php \
  routes/web.php \
  routes/api.php
do
  if [ -f "$f" ]; then
    echo "========== $f ($(wc -l < "$f") lines) =========="
    cat "$f"
    echo
  else
    echo "MISSING: $f"
  fi
done

echo "====== VIEW REFERENCE GREP ======"
grep -rn "view(" app/Controllers/ routes/ 2>/dev/null | head -80 || true

echo "====== CSS TOKENS FIRST 120 LINES ======"
head -120 public/assets/css/tokens.css
