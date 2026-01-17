# gEditorial Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [3.33.0]
* internal/core-post-type: parent module on post-type args
* internal/core-post-types: combine register sub-methods
* internal/core-taxonomies: bail setting if capabilities are customized
* internal/core-taxonomies: better handling rest base
* internal/core-taxonomies: parent module on taxonomy args
* internal/meta-box-custom: excerpt box revised
* internal/module-links: auto hook calendar linked post
* internal/paired-assignment: app for ordered connections
* internal/paired-core: apply ordering from term relations
* internal/paired-core: custom capabilities for paired taxonomy
* internal/paired-core: terms related as paired setting
* internal/post-type-fields: access check for draft posts
* internal/post-type-fields: customize name attr format
* internal/quick-posts: new-post hooks revised
* internal/raw-imports: support multiple data files
* internal/rest-api: get route helper
* internal/settings-fields: register fields via constant
* internal/sub-contents: check post-type exists on searchable fields
* internal/sub-contents: table overview on reports
* internal/sub-contents: treat comment date as modified
* internal/template-taxonomy: avoid override on product taxonomies
* internal/template-taxonomy: support for custom archives
* internal/view-engine: check if partials path exists
* main/datetime: apply calendar on helpers
* main/datetime: dynamic default calendar based on current locale
* main/datetime: format by calendar with fallback
* main/datetime: make my-sql from array
* main/datetime: the day array by calendar
* main/module: contexts on screen option pre page
* main/plugin: drop dark mode styles
* main/plugin: extend allowed url protocols
* main/plugin: setup services helper
* main/plugin: style for woo-commerce admin
* main/scripts: refresh api for color-box iframes
* main/settings: context url revised
* main/settings: passing context into header title helper
* main/settings: support for ortho on settings fields
* main/shortcode: core filter for image css class
* main/shortcode: list post assigned to a term
* main/shortcode: passing singular term into term title renderer
* main/table-list: correct pagination for terms
* main/template: event date/time form timespan
* main/template: hide term intro on name only
* main/template: support for more media short-codes
* main/visual: support for all icon formats on fallback
* module/alphabet: display empty terms option
* module/alphabet: filter posts by term
* module/alphabet: wrap the description/excerpt tags
* module/archives: support for term archive pages
* module/badges: migrate to taxonomy overview on reports
* module/book: rename p2p short-code
* module/book: support for quick new-post
* module/bookmarked: support for behkhaan books
* module/bookmarked: support more option types
* module/byline: check for assign access of targets
* module/byline: exclude taxonomies from empty terms
* module/byline: exclude taxonomy from empty terms
* module/byline: short-circuit post count by users
* module/byline: support tax query on products short-code
* module/config: empty modules message
* module/config: export enabled module settings
* module/config: import options as object for each module
* module/config: passing context to handlers
* module/course: support for date fields
* module/estimated: meta-ley from constants
* module/happening: override dates by meta
* module/happening: support for status taxonomy
* module/home: apply post-types on archives widget
* module/identified: already identified warning on new posts
* module/identified: binary-eye button on admin-bar
* module/identified: check for already identified on new-post template
* module/identified: post identify logic separated
* module/identified: sanitized to isbn-13
* module/identified: support for identifier on meta array
* module/iranian: state/city info for post-codes
* module/isbn: convert initial meta fields to isbn13
* module/isbn: prep meta fields
* module/like: meta-ley from constants
* module/magazine: support for date fields
* module/magazine: support for thumbnail fallbacks
* module/meta: support for lat/lng type field
* module/meta: support for plate type fields
* module/national-library: default title on new post via admin
* module/national-library: fallback via altering isbn format
* module/national-library: filter for table css class
* module/national-library: fipa on saved supported posts
* module/national-library: header button on new posts
* module/national-library: new-post hints revised
* module/national-library: scrape pool tool
* module/ortho: avoid trimming the first letter on word footnotes
* module/ortho: handle dash in input
* module/ortho: support for hook input types
* module/ortho: support for slug fields
* module/physical: 🆕 module
* module/short-codes: :new: shortcode for assigned posts
* module/socialite: prep meta fields
* module/statistical: 🆕 module
* module/tabs: before/after content hooks
* module/tabs: support for post comments
* module/terms: convert image attachment id to full url
* module/terms: same order as module on script
* module/today: display same day on other calendars
* module/today: drop search form support
* module/today: front page handling revised
* module/today: handling the day fully revised
* module/today: link structure revised
* module/today: migrate to meta-box internals
* module/today: navigation buttons
* module/today: new post buttons as array
* module/today: support for meta fields on the post-type
* module/today: support for shortcodes
* module/today: support year on rewrite rules
* module/today: the day ical on supported archives
* module/today: the day prefix on the post list
* module/uploader: 🆕 module
* module/venue: cap-type/viewable for primary post-type
* module/views: meta-ley from constants
* module/wc-checkout: 🆕 module
* module/wc-identify: custom action hook for gtin data
* module/wc-identify: gtin support on aws
* module/wc-shortcodes: 🆕 module
* module/wc-terms: support for list of assigned posts other than products
* module/wc-terms: support for sub-terms after archive description
* module/wc-tracking: renamed from wc-postal
* module/wc-widgets: passing sidebar args only if exists
* service/admin-screen: migrate from actions module
* service/admin-screen: restrict by taxonomy settings
* service/barcodes: header button for binary-eye
* service/calendars: check for viewable data
* service/calendars: disable constant for ical
* service/calendars: filter for event description
* service/calendars: support for ical format
* service/content-actions: 🆕 service
* service/file-cache: 🆕 service
* service/location: singular location from the post
* service/post-type-fields: date field helpers
* service/rest-api: filter for all terms rendered props
* service/rest-api: terms rendered field for supported post-types
* service/taxonomy-fields: 🆕 service
* service/term-hierarchy: prioritize the child terms on single selected
* service/term-relations: rest field for taxonomy with post prop

