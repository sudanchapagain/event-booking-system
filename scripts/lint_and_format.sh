# IMPORTANT:
# this has two different linters/formatters
# djlint for django templates
# ruff for python files


# format and lint dtl
# NOTE TO OTHERS: it will throw image tag should have HxW, and inline style errors. ignore it for now.
djlint templates --reformat --lint --format-css --format-js --profile django --quiet --use-gitignore --format-attribute-template-tags --indent-css 4 --indent-js 4 --max-blank-lines 1

# format and lint python source files
# TODO: add ruff.toml (defaults are a bit too loose, but most get started ruff.toml are too "strict")
ruff check . --fix
ruff format . --respect-gitignore
