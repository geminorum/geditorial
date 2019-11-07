<?php namespace geminorum\gEditorial\Core;

defined( 'ABSPATH' ) || die( header( 'HTTP/1.0 403 Forbidden' ) );

class Icon extends Base
{

	// https://github.com/JJJ/meta-icon

	const BASE = 'geditorial';

	public static $icons = array(

		// mostly from https://icomoon.io
		'old' => array(
			'unchecked'  => '<path d="M28 0h-24c-2.2 0-4 1.8-4 4v24c0 2.2 1.8 4 4 4h24c2.2 0 4-1.8 4-4v-24c0-2.2-1.8-4-4-4zM28 28h-24v-24h24v24z"></path>',
			'email'      => '<path d="M26.666 0h-21.332c-2.934 0-5.334 2.4-5.334 5.333v21.333c0 2.934 2.4 5.334 5.334 5.334h21.332c2.936 0 5.334-2.4 5.334-5.334v-21.333c0-2.934-2.398-5.333-5.334-5.333zM8 8h16c0.286 0 0.563 0.061 0.817 0.177l-8.817 10.286-8.817-10.287c0.254-0.116 0.531-0.177 0.817-0.177zM6 22v-12c0-0.042 0.002-0.084 0.004-0.125l5.864 6.842-5.8 5.8c-0.045-0.167-0.069-0.34-0.069-0.517zM24 24h-16c-0.177 0-0.35-0.024-0.517-0.069l5.691-5.691 2.826 3.297 2.826-3.297 5.691 5.691c-0.167 0.045-0.34 0.069-0.517 0.069zM26 22c0 0.177-0.024 0.35-0.069 0.517l-5.8-5.8 5.864-6.842c0.003 0.041 0.004 0.083 0.004 0.125v12z"></path>',
			'barcode'    => '<path d="M0 4h4v20h-4zM6 4h2v20h-2zM10 4h2v20h-2zM16 4h2v20h-2zM24 4h2v20h-2zM30 4h2v20h-2zM20 4h1v20h-1zM14 4h1v20h-1zM27 4h1v20h-1zM0 26h2v2h-2zM6 26h2v2h-2zM10 26h2v2h-2zM20 26h2v2h-2zM30 26h2v2h-2zM24 26h4v2h-4zM14 26h4v2h-4z"></path>',
			'whatsapp'   => '<path d="M27.281 4.65c-2.994-3-6.975-4.65-11.219-4.65-8.738 0-15.85 7.112-15.85 15.856 0 2.794 0.731 5.525 2.119 7.925l-2.25 8.219 8.406-2.206c2.319 1.262 4.925 1.931 7.575 1.931h0.006c0 0 0 0 0 0 8.738 0 15.856-7.113 15.856-15.856 0-4.238-1.65-8.219-4.644-11.219zM16.069 29.050v0c-2.369 0-4.688-0.637-6.713-1.837l-0.481-0.288-4.987 1.306 1.331-4.863-0.313-0.5c-1.325-2.094-2.019-4.519-2.019-7.012 0-7.269 5.912-13.181 13.188-13.181 3.519 0 6.831 1.375 9.319 3.862 2.488 2.494 3.856 5.8 3.856 9.325-0.006 7.275-5.919 13.188-13.181 13.188zM23.294 19.175c-0.394-0.2-2.344-1.156-2.706-1.288s-0.625-0.2-0.894 0.2c-0.262 0.394-1.025 1.288-1.256 1.556-0.231 0.262-0.462 0.3-0.856 0.1s-1.675-0.619-3.188-1.969c-1.175-1.050-1.975-2.35-2.206-2.744s-0.025-0.613 0.175-0.806c0.181-0.175 0.394-0.463 0.594-0.694s0.262-0.394 0.394-0.662c0.131-0.262 0.069-0.494-0.031-0.694s-0.894-2.15-1.219-2.944c-0.319-0.775-0.65-0.669-0.894-0.681-0.231-0.012-0.494-0.012-0.756-0.012s-0.694 0.1-1.056 0.494c-0.363 0.394-1.387 1.356-1.387 3.306s1.419 3.831 1.619 4.1c0.2 0.262 2.794 4.269 6.769 5.981 0.944 0.406 1.681 0.65 2.256 0.837 0.95 0.3 1.813 0.256 2.494 0.156 0.762-0.113 2.344-0.956 2.675-1.881s0.331-1.719 0.231-1.881c-0.094-0.175-0.356-0.275-0.756-0.475z"></path>',
			'telegram'   => '<path d="M16 0c-8.838 0-16 7.162-16 16s7.162 16 16 16 16-7.163 16-16-7.163-16-16-16zM23.863 10.969l-2.625 12.369c-0.181 0.881-0.712 1.087-1.45 0.681l-4-2.956-1.919 1.869c-0.225 0.219-0.4 0.4-0.8 0.4-0.519 0-0.431-0.194-0.606-0.688l-1.363-4.475-3.956-1.231c-0.856-0.262-0.862-0.85 0.194-1.269l15.412-5.95c0.7-0.319 1.381 0.169 1.113 1.25z"></path>',
			'markdown'   => '<path d="M29.692 6h-27.385c-1.272 0-2.308 1.035-2.308 2.308v15.385c0 1.273 1.035 2.308 2.308 2.308h27.385c1.273 0 2.308-1.035 2.308-2.308v-15.385c0-1.272-1.035-2.308-2.308-2.308zM18 21.996l-4 0.004v-6l-3 3.846-3-3.846v6h-4v-12h4l3 4 3-4 4-0.004v12zM23.972 22.996l-4.972-6.996h3v-6h4v6h3l-5.028 6.996z"></path>',
			'pen'        => '<path d="M31.818 9.122l-8.939-8.939c-0.292-0.292-0.676-0.226-0.855 0.146l-1.199 2.497 8.35 8.35 2.497-1.199c0.372-0.178 0.438-0.563 0.146-0.855z"></path><path class="path2" d="M19.231 4.231l-8.231 0.686c-0.547 0.068-1.002 0.184-1.159 0.899-0 0.001-0 0.001-0.001 0.002-2.232 10.721-9.84 21.183-9.84 21.183l1.793 1.793 8.5-8.5c-0.187-0.392-0.293-0.83-0.293-1.293 0-1.657 1.343-3 3-3s3 1.343 3 3-1.343 3-3 3c-0.463 0-0.902-0.105-1.293-0.293l-8.5 8.5 1.793 1.793c0 0 10.462-7.608 21.183-9.84 0.001-0 0.001-0 0.002-0.001 0.714-0.157 0.831-0.612 0.898-1.159l0.686-8.231-8.538-8.539z"></path>',
			'bug'        => '<path d="M32 18v-2h-6.040c-0.183-2.271-0.993-4.345-2.24-6.008h5.061l2.189-8.758-1.94-0.485-1.811 7.242h-5.459c-0.028-0.022-0.056-0.043-0.084-0.064 0.21-0.609 0.324-1.263 0.324-1.944 0-3.305-2.686-5.984-6-5.984s-6 2.679-6 5.984c0 0.68 0.114 1.334 0.324 1.944-0.028 0.021-0.056 0.043-0.084 0.064h-5.459l-1.811-7.242-1.94 0.485 2.189 8.758h5.061c-1.246 1.663-2.056 3.736-2.24 6.008h-6.040v2h6.043c0.119 1.427 0.485 2.775 1.051 3.992h-3.875l-2.189 8.757 1.94 0.485 1.811-7.243h3.511c1.834 2.439 4.606 3.992 7.708 3.992s5.874-1.554 7.708-3.992h3.511l1.811 7.243 1.94-0.485-2.189-8.757h-3.875c0.567-1.217 0.932-2.565 1.051-3.992h6.043z"></path>',
			'twitter'    => '<path d="M32 7.075c-1.175 0.525-2.444 0.875-3.769 1.031 1.356-0.813 2.394-2.1 2.887-3.631-1.269 0.75-2.675 1.3-4.169 1.594-1.2-1.275-2.906-2.069-4.794-2.069-3.625 0-6.563 2.938-6.563 6.563 0 0.512 0.056 1.012 0.169 1.494-5.456-0.275-10.294-2.888-13.531-6.862-0.563 0.969-0.887 2.1-0.887 3.3 0 2.275 1.156 4.287 2.919 5.463-1.075-0.031-2.087-0.331-2.975-0.819 0 0.025 0 0.056 0 0.081 0 3.181 2.263 5.838 5.269 6.437-0.55 0.15-1.131 0.231-1.731 0.231-0.425 0-0.831-0.044-1.237-0.119 0.838 2.606 3.263 4.506 6.131 4.563-2.25 1.762-5.075 2.813-8.156 2.813-0.531 0-1.050-0.031-1.569-0.094 2.913 1.869 6.362 2.95 10.069 2.95 12.075 0 18.681-10.006 18.681-18.681 0-0.287-0.006-0.569-0.019-0.85 1.281-0.919 2.394-2.075 3.275-3.394z"></path>',
			'facebook'   => '<path d="M29 0h-26c-1.65 0-3 1.35-3 3v26c0 1.65 1.35 3 3 3h13v-14h-4v-4h4v-2c0-3.306 2.694-6 6-6h4v4h-4c-1.1 0-2 0.9-2 2v2h6l-1 4h-5v14h9c1.65 0 3-1.35 3-3v-26c0-1.65-1.35-3-3-3z"></path>',
			'soundcloud' => '<path d="M29 0h-26c-1.65 0-3 1.35-3 3v26c0 1.65 1.35 3 3 3h26c1.65 0 3-1.35 3-3v-26c0-1.65-1.35-3-3-3zM5.5 22h-1l-0.5-3 0.5-3h1l0.5 3-0.5 3zM9.5 22h-1l-0.5-4 0.5-4h1l0.5 4-0.5 4zM13.5 22h-1l-0.5-6 0.5-6h1l0.5 6-0.5 6zM25.788 22c-0.063 0-9.413-0.006-9.419-0.006-0.2-0.019-0.363-0.194-0.369-0.4v-10.787c0-0.2 0.069-0.3 0.325-0.4 0.663-0.256 1.406-0.406 2.175-0.406 3.131 0 5.7 2.4 5.975 5.469 0.406-0.169 0.85-0.262 1.313-0.262 1.875 0 3.4 1.525 3.4 3.4s-1.525 3.394-3.4 3.394z"></path>',
			'tumblr'     => '<path d="M29 0h-26c-1.65 0-3 1.35-3 3v26c0 1.65 1.35 3 3 3h26c1.65 0 3-1.35 3-3v-26c0-1.65-1.35-3-3-3zM22.869 25.769c-0.944 0.444-1.8 0.756-2.563 0.938-0.762 0.175-1.594 0.269-2.481 0.269-1.012 0-1.606-0.125-2.381-0.381s-1.438-0.619-1.988-1.087c-0.55-0.475-0.925-0.975-1.137-1.506s-0.319-1.3-0.319-2.313v-7.744h-3v-3.125c0.869-0.281 1.875-0.688 2.488-1.213 0.619-0.525 1.119-1.156 1.488-1.894 0.375-0.737 0.631-1.675 0.775-2.813h3.138v5.1h5.113v3.944h-5.106v5.662c0 1.281-0.019 2.019 0.119 2.381s0.475 0.738 0.844 0.95c0.488 0.294 1.050 0.438 1.675 0.438 1.119 0 2.231-0.363 3.337-1.087v3.481z"></path>',
			'xing'       => '<path d="M29 0h-26c-1.65 0-3 1.35-3 3v26c0 1.65 1.35 3 3 3h26c1.65 0 3-1.35 3-3v-26c0-1.65-1.35-3-3-3zM9.769 20.813h-3.456c-0.206 0-0.362-0.094-0.45-0.238-0.094-0.15-0.094-0.337 0-0.531l3.675-6.488c0.006-0.006 0.006-0.012 0-0.019l-2.338-4.050c-0.094-0.194-0.112-0.381-0.019-0.531 0.088-0.144 0.263-0.219 0.475-0.219h3.462c0.531 0 0.794 0.344 0.963 0.65 0 0 2.363 4.125 2.381 4.15-0.137 0.25-3.737 6.606-3.737 6.606-0.188 0.325-0.438 0.669-0.956 0.669zM26.137 4.756l-7.662 13.55c-0.006 0.006-0.006 0.019 0 0.025l4.881 8.913c0.094 0.194 0.1 0.387 0.006 0.538-0.087 0.144-0.25 0.219-0.462 0.219h-3.456c-0.531 0-0.794-0.35-0.969-0.656 0 0-4.906-9-4.919-9.025 0.244-0.431 7.7-13.656 7.7-13.656 0.188-0.331 0.413-0.656 0.925-0.656h3.506c0.206 0 0.375 0.081 0.462 0.219 0.087 0.144 0.087 0.338-0.012 0.531z"></path>',
			'vk'         => '<path d="M29 0h-26c-1.65 0-3 1.35-3 3v26c0 1.65 1.35 3 3 3h26c1.65 0 3-1.35 3-3v-26c0-1.65-1.35-3-3-3zM25.919 22.4l-2.925 0.044c0 0-0.631 0.125-1.456-0.444-1.094-0.75-2.125-2.706-2.931-2.45-0.813 0.256-0.788 2.012-0.788 2.012s0.006 0.375-0.181 0.575c-0.2 0.219-0.6 0.262-0.6 0.262h-1.306c0 0-2.888 0.175-5.431-2.475-2.775-2.887-5.225-8.619-5.225-8.619s-0.144-0.375 0.013-0.556c0.175-0.206 0.644-0.219 0.644-0.219l3.131-0.019c0 0 0.294 0.050 0.506 0.206 0.175 0.125 0.269 0.369 0.269 0.369s0.506 1.281 1.175 2.438c1.306 2.256 1.919 2.75 2.362 2.513 0.644-0.35 0.45-3.194 0.45-3.194s0.012-1.031-0.325-1.488c-0.262-0.356-0.756-0.463-0.969-0.488-0.175-0.025 0.113-0.431 0.488-0.619 0.563-0.275 1.556-0.294 2.731-0.281 0.913 0.006 1.181 0.069 1.538 0.15 1.081 0.262 0.712 1.269 0.712 3.681 0 0.775-0.137 1.863 0.419 2.219 0.238 0.156 0.825 0.025 2.294-2.469 0.694-1.181 1.219-2.569 1.219-2.569s0.113-0.25 0.288-0.356c0.181-0.106 0.425-0.075 0.425-0.075l3.294-0.019c0 0 0.988-0.119 1.15 0.331 0.169 0.469-0.369 1.563-1.712 3.356-2.206 2.944-2.456 2.669-0.619 4.369 1.75 1.625 2.113 2.419 2.175 2.519 0.712 1.2-0.813 1.294-0.813 1.294z"></path>',
			'linkedin'   => '<path d="M29 0h-26c-1.65 0-3 1.35-3 3v26c0 1.65 1.35 3 3 3h26c1.65 0 3-1.35 3-3v-26c0-1.65-1.35-3-3-3zM12 26h-4v-14h4v14zM10 10c-1.106 0-2-0.894-2-2s0.894-2 2-2c1.106 0 2 0.894 2 2s-0.894 2-2 2zM26 26h-4v-8c0-1.106-0.894-2-2-2s-2 0.894-2 2v8h-4v-14h4v2.481c0.825-1.131 2.087-2.481 3.5-2.481 2.488 0 4.5 2.238 4.5 5v9z"></path>',
			'reddit'     => '<path d="M8 20c0-1.105 0.895-2 2-2s2 0.895 2 2c0 1.105-0.895 2-2 2s-2-0.895-2-2zM20 20c0-1.105 0.895-2 2-2s2 0.895 2 2c0 1.105-0.895 2-2 2s-2-0.895-2-2zM20.097 24.274c0.515-0.406 1.262-0.317 1.668 0.198s0.317 1.262-0.198 1.668c-1.434 1.13-3.619 1.86-5.567 1.86s-4.133-0.73-5.567-1.86c-0.515-0.406-0.604-1.153-0.198-1.668s1.153-0.604 1.668-0.198c0.826 0.651 2.46 1.351 4.097 1.351s3.271-0.7 4.097-1.351zM32 16c0-2.209-1.791-4-4-4-1.504 0-2.812 0.83-3.495 2.057-2.056-1.125-4.561-1.851-7.29-2.019l2.387-5.36 4.569 1.319c0.411 1.167 1.522 2.004 2.83 2.004 1.657 0 3-1.343 3-3s-1.343-3-3-3c-1.142 0-2.136 0.639-2.642 1.579l-5.091-1.47c-0.57-0.164-1.173 0.116-1.414 0.658l-3.243 7.282c-2.661 0.187-5.102 0.907-7.114 2.007-0.683-1.227-1.993-2.056-3.496-2.056-2.209 0-4 1.791-4 4 0 1.635 0.981 3.039 2.387 3.659-0.252 0.751-0.387 1.535-0.387 2.341 0 5.523 6.268 10 14 10s14-4.477 14-10c0-0.806-0.134-1.589-0.387-2.34 1.405-0.62 2.387-2.025 2.387-3.66zM27 5.875c0.621 0 1.125 0.504 1.125 1.125s-0.504 1.125-1.125 1.125-1.125-0.504-1.125-1.125 0.504-1.125 1.125-1.125zM2 16c0-1.103 0.897-2 2-2 0.797 0 1.487 0.469 1.808 1.145-1.045 0.793-1.911 1.707-2.552 2.711-0.735-0.296-1.256-1.016-1.256-1.856zM16 29.625c-6.42 0-11.625-3.414-11.625-7.625s5.205-7.625 11.625-7.625c6.42 0 11.625 3.414 11.625 7.625s-5.205 7.625-11.625 7.625zM28.744 17.856c-0.641-1.003-1.507-1.918-2.552-2.711 0.321-0.676 1.011-1.145 1.808-1.145 1.103 0 2 0.897 2 2 0 0.84-0.52 1.56-1.256 1.856z"></path>',
			'pinterest'  => '<path d="M16 2.138c-7.656 0-13.863 6.206-13.863 13.863 0 5.875 3.656 10.887 8.813 12.906-0.119-1.094-0.231-2.781 0.050-3.975 0.25-1.081 1.625-6.887 1.625-6.887s-0.412-0.831-0.412-2.056c0-1.925 1.119-3.369 2.506-3.369 1.181 0 1.756 0.887 1.756 1.95 0 1.188-0.756 2.969-1.15 4.613-0.331 1.381 0.688 2.506 2.050 2.506 2.462 0 4.356-2.6 4.356-6.35 0-3.319-2.387-5.638-5.787-5.638-3.944 0-6.256 2.956-6.256 6.019 0 1.194 0.456 2.469 1.031 3.163 0.113 0.137 0.131 0.256 0.094 0.4-0.106 0.438-0.338 1.381-0.387 1.575-0.063 0.256-0.2 0.306-0.463 0.188-1.731-0.806-2.813-3.337-2.813-5.369 0-4.375 3.175-8.387 9.156-8.387 4.806 0 8.544 3.425 8.544 8.006 0 4.775-3.012 8.625-7.194 8.625-1.406 0-2.725-0.731-3.175-1.594 0 0-0.694 2.644-0.863 3.294-0.313 1.206-1.156 2.712-1.725 3.631 1.3 0.4 2.675 0.619 4.106 0.619 7.656 0 13.863-6.206 13.863-13.863 0-7.662-6.206-13.869-13.863-13.869z"></path>',

			'heart' => '<path d="M29.193 5.265c-3.629-3.596-9.432-3.671-13.191-0.288-3.76-3.383-9.561-3.308-13.192 0.288-3.741 3.704-3.741 9.709 0 13.415 1.069 1.059 11.053 10.941 11.053 10.941 1.183 1.172 3.096 1.172 4.278 0 0 0 10.932-10.822 11.053-10.941 3.742-3.706 3.742-9.711-0.001-13.415zM27.768 17.268l-11.053 10.941c-0.393 0.391-1.034 0.391-1.425 0l-11.053-10.941c-2.95-2.92-2.95-7.671 0-10.591 2.844-2.815 7.416-2.914 10.409-0.222l1.356 1.22 1.355-1.22c2.994-2.692 7.566-2.594 10.41 0.222 2.95 2.919 2.95 7.67 0.001 10.591zM9.253 7.501c0.277 0 0.5 0.224 0.5 0.5s-0.224 0.5-0.5 0.5h-0.001c-1.794 0-3.249 1.455-3.249 3.249v0.001c0 0.276-0.224 0.5-0.5 0.5s-0.5-0.224-0.5-0.5v0c0-2.346 1.901-4.247 4.246-4.249 0.002 0 0.002-0.001 0.004-0.001z"></path>',

			'json' => '<path d="M16.034 32c0.64-0.006 1.272-0.040 1.904-0.124 1.44-0.184 2.86-0.58 4.2-1.152 1.88-0.804 3.6-1.964 5.060-3.396 1.34-1.32 2.46-2.872 3.26-4.574 0.68-1.464 1.16-3.036 1.36-4.64 0.2-1.548 0.18-3.13-0.060-4.67-0.2-1.28-0.56-2.53-1.060-3.72-0.32-0.748-0.7-1.47-1.14-2.16-1.558-2.452-3.818-4.424-6.418-5.74-0.926-0.474-1.9-0.86-2.9-1.16h-0.006c0.16 0.084 0.32 0.176 0.46 0.268 0.16 0.1 0.32 0.196 0.468 0.3 0.76 0.514 1.46 1.104 2.080 1.776 1.42 1.526 2.4 3.4 2.96 5.392 0.38 1.312 0.58 2.668 0.66 4.028 0.060 0.992 0.060 1.99-0.060 2.976-0.2 1.694-0.74 3.35-1.64 4.8-0.66 1.070-1.52 2.026-2.54 2.78-1.66 1.24-3.84 1.98-5.92 1.488-0.34-0.080-0.68-0.196-1-0.344-0.36-0.166-0.72-0.37-1.040-0.6-0.62-0.414-1.18-0.914-1.66-1.476-0.58-0.694-1.060-1.484-1.4-2.332-0.42-1.076-0.64-2.232-0.66-3.39-0.040-1.7 0.3-3.408 1.14-4.9 0.56-0.992 1.32-1.864 2.22-2.56 0.3-0.236 0.62-0.444 0.94-0.64l0.020-0.008c-0.62-0.14-1.28-0.2-1.92-0.16-0.56 0.040-1.1 0.14-1.64 0.32-0.48 0.16-0.94 0.36-1.36 0.6-0.34 0.2-0.68 0.42-0.98 0.66-0.28 0.24-0.56 0.48-0.82 0.74-1.5 1.54-2.36 3.58-2.7 5.68-0.2 1.3-0.2 2.62-0.12 3.94 0.14 1.86 0.52 3.72 1.26 5.42 0.46 1.060 1.060 2.040 1.8 2.9 1.14 1.32 2.58 2.34 4.18 2.98 0.9 0.36 1.86 0.62 2.82 0.72 0.1 0.020 0.2 0.020 0.28 0.020zM12.118 31.47c-0.22-0.094-0.42-0.2-0.6-0.304-0.2-0.11-0.4-0.226-0.6-0.346-0.78-0.484-1.48-1.050-2.12-1.704-1.46-1.516-2.44-3.424-3-5.446-0.38-1.38-0.58-2.804-0.66-4.232-0.060-0.94-0.040-1.86 0.060-2.78 0.18-1.68 0.66-3.34 1.5-4.78 0.6-1.060 1.42-2 2.38-2.74 0.66-0.5 1.4-0.92 2.18-1.2 1.1-0.4 2.28-0.52 3.42-0.36 0.44 0.060 0.88 0.16 1.28 0.3 0.040 0 0.040 0 0.060 0.040 0.020 0.020 0.060 0.020 0.080 0.040 0.060 0.020 0.14 0.060 0.22 0.1 0.32 0.16 0.64 0.36 0.94 0.56 1.2 0.84 2.18 2 2.8 3.34 0.56 1.22 0.82 2.58 0.84 3.92 0.020 1.44-0.24 2.88-0.86 4.2-0.74 1.58-2 2.92-3.52 3.78 0.12 0.040 0.24 0.060 0.36 0.1 0.3 0.060 0.6 0.1 0.92 0.1 1.96 0.060 3.86-0.88 5.26-2.2 0.26-0.24 0.5-0.5 0.72-0.78 0.3-0.36 0.58-0.72 0.82-1.1 0.32-0.5 0.58-1 0.82-1.54 0.3-0.72 0.54-1.46 0.68-2.22 0.26-1.34 0.28-2.7 0.2-4.040-0.18-2.7-0.9-5.4-2.46-7.64-0.24-0.34-0.48-0.66-0.74-0.96-0.44-0.5-0.92-0.96-1.42-1.38-0.56-0.46-1.18-0.88-1.82-1.22-0.658-0.298-1.438-0.598-2.258-0.798l-0.4-0.080c-0.28-0.040-0.56-0.060-0.846-0.080-0.434-0.020-0.894-0.014-1.354 0.020-0.94 0.060-1.886 0.212-2.82 0.448-4.48 1.156-8.4 4.28-10.486 8.42-0.668 1.32-1.14 2.732-1.416 4.18-0.32 1.66-0.36 3.368-0.14 5.040 0.16 1.36 0.5 2.7 1.040 3.974 0.3 0.76 0.68 1.5 1.1 2.2 1.46 2.38 3.58 4.32 6.020 5.66 0.92 0.52 1.88 0.92 2.88 1.26 0.3 0.1 0.6 0.2 0.92 0.28z"></path>',
			'writing' => '<path d="M29.024 6.499l-2.467 2.467-3.523-3.523 2.467-2.467c0.973-0.973 2.551-0.973 3.523 0s0.973 2.55 0 3.523zM27.614 9.317c0.195-0.194 0.511-0.194 0.705 0 0.195 0.195 0.195 0.511 0 0.705l-9.16 9.161c-0.195 0.194-0.511 0.194-0.705 0s-0.194-0.51 0-0.704l5.99-5.99-4.934-4.934 2.114-2.113 4.933 4.933 1.057-1.058zM19.511 8.966l3.523 3.523-14.094 14.094-3.523-3.523 14.094-14.094zM2.246 29.754l2.466-5.989 3.523 3.523-5.989 2.466z"></path>',
		),

		// viewBox 24
		'old-24' => array(
			// from Materical Icon (icomoon.io)
			'today' => '<path d="M6.984 9.984h5.016v5.016h-5.016v-5.016zM18.984 18.984v-10.969h-13.969v10.969h13.969zM18.984 3c1.078 0 2.016 0.938 2.016 2.016v13.969c0 1.078-0.938 2.016-2.016 2.016h-13.969c-1.125 0-2.016-0.938-2.016-2.016v-13.969c0-1.078 0.891-2.016 2.016-2.016h0.984v-2.016h2.016v2.016h7.969v-2.016h2.016v2.016h0.984z"></path>',
		),

		// @REF: https://github.com/Automattic/social-logos
		'social-logos' => array(
			'youtube'     => '<path d="M21.8 8s-.195-1.377-.795-1.984c-.76-.797-1.613-.8-2.004-.847-2.798-.203-6.996-.203-6.996-.203h-.01s-4.197 0-6.996.202c-.39.046-1.242.05-2.003.846C2.395 6.623 2.2 8 2.2 8S2 9.62 2 11.24v1.517c0 1.618.2 3.237.2 3.237s.195 1.378.795 1.985c.76.797 1.76.77 2.205.855 1.6.153 6.8.2 6.8.2s4.203-.005 7-.208c.392-.047 1.244-.05 2.005-.847.6-.607.795-1.985.795-1.985s.2-1.618.2-3.237v-1.517C22 9.62 21.8 8 21.8 8zM9.935 14.595v-5.62l5.403 2.82-5.403 2.8z"/>',
			'telegram'    => '<path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm3.08 14.757s-.25.625-.936.325l-2.54-1.95-1.63 1.487s-.128.095-.267.035c0 0-.12-.01-.27-.486-.15-.476-.91-2.973-.91-2.973L6 12.35s-.387-.138-.425-.44c-.037-.3.437-.46.437-.46l10.03-3.935s.824-.362.824.238l-1.786 9.004z"/>',
			'twitter'     => '<path d="M19 3H5c-1.105 0-2 .895-2 2v14c0 1.105.895 2 2 2h14c1.105 0 2-.895 2-2V5c0-1.105-.895-2-2-2zm-2.534 6.71c.004.1.007.198.007.298 0 3.045-2.318 6.556-6.556 6.556-1.3 0-2.512-.38-3.532-1.035.18.02.364.03.55.03 1.08 0 2.073-.367 2.862-.985-1.008-.02-1.86-.685-2.152-1.6.14.027.285.04.433.04.21 0 .414-.027.607-.08-1.054-.212-1.848-1.143-1.848-2.26v-.028c.31.173.666.276 1.044.288-.617-.413-1.024-1.118-1.024-1.918 0-.422.114-.818.312-1.158 1.136 1.393 2.834 2.31 4.75 2.406-.04-.17-.06-.344-.06-.525 0-1.27 1.03-2.303 2.303-2.303.664 0 1.262.28 1.683.728.525-.103 1.018-.295 1.463-.56-.172.54-.537.99-1.013 1.276.466-.055.91-.18 1.323-.362-.31.46-.7.867-1.15 1.192z"/>',
			'twitter-alt' => '<path d="M22.23 5.924c-.736.326-1.527.547-2.357.646.847-.508 1.498-1.312 1.804-2.27-.793.47-1.67.812-2.606.996C18.325 4.498 17.258 4 16.078 4c-2.266 0-4.103 1.837-4.103 4.103 0 .322.036.635.106.935-3.41-.17-6.433-1.804-8.457-4.287-.353.607-.556 1.312-.556 2.064 0 1.424.724 2.68 1.825 3.415-.673-.022-1.305-.207-1.86-.514v.052c0 1.988 1.415 3.647 3.293 4.023-.344.095-.707.145-1.08.145-.265 0-.522-.026-.773-.074.522 1.63 2.038 2.817 3.833 2.85-1.404 1.1-3.174 1.757-5.096 1.757-.332 0-.66-.02-.98-.057 1.816 1.164 3.973 1.843 6.29 1.843 7.547 0 11.675-6.252 11.675-11.675 0-.178-.004-.355-.012-.53.802-.578 1.497-1.3 2.047-2.124z"/>',
			'facebook'    => '<path d="M20.007 3H3.993C3.445 3 3 3.445 3 3.993v16.013c0 .55.445.994.993.994h8.62v-6.97H10.27V11.31h2.346V9.31c0-2.325 1.42-3.59 3.494-3.59.993 0 1.847.073 2.096.106v2.43h-1.438c-1.128 0-1.346.537-1.346 1.324v1.734h2.69l-.35 2.717h-2.34V21h4.587c.548 0 .993-.445.993-.993V3.993c0-.548-.445-.993-.993-.993z"/>',
		),

		// @REF: https://github.com/Automattic/gridicons
		'gridicons' => array(
			'calendar' => '<path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.105 0-2 .896-2 2v13c0 1.104.895 2 2 2h14c1.104 0 2-.896 2-2V6c0-1.104-.896-2-2-2zm0 15H5V8h14v11z"/>',
		),

		// @REF: https://github.com/primer/octicons/
		'octicons' => array(
			'pulse' => '<path d="M23 15.998l-5.4-5.198-4.399 6.2-2.2-13.8-6.236 12.798h-4.764v4.002h7.2l1.8-3.6 1.8 10.799 7.2-10.199 3.199 3h6.801v-4.002h-5z"></path>',
		),

		// @REF: https://github.com/Automattic/genericons-neue
		'genericons-neue' => array(
			'download' => '<path d="M11 7H9V3H7v4H5l3 3 3-3zm-8 4v2h10v-2H3z"/>',
			'share'    => '<path d="M7 4.4V10h2V4.4l1.1 1.1 1.4-1.4L8 .6 4.5 4.1l1.4 1.4L7 4.4zM12 6h-2v1.5h2c.3 0 .5.2.5.5v4c0 .3-.2.5-.5.5H4c-.3 0-.5-.2-.5-.5V8c0-.3.2-.5.5-.5h2V6H4c-1.1 0-2 .9-2 2v4c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2z"/>',
			'link'     => '<path d="M13 4h-3c-1.1 0-2 .9-2 2v.8H7V6c0-1.1-.9-2-2-2H2C.9 4 0 4.9 0 6v3c0 1.1.9 2 2 2h3c1.1 0 2-.9 2-2v-.8h1V9c0 1.1.9 2 2 2h3c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM5.5 9c0 .3-.2.5-.5.5H2c-.3 0-.5-.2-.5-.5V6c0-.3.2-.5.5-.5h3c.3 0 .5.2.5.5v.8H5c-.4 0-.8.3-.8.8s.4.6.8.6h.5V9zm8 0c0 .3-.2.5-.5.5h-3c-.3 0-.5-.2-.5-.5v-.8h.5c.4 0 .8-.3.8-.8s-.4-.6-.8-.6h-.5V6c0-.3.2-.5.5-.5h3c.3 0 .5.2.5.5v3z"/>',
			'time'     => '<path d="M8 2C4.7 2 2 4.7 2 8s2.7 6 6 6 6-2.7 6-6-2.7-6-6-6zm2.5 9.5L7.2 8.3V4h1.5v3.7l2.8 2.8-1 1z"/>',
			'month'    => '<path d="M12 3h-1V2H9v1H7V2H5v1H4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 7c0 .4-.2.7-.4 1 .2.3.4.6.4 1v.5c0 .8-.7 1.5-1.5 1.5h-1C5.7 11 5 10.3 5 9.5V9h1v.5c0 .3.2.5.5.5h1c.3 0 .5-.2.5-.5V9c0-.3-.2-.5-.5-.5H7v-1h.5c.3 0 .5-.2.5-.5v-.5c0-.3-.2-.5-.5-.5h-1c-.3 0-.5.2-.5.5V7H5v-.5C5 5.7 5.7 5 6.5 5h1C8.3 5 9 5.7 9 6.5V7zm2 4h-1V5h1v6z"/>',
		),

		// @REF: https://github.com/IKAcc/Gorbeh
		'gorbeh' => array(
			'aparat' => '<path d="M157.378 11.377q9.755.092 19.283 2.627l44.31 11.79q-38.325 5.526-82.366 30.952-44.04 25.427-67.942 55.83l11.68-43.898q6.88-25.86 27.93-41.702 21.052-15.842 47.106-15.6zm98.027 31.156q89.144 0 152.234 62.295t63.09 150.315q0 88.022-63.09 150.317-63.09 62.294-152.235 62.294-89.147 0-152.237-62.294-63.09-62.295-63.09-150.316t63.09-150.316q63.09-62.295 152.237-62.295zm143.77 30.678l45.814 12.19q30.953 8.238 46.933 35.8 15.979 27.565 7.742 58.518l-12.984 48.797q-4.827-39.603-30.466-85.108-25.64-45.504-57.04-70.196zM195.06 99.216q-25.43 0-43.426 17.997-17.996 17.998-17.996 43.427t17.997 43.425 43.425 17.997q25.43 0 43.427-17.996 17.998-17.997 17.998-43.426 0-25.43-17.998-43.428-17.997-17.997-43.427-17.997zm153.4 29.5q-25.43 0-43.427 17.997-17.996 17.998-17.996 43.427t17.997 43.425q17.998 17.997 43.425 17.997 25.43 0 43.427-17.996 17.997-17.997 17.997-43.426 0-25.43-17.997-43.428-17.997-17.997-43.428-17.997zm-93.128 100.93q-11.342 0-19.368 8.027-8.026 8.026-8.026 19.367 0 11.34 8.026 19.367 8.026 8.026 19.366 8.026h.003q11.34 0 19.366-8.026 8.025-8.026 8.025-19.366 0-11.342-8.026-19.368-8.027-8.026-19.368-8.026zm-91.457 30.557q-25.43 0-43.428 17.996-17.997 17.997-17.997 43.426 0 25.43 17.997 43.427t43.427 17.997q25.43 0 43.426-17.998 17.997-17.998 17.997-43.426T207.3 278.2Q189.303 260.2 163.875 260.2zm154.24 28.656q-25.427 0-43.424 17.997-17.997 17.998-17.997 43.425 0 25.43 17.996 43.428 17.997 17.997 43.426 17.997 25.43 0 43.427-17.997 17.997-17.998 17.997-43.426 0-25.43-17.997-43.428-17.997-17.996-43.427-17.996zm-294.502.533q5.347 37.138 29.684 80.285 24.337 43.146 53.378 66.974L67.01 426.095q-30.953-8.237-46.933-35.8Q4.1 362.732 12.335 331.78zm418.55 106.43l-12.505 46.998q-8.237 30.953-35.8 46.932-27.566 15.978-58.52 7.742l-41.765-11.113q38.307-6.413 81.843-32.947 43.537-26.534 66.747-57.614z"/>',
		),

		// @REF: https://github.com/cedaro/themicons
		'themicons' => array(),

		// @REF: https://simpleicons.org/
		'simpleicons' => array(),

		// @REF: https://linearicons.com/
		'linearicons' => array(),
	);