## [3.32.0]
* app/assignment-dock: 🆕 app
* internal/core-admin: support for ajax on multiple column
* internal/core-admin: tidy up using post-type icons
* internal/core-constants: customize constants via options
* internal/core-includes: access filtered module path
* internal/core-post-types: header button to manage
* internal/core-post-types: support for `custom_icon` setting
* internal/core-roles: also check for edit-post cap
* internal/core-taxonomies: content rich support
* internal/core-taxonomies: quick access as admin-bar node
* internal/core-taxonomies: support for `custom_icon` setting
* internal/core-taxonomy: define target objects
* internal/core-taxonomy: integrated settings
* internal/core-thumbnails: side cover for tabloid overview
* internal/frame-page: main-link url helper
* internal/post-type-fields: support for auto-complete
* internal/rest-api: setting for default permission
* internal/sub-content: support for colors as css variables
* internal/sub-contents: data summary via view engines
* internal/template-taxonomy: custom empty items message
* internal/template-taxonomy: render term intro before archives
* internal/view-engines: revised to support override by theme
* main/datetime: support for year only on date fields
* main/helper: contact icons from bootstrap
* main/helper: support for none/raw-url icons
* main/plugin: proper die message wrapper
* main/plugin: support for woo-commerce styles
* main/settings: setting sections revised
* main/settings: support for fields on quick-edit
* main/table-list: support for custom object as posts
* main/template: callback args revised for meta summary
* main/template: term introduction
* main/template: title swap on meta links
* main/visual: icon helper
* main/visual: passing icon on the original format
* module/achieved: 🆕 module
* module/alphabet: check for empty post-type/taxonomy
* module/alphabet: custom head tag
* module/alphabet: support for list mode
* module/alphabet: support for meta as link title
* module/attachments: optional caption/description on short-code
* module/audit: migrate to module-settings
* module/bookmarked: custom desc for attachment by caption
* module/bookmarked: custom icon for attachment by mime
* module/bookmarked: fidibo re-brand
* module/bookmarked: support data summary and woo-commerce
* module/bookmarked: support for e-pub/navaar/wikipedia option types
* module/byline: 🆕 module
* module/identified: redirect to not found
* module/identified: support for email type
* module/interested: 🆕 module
* module/isbn: migrate data from book module
* module/meta: support for `reference` field
* module/national-library: custom user agent
* module/national-library: handling isbn on parsing
* module/national-library: public api
* module/originated: 🆕 module
* module/ortho: 🆙 virastar v0.22.1
* module/people: 🆕 module
* module/socialite: better support for icon rendering
* module/socialite: support fields on post-types
* module/socialite: support for fidibo/goodreads/tiktok/wikipedia fields
* module/subjects: 🆕 module
* module/terms: support for born/dead/source/embed/url/subtitle fields
* module/terms: term introduction template
* module/tweaks: exclude product post-type
* module/units: support for legacy meta-keys
* module/wc-attributes: 🆕 module
* module/wc-identify: 🆕 module
* module/wc-identity: 🆕 module
* module/wc-meta: 🆕 module
* module/wc-postal: support for tracking url token
* module/wc-terms: 🆕 module
* service/admin-screen: 🆕 service
* service/admin-screen: quick edit support
* service/custom-post-types: 🆕 service
* service/custom-taxonomy: 🆕 service
* service/individuals: apply name parser on people format
* service/individuals: make full-name api
* service/locations: 🆕 service
* service/object-hints: 🆕 service
* service/post-type-fields: refined search for dates
* service/taxonomy-taxonomy: 🆕 service
* service/term-hierarchy: avoid double id on quick/bulk edit
* service/term-hierarchy: is single term check
* service/term-relations: 🆕 service

## [3.31.0]
* internal/bulk-exports: avoid column header duplicates
* internal/bulk-exports: column width for post props
* internal/bulk-exports: default taxonomy by primary prop
* internal/bulk-exports: field average data length
* internal/bulk-exports: initial filter for all taxonomies
* internal/bulk-exports: prep date props
* internal/bulk-exports: support for assigned posts to a term
* internal/core-roles: override module cuc by taxonomy
* internal/core-taxonomies: before/after action hooks on meta-boxes
* internal/core-taxonomies: is viewable support for terms
* internal/dashboard-summary: helper for hooking term widget
* internal/meta-box-supported: action context based on current screen
* internal/paired-front: support paired list on post-tabs
* internal/post-type-fields: apply core filter for quick edit enabled
* internal/post-type-fields: support for bulk-edit posts
* internal/post-type-fields: support for multiline on quick/bulk edit
* internal/post-type-fields: support template new-post
* internal/post-type-fields: unified hook setup ajax
* internal/quick-posts: check for post-type supports
* internal/rewrites: renamed from rewrite-endpoints
* internal/sub-contents: support data on post-tabs
* internal/taxonomy-overview: reports sub for overview and exports
* internal/template-post-type: support for new-post on front-end
* main/helper: generate security token
* main/helper: prep contact support for icon
* main/meta-box: bail if no parent module
* main/meta-box: fills the meta by query data only on new posts
* main/meta-box: support name attr on front
* main/module: global summary on edit-form with exports
* main/module: using reports roles for exports
* main/settings: frontend search option
* main/settings: processing all done
* main/short-code: clear id type passed in
* main/template: getting raw field on source url
* module/attachments: use static tool action names
* module/audit: factoring helpers
* module/book: span tile on archives if year-span taxonomy enabled
* module/classified: 🆕 module
* module/config: side navigation on main contexts
* module/dead-drops: post-title and close button on drop-zone
* module/dead-drops: support for unicode filenames
* module/drafts: disable robots on public previews
* module/drafts: post state for public previews
* module/drafts: table view of public previews for supported post-types
* module/identified: add new post button with identifier code
* module/identified: better support for gtin
* module/identified: not-found identifiers on queries
* module/identified: search with barcode scanner button on supported
* module/identified: support for front-end search by identifier
* module/identified: support for queryable identifier types
* module/iranian: custom roles settings
* module/iranian: imports on module settings
* module/iranian: tools on module settings
* module/iranian: update location data
* module/isbn: 🆕 module
* module/isbn: handle isbn data on not-found queries
* module/isbn: handling bibliographic data
* module/isbn: support for search on all fields
* module/isbn: support raw data on short-code
* module/isbn: support woo-commerce products attributes
* module/isbn: wc support revised
* module/national-library: 🆕 module
* module/national-library: parsed fipa from cached
* module/national-library: support template new-post
* module/national-library: validate data before external request
* module/national-library: wc support revised
* module/papered: passing flags into item data
* module/personage: :new: import from full-name
* module/personage: tools on module settings
* module/phonebook: moving up prep address
* module/phonebook: search for sanitized numbers
* module/ranged: support more from units module
* module/ranged: support positions
* module/tabloid: drop support for custom title/attr
* module/tabloid: support for term overviews
* module/tabs: 🆕 module
* module/tabs: filter tab title
* module/tabs: preserve argument order on callback
* module/terms: support for venue field
* module/wc-postal: support for tracking id on pwoosms shortcodes
* service/advanced-queries: append meta to posts search
* service/paired: precedence of the print title/date fields
* service/post-type-fields: replace token support
* service/term-hierarchy: support bulk-edit for single select term

