### 3.23.4
* main/helper: term title row
* main/module: :new: paired taxonomy bulk actions
* main/module: better handling default image size
* main/module: field type for mobile phones
* main/module: force parents option on paired api
* main/module: parent dropdown on new-post frame
* main/module: prep term ids before setting on object
* main/module: store current site id
* main/module: store parent data on paired api
* main/module: widget title via internal strings api
* main/template: post image helper revised
* main/widget: prop widget name from class
* module/attachments: check for term thumbnails
* module/attachments: empty meta/remove thumbnails actions
* module/attachments: file path as title attr on sizes
* module/contest: support for custom meta fields
* module/importer: :new: append to all taxonomies
* module/terms: clear image meta upon attachment deleted
* module/wc-widgets: :new: message widget
* module/wc-widgets: support hook on before order details
* module/wc-widgets: support hooks on wc dashboard

### 3.23.3
* main/module: more accurate hooking
* main/module: support for async decoding attr on image tags
* main/shortcode: link to preview on unpublished posts
* main/shortcode: passing posttype on id all
* main/shortcode: support for posttype as title of the list
* main/shortcode: using paired api for the term
* module/attachments: bulk delete attachments action
* module/attachments: display attachment filesize
* module/attachments: display attachment parent
* module/attachments: posttype label as title attr on searches
* module/attachments: search for attachments as thumbnails

### 3.23.2
* main/datetime: disable date separator on formatting
* main/helper: filtering label templates
* main/helper: filtering posttype message templates
* main/module: exclude by term on paired post-types
* main/module: paired to checks for viewable post statuses
* main/module: prevent notice on filtered noop strings
* main/module: reorder posttype fields args
* main/settings: field helper for paired exclude terms
* main/shortcode: better handling title on list posts
* main/shortcode: paired main posts list on supported
* main/shortcode: passing module into default args
* main/shortcode: rethinking post-type query arguments
* main/template: support filtering embed urls
* module/archives: link info on extra-tab/help-sidebar
* module/audit: better check for equal terms list
* module/audit: passing update into auto audit filter
* module/book: :new: publication category
* module/book: default terms for publication status
* module/contest: auto audit for abandoned applies
* module/course: auto audit for abandoned lessons
* module/course: reversed sort spans by name on metabox
* module/headings: filtering the toc items
* module/headings: strip tag on heading titles
* module/magazine: reversed sort spans by name on metabox
* module/meta: more default fields
* module/tube: more meta fields
* module/venue: :new: `place` short-code
* module/venue: dropdown exclude by category
* module/venue: module template helper
* module/venue: more meta fields
* module/venue: support template api

### 3.23.1
* main/module: custom content callback on template api
* main/module: filtering default terms
* main/module: late set labels on registering posttypes
* main/shortcode: anchor on title links
* main/shortcode: list term revised
* main/shortcode: tiles on list posts
* main/template: :new: span tiles renderer
* module/archives: filtering taxonomy archive links on parent plugin
* module/audit: filtering auto attributes on save post
* module/grouping: :new: module
* module/today: support for auto-audit on empty the-day

### 3.23.0
* main/listtable: skip restrict by author for large user count
* main/module: better checks for inline save posttypes
* main/module: better checks for inline save taxonomy
* main/module: check draft metabox
* main/module: check supported count before paired register
* main/module: p2p support revised
* main/module: paired render listbox metabox
* main/module: paired render tools card
* main/module: paired tweaks column attr
* main/module: passing paired tax for metabox dropdown
* main/module: render tools/reports html after methods
* main/module: tablelist render/handle for paired
* main/plugin: general filter for markdown conversion
* main/settings: support module links as configure button
* main/tablelist: actions for term name column
* main/tablelist: helper for get terms
* main/template: passing raw meta into filter
* module/audit: :new: empty fields tools
* module/audit: auto-audit for empties
* module/audit: protect empty terms from deletion
* module/audit: reports page revised
* module/book: initial paired api on publication posttype
* module/book: p2p support revised
* module/book: publication print number to ordinal
* module/event: taxonomies revised
* module/shortcodes: remove empty p tags
* module/terms: protect empty terms with meta from deletion
* module/today: auto-select date inputs
* module/wc-images: :new: module

### 3.22.1
* main/widget: :warning: passing new instance as base
* main/widget: custom title on the form
* main/widget: wrap as items on the form
* module/book: :warning: missing return on isbn links
* module/book: more preping meta fields data
* module/importer: non-forced old id numbers
* module/regional: :new: module

### 3.22.0
* main/helper: cache folder api
* main/helper: optional skip filters on post titles
* main/module: custom caps based on taxonomy
* main/module: filter thumbnail id for paired fallback images
* main/module: hierarchical slugs based on taxonomy args
* main/module: logging failed register posttype/taxonomy
* main/module: optimized page template registering
* main/module: raise resources revised
* main/module: support for default settings per module
* main/plugin: default plugin constants
* main/shortcode: better hash/group on list caching
* main/shortcode: check for term on item helper
* main/shortcode: rename item file size arg
* main/shortcode: support tiles on list terms
* main/tablelist: icon for post-id column
* main/widget: internal api for filtering
* main/widget: open/close group helpers
* main/widget: support for keys field types on form update
* main/widget: support for taxonomies in form
* module/attachments: fallback alt to title
* module/book: move up isbn methods
* module/drafts: logic separation of preview status check
* module/importer: set author/title/alt for image imports
* module/quotation: :new: module
* module/recount: icon on list table heading
* module/recount: late filters for columns
* module/recount: missed check for supported taxonomy before count
* module/recount: optional count empty data on display
* module/recount: recount on display for never counted
* module/recount: separate count logic from the filter
* module/recount: thrift mode
* module/shortcodes: :new: shortcode `term-tiles`
* module/terms: filtered supported raw fields
* module/terms: filtering setting title/desc for fields
* module/terms: meta-type for fields
* module/terms: support hiding field columns by filter
* module/wc-dashboard: :new: module
* module/wc-related: hide outofstock items
* module/wc-related: support for all taxonomies
* module/wc-widgets: api for override settings
* module/widgets: custom html: bypass cache optional
* module/widgets: namesake terms: :new: widget
* module/widgets: namesake terms: filtering the criteria
* module/widgets: search terms: filtering the criteria
* module/widgets: search terms: support for selective taxonomies
* module/widgets: search terms: taxonomy name hint
* module/widgets: search terms: widget form revised

