<!--

  support for more fields:
    info.datetime
    info.time ago using sprintf() with i18n.timeago

-->
<template>
  <div class="grid-info">
    <div class="grid-info-image" v-show="info.image"><img :src="info.image" alt="" /></div>
    <h4 class="grid-info-head">{{ info.title }}<a v-show="info.link" :href="info.link" target="_blank" class="grid-info-link"><span class="dashicons dashicons-external"></span></a></h4>
    <div class="grid-info-desc" v-show="info.description" v-html="info.description"></div>
    <div class="grid-info-author" v-show="info.author" v-html="info.author"></div>
  </div>
</template>

<style lang="scss">
$brand-success: #5cb85c !default;
$brand-info:    #5bc0de !default;
$brand-warning: #f0ad4e !default;
$brand-danger:  #d9534f !default;

.grid-info {
  font-size: .75rem !important;
  margin: 1rem;
  min-width: 145px;
}

.grid-info-image {
  text-align: center;

  img {
    max-width: 120px;
    height: auto;
	}
}

.grid-info-head {
  margin: 0.5rem 0;
}

.grid-info-link {
  margin: 0 0.25rem;
  font-size: smaller;
  color: inherit;
  text-decoration: none;

  &:hover {
    color: #333;
  }

  span {
    font-size: 14px;
  }
}

.grid-info-desc {
  p {
    margin: 0.5rem 0;
    font-size: .75rem !important;
  }

  p:last-of-type {
    margin-bottom: 0;
  }
}

.grid-info-author {
  margin-top: 0.5rem;
  font-size: .5rem !important;
}
</style>

<script>
export default {
  props: ['info', 'item']
}
</script>
