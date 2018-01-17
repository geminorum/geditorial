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
* module/audit: adminbar node depricated
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