### 3.21.0
* :wrench: psr-4 autoloading comp
* main/datetime: initial date string format helper
* main/metabox: optional display empty dropdown on paired
* main/module: :new: quick new post api
* main/module: better get setting methods
* main/module: custom meta fields for terms
* main/module: default form fields on reports context
* main/module: enqueue asset args only
* main/module: extra checks for supported taxonomies api
* main/module: filtering column title
* main/module: general hook menu helper
* main/module: hook taxonomies excluded
* main/module: icon as column title
* main/module: main button via thickbox
* main/module: passing custom key into enqueue asset
* main/module: restapi register route revised
* main/module: thumnail fallack on admin thumb column
* main/plugin: general denied helper
* main/scripts: support for vuejs2/vuejs3
* main/settings: template for field value display
* main/shortcode: general list terms method
* main/template: excludes on fields summary
* main/template: moving up meta source
* main/template: recent by posttype renderer
* main/template: support for fallback meta field
* main/widget: avoid using input type url for relatives
* main/widget: custom html on open/close/after title
* main/widget: handling update internal api
* module/archives: slug prefix on custom menu items
* module/archives: support for archive links on menu manager
* module/attachments: display attachment desc after item
* module/book: isbn link as post action
* module/book: new meta field for tagline
* module/book: new meta fields
* module/book: support for upcoming datacodes module
* module/magazine: new meta fields
* module/magazine: support for quick new post
* module/meta: new action title/url fields
* module/meta: prep meta by field for display
* module/meta: support for cover price/blurb  fields
* module/overwrite: :new: module
* module/recount: :new: module
* module/shortcodes: :new: display terms shortcode
* module/terms: media state if assigned to a term
* module/terms: prevent empty term deletion if has meta
* module/terms: support for registered custom fields
* module/terms: support for upcoming datacodes module
* module/today: :warning: correct use of trim
* module/today: better css class naming
* module/today: id/author for dummy posts
* module/today: using date separator
* module/tube: bulk/single post updated messages
* module/tube: meta fields for channels
* module/tube: prep meta by field for display
* module/uncategorized: :new: module
* module/wc-ilimited: once purchased
* module/wc-purchased: summary of purchased on user dashboard
* module/wc-related: :new: module
* module/wc-tracking: :new: module
* module/wc-units: :new: module
* module/wc-widgets: :new: module
* module/widgets: more options on search terms

### 3.20.1
* main/helper: using internal method for getting the post
* main/module: :new: paired thumbnail fallback
* main/module: hook paired to on rest api
* main/module: paired tax also sets on paired posttype
* main/plugin: :new: restrict posts api
* main/template: filter for raw meta fields
* module/widgets: :new: wprest-single widget

### 3.20.0
* main/module: better handling settings args
* main/module: filtering taxonomy excluded
* main/module: generalizing method for linked to posts
* main/module: is save post logic revised
* main/module: paired check for posttype before trashing
* main/module: paired default hooks
* main/module: paired get to posts
* main/module: paired register objects
* main/module: paired save to post
* main/module: paired saving logic revised
* main/module: tab panel for supported objects settings
* main/module: unified naming for paired api
* main/rest: dropped!
* main/settings: summary excludes as general setting
* main/shortcode: dummy markup after list items
* main/template: targeted filter for meta fields
* main/widget: form has thumbnail method
* main/widget: handle empty taxonomies on form helper
* module/alphabet: account for same locale as alternative
* module/alphabet: dummy tag for styling titles
* module/archives: better handling internal methods
* module/archives: custom title/content/slug for each supported
* module/archives: taxonomy rewrite rules revised
* module/attachments: support caption in the shortcode
* module/attachments: support videos meta on table-list
* module/book: :new: direct isbn queries
* module/book: :new: taxonomy/shortcode for publication series
* module/book: optional summary excludes
* module/book: shortcode for publication subjects
* module/home: filter dashboard draft posttypes
* module/home: support content rows on gtheme
* module/meta: rename the rest field
* module/terms: avoid using input type color
* module/wc-purchased: product sku on export filename
* module/wc-purchased: support order statuses
* module/wc-purchased: titles with sku

### 3.19.0
* :wrench: php 7.2
* assets/package: :new: libre barcode fonts
* main/helper: better contexts for cpt/tax string generators
* main/helper: get layout from current theme with fallback
* main/helper: limit on get separated
* main/helper: missing string part
* main/helper: move up default image sizes
* main/helper: parse csv from file path
* main/helper: term edit row renderers
* main/helper: woocommerce active checks
* main/metabox: css class for checklist wrap
* main/metabox: object taxonomy helper methods
* main/metabox: passing posttype on taxonomy edit link
* main/module: :new: internal api for rest endpoints
* main/module: account for empty p2p strings
* main/module: admin-page helpers
* main/module: adminpage api revised
* main/module: avoid recursive parse for register posttype args
* main/module: better naming for get dashboard term summary
* main/module: correct text-domain for multipart module names
* main/module: dashboard widget api
* main/module: default form fields only on main contexts
* main/module: enqueue asset style helper
* main/module: exclude terms from dashboard summary
* main/module: explicitly passing context into helper methods
* main/module: handle default terms using gnetwork taxonomy tabs
* main/module: header titles for settings pages
* main/module: initial support for user taxonomies
* main/module: is taxonomy supported
* main/module: list supported taxonomies with labels
* main/module: menu position for posttypes/adminpages
* main/module: metabox strings revised
* main/module: move up screen option helpers
* main/module: moving up raise resources method
* main/module: no terms string from taxonomy labels
* main/module: optional require once on core require
* main/module: override callback for mainpage content
* main/module: passing class to mainbutton
* main/module: pre-configured labels from strings
* main/module: print iframe helpers
* main/module: print page helpers
* main/module: roles excluded api
* main/module: support delete on restapi
* main/module: support gnetwork analog api
* main/module: taxonomy label helper
* main/module: unify adminpage url method
* main/module: unifying printpage menu hook
* main/scripts: :new: onscan.js packaged
* main/scripts: :up: list.js v2.3.0
* main/scripts: more enqueue helpers
* main/settings: css class for empty strings
* main/settings: field after helpers
* main/settings: field taxonomies wrapped in tab panel
* main/settings: field type for switch on-off
* main/settings: support for object field types
* main/shortcode: trim chars on post item
* main/tablelist: action conditional
* main/tablelist: new main for table list methods
* main/widget: trim chars field
* module/alphabet: :new: alternative alphabet in posts/terms
* module/alphabet: :new: first letter numbers on posts/terms
* module/alphabet: first letter uppercase
* module/alphabet: moving up the helper methods
* module/archives: better handling document title/navigation crumb
* module/archives: get taxonomy archive link
* module/archives: prioritize rest base over slugs on taxonomy archive links
* module/archives: taxonomy archive link on gtheme navigation
* module/book: default type terms
* module/importer: better handling empty rows on tablelist
* module/ortho: support for type alphabet inputs
* module/terms: :new: support for barcode field
* module/terms: :new: supported fields for arrow/label/code
* module/terms: :warning: fixed fatal
* module/terms: correct handling field classes
* module/terms: display arrow icons
* module/terms: filter meta fields for export
* module/terms: filter supported field metakey
* module/terms: passing metakey into filters
* module/users: missing methods for type taxonomy
* module/wc-limited: :new: module
* module/wc-purchased: :new: module
* module/widgets: :new: custom widget areas by action hooks
* module/widgets: rest posts: trim chars option
* module/widgets: search terms: avoid filtred search query
* module/widgets: search terms: display empty terms
* module/widgets: search terms: prefix with name
* module/widgets: search terms: search names and slugs
* module/widgets: search terms: singular name as title on multiple taxonomy setup

