### 3.9.0
* core/html: moved for notice generations
* core/wordpress: redirect helpers
* core/wordpress: moved post/tax edit link generators
* main/main: skip loading on user admin
* main/helper: hopefully end of nooped strings count fiasco!
* main/helper: post modified/date diff helpers
* main/modulecore: using setup ajax method
* main/modulecore: returning register cpt/tax errors to caller
* main/modulecore: tax menu label from misc strings
* main/modulecore: check for cb method on sections
* main/modulecore: :new: inline save checker
* main/modulecore: :new: setting type: priority
* main/modulecore: :new: hook on init ajax
* main/modulecore: posttype/tax setting titles from module strings
* main/modulecore: :pray: settings args only on settings pages
* main/modulecore: :pray: skip hooking on ajax
* main/modulecore: auto including partials via class vars
* main/settings: field generator separated from module api
* main/settings: rethinking messages
* main/settings: translating help screen titles
* main/settings: module slug helper for wiki links
* main/template: before/after args on shortcode wrap helper
* module/audit: widget links with author query
* module/drafts: complete js overhaul
* module/estimated: :new: word count row on tweaks column
* module/estimated: insert priority option
* module/estimated: skip on paginated posts
* module/home: :new: featured content api for themes
* module/headings: :warning: fixed notice!
* module/headings: skip on paginated posts
* module/headings: insert priority option
* module/meta: skip lead insertion on paginated posts
* module/meta: display author info on meta column
* module/meta: rel nofollow for source links
* module/ortho: support attachment alt/caption
* module/settings: tools page for non admins
* module/settings: message handling on settings header
* module/settings: intro after method renamed
* module/spec: jquery migrate: using prop
* module/tweaks: row as an action hook
* module/tweaks: :new: page template row
* module/tweaks: row icon title for revisions
* module/today: correct translation context
* module/users: :new: user groups

### 3.8.2
* core/base: internal log method
* core/html: moved to HTML class for tag generations
* main/modulecore: using submit button helper
* main/helper: check ajax referer
* module/audit: fixed broken link form missed tax var
* module/estimated: default min words to one thousand
* module/entry: removed unused settings
* module/drafts: fixed ajax notice
* module/meta: fixed cap notice upon sanitizing
* module/settings: revamped!

### 3.8.1
* main/helper: nooped strings for count format
* main/settings: revising strings
* module/audit: preventing empty reports
* module/settings: module icon before settings title
* module/reshare: source meta before/after content
* module/book: p2p info on settings

### 3.8.0
* core/base: moved to core folder
* core/wordpress: new class
* core/html: new class
* core/date: new class
* main/modulecore: users setting type
* main/modulecore: fixed multiple checkboxs setting
* main/modulecore: submit button wrapper
* main/modulecore: settings button valuse from strings
* main/modulecore: list supported posttypes helper
* main/modulecore: `delete_with_user` default to FALSE on cpt registrations
* main/modulecore: default action css class for metabox
* main/helper: `name_admin_bar` on cpt labels
* main/helper: passing dev to the global js object
* main/metabox: new helper class
* main/settings: new helper class
* module/audit: dashboard summary by all/user
* module/audit: restrict edit posts
* module/book: also display p2p on the main cpt
* module/event: ordering events by post date
* module/entry: drafts on quickpress widget
* module/entry: autolink terms in the content
* module/entry: do shortcode on before/after custom html
* module/meta: fields via actions
* module/meta: defaults to the new fields api
* module/meta: old es/ol fields removed
* module/meta: refining admin edit column
* module/meta: lead on gNetwork content actions
* module/magazine: new meta fields api
* module/magazine: using current screen hook
* module/magazine: the issue posts list box
* module/magazine: drop admin column arg for the tax
* module/series: hide when no series found
* module/settings: reports api
* module/today: check for home instead of front page
* module/tweaks: revision count/authors
* module/home: new module
* module/users: new module
* module/headings: new module
* module/ortho: new module

### 3.7.6
* module: skip empty items on dashboard glance
* module: revert back to tweaks strings on setup
* book: hook p2p connected to content actions

### 3.7.5
* today: fixed fatal

### 3.7.4
* module: setting for admin edit page ordering
* module: p2p admin column
* module: tweaks strings moved to current screen hook
* book: tweaks strings
* entry: support for new meta fields
* entry: custom post updated messages
* today: temporarily using text type for inputs

### 3.7.3
* module: check tax query var from constants
* module: disable tax tagcloud
* module: disable auto custom cpt permalink args
* today: fixed notice on edit page column
* entry: quick edit box for section tax
* magazine: quick edit box for section tax

### 3.7.2
* tweaks: link on tax icon
* settings: delete all options via general tools
* entry: default comment status setting
* event: default comment status setting
* book: default comment status setting
* book: p2p meta renamed
* audit: tax renamed
* audit: admin box no checked on top
* meta: hide before/after input if no js

### 3.7.1
* book: p2p support
* today: setting for draft is in today

### 3.7.0
* all: internal api for at a glance dashboard widget
* book: new module
* meta: another step on migrating to the new meta fields api
* meta: moving to current screen hook
* meta: source title/source url default meta fields
* meta: columns on ajax callbacks
* magazine: using label generators
* reshare: fields moved to meta module
* series: removed useless editor button
* series: using label generators
* specs: using fields api
* specs: using label generators
* tweaks: passing current post type to string helper

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