	public static $viewboxes = array(
		'old'             => '0 0 32 32',
		'old-24'          => '0 0 24 24',
		'social-logos'    => '0 0 24 24',
		'gridicons'       => '0 0 24 24',
		'octicons'        => '0 0 28 32',
		'genericons-neue' => '0 0 16 16',
		'gorbeh'          => '0 0 512 512',
	);

	public static function guess( $for, $fallback = '' )
	{
		switch ( $for ) {

			case 'twitter':
			case 'facebook':
				return $for;
			break;

			// case 'youtube':
			case 'telegram':
				return [ 'old', $for ];
			break;

			case 'mobile':
			case 'phone':
				return 'phone';
		}

		return $fallback;
	}

	public static function get( $icon, $group )
	{
		return '<span data-icon="svg" class="'.static::BASE.'-icon -iconsvg icon-'.$group.'-'.$icon.'"><svg><use xlink:href="#icon-'.$group.'-'.$icon.'"></use></svg></span>';
	}

	// FIXME: use css background
	// SEE: #adminmenu div.wp-menu-image.svg
	// SEE: https://stackoverflow.com/a/19570011
	public static function wrapBase64( $data )
	{
		return '<span data-icon="base64" class="'.static::BASE.'-icon -iconbase64"><img src="'.$data.'" /></span>';
	}