## [3.30.0]
* internal/core-admin: display taxonomy states
* internal/core-admin: multiple supported column for taxonomies
* internal/core-admin: state background color based on the term color
* internal/core-post-type: support for status taxonomy prop
* internal/core-roles: moved from module main
* internal/core-roles: override module cuc
* internal/core-row-actions: force default term
* internal/core-users: renamed from core-roles
* internal/dashboard-summary: support summary by paired
* internal/post-meta: hook column row revised
* internal/post-type-fields: enqueue for edit screens
* internal/post-type-fields: hook edit screen
* internal/post-type-fields: initial support for distance type fields
* internal/post-type-fields: moving up render/store meta-box
* internal/post-type-fields: support access checks on quick-edit
* internal/post-type-fields: support for area type
* internal/post-type-fields: support meta caps on access checks
* internal/settings-core: support side-box for all main contexts
* internal/settings-roles: mover from module main
* internal/sub-contents: check for supported comment type
* internal/sub-contents: support for selectable fields
* internal/sub-contents: support for type options
* internal/sub-contents: thrift mode on column rows
* main/info: abbreviation for units
* main/meta-box: passing data unit/length to html tags
* main/parser: :new: main
* main/scripts: click to clip api
* main/scripts: color-box support full width on mobile
* main/scripts: initial support for chart.js
* main/scripts: support for dropzone.js
* main/settings: constant for hiding credits
* main/tablelist: support query for all posts/terms
* main/template: move field methods to post-type-fields service
* module/attachments: support summary pointer
* module/bookmarked: :new: module
* module/bookmarked: support for Behkhaan
* module/bookmarked: support for logo extension
* module/chronicles: support for release/expire fields
* module/config: primitive and meta caps for editorial pages
* module/dead-drops: :new: module
* module/dead-drops: additional salt
* module/directorate: :new: module
* module/happening: :warning: renamed from `event`
* module/housed: support for units
* module/housed: support sub-content data for visiting
* module/importer: :new: wizard for new/append/override terms
* module/importer: apply single term prop on taxonomy assignments
* module/importer: better user data handling
* module/importer: extract callback for row checks
* module/importer: handling steps revised
* module/importer: select single term via service
* module/importer: source mime-types unified
* module/importer: static cover image import for posts
* module/importer: support more than one upload button
* module/importer: toolbox card for first steps
* module/iranian: city/country summary reports
* module/iranian: data updated
* module/meta: support for duration field type
* module/mobilized: :new: module
* module/overwrite: exclude paired taxonomies
* module/phonebook: prep address revised
* module/pointers: render on static-covers summaries
* module/positions: check for profile before action/column
* module/remoted: check for upload url
* module/socialite: support for Behkhaan
* module/static-covers: support for secondary post covers
* module/tabloid: edit action button
* module/terms: support for `fullname` field
* module/users: tools for duplicate/remove current roles
* service/header-buttons: :new: service
* service/header-buttons: migrate old buttons
* service/post-type-fields: prep meta row moved
* service/primary-taxonomy: :new: service
* service/search-select: result extra for terms
* service/term-hierarchy: quick-edit support for single select taxonomies
* service/term-hierarchy: single term select prop
* service/term-hierarchy: support for auto assigned taxonomies

## [3.29.2]
* internal/settings-core: render upload field
* module/config: export/import all options
* module/execution: sub-contents for executive board
* module/next-of-kin: count for underline on module name
* module/next-of-kin: father name field
* module/static-covers: handle roles for reports
* module/static-covers: i18n support on mustache
* module/was-born: handle roles for gender taxonomy
* service/search-select: use of search column arg for title only

## [3.29.1]
* internal/core-taxonomies: term parents as views
* main/plugin: untitled string helper
* main/scripts: support for plupload v3
* module/athlete: calculate bmi as pointer
* module/athlete: move mass/stature units
* module/diagnosed: support sub-contents for medical records
* module/equipped: move shoe/shirt/pants units
* module/remoted: :new: module
* module/units: total number units revised
* service/post-type-fields: default field icons
* service/term-hierarchy: reverse ordered terms

## [3.29.0]
* app/import-items: clear already added rows
* internal/bulk-exports: cap check on each targets
* internal/bulk-exports: general filters for customs/taxonomies
* internal/bulk-exports: refactor generating data
* internal/bulk-exports: support for data length
* internal/bulk-exports: support for post-type targets
* internal/core-post-types: auto-save control
* internal/core-row-actions: callback suffix for main-link
* internal/dashboard-summary: limit summary to parents
* internal/paired-core: append identifier code
* internal/paired-core: main getter from/to revised
* internal/paired-meta-box: check if assign is available
* internal/paired-reports: primary post-type overview
* internal/paired-reports: support taxonomies on overview
* internal/post-type-fields: moving up common methods
* internal/post-type-overview: summary table support
* internal/post-type-overview: support for paired items
* internal/post-type-overview: support for units columns
* internal/settings-help: disable help tabs by constant
* internal/sub-contents: :new: general app
* internal/sub-contents: better handling meta stores
* internal/sub-contents: comment author on info
* internal/sub-contents: ensure hidden fields
* internal/sub-contents: extensive sanitizations on fields
* internal/sub-contents: general strings for js
* internal/sub-contents: preserve the original info on summary
* internal/sub-contents: refresh the meta-box table on close
* internal/sub-contents: render iframe content
* internal/sub-contents: storing order as comment karma
* internal/sub-contents: support for importer module
* internal/sub-contents: support for read-only/searchable fields
* internal/sub-contents: support for sort/order
* internal/sub-contents: support for summary
* internal/sub-contents: support more types on sanitization
* internal/sub-contents: support refining data counts
* internal/sub-contents: support unique fields
* internal/sub-contents: unified register routes
* main/info: data from post-code
* main/plugin: base country with fallback to woo-commerce
* main/plugin: enforce default auto-load options
* main/plugin: no-information notice helper
* main/scripts: support for jsbarcode/qrcodesvg
* main/template: accept field object of getting meta field data
* main/template: filter empty value on getting meta field data
* module/agenda: :new: module
* module/athlete: :new: module
* module/audit: strip from tabloid terms rendered
* module/banking: migrate to general app
* module/book: support for isbn as identifier
* module/chronicles: :new: module
* module/driving: :new: module
* module/identified: avoid blocking other filters on line-discovery
* module/importer: avoid skipping one column data
* module/importer: better handling the taxonomies
* module/iranian: optimized bank logo svgs
* module/iranian: support for bank logos
* module/jobbed: :new: module
* module/meta: refactor post-type fields methods
* module/meta: support for people field type
* module/meta: support for vin/year field types
* module/next-of-kin: migrate to general app
* module/papered: passing profile flags into data filters
* module/personage: make human title on empty full-name field
* module/personage: secondary data on v-card
* module/personage: vcard on tabloid meta summary
* module/phonebook: :new: module
* module/positions: :new: module
* module/ranged: :new: module
* module/static-covers: support for post summary
* module/sufficed: :new: module
* module/tabloid: filters for meta/term rendered
* module/tabloid: overview on edit post page
* module/tabloid: refactoring strip empty meta values
* module/tabloid: signal scripts via flags
* module/tabloid: support for post comments
* module/tabloid: support for side summaries
* module/tabloid: support for today data
* module/units: support for payload/max-speed
* module/was-born: filter search-select result for posts
* service/barcodes: :new: service
* service/paired: global summary on print profiles via flag
* service/paired: support for global summary
* service/post-type-fields: get post by field as service
* service/search-select: filters available for others
* service/search-select: support for extra/image on results
* service/term-hierarchy: :new: service

