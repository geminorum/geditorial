### 3.1
* enqueue front styles for drafts & estimated modules
* skip registering alpha modules on production

### 3.0
* moved to [Semantic Versioning](http://semver.org/)
* almost complete rewriting of all internal apis to reduce memory footprint

### 0.2.13
* all: foundation for new meta fields api
* all: remove meta box api
* all: load frontend arg changed
* modulecore: overriding pre options titles
* modulecore: linked cpt & term helpers
* meta: correctly support for all posttypes!
* meta: tabindex zero for inputs
* magazine: page start meta as number
* magazine: semantic issue/issues renamed to main/supported
* magazine: full cpt and tax labels
* magazine: using default meta ot/st callback
* reshare: correct escape for source url meta
* book: default size installer
* tweaks: fixed fallback for tax icon / attr
* contest: code refactoring

### 0.2.12
* all: removed old options calls
* all: new api for: tools page / save post / parse query / restrict manage posts / post parent field
* settings: upgrade option tool
* book: basic query class / see [Extending WP_Query](http://bradt.ca/blog/extending-wp_query/)

### 0.2.11
* all: new widget api based on [gTheme 3](https://github.com/geminorum/gtheme_03) code
* all: better handling image sizes
* entry: code refactoring
* magazine: semantic default callback for cover

### 0.2.10
* all: check if theme support thumbnail for all posttypes
* all: default terms api
* reshare: support for cpt thumbnail

### 0.2.9
* all: internal api for: post type thumbnail / list table column / p2p
* book: new module
* magazine: code refactoring

### 0.2.8
* magazine: fallback for issues with no cover
* reshare: template helper

### 0.2.7
* all: internal api for tinymce plugins
* series: switch to template class structure

### 0.2.6
* tweaks: simple post excerpt meta box
* audit: using internal tax meta box api

### 0.2.5
* reshare: new module

### 0.2.4
* magazine: new option for redirecting issue cpt archives
* magazine: restrict issue cpt by span on admin edit
* magazine: disable months dropdown for issue cpt on admin edit
* meta: inline edit on post table override fixed!

### 0.2.3
* magazine: using pages dropdown instead of terms
* magazine: separate save post and update post
* magazine: handle trash and delete issues

### 0.2.2
* all: new `add_image_size()` method with post type support
* all: moveup `set_meta()` and used as internal api. this will remove empty meta rows in db
* alphabets: new module draft
* gallery: new module draft
* submit: new module draft

### 0.2.1
* cleanup and updated language pot
* meta: support label tax in tweaks module

### 0.2.0
* github publish