### 3.18.0
* main/helper: filter empty strings on arrays
* main/module: avoid checking empty subgroups on strings api
* main/module: better slug/rest base for taxonomies
* main/module: dahsboard term summary as api
* main/module: fallback for posttype labels
* main/module: force register shortcodes
* main/module: using page template for archives
* main/rest: :new: term rendered field for each post
* main/scripts: package vuejs
* main/settings: setting helper for summary drafts
* main/template: support multiple terms on meta summary
* main/template: term list with core filter
* module/alphabet: always register shortcodes
* module/archives: :new: module
* module/book: :warning: skip shortcode if p2p not enabled
* module/book: display publication archives by alphabet
* module/book: formatting on numeral fields
* module/book: linkify collection meta
* module/book: new taxonomy for publication audience
* module/book: publication status summary widget on dashboard
* module/countables: :new: module
* module/drafts: more checks on public previews
* module/entry: display entry archives by alphabet
* module/meta: :new: register meta/rest fields
* module/meta: logic separation on init fields
* module/terms: :new: register fields as term meta
* module/terms: :warning: fixed typo on filter name
* module/terms: :wrench: simplify hooks
* module/terms: prep fields settings from strings api
* module/terms: register general fields for prepared meta data
* module/terms: set current user as author of the term
* module/widgets: :new: search terms widget

### 3.17.1
* main/module: rename default method for display meta row
* main/template: filter meta summary rows
* main/template: get postmeta raw revised
* module/book: better handling isbn data
* module/importer: optional skip imports into posts with thumbnails

### 3.17.0
* main/module: :warning: fixed ignoring extra supported posttypes
* main/module: check for cap on list of supported posttypes
* main/module: default filter for calendar post row title
* main/module: fields for current settings form helper
* main/module: internal api for subterms
* main/module: internal api for subterms
* main/module: remove taxonomy submenu for supported pottypes
* main/module: settings helper for arrays
* main/module: support for extra subs on check for settings
* main/settings: count on field separate helper
* main/settings: show radio none helper
* main/template: :new: term contact helper
* main/template: fallback to taxonomies on meta summary fields
* main/template: support for other core/meta term fields
* module/collect: migrate to subterms api for collection parts
* module/contest: :new: contest sections with subterms api
* module/course: :new: course topics with subterms api
* module/importer: :new: import remote files as attachments
* module/importer: avoid updating terms
* module/importer: better column name on custom meta fields
* module/importer: check for hierarchical on tax input
* module/importer: correct key for custom meta import
* module/importer: count empty cells on mapping
* module/importer: optional skip importing posts with same title
* module/importer: passing original header key into prepare filter
* module/importer: prepare title before search for duplicates
* module/importer: raise resources helper
* module/importer: skip empty values on terms
* module/importer: skip none selected columns
* module/importer: stroing fields map history
* module/importer: support for custom meta on step three table
* module/magazine: migrate to subterms api for issue sections
* module/terms: filter support for disabling fields
* module/tweaks: attr rows optional per posttype
* module/venue: :new: place facilities with subterms api
* module/venue: missing assoc post helper
* module/venue: suffix for supported on calendar rows

### 3.16.3
* main/template: customize context on term field helper
* main/template: meta summary helper
* main/template: optional ignore of co-fields with term type
* main/template: rename term field helper
* main/widget: :new: title image for widgets
* main/widget: prep css classes
* module/book: isbn barcode helper
* module/book: meta summary helper

### 3.16.2
* main/module: tidy-up constants for taxonomy slugs
* main/widget: method for cache keys
* module/contest: total hide of the assoc tax
* module/course: missing assoc post helper
* module/course: quickedit support for byline/published fields
* module/course: template partial
* module/course: total hide of the assoc tax
* module/team: rename category taxonomy to group

### 3.16.1
* main/module: check for p2p before helpers
* main/shortcode: disabling title on list posts
* module/book: check for p2p before helpers
* module/book: main posttype shortcode as singular form
* module/book: new field for publication byline
* module/contest: default shortcodes
* module/contest: revert back to old metabox rendering
* module/course: default shortcodes
* module/course: limit meta fields to lesson posttype only
* module/course: rename the constant key for lesson format
* module/course: revert back to old metabox rendering
* module/course: span tax for course posttype
* module/tweaks: revert excluding essential taxes
* module/venue: :new: module

### 3.16.0
* main/metabox: passing supported posttypes for terms posts
* main/module: add fields for supported posttypes
* main/module: auto hook store metabox action
* main/module: better sanitize links
* main/module: clean empty legacy meta
* main/module: clean postmeta legacy
* main/module: cleanup old field keys data if updated
* main/module: custom posttype field titles on the all list
* main/module: dynamic contexts on posttype fields
* main/module: getting single field post meta
* main/module: internal cache for legacy meta data
* main/module: limit posttype fields to current module
* main/module: metabox contexts revised
* main/module: new field type for float numbers
* main/module: postmeta field api tweaks
* main/module: priority for current screen hook
* main/module: sanitize postmeta field on saved settings
* main/module: separation of logic on getting post meta
* main/module: set post meta revised
* main/module: support for custom metabox sanitize callbacks on taxonomies
* main/template: helper for meta field html
* module/contest: :warning: fixed not saving metabox data
* module/course: :new: module
* module/inquire: default metabox for status/priority taxes
* module/inquire: default terms for status/priority taxes
* module/meta: avoid sending term types as importer fields
* module/meta: avoiding double checks on field sanitize
* module/meta: column rows based on quick-edit arg
* module/meta: column rows extra proper checking
* module/meta: column rows on excerpt mode based on type
* module/meta: column rows proper exclusion of fields
* module/meta: drop author row option on settings
* module/meta: field description as info on lonebox metaboxes
* module/meta: field names revised
* module/meta: more info on import meta table
* module/meta: no need for jquery show on quick-edit preps
* module/meta: passing not enabled legacy data
* module/meta: proper handling fields on quick-edit
* module/meta: refine meta field names
* module/meta: rename metabox context callbacks
* module/meta: sanitize posttype fields revised
* module/meta: storing meta fields separately
* module/meta: upgrade posttype fields on importer filters
* module/meta: upgrade posttype fields on raw imports
* module/tweaks: avoid double input for menu order

### 3.15.9
* main/helper: :warning: correct logic for domain absence on nooped strings
* main/helper: empty string helper
* main/helper: filter results on seperated strings
* main/helper: post link helper
* main/helper: table filter by search
* main/module: apply setting on exclude from search
* main/module: delegating textdomain loads
* main/module: display field name after field title on settings
* main/module: hide adjacent post links on custom templates
* main/module: internal api for cards on tools page
* main/module: passing root folder into module construct
* main/module: refactoring supported posttypes helper
* main/plugin: :new: separated pots for each module
* main/shortcode: avoid linking to the same page
* misc/walker page dropdown: check for enabled module
* module/attachments: :warning: check for attr row option
* module/attachments: search post contents for attachment url
* module/entry: avoiding the links in autolink terms
* module/estimated: adminbar summary
* module/home: exclude from search posttypes
* module/importer: :warning: correct key for stored field map
* module/importer: better map table
* module/importer: check for empty field values
* module/importer: custom mete field
* module/importer: extra field for old ids stored on meta
* module/importer: filter bail-out on field values
* module/importer: map taxonomies for the posttype
* module/importer: passing posttype taxonomies
* module/inquire: additional taxes
* module/like: :warning: adminbar summary fixed
* module/like: check settings outside of avatars logic
* module/like: like count on admin list attr rows
* module/like: reports page
* module/magazine: meta fields for source title/url
* module/meta: check for enabled module
* module/meta: new field: published
* module/meta: new field: abstract
* module/meta: optional insert content
* module/meta: refactoring sanitize meta before save
* module/meta: revert to old type on dashboard field
* module/modified: account for sites with no published content
* module/ortho: :up: virastar 0.21.0
* module/ortho: support for custom meta field on importer
* module/revisions: :warning: correct post id for ajax nonce
* module/revisions: revision row enhancements
* module/specs: :warning: correct class for excerpt handling

