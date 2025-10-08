#!/bin/bash
# Make sure you run this from: /var/www/html/omega_presecen360

mkdir -p ../erp_presence360

# List of files to sync
files=(
"app/Http/Controllers/ErpEquipmentController.php"
"app/Http/Controllers/Plant/MaintBomController.php"
"app/Http/Controllers/Plant/MaintWoController.php"
"resources/views/partials/amendement-submit-modal.blade.php"
"resources/views/plant/maint_bom/create.blade.php"
"resources/views/plant/maint_bom/edit.blade.php"
"resources/views/plant/maint_bom/show.blade.php"
"resources/views/plant/maint_wo/create.blade.php"
"resources/views/plant/maint_wo/edit.blade.php"
"resources/views/plant/maint_wo/show.blade.php"
"routes/web.php"
)

# Loop through all files
for file in "${files[@]}"; do
  src="$file"
  dest="../erp_presence360/$file"

  mkdir -p "$(dirname "$dest")"

  if [ -f "$src" ]; then
    cp "$src" "$dest"
    echo "✅ Copied: $file"
  else
    echo "⚠️ Source missing: $file — creating blank file"
    touch "$dest"
  fi
done

echo "🎯 All files processed successfully!"

