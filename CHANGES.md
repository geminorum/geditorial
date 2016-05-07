### 3.6.2
* series: removed useless editor button
* series: using label generators

### 3.6.1
* all: Persian translation updated
* today: fixed template fatal!
* entry: fixed admin edit notice
* reshare: using label generators

### 3.6.0
* entry: support for [gPeople](http://geminorum.ir/wordpress/gpeople)
* estimated: fixed strict notice
* event: new module
* today: new module

### 3.5.0
* modulecore: fast forward registering tax meta boxes
* modulecore: using filter for shortcodes
* modulecore: help side bar for all modules
* estimated: support for [gNetwork](http://geminorum.ir/wordpress/gnetwork) [Themes](https://github.com/geminorum/gnetwork/wiki/Modules-Themes) content actions
* estimated: minimum words setting
* tweaks: word count for excerpt & meta lead
* tweaks: filtering taxonomies before columns
* meta: now field can be an array
* entry: revised, now focused on just be section/entry

### 3.4.0
* tools: orphaned term converter

### 3.3.0
* all: changes in edit-tags screen on WP4.5, [see](https://make.wordpress.org/core/2016/03/07/changes-to-the-term-edit-page-in-wordpress-4-5/)
* modulecore: using edit tags link helper
* modulecore: column cover image small size & link to original
* modulecore: extending supported posttypes
* entry: prefix section tax
* entry: rewriting section shortcode
* magazine: prefix span tax
* magazine: section tax draft
* magazine: options to control feeds & pagination
* tweaks: checklist tree, adopted from [Category Checklist Tree](https://wordpress.org/plugins/category-checklist-tree/) by [scribu](http://scribu.net/wordpress/category-checklist-tree)
* tweaks: search box for cat taxes, adopted from [Searchable Categories](https://wordpress.org/plugins/searchable-categories/) by [Jason Corradino](http://ididntbreak.it)

### 3.2.0
* main: helping if module enabled
* gallery: exclude after global filters

### 3.1.0
* enqueue front styles for drafts & estimated modules
* skip registering alpha modules on production

### 3.0.0
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