### 3.15.8
* main/listtable: restrict by author helper
* main/metabox: :warning: display empty term select
* main/metabox: refresh field list styles
* main/module: current action helper
* main/module: restrict posts by author
* main/plugin: proper way to add rtl data for styles
* main/relation: initial rest endpoints
* main/settings: admin column setting helper
* main/settings: custom wrap tags in field types
* main/settings: textarea filed type for tokens/code editor
* main/shortcode: item size on list posts
* module/alphabet: count template
* module/alphabet: hide empty desc on non dd tags
* module/alphabet: more options on shortcodes
* module/attachment: restrict media by authors on list view
* module/audit: inline wrap for non-linked on summary
* module/audit: refresh summery cap
* module/audit: skip adminbar for no terms
* module/inquire: style for readonly excerpts
* module/meta: highlight field template helper
* module/terms: :new: action: move tagline to desc
* module/today: disable column on edit list

### 3.15.7
* main/helper: author filter for tables
* main/helper: html empty method
* main/helper: prep contact method
* main/metabox: autosize for editor postbox
* main/module: better handling author on table lists
* main/module: better handling filters on table lists
* main/module: check for setting before init widgets
* main/module: custom archive title on more filters
* main/module: rename remove linked term
* main/module: store thumbnail as term image on linked terms
* main/plugin: check for block editor by post
* main/plugin: wrapping metaboxes in container
* main/scripts: more helpers
* main/template: custom caption text for term images
* main/template: fallback for alt on term image
* main/widget: hidden default field for n/a
* module/audit: skip cap check on reports dropdown
* module/book: better namespace for widgets
* module/collect: bail metabox if no collection
* module/collect: better namespace for widgets
* module/contest: bail metabox if no contest
* module/like: store total counts
* module/magazine: bail metabox if no issue
* module/magazine: better namespace for widgets
* module/magazine: empty term desc on tools table
* module/magazine: rethinking tools table
* module/magazine: sync desc on tools table
* module/magazine: sync images on tools table
* module/magazine: thumbnail/image on tools table
* module/meta: :new: meta field types
* module/meta: :warning: quick edit posts fixed
* module/meta: avoid repeating extend
* module/meta: rename old meta types
* module/meta: rename old meta types
* module/ortho: :up: Virastar v0.18.0
* module/revision: filter by authors on revision reports
* module/series: better callback for series with no meta
* module/series: support admin restrict dropdown
* module/terms: :new: contact field
* module/terms: :new: tagline field
* module/terms: better contact column
* module/terms: bulk sync image titles based on linked term
* module/terms: cleanup uncategorized tool
* module/terms: merging report menus
* module/terms: n/a for image meta on toolbar summary
* module/terms: pointers links to cleanup tool
* module/terms: save contact fileds on ajax
* module/terms: uncategorized count as pointers

###  3.15.6
* main/datetime: passing fallback
* main/module: 404 override for logged in only
* main/module: hooking admin post actions
* main/module: more control over hooking ajax
* main/module: passing sub into table posts
* module/terms: clean uncategorized terms
* module/tweaks: modified as action on publish box

### 3.15.5
* main/helper: count for empty dates on edit rows
* main/module: normalize path
* main/plugin: passing already class names
* main/plugin: passing folder into module registeration
* main/plugin: prevent overriding lists with modules
* main/plugin: reduce memory usage on front
* main/settings: word wrap for module desc
* main/shortcode: check if item after is string
* main/shortcode: missing prop for title after
* main/shortcode: non-html desc for title attr option
* main/shortcode: wrap for post/term items
* main/template: caption link for image figures
* main/widget: skip linking empty titles
* module/attachments: custom permalinks with atachment ids
* module/audit: hides audit tax from public view
* module/config: flush rewrite rules on dashboard pointers
* module/config: new layouts folder
* module/ortho: :up: virastar to 0.15.1
* module/series: correct order of args for item after
* module/series: wrap post title with heading
* module/tube: optional support for channels

### 3.15.4
* main/datetime: new main class
* main/helper: better handling the day for reschedule
* main/module: append subs once
* main/module: default methods for reports/tools sub html
* main/module: default terms as help tab
* main/module: extending append sub method
* main/module: passing context into help tab methods
* main/module: skip metabox enhancements on block editor
* main/plugin: avoid deprecated notice
* main/plugin: module loading logic separated
* main/plugin: passing path into module
* main/plugin: path as prop of plugin
* module/importer: correct hook for saved action
* module/importer: default audit attribute
* module/meta: prevent duplicate metabox on block editor
* module/ortho: filtering the imported
* module/revisions: disable on block editor
* module/schedule: hide link for single cal
* module/schedule: reneamed from calendar
* module/schedule: reschedule makes time on utc
* module/schedule: reschedule post method as helper
* module/schedule: reschedule posts for nex/prev month
* module/series: prevent duplicate metabox on block editor
* module/terms: default reports sub
* module/terms: summary of term meta on admin-bar
* module/today: deafult cal on imported
* module/today: moved summary to reports
* module/today: reschedule by the day tool
* module/tweaks: skip metabox scripts on block editor

### 3.15.3
* main/helper: better prep for title/desc
* main/helper: more labels for posttypes
* main/module: initial support for blocks
* main/shortcode: datetime for published only
* main/shortcodes: post item published datetime attr
* module/headings: skip on numerial section titles
* module/modified: help tab placeholders info
* module/modified: last published date option

### 3.15.2
* main/plugin: content actions api
* main/plugin: initial support for dark mode
* main/module: optional support for rest
* main/module: help tab/sidebar tweaks
* main/module: refactoring before/after content actions
* main/module: exclude posttypes from gutenberg by default
* main/module: better exclusion for supported posttypes/taxonomies
* main/module: permalink api revised
* main/module: internal api for front-end empty/archive pages
* main/metabox: using internal count posts
* main/metabox: :warning: fixed no terms found
* main/helper: initial mustache api
* main/shortcode: next gen list of posts
* main/shortcode: support for attachments
* main/shortcode: better item/title callbacks
* main/shortcode: callback for item text
* main/shortcode: callback for post/term title 
* main/shortcode: download links for attachments
* main/settings: removed header nav wrapper
* main/scripts: :up: autosize 4.0.2
* main/template: term image api
* module/attachments: attachments shortcode
* module/book: front-end content overrides
* module/book: publications shortcode
* module/book: :warning: correct post for p2p connected list
* module/config: filters for list.js
* module/drafts: preview for viewable posttypes only
* module/entry: correct status on 404s
* module/entry: no cache headers on 404s
* module/entry: custom empty message
* module/entry: fallback title for empty content
* module/estimated: extract logic for the output
* module/revisions: :warning: check for defined `WP_POST_REVISIONS`
* module/headings: fixed not passing item into callback
* module/terms: term image template
* module/tweaks: better exclusion of supported posttypes
* module/widgets: wprest-posts: custom endpoint

