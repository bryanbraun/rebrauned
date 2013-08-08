<?php

/*
 * Implement hook_preprocess_html().
 */
function rebrauned_preprocess_html(&$vars) {

  // Add a theme-specific css class to the body tag.
  $vars['classes_array'][] = 'rebrauned';
  $vars['classes_array'][] = _rebrauned_get_layout();
  
  // Add css for our fonts.
  drupal_add_css(
    'http://fonts.googleapis.com/css?family=Vollkorn:400italic,400,700|Oswald',
    array('type' => 'external')
  );
  
  // Set up meta tags.
  // Modern IE & chrome-frame rendering engine tag.
  $rendering_meta = array(
    '#tag' => 'meta',
    '#attributes' => array(
      'http-equiv' => 'X-UA-Compatible',
      'content' => 'IE=edge,chrome=1',
    ),  
  );
  // Mobile viewport tag.
  $mobile_meta = array(
    '#tag' => 'meta',
    '#attributes' => array(
      'name' => 'viewport',
      'content' => 'width=device-width',
    ),
  );
 // Include meta tags.
  drupal_add_html_head($rendering_meta, 'rendering_meta');
  drupal_add_html_head($mobile_meta, 'responsive_meta');
}

/**
 * Implements hook_html_head_alter().
 */
function rebrauned_html_head_alter(&$head_elements) {
  // If the theme's info file contains the custom theme setting
  // default_favicon_path, change the favicon <link> tag to reflect that path.
  if (($default_favicon_path = theme_get_setting('default_favicon_path')) && theme_get_setting('default_favicon')) {
    $favicon_url = file_create_url(path_to_theme() . '/' . $default_favicon_path);
  }
  else {
    if (module_exists('gardens_misc')) {
      $favicon_url = file_create_url(drupal_get_path('module', 'gardens_misc') . '/images/gardens.ico');
    }
  }
  if (!empty($favicon_url)) {
    $favicon_mimetype = file_get_mimetype($favicon_url);
    foreach ($head_elements as &$element) {
      if (isset($element['#attributes']['rel']) && $element['#attributes']['rel'] == 'shortcut icon') {
	$element['#attributes']['href'] = $favicon_url;
	$element['#attributes']['type'] = $favicon_mimetype;
      }
    }
  }
}

/**
* Implements hook_preprocess_page().
*/

function rebrauned_preprocess_page(&$variables) {
  $is_front = $variables['is_front'];
  // Adjust the html element that wraps the site name. h1 on front page, p on other pages
  $variables['wrapper_site_name_prefix'] = ($is_front ? '<h1' : '<p');
  $variables['wrapper_site_name_prefix'] .= ' id="site-name"';
  $variables['wrapper_site_name_prefix'] .= ' class="site-name'.($is_front ? ' site-name-front' : '').'"';
  $variables['wrapper_site_name_prefix'] .= '>';
  $variables['wrapper_site_name_suffix'] = ($is_front ? '</h1>' : '</p>');
  // If the theme's info file contains the custom theme setting
  // default_logo_path, set the $logo variable to that path.
  $default_logo_path = theme_get_setting('default_logo_path');
  if (!empty($default_logo_path) && theme_get_setting('default_logo')) {
    $variables['logo'] = file_create_url(path_to_theme() . '/' . $default_logo_path);
  }
  else {
    $variables['logo'] = null;
  }
  
  //Arrange the elements of the main content area (content and sidebars) based on the layout class
  $layoutClass = _rebrauned_get_layout();
  $layout = substr(strrchr($layoutClass, '-'), 1); //Get the last bit of the layout class, the 'abc' string
  
  $contentPos = strpos($layout, 'c');
  $sidebarsLeft = substr($layout,0,$contentPos);
  $sidebarsRight = strrev(substr($layout,($contentPos+1))); // Reverse the string so that the floats are correct.
  
  $sidebarsHidden = ''; // Create a string of sidebars that are hidden to render and then display:none
  if(stripos($layout, 'a') === false) { $sidebarsHidden .= 'a'; }
  if(stripos($layout, 'b') === false) { $sidebarsHidden .= 'b'; }
  
  $variables['sidebars']['left'] = str_split($sidebarsLeft);
  $variables['sidebars']['right'] = str_split($sidebarsRight);
  $variables['sidebars']['hidden'] = str_split($sidebarsHidden);
}

/**
 * Implement hook_preprocess_block().
 */