## [3.28.1]
* internal/bulk-exports: get enabled fields for full export
* internal/bulk-exports: support for export title
* internal/bulk-exports: support for unit fields
* internal/core-post-types: mapping edit comment cap
* internal/paired-core: check for reports/exports roles
* internal/paired-core: support for export buttons on tabloid overviews
* module/papered: refactoring helpers
* module/papered: support for primary taxonomy

## [3.28.0]
* internal/bulk-exports: support custom export fields
* internal/bulk-exports: support for column titles
* internal/settings-taxonomies: show in quick-edit
* internal/sub-contents: :new: internal trait
* internal/sub-contents: common field types
* main/helper: info for card/iban
* main/info: info from iban/card
* main/info: lookup return markup
* main/module: filter edit-form meta summary
* main/scripts: table overflow
* module/abo: :new: module
* module/banking: :new: module
* module/banking: advanced prep data
* module/banking: app tuned
* module/banking: country field support
* module/diagnosed: :new: module
* module/equipped: :new: module
* module/housed: :new: module
* module/iranian: support info from iban/card
* module/listed: :new: module
* module/meta: bank-card field type
* module/meta: support for tokens within values
* module/next-of-kin: :new: module
* module/next-of-kin: advanced prep data
* module/next-of-kin: app tuned
* module/papered: list view revised
* module/papered: support for index
* module/papered: support for meta fields
* module/papered: support for units fields
* module/personage: default status terms
* module/personage: line parser tool
* module/personage: sort by last/first name on paired lists
* module/personage: support for public statuses
* module/personage: support view item on papred

## [3.27.4]
* internal/bulk-exports: support for format
* internal/paired-row-actions: actions for supported post-types
* main/helper: xlsx generator
* main/info: post-type prop titles
* main/info: support for more noops
* main/settings: check for top-level on imports context
* module/conditioned: :new: module
* module/importer: check for each type permissions
* module/ortho: strip tel link prefix
* module/quotation: prep meta fields for display
* module/static-covers: support rest field

## [3.27.3]
* main/module: auto-hook terms init action
* module/importer: prep meta-keys for purge
* module/personage: renamed from `persona`

## [3.27.2]
* module/execution: renamed from `executed`
* module/organization: support for custom cap-type
* module/units: support for member/person fields

## [3.27.1]
* module/meeted: :new: module
* module/missioned: upgrade to latest
* module/programmed: :new: module
* module/yearly: :new: module

## [3.27.0]
* app/import-items: account for the lack of identifier prop
* internal/admin-page: cap check revised for non admins
* internal/bulk-exports: better file names
* internal/bulk-exports: support for terms
* internal/core-admin: unset views
* internal/core-capabilities: check for plural cap
* internal/core-dashboard: optional check for roles
* internal/core-post-types: apply cap-type on post-tags
* internal/core-post-types: apply settings
* internal/core-post-types: support viewable in settings
* internal/core-post-types: supports defaults
* internal/core-posttypes: initial support for custom capabilities
* internal/core-row-actions: support prepend
* internal/core-taxonomies: apply settings for object
* internal/core-taxonomies: meta-box callbacks revised
* internal/default-terms: avoid generating defaults on all calls
* internal/default-terms: better handling default terms
* internal/late-chores: admin bulk actions for after-care
* internal/late-chores: skip updates upon no changes
* internal/late-chores: support process disabled
* internal/paired-core: edit cap checks for pointers on supported
* internal/paired-core: hook importer term parents
* internal/paired-core: list of main posts for current supported
* internal/paired-core: simplified register method
* internal/paired-core: tabloid post summary for paired post-type
* internal/paired-core: view list for papred
* internal/paired-tools: cards revised
* internal/paired-tools: move from to card
* internal/post-date: aftercare via meta
* internal/post-date: bulk action for post date by meta fields
* internal/post-type-fields: avoid accepting year only on dates
* internal/print-page: additional on loading admin-page
* internal/print-page: inline script for barcode/qr-code
* internal/print-page: link style-sheet for boostrap/vazir fonts
* internal/print-page: print core default scripts
* internal/print-page: render footer callback
* main/datetime: is date only checker
* main/datetime: number of years helper
* main/metabox: better handling empty notices
* main/metabox: better support for custom name attr
* main/metabox: header support for check-lists
* main/metabox: minus count support for check-lists
* main/metabox: select single restricted terms
* main/metabox: show count support for check-lists
* main/module: auto hook screen options on check settings
* main/module: auto-hook importer init
* main/module: column markup revised
* main/module: generate constant plural
* main/module: module custom path on text-domain registry
* main/module: moving up into internals
* main/module: raise resources revised
* main/module: support debug mode for each module
* main/module: support for custom capability type api
* main/plugin: text-domain for admin area
* main/settings: contents viewable handling
* main/settings: extend from main
* main/settings: more control over taxonomy register arguments
* main/settings: predefined settings for roles
* main/settings: processing list open
* main/settings: support link small
* main/settings: tool-box card open
* main/template: check for given module on meta fields
* module/almanac: :new: module
* module/attachments: bulk tools for supported
* module/book: ddc/lcc meta field support
* module/certificated: :new: module
* module/config: initial support for roles pages
* module/config: reports/imports general summary hook
* module/conscripted: :new: module
* module/employed: :new: module
* module/executed: :new: module
* module/honored: :new: module
* module/importer: before/after process actions
* module/importer: bulk cleanup raw data
* module/importer: clarify override setting on set terms filter
* module/importer: initial string checks on values
* module/importer: prefix comments with column name
* module/importer: record fields map entries
* module/importer: setting terms for post revised
* module/importer: skip no source id
* module/importer: submenu for each supported
* module/iranian: avoid certificate checks if has no post
* module/iranian: more bulk checks on certificate numbers
* module/meta: avoid double sanitize fields
* module/meta: optional import field ignored
* module/meta: save post parent fields on non edit pages
* module/ortho: :warning: fixed iban validation
* module/ortho: simple identity/iban validation fields on tools
* module/overwrite: support paired taxonomy
* module/papered: :new: module
* module/persona: cleanup chars helper
* module/persona: initial process of names
* module/persona: print context on papered
* module/persona: raw vcard/identity data for papered
* module/persona: restrict reports to selected roles
* module/persona: reverse name family on full-name
* module/persona: support context on make full-name
* module/persona: vcard generator
* module/personal: sanitize passport number
* module/pointer: not available action notice
* module/regioned: :new: module
* module/roled: renamed from roles
* module/skilled: :new: module
* module/static-covers: counter support
* module/tabloid: filter post overview link
* module/terms: support for custom meta-key for order
* module/trained: add missing taxonomy dropdowns
* module/tweaks: skip paired taxonomies on attr-column
* module/was-born: calculated data on papered
* module/was-born: hide gender on tabloid terms rendered
* module/was-born: override-dates imports by settings
* module/was-born: single-select on gender
* service/post-type-fields: post meta-key helper

