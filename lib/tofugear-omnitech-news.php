<?php

function tgom_onQueryVars($query_vars) {
  $query_vars[] = "tofuom";
  $query_vars[] = "paged";
  $query_vars[] = "posts_per_page";
  $query_vars[] = "tofuom_news_id";
  return $query_vars;
}

/**
 * Hook when request is initiated, include two parts
 *
 * First part will output the thumbnail image and excerpt of all the acticles by page
 * the param 'image_url' is from the following resource (by priority)
 * feature image >> first image of attachement >> first image of acticle content
 *
 * Second part will output the details of the specified article.
 *
 */
function tgom_onParseRequest() {
  global $wp;
  if (!array_key_exists('tofuom', $wp->query_vars)) {
    return;
  }
  if (!array_key_exists('tofuom_news_id', $wp->query_vars)) {
    $page = $wp->query_vars['paged'];
    // limit the page size to 100 to avoid malicious action
    $page_size = $wp->query_vars['posts_per_page'] > 100? 100 : $wp->query_vars['posts_per_page'];

    $args = array(
      'post_status' => 'publish',
      'posts_per_page' => $page_size,
      'paged' => $page,
      'orderby' => 'post_date',
      'order' => 'DES'
    );

    $query = new WP_Query( $args );

    $articles = array();
    $page_info = array();

    // add page info
    $page_info = array(
      'current_page' => $page,
      'total_pages' => $query->max_num_pages,
      'total_count' => wp_count_posts()->publish,
    );

    while( $query->have_posts() ) : $query->the_post();
      if (get_post_status() != 'publish') {
        // Post with status like 'pending', 'draft' should not be shown to users
        continue;
      }
      // Add an article entry
      $content = get_the_content();
      $id = get_the_id();
      $images = tgom_getImagesFromHtml($content);
      $attachments = tgom_getAttachmentsByPostID($id);
      $image_url = '';
      if(has_post_thumbnail($id)) {
        $dom = simplexml_load_string(get_the_post_thumbnail($id, 'mobile_thumbnail'));
        $image_url = (string)$dom->attributes()->src;
      } else if (wp_attachment_is_image($id) && count($attachments) > 0) {
        $image_url = wp_get_attachment_image_url($attachments[0]->ID, 'mobile_thumbnail');
      }

      $articles[] = array(
        'id' => $id,
        'title' => tgom_stripHtmlTags(get_the_title()),
        'excerpt' =>  tgom_limit_text(tgom_stripHtmlTags($content), 80),
        'status' => get_post_status(),
        //'create_at' => get_the_date(DateTime::ISO8601),
        //'update_at' => get_the_modified_date(DateTime::ISO8601),
        'publish_start_date' => get_the_date(DateTime::ISO8601),
        // use mobile thumbnail image first, if not use first image in html content
        'image_url' => tgom_replaceImageUrlByCDNUrl((boolean)$image_url ? $image_url: (count($images) > 0 ? $images[0] : ''))
      );
    endwhile;

    wp_reset_query();

    $output = (object) [
      'page_info' => $page_info,
      'articles' => $articles,
    ];

    tgom_renderJson($output);
  } else {
    $article_id = $wp->query_vars['tofuom_news_id'];
    $article = get_post($article_id);
    //$content = $article->post_content; // don't do this!
    $content = apply_filters( 'the_content', $article->post_content );

    $image_attachments = array();

    $attachments = tgom_getAttachmentsByPostID( $article_id );
    if ( $attachments ) {
      foreach ( $attachments as $attachment ) {
        $image_attachments[] = array (
          'image_mobile_large_url' => tgom_replaceImageUrlByCDNUrl(wp_get_attachment_image_url($attachment->ID, 'mobile_large')),
          'image_mobile_small_url' => tgom_replaceImageUrlByCDNUrl(wp_get_attachment_image_url($attachment->ID, 'mobile_thumbnail')),
        );
      }
    }

    $output = (object)[
      'id' => $article_id,
      'content' => $content,
      'title' => $article->post_title,
      //'share_url' => get_permalink($article_id),
      'publish_start_date' => $article->post_date,
      'article_image_attachments' => $image_attachments
    ];

    tgom_renderJson((object)['article' => $output]);
  }
  exit;
}

/**
 * render object as JSON
 */
function tgom_renderJson($output) {
  header( 'Content-type: application/json' );
  echo json_encode($output);
}

/**
 * limit text length for excerpt
 */
function tgom_limit_text($text,$length = 7){
  if( mb_strlen($text, mb_detect_encoding($text)) < $length + 10 ) return $text; //don't cut if too short
  //$break_pos = mb_strpos($text, ' ', $length, mb_detect_encoding($text)); //find next space after desired length
  $visible = mb_substr($text, 0, $length, mb_detect_encoding($text));
  return $visible;
}

/**
 * strip all html tags whether encoded or not
 */
function tgom_stripHtmlTags($html) {
  return wp_strip_all_tags(html_entity_decode($html));
}

/**
 * get images from html
 */
function tgom_getImagesFromHtml($content) {
  $linkArray = array();
  if(preg_match_all("/<img\s+.*?src=[\"\']?([^\"\' >]*)[\"\']?[^>]*>/i",$content,$matches,PREG_SET_ORDER)){
    foreach($matches as $match) {
      array_push($linkArray,$match[1]);
    }
  }
  return $linkArray;
}

/**
 * get attachments by post ID
 */
function tgom_getAttachmentsByPostID($post_id) {
  $args = array(
   'post_type' => 'attachment',
   'posts_per_page' => -1,
   'post_status' => 'any',
   'post_parent' => $post_id
  );

  return get_posts( $args );
}

function tgom_replaceImageUrlByCDNUrl($image_url) {
  $cdn_url = get_option('cdn_url');
  if (trim($cdn_url)!='') {
    $index = strpos($image_url, '/', 9);
    return $cdn_url.substr($image_url, $index);
  } else {
    return $image_url;
  }
}

/**
 * Setup the custom route for own news API. To flush the
 * rewrite cache manually, refer to:
 *
 * https://codex.wordpress.org/Rewrite_API/flush_rules
 *
 * The endpoint is created as follow:
 *
 * /news-api/page/1/page_size/10
 * /news-api/detail/1
 */
function tgom_setup() {
  global $wp, $wp_rewrite;
  add_rewrite_rule(
    'tofuom-news-api/page/([^/]+)/page_size/([^/]+)/?',
    'index.php?tofuom=newsfeed&paged=$matches[1]&posts_per_page=$matches[2]',
    'top'
  );
  add_rewrite_rule(
    'tofuom-news-api/detail/([^/]+)/?',
    'index.php?tofuom=newsfeed&tofuom_news_id=$matches[1]',
    'top'
  );
  // Remove below in production. For debugging only
  $wp_rewrite->flush_rules(false);
}

