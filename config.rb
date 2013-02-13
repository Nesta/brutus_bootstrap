# These are the root folders for theme assets:
css_dir         = "css"
sass_dir        = "sass"
extensions_dir  = "sass-bootstrap"
images_dir      = "images"
javascripts_dir = "js"

# Require any additional compass plugins here.
require "susy"

# Firesass for DOM inspector
sass_options = {:debug_info => false} 

# You can select your preferred output style here (can be overridden via the command line):
# output_style = :expanded or :nested or :compact or :compressed
output_style = :expanded

# To enable relative paths to assets via compass helper functions. Since Drupal
# themes can be installed in multiple locations, we don't need to worry about
# the absolute path to the theme from server root.
relative_assets = true

# To disable debugging comments that display the original location of your selectors. Uncomment:
line_comments = false


# If you prefer the indented syntax, you might want to regenerate this
# project again passing --syntax sass, or you can uncomment this:
# preferred_syntax = :sass
# and then run:
# sass-convert -R --from scss --to sass sass scss && rm -rf sass && mv scss sass