## [3.26.7]
* internal/late-chores: avoid re-hooking on cron calls
* internal/paired-core: optional manage restricted to admins
* internal/paired-tools: force assign parents
* internal/paired-tools: rethink passing constants
* internal/strings: noop from filtered
* main/info: front-end icon methods
* main/info: registered message
* main/module: render tools/reports/imports/ html before
* module/addendum: self as an appendage only if has download
* module/addendum: support short-code on main post-type
* module/importer: filter for assigned terms
* module/static-covers: check parent cap before display
* module/was-born: avoid zero summaries
* module/widgets: :new: edit theme options for roles
* module/widgets: :new: post terms widget
* module/widgets: :new: profile summary widget
* service/sitemaps: display empty terms

## [3.26.6]
* main/list-table: passing object as taxonomy
* module/identified: :new: module
* module/iranian: location database revised
* module/missioned: :new: module
* module/persona: :new: module
* module/trained: :new: module

## [3.26.5]
* internal/post-type-fields: check for viewable for logged-out

## [3.26.4]
* internal/post-type-fields: check for correct caps
* internal/post-type-fields: check if site is switched

## [3.26.3]
* main/template: handle pseudo-meta on switch sites

## [3.26.2]
* main/template: override field args for remote posts

## [3.26.1]
* internal/main-download: better handling file length
* main/template: avoid bail-out on undefined fields

## [3.26.0]
* app/import-items: :new: vue js app
* internal/core-admin: unset columns on table-list
* internal/core-capabilities: handle forced caps for taxonomy
* internal/core-post-types: check for feature support
* internal/core-post-types: check for post-type visibility
* internal/core-restrict-posts: extract methods
* internal/core-row-actions: main-link for term
* internal/core-row-actions: passing priority
* internal/core-template: include template renamed
* internal/dashboard-summary: general summary renderer
* internal/frame-page: custom ref-key
* internal/frame-page: main-link for term
* internal/imports: extract from main module
* internal/late-chores: :new: post after-care
* internal/paired-core: do connection
* internal/paired-core: get all connected to
* internal/paired-core: pointer actions
* internal/paired-core: register objects
* internal/paired-core: simpler check for constants
* internal/paired-imports: :new: server-side for import-items
* internal/paired-rest: :new: paired connect/disconnect via rest
* internal/post-type-fields: check for read post instead of viewable
* internal/post-type-fields: connect paired by
* internal/post-type-fields: filter for fields
* internal/posttype-fields: support for exclude posts
* internal/print-page: layout api revised
* internal/raw-imports: cache data
* internal/raw-imports: passing manual file-type
* internal/rest-api: extract from main module
* internal/template-post-type: limit api to posttypes
* internal/template-taxonomy: support override for terms
* internal/view-engines: get view part by post/term
* internal/view-engines: render string templates
* main/datetime: common age structure info
* main/datetime: decade/year generators
* main/datetime: medical age designations
* main/datetime: reversed prepping date-of-birth
* main/helper: check if post-type-field is available
* main/helper: get separated with filtered delimiters
* main/helper: priorities for prep meta row
* main/helper: rename meta-keys on switch paired post-types
* main/helper: support prepping measurements
* main/helper: taxonomy label for no items available
* main/info: get noop for common strings
* main/info: helpers for screen help tabs
* main/info: wrong message helper
* main/listtable: paired labels by post-type
* main/listtable: restrict by post-meta
* main/metabox: customize select for types
* main/metabox: render field number
* main/metabox: render texarea for fields
* main/metabox: support for none title/value
* main/module: auto check for linked to posts for paired
* main/module: auto hook default ajax callback
* main/module: auto hook sync primary paired post-type
* main/module: auto-hook override term link for paired
* main/module: auto-hook save post for supported meta-boxes
* main/module: autofill post-title with slugs
* main/module: better caps for taxonomy for taxonomy
* main/module: better css class for meta-boxes
* main/module: better handling admin class
* main/module: better handling private post-type/taxonomy
* main/module: check for archive override
* main/module: check for thrift mode
* main/module: check post/term by posttype/taxonomy constant
* main/module: customize list-box titles
* main/module: exclude paired post-type from sub-term archives
* main/module: export buttons as list-box extra
* main/module: extensive actions for supported-box
* main/module: fewer hook callbacks for ajax/rest
* main/module: get admin menu deprecated
* main/module: handle non-flat default terms on help tabs
* main/module: init action for each posttype field
* main/module: main-box action by post-type
* main/module: menu icon for taxonomies
* main/module: paired better handling parent exclusions
* main/module: paired naming updated
* main/module: primary taxonomy on paired register
* main/module: register taxonomy with no posttypes
* main/module: role can now accepts arrays
* main/module: settings option for posttypes/taxonomies
* main/module: skip register help tabs on iframe
* main/module: template for no connected items on list-box
* main/module: tweaks taxonomies display paired connected
* main/module: using helper method for object labels
* main/plugin: check setup disabled
* main/plugin: initial support for thrift mode
* main/plugin: support for module keywords
* main/template: table render callback
* module/actions: initial support for post actions
* module/actions: meta-box sidebar for terms
* module/addendum: :new: module
* module/audit: bulk action for force auto-audit
* module/audit: initial support for frame-page
* module/audit: separate auto-audit logic
* module/audit: tool for bulk force auto-audit
* module/genres: :new: module
* module/importer: :warning: fixed post pros for new posts
* module/importer: :warning: missing insert for new posts
* module/importer: always display attachment title
* module/importer: avoid empty comments
* module/importer: avoid override terms for existing posts
* module/importer: avoid repeating filters on preview table
* module/importer: check headers for duplicates
* module/importer: checks for empty post data
* module/importer: correct audit attribute handling
* module/importer: correct search key on table-list
* module/importer: different action hook for existing posts
* module/importer: moved to imports subs
* module/importer: override option on posts
* module/importer: pass correct data into filters
* module/importer: rethinking source id
* module/importer: support for comments
* module/importer: title action for each supported post-types
* module/iranian: :new: module
* module/labeled: :new: module
* module/meta: append supported fields as column type on paired-imports
* module/meta: better handling quick-edit
* module/meta: check for assign cap before term type fields
* module/meta: check for empty meta before import
* module/meta: downloadable field type
* module/meta: fallback for unknown type ltr fields
* module/meta: field for image source
* module/meta: initial support for attachment field type
* module/meta: initiate the posttype fields for each posttype
* module/meta: meta data on prepped for paired
* module/meta: migrate to imports
* module/meta: missing datetime type handling
* module/meta: proper checks for override
* module/meta: support for export context
* module/meta: support for hours field
* module/meta: support for period/amount fields
* module/meta: support for postal-code field type
* module/meta: support for venue string field
* module/meta: titles for meta rendered on rest
* module/meta: validation class for identity/iban fields
* module/organization: :warning: correct constant for subterms
* module/organization: hide departments on tabloid overview terms
* module/organization: importer support for code field
* module/organization: support for organization code identifier
* module/ortho: support for date-time inputs
* module/overwrite: keep custom labels
* module/pointers: :new: module
* module/quotation: column-row for quote/parents
* module/quotation: proper remove parent/menu-order meta-boxes
* module/ranked: :new: module
* module/recount: apply sortable column
* module/socialite: :new: module
* module/static-covers: :new: module
* module/static-covers: image on inline edit
* module/static-covers: initial support for counter token
* module/static-covers: support tabloid overview
* module/suited: default terms
* module/tabloid: :new: module
* module/tabloid: better cleanup export data
* module/tabloid: support for custom summaries
* module/tabloid: support for hooks on overview
* module/terms: support for hours field
* module/terms: support for Lat/Lng
* module/terms: support for min/max fields
* module/terms: support for parent field
* module/terms: support for period/amount fields
* module/territory: :new: module
* module/tweaks: alias for post-meta table on db queries
* module/tweaks: use menu icon prop for taxonomies
* module/unavailable: :new: module
* module/units: :new: module
* module/was-born: :new: module
* service/late-chores: :new: service
* service/late-chores: avoid wondering shutdown hook
* service/late-chores: one time hooked for terms count
* service/late-chores: support for late terms count after disabling
* service/late-chores: support for wc queue/action scheduler
* service/line-discovery: :new: service
* service/post-type-fields: :new: service
* service/search-select: offset on posts
* service/search-select: pre query filters
* service/search-select: renamed from select-single
* service/search-select: support for wp error as result