### 3.15.1
* assets/package: virastar updated to 0.13.0
* main/main: using psr-4 autoload for core and main files
* main/module: create posts only for mapped caps
* main/metabox: passing terms to checklist box
* module/meta: :warning: fixed posttype on ajax
* module/tweaks: link id column to shortlinks
* module/tweaks: support id on users tablelist
* module/users: :new: author categories
* module/users: support tweaks for counts columns

### 3.15.0
* main/plugin: iframe styles
* main/ajax: js module container deprecated
* main/listtable: :new: new main component
* main/listtable: column helper moved
* main/module: create posts cap for cpts
* main/module: list posttypes based on show ui
* main/module: passing box object to check for hidden
* main/module: register help tabs on ui only
* main/module: custom string for author/excerpt
* main/module: correct callback for metabox checklist terms
* main/module: auto hook add metaboxes
* main/module: metabox tweaks
* main/module: get default settings cap from module
* main/module: rethinking save post hook
* main/metabox: preserve hidden selected terms
* main/metabox: hide empty lists on terms checklists
* main/metabox: core filter for page attributes dropdown
* main/metabox: glance helpers moved
* main/settings: display threshold pre setting
* main/setting: checkbox-panel field type
* misc/walker/user: user avatar
* module/audit: correct check for caps
* module/audit: passing user into role checks
* module/book: hide install sizes when no meta
* module/calendar: better support for post row actions
* module/cartable: support for type cartables
* module/cartable: correct check for caps
* module/cartable: passing user into role checks
* module/cartable: correct caps for user/group terms
* module/cartable: correct check for the current sub
* module/cartable: checks user for group cartable pages
* module/cartable: icons on metabox summary
* module/cartable: user cartable as dashboard widget
* module/cartable: group cartables as dashboard widgets
* module/cartable: hiding group settings if not enabled
* module/cartable: prevent access to other users
* module/config: disable module button
* module/config: using tools menu as parent when no access to settings
* module/drafts: better support for post row actions
* module/inquire: make public option
* module/inquire: locked question roles
* module/inquire: hack for users with no create posts cap
* module/modified: optional status column on dashboard widget
* module/ortho: optional on paste
* module/roles: passing the actual grant into duplicated role
* module/terms: correct taxonomy on ajax calls
* module/terms: limit sortable columns
* module/terms: correct input for color fields
* module/today: initial admin dashboard
* module/today: advanced excerpt editor
* module/tweaks: :new: group metaboxes
* module/tweaks: :new: advanced excerpt
* module/tweaks: excerpt after title
* module/tweaks: get page templates not available on ajax
* module/users: :new: re-map post authors tool
* module/views: :new: new module
* module/workflow: fixed array column not working on objects
* module/workflow: handling taxonomy columns on ajax calls
* module/workflow: draft roles setting
* module/workflow: time actions setting
* module/workflow: hide disabled status setting
* module/workflow: temporarily hiding inline/bulk edit action
* module/workflow: display current status description

### 3.14.0
* main/plugin: editorial user api
* main/module: internal api for metabox class
* main/module: returning html for columns
* main/module: better hooks for column row/attr
* main/metabox: custom metabox for excerpt
* main/metabox: better args for post parent field
* main/metabox: roles meta support for cat checklist
* main/scripts: :new: new main component
* module/audit: omit name for one posttype support
* module/audit: role based context on widget summary
* module/audit: using debounce for front submissions
* module/calendar: check for status on row actions
* module/cartable: :new: new module
* module/inquire: :new: new module
* module/ortho: button icon changed
* module/statuses: check roles for status changes
* module/statuses: default status setting
* module/statuses: check for taxonomy before posttype on screen
* module/terms: multiple role/posttype support
* module/terms: filtering supported fields
* module/tweaks: optional post slug attribute
* module/tweaks: checks posttype templates before attr action
* module/tweaks: checks status not set before attr action
* module/users: :warning: fixed clearing up user groups
* module/workflow: :new: new module

### 3.13.3
* main/module: check for init action along with posttype exists
* main/module: default filter for increasing menu order on new posts
* main/module: comment status on more modules
* main/module: passing module options into init action
* main/module: optional admin checks for role can
* main/module: metabox title info api
* main/helper: custom list of taxonomies for table term column
* main/helper: moving up common used script packages
* main/metabox: checklist overhaul
* main/metabox: refresh title action
* main/shortcode: correct order of args for helper methods
* main/settings: pre setting for post row actions
* module/attachments: restrict media library for non editors
* module/audit: factor role meta for each term
* module/config: correct submenu file for the current module
* module/roles: custom names for duplicated roles
* module/roles: :warning: passing other caps into duplicated roles
* module/roles: check for already duplicated
* module/statuses: :new: new module
* module/terms: support for role/posttype fields
* module/terms: filter title/desc for each field
* module/tweaks: support for custom statuses
* module/tube: dashboard glance items
* module/users: multiple group for each user
* module/users: roles/groups/types on the profile widget
* module/widgets: wprest: fallback for themes with no partial for rows

### 3.13.2
* main/main: check for minimum required veriosn of php
* main/module: :new: internal api for bailing on hidden metaboxes
* main/module: conditional check for embed before insert content
* main/module: saintizing twitter handles
* main/module: check if saved supported posttype currently registered
* main/module: passing description to setting methods
* main/shortcode: better handling order meta fields
* main/shortcode: conditional list tag
* main/settings: support for multiple text fields
* module/alphabet: item callback for markup
* module/alphabet: horizontal class if excerpt/description
* module/audit: refactor widget styles
* module/audit: better check for reports
* module/collect: :new: new module
* module/gallery: :new: new module
* module/like: skip scripts on embed
* module/magazine: latest issue helper
* module/users: profile edit as metabox action
* module/users: avoid using post terms cache for user objects

### 3.13.1
* main/module: internal api for dashboard setup
* main/module: skip pasing section to setting methods
* module/alphabet: refactoring shortcode markup
* module/audit: independent dashboard widget
* module/audit: warning color for not counted posts
* module/audit: cap check on rest
* module/home: filter dashboard recent posts
* module/roles: adding theme caps to editor role
* module/tweaks: :new: including meta in search
* module/tweaks: last edited user
* module/tube: more meta fields
* module/widgets: :new: wp-rest posts widget
* module/widgets: selective enabling widgets
* moduel/widgets: custom empty message