	public static function wrapURL( $url )
	{
		return '<span data-icon="url" class="'.static::BASE.'-icon -iconurl"><img src="'.$url.'" /></span>';
	}

	// @REF: https://stackoverflow.com/a/42265057
	// The fill="black" is important. Without the fill attribute, WordPress won’t be able to match the admin color scheme
	public static function getBase64( $icon, $group )
	{
		if ( isset( self::$icons[$group][$icon] ) )
			return 'data:image/svg+xml;base64,'.base64_encode(
				'<svg width="20" height="20" viewBox="'.self::$viewboxes[$group].'" xmlns="http://www.w3.org/2000/svg">'
				.str_replace( '<path d="', '<path fill="black" d="', self::$icons[$group][$icon] )
				// .preg_replace( '/abc/', '', self::$icons[$group][$icon], 1 )
				.'</svg>'
			);

		return FALSE;
	}

	public static function printSprites( $icons = array() )
	{
		if ( empty( $icons ) )
			return;

		echo '<svg style="position: absolute; width: 0; height: 0; overflow: hidden" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><defs>';

		foreach ( $icons as $icon )
			if ( isset( self::$icons[$icon['group']][$icon['icon']] ) )
				echo '<symbol id="icon-'.$icon['group'].'-'.$icon['icon'].'" viewBox="'.self::$viewboxes[$icon['group']].'"><title>'.$icon['icon'].'</title>'.self::$icons[$icon['group']][$icon['icon']].'</symbol>';

		echo '</defs></svg>';
	}
}