## [3.25.0]
* main/helper: action hook for editor status info
* main/helper: description field label for taxonomies
* main/helper: fallback for featured string for post-type labels
* main/helper: get posttype/taxonomy labels with fallbacks
* main/helper: label key for post-type author/excerpt renamed
* main/helper: lookup isbn moved up
* main/helper: metabox title on object labels
* main/helper: method to set audit terms
* main/helper: wordcount revised
* main/info: :new: main
* main/info: moved lookup helpers
* main/listtable: better checks for query vars
* main/metabox: better select single terms
* main/metabox: passing field empty for taxonomy/posttypes
* main/metabox: render field input general
* main/metabox: support for non hierarchical single terms
* main/module: auto-hook help tab default terms
* main/module: auto-hook register default terms
* main/module: column title from object labels
* main/module: correct columns on adding tags via ajax
* main/module: default callback for meta-box checklist restricted terms
* main/module: default roles revised
* main/module: fallback for tweaks column icon title
* main/module: get meta-box title for taxonomy revised
* main/module: override archive content via settings
* main/module: post-types parents internal api
* main/module: prep meta row revised
* main/module: proper check for accessing meta fields
* main/module: restrict by taxonomy on paired
* main/module: unified prop for current queried
* main/plugin: module access prop
* module/action: moved edit form after title hook
* module/assigned: :new: module
* module/audit: correct role checks for restrict/widget
* module/audit: global helper for taxonomy check
* module/audit: menu moved under options
* module/badges: :new: module
* module/educated: :new: module
* module/importer: avoid replacing raw data on each import
* module/importer: passing associative array on raw data
* module/importer: store prepared data for each post
* module/licensed: :new: module
* module/lingo: renamed from regional
* module/meta: :warning: nonce field with correct action
* module/meta: support for isbn field type
* module/meta: support for user field type
* module/organization: support for sub term short-code
* module/ortho: support for phone/isbn input
* module/overwrite: support post/bulk messages
* module/regional: custom capabilities based on roles
* module/suited: :new: module
* module/symposium: custom archives support
* module/terms: :new: support for plural
* module/terms: :warning: regression on quick edit for contact/barcode
* module/terms: dropped redundant strings on column titles
* module/terms: support for viewable field
* module/workflow: apply viewable meta on filters
* module/wysiwyg: :new: module
* service/paired: store paired posttype on taxonomy args
* service/select-single: support for user queries
* service/select-single: using rest nonce to auth