### 3.13.0
* main/plugin: rtl style on front
* main/plugin: :new: module disabled notice
* main/plugin: separate adminbar styles
* main/plugin: early fire of adminbar hook
* main/plugin: list modules helper
* main/module: check for custom key settings sub
* main/module: available calendars method
* main/module: default calendar method
* main/module: auto hook admin menu action
* main/module: auto hook template redirect/include
* main/module: internal api for front-end search form
* main/module: internal api for user in supported roles
* main/module: internal api for disabling the module
* main/module: internal api for admin menu slug/url
* main/module: internal api for module extra links
* main/module: internal api for module urls
* main/module: correct cap check for glance items
* main/module: correct setting help tabs
* main/module: better help sidebar
* main/helper: passing type for first/last of the month
* main/helper: handling non dashicon post icons
* main/helper: new key string for taxes: `back_to_items`
* main/ajax: admin/front spinner helper
* main/ajax: custom error for cap
* main/settings: admin menu supported roles
* main/settings: multiple title actions
* main/settings: correct wiki contents
* main/settings: better handling help contents
* main/settings: using callbacks for expensive help contents
* main/metabox: post edit row statuses
* main/metabox: optional title on term posts
* main/metabox: module helpers
* main/shortcode: extra attrs for wraping
* module/alphabet: :new: sort by locale
* module/audit: :new: frontend control
* module/audit: drafts in not audited summary
* module/calendar: :new: module
* module/config: :new: sort modules by title
* module/config: uniting settings form methods
* module/config: fewer hooks & args
* module/drafts: using internal api for adminbar
* module/drafts: admin bar supported roles
* module/entry: :new: custom content on archives/404
* module/event: date/time support disabled by default
* module/markdown: support wiki like links
* module/markdown: :new: summary of markdown posts
* module/markdown: :new: frontend adminbar actions
* module/markdown: :new: conversion html into markdown
* module/markdown: remove p tags after process
* module/media: correct js deps
* module/meta: :new: extend admin search into meta values
* module/ortho: disabled on non-persian locale
* module/today: :new: frontend handling
* module/today: check for saved day post
* module/home: filter gnetwork search posttypes
* module/home: override defaults only on calendars
* module/home: support recent comments widget
* module/revisions: disabled while disabled by constant
* module/tube: default meta fields

### 3.12.0
* main/plugin: check if adminbar is showing
* main/plugin: bypass module registration
* main/plugin: skip front hooks on admin
* main/module: internal api for nonces
* main/module: thumbnail/shortcode registeration api
* main/module: skip strings for non admin
* main/module: moving up column row meta helpers
* main/helper: :new: specefic rtl styles
* main/widget: form before/after
* main/widget: form form content field
* module/audit: option to include drafts in summary
* module/audit: no term notice on reports
* module/connected: :new: module
* module/drafts: hide row action for published posts
* module/like: custom strings
* module/like: max avatars
* module/like: deprecate genericons
* module/magazine: removed p2p
* module/markdown: :new: module
* module/meta: insert source only in the last page
* module/tube: category shortcodes
* module/tweaks: simpler check for posttype image size
* module/widgets: :new: widget: custom html

### 3.11.3
* main/ajax: better success method
* main/helper: support timestamp for date edit row
* main/helper: attachment link row action
* main/helper: table methodes updated
* main/helper: internal date format api
* main/helper: check for term cap before linking
* main/helper: support for filtered noops
* main/module: parent in extra args on table posts
* main/module: better metabox title actions
* main/module: p2p meta row helper
* main/module: check if posttype support excerpt/author for metabox
* main/plugin: adminbar node cap based on queried object
* main/settings: admin notices revised
* main/widget: form custom code field
* settings/options: check if fresh install
* module/attachments: :new: module
* module/audit: :new: locking terms
* module/book: skip cover widget/shortcode on not supported posttypes
* module/book: insert cover only on main posttype
* module/book: :new: meta field: reference
* module/book: :new: p2p fields: pages/volume
* module/home: :new: posttypes on feeds
* module/magazine: skip cover widget on not supported posttypes
* module/magazine: insert cover only on main posttype
* module/terms: trigger action on updating term images
* module/widgets: :new: module

### 3.11.2
* main/module: passing terms into default terms installer
* main/module: some default section titles
* main/module: custom hook for ajax helper
* main/settings: :new: calendar list option
* main/settings: :new: email setting type
* main/helper: media related methods moved
* main/helper: helper for current time html tag
* module/config: flush rewrite rules warning
* module/tweaks: reorganizing setting options

### 3.11.1
* main/module: :new: support for custom svg icons
* main/module: some default section titles
* main/helper: data count on get counted
* module/alphabet: :new: module
* module/terms: tuning scripts on edit tags screen

### 3.11.0
* main/plugin: :new: internal api for posttype templates
* main/module: filter for column thumb html
* main/module: cleanup init actions
* main/module: init settings before before settings
* main/module: posttype supports api revised
* main/module: dynamic insert priority option
* main/module: posttype supports moved up to the core
* main/module: jump to tools/reports subs on settings
* main/module: column order/image helpers
* main/module: count/order as data on columns
* main/module: p2p from/to column rows moved here form book module
* main/module: internal for table posts
* main/module: some default section titles
* main/helper: new string on tax label generator
* main/helper: posttype icon helper
* main/helper: custom posttype support for table filters
* main/helper: excerpt for title columns
* main/settings: support for links via submit button generator
* main/settings: more posttype supports and default excludes
* main/template: fallback for custom links on post images
* main/widget: :new: form field for custom links
* main/widget: form post id field only for hierarchical
* module/audit: :new: audit roles
* module/audit: correct none value on reports
* module/book: rtl control chars before/after isbn numbers
* module/book: :warning: correct check for p2p installed
* module/book: get assoc for p2p
* module/book: :new: cover shortcode
* module/book: :new: cover widget
* module/book: initial support for importer module
* module/config: default reports page for authors
* module/importer: :new: module
* module/magazine: :new: cover shortcode
* module/magazine: :warning: cover widget fixed not factoring options
* module/meta: column rows as actions
* module/meta: row actions after quick edit
* module/meta: initial support for importer module
* module/modified: reorder columns on dashboard widget
* module/ortho: first draft to bulk cleanup chars
* module/ortho: persiantools verson on settings intro after
* module/reshare: default comment status
* module/revisions: link to the post revision browser on table summary
* module/roles: :new: module
* module/team: :new: module
* module/terms: :new: support for order/image/author/color meta for each term
* module/terms: :warning: broken orphaned terms tool
* module/today: better handling meta
* module/today: insert the day badge on frontend
* module/today: fill empty day title with d/m/y/cal
* module/today: fill the current day option
* module/today: initial support for importer module
* module/today: more checks: year with cal/month with cal/day with month
* module/today: empty cal select before new quick edit
* module/tweaks: :warning: thumbnail size for column image
* module/tweaks: handle ajax quick edit requests
* module/tweaks: url link handling on user contacts
* module/users: check for selected all users on author restrict
* module/users: :new: dashboard widget for current user contacts

### 3.10.2
* main/helper: sanitize calendar
* main/settings: insert cover default setting
* main/widget: switch to transient
* main/module: helper for field icons
* main/module: p2p connect helper
* module/book: check if p2p installed on settings
* module/book: insert cover in the content
* module/book: location/volumes meta fields
* module/book: cover column removed in favor of tweaks thumb
* module/book: meta fields reordered
* module/book: fields on meta column
* module/contest: cover/order column removed in favor of tweaks thumb/order
* module/entry: order column removed in favor of tweaks order
* module/event: cleanup post columns
* module/magazine: insert cover in the content
* module/magazine: support for meta column
* module/magazine: cover/order column removed in favor of tweaks thumb/order
* module/meta: check for field context before metabox/dbx cb
* module/meta: caching field types
* module/meta: internal api for meta column
* module/meta: more fields on meta column
* module/meta: ortho support for metabox number types
* module/meta: wrapper for source links
* module/ortho: :pray: persiantools lib in tools
* module/terms: adminbar summary only on singular
* module/today: cover/children column removed in favor of tweaks thumb
* module/tube: :new: module
* module/tweaks: :new: custom columns for user list table
* module/tweaks: :new: order column
* module/tweaks: check if posttype support thumbnail before column
* module/tweaks: hide author column if has attr action
* module/users: user types on tweaks column