function rebrauned_preprocess_block(&$vars) {
  $vars['content_attributes_array']['class'][] = 'content';
}

/**
 * Retrieves the value associated with the specified key from the current theme.
 * If the key is not found, the specified default value will be returned instead.
 *
 * @param <string> $key
 *   The name of the key.
 * @param <mixed> $default
 *   The default value, returned if the property key is not found in the current
 *   theme.
 * @return <mixed>
 *   The value associated with the specified key, or the default value.
 */
function _rebrauned_variable_get($key, $default) {
  global $theme;
  $themes_info =& drupal_static(__FUNCTION__);
  if (!isset($themes_info[$theme])) {
    $themes_info = system_get_info('theme');
  }

  $value = $themes_info[$theme];
  foreach (explode('/', $key) as $part) {
    if (!isset($value[$part])) {
      return $default;
    }
    $value = $value[$part];
  }
  return $value;
}

/**
 * Returns the name of the layout class associated with the current path.  The
 * layout name is used as a body class, which causes the page to be styled
 * with the corresponding layout.  This function makes it possible to use
 * different layouts on various pages of a site.
 *
 * @return <string>
 *   The name of the layout associated with the current page.
 */
function _rebrauned_get_layout() {
  $layout_patterns = _rebrauned_variable_get('layout', array('<global>' => 'body-layout-fixed-ca'));
  $global_layout = $layout_patterns['<global>'];
  unset($layout_patterns['<global>']);

  $alias_path = drupal_get_path_alias($_GET['q']);
  $path = $_GET['q'];
  foreach ($layout_patterns as $pattern => $layout) {
    if (drupal_match_path($alias_path, $pattern) ||
        drupal_match_path($path, $pattern)) {
      return $layout;
    }
  }
  return $global_layout;
}

/**
 * Implements hook_node_view_alter().
 */
function rebrauned_node_view_alter(&$build) {
  if (isset($build['links']) && isset($build['links']['comment']) &&
    isset($build['links']['comment']['#attributes']) &&
    isset($build['links']['comment']['#attributes']['class'])) {
    $classes = $build['links']['comment']['#attributes']['class'];
    array_push($classes, 'actions');
    $build['links']['comment']['#attributes']['class'] = $classes;
  }
  if (isset($build['#node']->type) && $build['#node']->type == 'testimonial' && isset($build['body'])) {
    $wrapper = '<div class="quote-top"><div class="slide-right"><div class="slide-left"></div></div></div><div class="content-right"><div class="content-left">';
    $wrapper .= $build['body'][0]['#markup'];
    $wrapper .= '</div></div><div class="quote-bottom"><div class="slide-right"><div class="slide-left"></div></div></div>';
    $build['body'][0]['#markup'] = $wrapper;
  }
}

/**
 * Implements hook_preprocess_forum_topic_list
 */
function rebrauned_preprocess_forum_topic_list(&$vars) {
  // Recreate the topic list header
  $list = array(
    array('data' => t('Topic'), 'field' => 'f.title'),
    array('data' => t('Replies'), 'field' => 'f.comment_count'),
    array('data' => t('Created'), 'field' => 't.created'),
    array('data' => t('Last reply'), 'field' => 'f.last_comment_timestamp'),
  );
  
  $ts = tablesort_init($list);
  $header = '';
  foreach ($list as $cell) {
    $cell = tablesort_header($cell, $list, $ts);
    $header .= _theme_table_cell($cell, TRUE);
  }
  $vars['header'] = $header;
}

/*
 * Implements hook_preprocess_media_gallery_license().
 */
function rebrauned_preprocess_media_gallery_license(&$vars) {
  if (in_array($vars['element']['#view_mode'], array('media_gallery_thumbnail', 'media_gallery_detail'))) {
    $vars['color'] = 'light';
  }
}

/**
 * Implements hook_preprocess_node().
 *
 * Reformat date info.
 */
function rebrauned_preprocess_node(&$variables) {
  // Use Drupal's format_date function to reformat dates.
  $variables['clean_date'] = format_date($variables['created'], 'custom', 'F d, Y');
}

/**
 * Implements hook_preprocess_comment().
 *
 * Clean up the default comment meta info.
 */
function rebrauned_preprocess_comment(&$variables) {
  $comment = $variables['comment'];
  // Use Drupal's format_date function to reformat dates for the <time> element.
  $clean_date = format_date($comment->created, 'custom', 'F d, Y');
  $variables['clean_date'] = $clean_date;
}