## [3.24.0]
* assets/dev: new module template
* main/datetime: prep date string for input/display
* main/helper: json/xml parser methods
* main/helper: module check for locale
* main/helper: prep meta row
* main/helper: switch post-type with paired api support
* main/helper: uncategorized label for taxonomy
* main/metabox: single select terms dropdown
* main/metabox: title with parent on dropdowns
* main/module: auto hook admin bulk actions
* main/module: auto hook plugin loaded action
* main/module: auto hook submenu pages
* main/module: better handling plural keys
* main/module: custom page template for archives
* main/module: default checklist reverse terms callback
* main/module: default checklist terms callback
* main/module: edit link on dashboard term summary
* main/module: exclude paired post-type by default
* main/module: exclude paired taxonomy from sitemap
* main/module: excluded objects revised
* main/module: factoring restrict taxonomy hooks
* main/module: has archives for taxonomies
* main/module: i18n/textdomain on module args
* main/module: initial imports api
* main/module: internal api for auto-fill post-title
* main/module: internal api for meta summery on edit form
* main/module: method for post-meta keys
* main/module: more module helpers
* main/module: otherwise true settings via features
* main/module: passing menu position on admin-page registeration
* main/module: post viewable with filters
* main/module: primary taxonomy for posttype
* main/module: process disabled api
* main/module: read-only title on edit form
* main/module: register importer on admin tools
* main/module: render imports toolbox card
* main/module: rendering view api
* main/module: sanitize for phone/mobile/identity field types
* main/module: support `0` on setting fallback
* main/module: support for default term
* main/module: support register taxonomy for taxonomies
* main/module: taxonomy prefix slugs are singular
* main/plugin: fallback for gnetwork front styles
* main/plugin: helper for something is wrong message
* main/plugin: moment string method
* main/plugin: proper accessing module instance
* main/plugin: query main moved to services
* main/relation: dedicated hook for o2o registers
* main/scripts: select2 package
* main/scripts: spin.js v4.1.1
* main/settings: advanced metabox setting
* main/settings: filter for posttype/taxonomy excludes
* main/settings: helper for common excluded pages
* main/settings: list of includes on default page excludes
* main/settings: new field type for nav-menu
* main/settings: submit checkbox helper
* main/tablelist: helper for date-start/date-end term column
* main/template: check for type and fallback to embed on media shortcode
* main/template: filter media shortcode html
* main/template: move up the helper method
* module/actions: :new: auto-load module
* module/archives: check and filter for default contents
* module/archives: custom page template for archives
* module/archives: exclude non-public/no-archive objects
* module/archives: module filter for posttype archive links
* module/audit: always run auto audit filter
* module/book: missing callback for paired insert content
* module/collect: upgrade paired tools
* module/config: :new: imports page
* module/config: data tab for imports
* module/config: overview actions
* module/config: settings card for autoloaded
* module/dashboard: :new: module
* module/dashboard: disable if page slug is non-ascii
* module/dashboard: exclude page from sitemap/wpseo
* module/dashboard: page as link button
* module/dashboard: support page as root
* module/dossier: :new: module
* module/entry: optional advanced metabox for sections
* module/importer: avoid using none value on field list
* module/importer: disable process on audit module
* module/importer: keeping source id for further imports
* module/importer: log source id on errors
* module/importer: passing prepared data through filters
* module/meta: :new: access check for fields
* module/meta: :new: date fields
* module/meta: label field/taxonomy from calling module
* module/meta: prep values based on field types
* module/meta: support for iban field type
* module/meta: support for media fields
* module/meta: support for parent post field type
* module/meta: support for post field
* module/meta: support select/date/identity field types
* module/meta: text source url field
* module/organization: :new: module
* module/ortho: check for identity length
* module/ortho: support for iban
* module/overwrite: :new: override post-type/taxonomy labels
* module/regional: dashboard glance count
* module/regional: default term support
* module/regional: import card on taxonomy tab extra
* module/regional: import from `ISO 639-1 Alpha-2`
* module/regional: optional advanced metabox
* module/regional: register importer on admin tools
* module/regional: show menu under options
* module/roles: disable built-in post post-type
* module/shortcodes: remove orphaned
* module/specs: default terms
* module/switcher: :new: module
* module/symposium: :new: module
* module/terms column hooks for supported
* module/terms: :new: support for date fields
* module/terms: :new: support for overwrite
* module/terms: apply ordering on supported
* module/terms: fill current author
* module/terms: new number fields
* module/today: full day importer field
* module/uncategorized: clean bulk actions
* module/uncategorized: clean unregistered action
* module/uncategorized: edit view for supported post-types
* module/uncategorized: extract clean logics
* module/uncategorized: move clean unattached from terms
* module/uncategorized: moved related tools
* module/uncategorized: prefix bulk actions
* module/uncategorized: raw taxonomies column on reports
* module/workflow: better handling status meta
* module/workflow: full label set for the custom taxonomy
* service/o2o: move to services
* service/paired: :new: initial service
* service/select-single: :new: service

## [3.23.4]
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

## [3.23.3]
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

## [3.23.2]
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

## [3.23.1]
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

## [3.23.0]
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

## [3.22.1]
* main/widget: :warning: passing new instance as base
* main/widget: custom title on the form
* main/widget: wrap as items on the form
* module/book: :warning: missing return on isbn links
* module/book: more preping meta fields data
* module/importer: non-forced old id numbers
* module/regional: :new: module

## [3.22.0]
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

## [3.21.0]
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

## [3.20.1]
* main/helper: using internal method for getting the post
* main/module: :new: paired thumbnail fallback
* main/module: hook paired to on rest api
* main/module: paired tax also sets on paired posttype
* main/plugin: :new: restrict posts api
* main/template: filter for raw meta fields
* module/widgets: :new: wprest-single widget

## [3.20.0]
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

## [3.19.0]
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
* module/widgets: search terms: avoid filtered search query
* module/widgets: search terms: display empty terms
* module/widgets: search terms: prefix with name
* module/widgets: search terms: search names and slugs
* module/widgets: search terms: singular name as title on multiple taxonomy setup

## [3.18.0]
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

## [3.17.1]
* main/module: rename default method for display meta row
* main/template: filter meta summary rows
* main/template: get postmeta raw revised
* module/book: better handling isbn data
* module/importer: optional skip imports into posts with thumbnails

## [3.17.0]
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

## [3.16.3]
* main/template: customize context on term field helper
* main/template: meta summary helper
* main/template: optional ignore of co-fields with term type
* main/template: rename term field helper
* main/widget: :new: title image for widgets
* main/widget: prep css classes
* module/book: isbn barcode helper
* module/book: meta summary helper

## [3.16.2]
* main/module: tidy-up constants for taxonomy slugs
* main/widget: method for cache keys
* module/contest: total hide of the assoc tax
* module/course: missing assoc post helper
* module/course: quickedit support for byline/published fields
* module/course: template partial
* module/course: total hide of the assoc tax
* module/team: rename category taxonomy to group

## [3.16.1]
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

## [3.16.0]
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

## [3.15.9]
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

## [3.15.8]
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

## [3.15.7]
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

## [3.15.5]
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

## [3.15.4]
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

## [3.15.3]
* main/helper: better prep for title/desc
* main/helper: more labels for posttypes
* main/module: initial support for blocks
* main/shortcode: datetime for published only
* main/shortcodes: post item published datetime attr
* module/headings: skip on numerial section titles
* module/modified: help tab placeholders info
* module/modified: last published date option

## [3.15.2]
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