### 3.10.1
* main/ajax: rest api in global js object
* main/ajax: correct send success message
* main/ajax: default nonce for wp rest
* main/metabox: hide choose box for empty tax
* main/metabox: wrap arg for choose box
* main/module: default setting for adminbar api
* main/module: default cap key
* main/module: register post rest base fallback to archive slug
* main/module: skip content actions on embed
* main/module: new before settings method
* main/plugin: better file loader
* main/plugin: using const as base
* main/plugin: icon api working draft
* main/plugin: adminbar api revised
* main/settings: new wp page heading structure, [ref](https://make.wordpress.org/core/?p=22141)
* main/settings: extra args for sub url helper
* main/settings: default setting for supported/excluded roles
* main/settings: default setting for admin bulk actions
* main/settings: default setting for dashboard widgets
* main/settings: default message for synced items
* main/helper: more table pre columns/args
* module/audit: adminbar node deprecated
* module/book: p2p type name changed
* module/config: missed module name check
* module/like: adminbar summary of post
* module/like: check stored ip for guests
* module/like: check for posttypes
* module/pitches: new pool tax
* module/revisions: bail if no revision on report table
* module/shortcodes: adminbar summary of post
* module/terms: adminbar summary of post
* module/users: author default restrictions
* module/users: groups on user tweaks column

### 3.10.0
* assets/js: rethinking structure
* assets/js: jquery sortable from packages dir
* main/main: first attempt for an adminbar api
* main/main: na helper method
* main/ajax: get nonce for each module
* main/helper: term title helpers
* main/helper: bulk post updated messages generator, [ref](https://github.com/morganestes/post-updated-messages)
* main/helper: class const for base string
* main/helper: register helper for asset packages
* main/helper: check for drafts on post title row
* main/helper: default fallback not to current user
* main/metabox: helper for assoc dropdown
* main/metabox: helpers moved from module
* main/metabox: helpers moved from helper
* main/module: is content insert helper
* main/module: better handling post updated messages
* main/module: internal cache for post meta
* main/module: settings form request defaults helper
* main/module: better hook helpers
* main/module: screen option/limit hlper for current sub
* main/module: post tag default for every cpt
* main/module: helper for checking settings sub
* main/module: basic o2o support
* main/module: scripts on other settings pages
* main/module: remove internal cap checking for each field
* main/module: posttype supports settings
* main/module: :warning: passing array post in settings form
* main/module: simpler post update messages
* main/module: posttype fields helper for js
* main/plugin: merge js args for existing module
* main/settings: submit button helper
* main/settings: default button strings moved
* main/settings: check for module with no desc
* main/settings: new default message string
* main/settings: delete buttons with js confirmation
* main/settings: custom settings field renderer
* main/settings: removing processed keys from request uri
* main/settings: correct check if exclude is an array
* main/settings: cpt/tax names along with labels
* main/shortcode: :new: new main helper class
* main/template: meta field helper moved up
* main/template: trim arg for meta fields
* module/book: basic template helpers
* module/book: custom titles for p2p lists
* module/book: helper for post item in p2p lists
* module/config: renamed from settings
* module/config: :new: search/filter for module list/options via [List.js](http://listjs.com)
* module/drafts: :new: public preview
* module/drafts: check cap for each draft
* module/entry: :up: utilizing internal helpers for shortcodes
* module/entry: first attempt on supporting shortcake
* module/entry: :warning: correct pattern for unicode section names
* module/entry: custom box for section tax in edit post
* module/home: support recent posts widget
* module/magazine: :up: utilizing internal helpers for shortcodes
* module/magazine: better handling assoc posts
* module/magazine: upgrading template helpers
* module/magazine: cover widget updated
* module/magazine: tools page revised
* module/magazine: not wrapping assoc links
* module/meta: auto insert source moved from reshare module
* module/meta: correct focus on quick edit
* module/meta: tools page revised
* module/meta: better handling meta import
* module/meta: lead on admin edit column in excerpt mode
* moduel/meta: :warning: importer helper sep fixed
* module/meta: fields as args in js
* module/modified: :new: shortcodes for post/site
* module/reshare: post update messages
* module/revisions: check each post for revision max count
* module/revisions: skip posts with no revison in the overview
* module/revisions: nonce for each post
* module/series: :up: utilizing internal helpers for shortcodes
* module/shortcodes: :new: module
* module/ortho: exporting meta fields as front matter on md download
* module/pitches: post updated messages
* module/pitches: support for restrict mange posts/dashboard glance items
* module/reshare: support for restrict mange posts/dashboard glance items
* module/terms: orphaned convertor moved from settings module
* module/terms: limit screen option
* module/terms: filter by posttype on overview
* module/terms: using table actions on overview
* module/tweaks: check for cap before additional info
* module/tweaks: switch to letter count for excerpts
* module/tweaks: :new: comment status on attr column
* module/tweaks: :new: post name on attr column
* module/tweaks: :new: thumbnail column

### 3.9.15
* main/modulecore: simplifying init/ajax hooks
* main/modulecore: settings form before/after helpers
* main/modulecore: revert linked term method
* main/helper: correct camelcase and class name for post type dates
* main/helper: post title helper
* main/helper: unifying table columns args
* main/helper: readable status names
* main/settings: button confirm as method
* module/audit: :new: reports by user
* module/contest: :new: delete terms on tools table
* module/contest: two column for linked and same slug posts on terms table
* module/content: :warning: sync linked posts on ajax
* module/home: support calendar posttypes
* module/ortho: :new: download button for quick tags
* module/ortho: :new: nbsp button for quick tags
* module/magazine: :new: delete terms on tools table
* module/magazine: two column for linked and same slug posts on terms table
* module/magazine: :warning: sync linked posts on ajax
* module/meta: author field renamed to byline
* module/modified: localized jquery timeago for the timestamp
* module/revisions: :new: overview and cleanup post revisions
* module/today: :new: quick edit support
* module/today: convert back localized numbers
* module/tweaks: simple filter hook for tax info
* module/tweaks: :warning: dropped $ sign!
* module/users: another try on user reports
* module/users: disable username sanitizing
* module/users: adding settings descriptions

### 3.9.14
* wordpress/database: exclude default statues from taxonomy count/not
* main/helper: more time diff helpers
* main/modulecore: thickbox for thumb column images
* module/audit: consistent l10n contexts
* module/audit: option to not count not audited
* module/audit: caching dashboard summary
* module/meta: force empty meta to array on import
* module/modified: consistent use of var names
* module/ortho: lib version/repo/demo links
* module/terms: :warning: fixed not counting all posts

### 3.9.13
* main/helper: revising text/number filters
* main/main: styles for edit comments/term base
* main/modulecore: api for trashed linked post/tax
* module/meta: reorder tab indexes if subtitle field exists
* module/ortho: :new: support taxonomies
* module/ortho: changing icon
* module/ortho: :up: [virastar](https://github.com/juvee/virastar/) updated to v0.12.0
* module/pitches: :new: new module
* module/terms: :new: cleanup terms action for uncategorized table
* module/tweaks: :new: user column on edit comments screen

### 3.9.12
* main/modulecore: try not to register ui when it's possible
* main/modulecore: rethinking strict manage posts
* main/modulecore: using core helper for multi sort
* main/modulecore: try to store selected type settings as boolean
* main/modulecore: unifying dashboard glance style classes
* main/modulecore: module slug helper
* ‌main/template: check for linking on label generator
* module/audit: :new: count not audited by posttype/author
* module/meta: switch to ul on edit column rows
* module/meta: action hook on edit column rows
* module/meta: option to disable post author on meta column
* module/ortho: :up: [virastar](https://github.com/juvee/virastar/) updated to v0.11.0
* module/tweaks: check if posttype support author for attr column

### 3.9.11
* assets/js: console logging the module
* main/modulecore: handling dashes in module names
* main/modulecore: :warning: space before new admin body class
* module/book: more chars for titles on p2p connected
* module/meta: workaround for weird css bug on no-js
* module/meta: edit pages columns hook
* module/meta: missing closing div on box template
* module/revisions: check for cap before bulk actions
* module/revisions: do not load on front-end
* module/terms: do not load on front-end
* module/terms: fewer calls for tax list
* module/terms: hide id column
* module/tweaks: do not load on front-end

### 3.9.10
* module/modified: check for cap before linking authors in dashboard
* module/today: styling admin edit date stamp
* module/tweaks: sortable id column

### 3.9.9
* main/helper: nbsp before hellip on trim chars
* main/helper: future status for term posts
* main/modulecore: api for sortable tax column, [see](http://scribu.net/wordpress/sortable-taxonomy-columns.html)
* main/modulecore: exclude attachment posttype for customized modules
* main/modulecore: internal settings fields handler
* main/settings: new taxonomies field type
* module/entry: renamed in class cache key to sections
* module/meta: :new: new highlight meta field
* module/modified: :new: new module
* module/ortho: :new: sanitizing number fields
* module/terms: :new: new module
* module/today: missing column strings
* module/tweaks: author in attr column
* module/tweaks: no need for posttype arg

### 3.9.8
* module/ortho: giving back focus to title input

### 3.9.7
* core/base: dep func helper
* main/helper: removed direct use of gen tax labels
* main/helper: prep desc helper
* main/modulecore: internal api for connecting posts to terms
* main/modulecore: buttons/scripts internal api
* main/modulecore: custom option all string for restricted dropdowns
* main/modulecore: lighter query for linked posts count
* main/modulecore: default nooped strings
* main/modulecore: printf nooped helper
* main/settings: wrapping sections
* main/template: replace deprecated helper
* main/template: disable title attr if no link on meta link gen
* module/audit: custom menu name
* module/book: correct link back to connected items in publication edit page
* module/contest: contests/applies on dashboard glance
* module/contest: using current screen for meta box
* module/contest: tools adopted from magazine module
* module/event: custom menu name for taxes
* module/event: remove old venue tax remains
* module/magazine: bulk tool to reconnect posts to terms
* module/magazine: post from term using selected ids
* module/magazine: tweaks attr row for connected items
* module/meta: adding dep func to old meta helpers
* module/meta: using new method for meta lead on content actions
* module/meta: default terms for column headers
* module/meta: dropping the old code
* module/ortho: more keyword for ms word footnotes
* module/series: getting meta info if one post is in the series
* module/series: title after arg for the shortcode
* module/tweaks: more tax exceptions
* module/tweaks: using column title helper

### 3.9.6
* core/date: :warning: fixed fatal
* wordpress/module: :warning: fixed fatal

### 3.9.5
* core/number: :new: new core class
* core/html: table side generator
* wordpress/user: :new: new core wp class
* main/ajax: :new: new helper class
* main/main: skip loading on network/user admin ajax
* main/main: reorder checking for style enqueue
* main/modulecore: internal for checking caps
* main/modulecore: returning handle after script enqueue
* main/modulecore: passing base as script filename prefix
* main/modulecore: p2p helpers
* main/modulecore: column icon helper
* main/modulecore: admin class internal
* main/modulecore: exclude attachment posttype by default
* main/modulecore: default caps
* main/modulecore: wordpress helper methods for admin links
* main/modulecore: enqueue scripts if no args
* main/modulecore: skip checking super admin for author box
* main/modulecore: :warning: init settings before validate
* main/metabox: default callback for checklist terms
* main/helper: joiner method
* main/helper: using dashicon helper
* main/helper: :warning: getting correct post for modified time
* main/helper: ajax helper for unknown action
* main/helper: posttype edit message generator
* main/helper: simplify post modified
* main/helper: check time diff with local time
* main/helper: trim chars method with full text as title attr
* main/helper: :new: date helper wrappers
* main/settings: using internal for request keys
* main/settings: html wrap open/close helper
* main/settings: override core setting section
* module/all: passing args into restrict manage posts filter
* module/audit: trim long term title on dashboard widget
* module/book: :new: description meta for p2p connections
* module/book: :new: p2p connected info on tweaks extra column
* module/estimated: word count moved to attributes column
* module/meta: order terms dropdown by name
* module/meta: using column icon helper
* module/ortho: complete script overhaul
* module/ortho: :up: [virastar](https://github.com/juvee/virastar/) updated to 0.10.0
* module/revisions: :new: new module
* module/settings: skip loading on frontend
* module/settings: :warning: not merging updated options
* module/settings: :new: tools tab: plugin option overview for super admins only
* module/tweaks: :new: new attributes column with status/published/modified dates
* module/tweaks: unordered list tag for column rows
* module/tweaks: more tax exceptions
* module/tweaks: :warning: fixed page extra column

### 3.9.4
* core/text: :new: core class
* wordpress/module: :new: new helper class
* wordpress/taxonomy: :new: new helper class
* main/modulecore: default tax arg set to no meta box
* main/modulecore: core meta box override helpers
* main/modulecore: prevent empty tax meta boxes
* main/modulecore: cap type/query var from constants
* main/modulecore: replace dot in script handles
* main/settings: id/name generator callback
* main/helper: new [posttype labels](https://make.wordpress.org/core/?p=20405) in WP4.7
* main/helper: html count as internal api
* module/drafts: using internal for caps
* module/estimated: check for posttype count
* module/magazine: span tax is now hierarchical
* module/meta: skip striping html on lead
* module/specs: check for terms before meta box
* module/specs: correct configure link for metabox
* module/series: using current screen for meta box
* module/settings: hiding conversion tool when no orphaned taxes
* module/tweaks: :new: id column
* module/tweaks: support posttype template, [see](https://make.wordpress.org/core/?p=20437)

### 3.9.3
* all: using constant helper method
* core/html: check for empty array before table code
* main/main: style for admin users page
* main/helper: correct gmt timestamp and human time diff
* main/modulecore: support for date-picker via [gPersianDate](https://github.com/geminorum/gpersiandate)
* main/modulecore: column title fallback
* main/templates: moving up meta template helpers
* main/settings: html attr class helper renamed
* module/book: publication cover in content actions
* module/meta: option to overwrite author meta
* module/meta: option to disable lead insertion
* module/users: post count summary in users list table

### 3.9.2
* main/main: skip checking for folders!
* module/meta: missed string for column row
* module/series: correct check for series count
* module/tweaks: attachment summery for each post
* module/tweaks: check for manage terms cap before linking on taxes

### 3.9.1
* core/html: method renamed, fixed fatal on PHP5.6

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