## [3.15.1]
* assets/package: virastar updated to 0.13.0
* main/main: using psr-4 autoload for core and main files
* main/module: create posts only for mapped caps
* main/metabox: passing terms to checklist box
* module/meta: :warning: fixed posttype on ajax
* module/tweaks: link id column to shortlinks
* module/tweaks: support id on users tablelist
* module/users: :new: author categories
* module/users: support tweaks for counts columns

## [3.15.0]
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

## [3.14.0]
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

## [3.13.3]
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

## [3.13.2]
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

## [3.13.1]
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

## [3.13.0]
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

## [3.12.0]
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

## [3.11.3]
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

## [3.11.2]
* main/module: passing terms into default terms installer
* main/module: some default section titles
* main/module: custom hook for ajax helper
* main/settings: :new: calendar list option
* main/settings: :new: email setting type
* main/helper: media related methods moved
* main/helper: helper for current time html tag
* module/config: flush rewrite rules warning
* module/tweaks: reorganizing setting options

## [3.11.1]
* main/module: :new: support for custom svg icons
* main/module: some default section titles
* main/helper: data count on get counted
* module/alphabet: :new: module
* module/terms: tuning scripts on edit tags screen

## [3.11.0]
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

## [3.10.2]
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

## [3.10.1]
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

## [3.10.0]
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

## [3.9.15]
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

## [3.9.14]
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

## [3.9.13]
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

## [3.9.12]
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

## [3.9.11]
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

## [3.9.10]
* module/modified: check for cap before linking authors in dashboard
* module/today: styling admin edit date stamp
* module/tweaks: sortable id column

## [3.9.9]
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

## [3.9.8]
* module/ortho: giving back focus to title input

## [3.9.7]
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

## [3.9.6]
* core/date: :warning: fixed fatal
* wordpress/module: :warning: fixed fatal

## [3.9.5]
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

## [3.9.4]
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

## [3.9.3]
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

## [3.9.2]
* main/main: skip checking for folders!
* module/meta: missed string for column row
* module/series: correct check for series count
* module/tweaks: attachment summery for each post
* module/tweaks: check for manage terms cap before linking on taxes

## [3.9.1]
* core/html: method renamed, fixed fatal on PHP5.6

## [3.9.0]
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

## [3.8.2]
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

## [3.8.1]
* main/helper: nooped strings for count format
* main/settings: revising strings
* module/audit: preventing empty reports
* module/settings: module icon before settings title
* module/reshare: source meta before/after content
* module/book: p2p info on settings

## [3.8.0]
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

## [3.7.6]
* module: skip empty items on dashboard glance
* module: revert back to tweaks strings on setup
* book: hook p2p connected to content actions

## [3.7.5]
* today: fixed fatal

## [3.7.4]
* module: setting for admin edit page ordering
* module: p2p admin column
* module: tweaks strings moved to current screen hook
* book: tweaks strings
* entry: support for new meta fields
* entry: custom post updated messages
* today: temporarily using text type for inputs

## [3.7.3]
* module: check tax query var from constants
* module: disable tax tagcloud
* module: disable auto custom cpt permalink args
* today: fixed notice on edit page column
* entry: quick edit box for section tax
* magazine: quick edit box for section tax

## [3.7.2]
* tweaks: link on tax icon
* settings: delete all options via general tools
* entry: default comment status setting
* event: default comment status setting
* book: default comment status setting
* book: p2p meta renamed
* audit: tax renamed
* audit: admin box no checked on top
* meta: hide before/after input if no js

## [3.7.1]
* book: p2p support
* today: setting for draft is in today

## [3.7.0]
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

## [3.6.1]
* all: Persian translation updated
* today: fixed template fatal!
* entry: fixed admin edit notice
* reshare: using label generators

## [3.6.0]
* entry: support for [gPeople](http://geminorum.ir/wordpress/gpeople)
* estimated: fixed strict notice
* event: new module
* today: new module

## [3.5.0]
* modulecore: fast forward registering tax meta boxes
* modulecore: using filter for shortcodes
* modulecore: help side bar for all modules
* estimated: support for [gNetwork](http://geminorum.ir/wordpress/gnetwork) [Themes](https://github.com/geminorum/gnetwork/wiki/Modules-Themes) content actions
* estimated: minimum words setting
* tweaks: word count for excerpt & meta lead
* tweaks: filtering taxonomies before columns
* meta: now field can be an array
* entry: revised, now focused on just be section/entry

## [3.4.0]
* tools: orphaned term converter

## [3.3.0]
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

## [3.2.0]
* main: helping if module enabled
* gallery: exclude after global filters

## [3.1.0]
* enqueue front styles for drafts & estimated modules
* skip registering alpha modules on production

## [3.0.0]
* moved to [Semantic Versioning](http://semver.org/)
* almost complete rewriting of all internal apis to reduce memory footprint

## [0.2.13]
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

## [0.2.12]
* all: removed old options calls
* all: new api for: tools page / save post / parse query / restrict manage posts / post parent field
* settings: upgrade option tool
* book: basic query class / see [Extending WP_Query](http://bradt.ca/blog/extending-wp_query/)

## [0.2.11]
* all: new widget api based on [gTheme 3](https://github.com/geminorum/gtheme_03) code
* all: better handling image sizes
* entry: code refactoring
* magazine: semantic default callback for cover

## [0.2.10]
* all: check if theme support thumbnail for all posttypes
* all: default terms api
* reshare: support for cpt thumbnail

## [0.2.9]
* all: internal api for: post type thumbnail / list table column / p2p
* book: new module
* magazine: code refactoring

## [0.2.8]
* magazine: fallback for issues with no cover
* reshare: template helper

## [0.2.7]
* all: internal api for tinymce plugins
* series: switch to template class structure

## [0.2.6]
* tweaks: simple post excerpt meta box
* audit: using internal tax meta box api

## [0.2.5]
* reshare: new module

## [0.2.4]
* magazine: new option for redirecting issue cpt archives
* magazine: restrict issue cpt by span on admin edit
* magazine: disable months dropdown for issue cpt on admin edit
* meta: inline edit on post table override fixed!

## [0.2.3]
* magazine: using pages dropdown instead of terms
* magazine: separate save post and update post
* magazine: handle trash and delete issues

## [0.2.2]
* all: new `add_image_size()` method with post type support
* all: moveup `set_meta()` and used as internal api. this will remove empty meta rows in db
* alphabets: new module draft
* gallery: new module draft
* submit: new module draft

## [0.2.1]
* cleanup and updated language pot
* meta: support label tax in tweaks module

## [0.2.0]
* github publish
