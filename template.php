<?php

$theme_path = drupal_get_path('theme', 'brutus_bootstrap');
require_once $theme_path . '/includes/bootstrap.inc';
require_once $theme_path . '/includes/theme.inc';
require_once $theme_path . '/includes/pager.inc';
require_once $theme_path . '/includes/form.inc';
require_once $theme_path . '/includes/admin.inc';
require_once $theme_path . '/includes/menu.inc';

// Load module specific files in the modules directory.
$includes = file_scan_directory($theme_path . '/includes/modules', '/\.inc$/');
foreach ($includes as $include) {
  if (module_exists($include->name)) {
    require_once $include->uri;
  }    
}

// Auto-rebuild the theme registry during theme development.
if (theme_get_setting('brutus_bootstrap_rebuild_registry') && !defined('MAINTENANCE_MODE')) {
  // Rebuild .info data.
  system_rebuild_theme_data();
  // Rebuild theme registry.
  drupal_theme_rebuild();
}

/**
 * hook_theme() 
 */
function brutus_bootstrap_theme(&$existing, $type, $theme, $path) {
  // If we are auto-rebuilding the theme registry, warn about the feature.
  if (
    // Only display for site config admins.
    function_exists('user_access') && user_access('administer site configuration')
    && theme_get_setting('brutus_bootstrap_rebuild_registry')
    // Always display in the admin section, otherwise limit to three per hour.
    && (arg(0) == 'admin' || flood_is_allowed($GLOBALS['theme'] . '_rebuild_registry_warning', 3))
  ) {
    flood_register_event($GLOBALS['theme'] . '_rebuild_registry_warning');
    drupal_set_message(t('For easier theme development, the theme registry is being rebuilt on every page request. It is <em>extremely</em> important to <a href="!link">turn off this feature</a> on production websites.', array('!link' => url('admin/appearance/settings/' . $GLOBALS['theme']))), 'warning', FALSE);
  }
  
  return array(
    'brutus_bootstrap_links' => array(
      'variables' => array(
        'links' => array(),
        'attributes' => array(),
        'heading' => NULL
      ),
    ),
    'brutus_bootstrap_btn_dropdown' => array(
      'variables' => array(
        'links' => array(),
        'attributes' => array(),
        'type' => NULL
      ),
    ),
    'brutus_bootstrap_search_form_wrapper' => array(
      'render element' => 'element',
    ),
  );
}

/**
 * Override theme_breadrumb().
 *
 * Print breadcrumbs as a list, with separators.
 */
function brutus_bootstrap_breadcrumb($variables) {
  $breadcrumb = $variables['breadcrumb'];

  if (!empty($breadcrumb)) {
    $breadcrumbs = '<ul class="breadcrumb">';
    
    $count = count($breadcrumb) - 1;
    foreach ($breadcrumb as $key => $value) {
      if ($count != $key) {
        $breadcrumbs .= '<li>' . $value . '<span class="divider">/</span></li>';
      }
      else{
        $breadcrumbs .= '<li>' . $value . '</li>';
      }
    }
    $breadcrumbs .= '</ul>';
    
    return $breadcrumbs;
  }
}

/**
 * Implements template_html_head_alter();
 *
 * Changes the default meta content-type tag to the shorter HTML5 version
 */
function brutus_bootstrap_html_head_alter(&$head_elements) {
  $head_elements['system_meta_content_type']['#attributes'] = array(
    'charset' => 'utf-8'
  );
}

/**
 * Override or insert variables in the html_tag theme function.
 */
function brutus_bootstrap_process_html_tag(&$variables) {
  $tag = &$variables['element'];

  if ($tag['#tag'] == 'style' || $tag['#tag'] == 'script') {
    // Remove redundant type attribute and CDATA comments.
    unset($tag['#attributes']['type'], $tag['#value_prefix'], $tag['#value_suffix']);

    // Remove media="all" but leave others unaffected.
    if (isset($tag['#attributes']['media']) && $tag['#attributes']['media'] === 'all') {
      unset($tag['#attributes']['media']);
    }
  }
}

/**
 * Implements template_preprocess_html().
 */
function brutus_bootstrap_preprocess_html(&$variables) {
  
  $variables['doctype'] = '<!DOCTYPE html>' . "\n";
  $variables['html5shiv'] = '<!--[if lt IE 9]><script src="'. base_path() . path_to_theme() .'/js/asset/js/html5shiv.js"></script><![endif]-->';
  if (isset($variables['node'])) {
    // For full nodes.
    $variables['classes_array'][] = ($variables['node']) ? 'full-node' : '';
    // For forums.
    $variables['classes_array'][] = (($variables['node']->type == 'forum') || (arg(0) == 'forum')) ? 'forum' : '';
  }
  else {
    // Forums.
    $variables['classes_array'][] = (arg(0) == 'forum') ? 'forum' : '';
  }
  if (module_exists('panels') && function_exists('panels_get_current_page_display')) {
    $variables['classes_array'][] = (panels_get_current_page_display()) ? 'panels' : '';
  }
  
  // Since menu is rendered in preprocess_page we need to detect it here to add body classes
  $has_main_menu = theme_get_setting('toggle_main_menu');
  $has_secondary_menu = theme_get_setting('toggle_secondary_menu');

  /* Add extra classes to body for more flexible theming */

  if ($has_main_menu or $has_secondary_menu) {
    $variables['classes_array'][] = 'with-navigation';
  }

  if ($has_secondary_menu) {
    $variables['classes_array'][] = 'with-subnav';
  }

  if (!empty($variables['page']['featured'])) {
    $variables['classes_array'][] = 'featured';
  }

  if ($variables['is_admin']) {
    $variables['classes_array'][] = 'admin';
  }

  if (!empty($variables['page']['sidebar_first'])) {
    $variables['classes_array'][] = 'two-sidebar';
  }
  if (!empty($variables['page']['sidebar_second'])) {
    $variables['classes_array'][] = 'two-sidebar';
  }
  if (!empty($variables['page']['sidebar_first'])) {
    $variables['classes_array'][] = 'sidebar-left';
  }

  if (!empty($variables['page']['sidebar_second'])) {
    $variables['classes_array'][] = 'sidebar-right';
  }

  if (!empty($variables['page']['header_top'])) {
    $variables['classes_array'][] = 'header_top';
  }

  if ($variables['is_admin']) {
    $variables['classes_array'][] = 'admin';
  }

  if (!$variables['is_front']) {
    // Add unique classes for each page and website section
    $path = drupal_get_path_alias($_GET['q']);
    $temp = explode('/', $path, 2);
    $section = array_shift($temp);
    $page_name = array_shift($temp);

    if (isset($page_name)) {
      $variables['classes_array'][] = brutus_bootstrap_id_safe('page-' . $page_name);
    }

    $variables['classes_array'][] = brutus_bootstrap_id_safe('section-' . $section);

    // add template suggestions
    $variables['theme_hook_suggestions'][] = "page__section__" . $section;
    $variables['theme_hook_suggestions'][] = "page__" . $page_name;

    if (arg(0) == 'node') {
      if (arg(1) == 'add') {
        if ($section == 'node') {
          array_pop($variables['classes_array']); // Remove 'section-node'
        }
        $variables['classes_array'][] = 'section-node-add'; // Add 'section-node-add'
      } elseif (is_numeric(arg(1)) && (arg(2) == 'edit' || arg(2) == 'delete')) {
        if ($section == 'node') {
          array_pop($variables['classes_array']); // Remove 'section-node'
        }
        $variables['classes_array'][] = 'section-node-' . arg(2); // Add 'section-node-edit' or 'section-node-delete'
      }
    }
  }
}

/**
 * Preprocess variables for page.tpl.php
 *
 * @see page.tpl.php
 */
function brutus_bootstrap_preprocess_page(&$variables) {
  // Add information about the number of sidebars.
  if (!empty($variables['page']['sidebar_first']) && !empty($variables['page']['sidebar_second'])) {
    $variables['columns'] = 3;
  }
  elseif (!empty($variables['page']['sidebar_first'])) {
    $variables['columns'] = 2;
  }
  elseif (!empty($variables['page']['sidebar_second'])) {
    $variables['columns'] = 2;
  }
  else {
    $variables['columns'] = 1;
  }

  // Primary nav
  $variables['primary_nav'] = FALSE;
  if ($variables['main_menu']) {
    // Build links
    $variables['primary_nav'] = menu_tree(variable_get('menu_main_links_source', 'main-menu'));
    // Provide default theme wrapper function
    $variables['primary_nav']['#theme_wrappers'] = array('menu_tree__primary');
  }

  // Secondary nav
  $variables['secondary_nav'] = FALSE;
  if ($variables['secondary_menu']) {
    // Build links
    $variables['secondary_nav'] = menu_tree(variable_get('menu_secondary_links_source', 'user-menu'));
    // Provide default theme wrapper function
    $variables['secondary_nav']['#theme_wrappers'] = array('menu_tree__secondary');
  }
  if (isset($variables['node'])) {
    // If the node type is "blog" the template suggestion will be "page--blog.tpl.php".
     $variables['theme_hook_suggestions'][] = 'page__'. str_replace('_', '--', $variables['node']->type);
  }

}

/**
 * Bootstrap theme wrapper function for the primary menu links
 */
function brutus_bootstrap_menu_tree__primary(&$variables) {
  return '<ul class="menu nav">' . $variables['tree'] . '</ul>';
}

/**
 * Bootstrap theme wrapper function for the secondary menu links
 */
function brutus_bootstrap_menu_tree__secondary(&$variables) {
  return '<ul class="menu nav pull-right">' . $variables['tree'] . '</ul>';
}

/**
 * Returns HTML for a single local action link.
 *
 * This function overrides theme_menu_local_action() to add the icons that ship
 * with Bootstrap to the action links.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with "title", "href", "localized_options", and
 *       "icon" keys. If "icon" is not passed, it defaults to "plus-sign".
 *
 * @ingroup themeable
 *
 * @see theme_menu_local_action().
 */
function brutus_bootstrap_menu_local_action($variables) {
  $link = $variables['element']['#link'];

  // Build the icon rendering element.
  if (empty($link['icon'])) {
    $link['icon'] = 'plus-sign';
  }
  $icon = '<i class="' . drupal_clean_css_identifier('icon-' . $link['icon']) . '"></i>';

  // Format the action link.
  $output = '<li>';
  if (isset($link['href'])) {
    $options = isset($link['localized_options']) ? $link['localized_options'] : array();

    // If the title is not HTML, sanitize it.
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }

    // Force HTML so we can add the icon rendering element.
    $options['html'] = TRUE;
    $output .= l($icon . $link['title'], $link['href'], $options);
  }
  elseif (!empty($link['localized_options']['html'])) {
    $output .= $icon . $link['title'];
  }
  else {
    $output .= $icon . check_plain($link['title']);
  }
  $output .= "</li>\n";

  return $output;
}

/**
 * Preprocess variables for node.tpl.php
 *
 * @see node.tpl.php
 */
function brutus_bootstrap_preprocess_node(&$variables) {
  if ($variables['teaser']) {
    $variables['classes_array'][] = 'row-fluid';
  }
  $variables['view_mode'] = $variables['elements']['#view_mode'];
  // Provide a distinct $teaser boolean.
  $variables['teaser'] = $variables['view_mode'] == 'teaser';
  $variables['node'] = $variables['elements']['#node'];
  $node = $variables['node'];

  $variables['date']      = format_date($node->created);
  $variables['name']      = theme('username', array('account' => $node));

  $uri = entity_uri('node', $node);
  $variables['node_url']  = url($uri['path'], $uri['options']);
  $variables['title']     = check_plain($node->title);
  $variables['page']      = $variables['view_mode'] == 'full' && node_is_page($node);

  // Flatten the node object's member fields.
  $variables = array_merge((array) $node, $variables);
  // Clean up name so there are no underscores.
  $node = $variables['node'];
  $variables['theme_hook_suggestions'][] = 'node__' . $node->type;
  $variables['theme_hook_suggestions'][] = 'node__' . $node->nid;
 // Add to array of handy node classes
  $variables['classes_array'][] = $variables['zebra'];                              // Node is odd or even
  $variables['classes_array'][] = (!$variables['teaser']) ? 'full-node' : '';       // Node is teaser or full-node

  $node_top_blocks = block_get_blocks_by_region('node_top');
  $node_bottom_blocks = block_get_blocks_by_region('node_bottom');
  if ($node_top_blocks) {
    $variables['node_top'] = $node_top_blocks; 
  }
  if ($node_bottom_blocks) {
    $variables['node_bottom'] = $node_bottom_blocks;
  }

  // Node is published
  $variables['classes_array'][] = ($variables['status']) ? 'published' : 'unpublished';

  // Node has comments?
  $variables['classes_array'][] = ($variables['comment']) ? 'with-comments' : 'no-comments';

  if ($variables['sticky']) {
    $variables['classes_array'][] = 'sticky'; // Node is sticky
  }

  if ($variables['promote']) {
    $variables['classes_array'][] = 'promote'; // Node is promoted to front page
  }


  if ($variables['uid'] && $variables['uid'] === $GLOBALS['user']->uid) {
    $classes[] = 'node-mine'; // Node is authored by current user.
  }
  
  $variables['submitted'] = t('Submitted by !username on', array('!username' => $variables['name']));
  $variables['submitted_date'] = t('!datetime', array('!datetime' => $variables['date']));
  $variables['submitted_pubdate'] = format_date($variables['created'], 'custom', 'Y-m-d');
}

/**
 * Views preprocessing
 * Add view type class (e.g., node, teaser, list, table)
 */
function brutus_bootstrap_preprocess_views_view(&$variables) {
  $variables['css_name'] = $variables['css_name'] . ' view-style-' . drupal_clean_css_identifier(strtolower($variables['view']->plugin_name));
}

/**
 * Preprocess variables for region.tpl.php
 *
 * @see region.tpl.php
 */
function brutus_bootstrap_preprocess_region(&$variables, $hook) {
  if ($variables['region'] == 'content') {
    $variables['theme_hook_suggestions'][] = 'region__no_wrapper';
  }
  
  if ($variables['region'] == "sidebar_first") {
    $variables['classes_array'][] = 'well';
  }
}

/**
 * Preprocess variables for block.tpl.php
 *
 * @see block.tpl.php
 */
function brutus_bootstrap_preprocess_block(&$variables, $hook) {
  //$variables['classes_array'][] = 'row';
  // Use a bare template for the page's main content.
  if ($variables['block_html_id'] == 'block-system-main') {
    $variables['theme_hook_suggestions'][] = 'block__no_wrapper';
  }
  $variables['title_attributes_array']['class'][] = 'block-title';
}

/**
 * Override or insert variables into the block templates.
 *
 * @param $variables
 *   An array of variables to pass to the theme template.
 * @param $hook
 *   The name of the template being rendered ("block" in this case.)
 */
function brutus_bootstrap_process_block(&$variables, $hook) {
  // Drupal 7 should use a $title variable instead of $block->subject.
  $variables['title'] = $variables['block']->subject;
}

/**
 * Returns the correct span class for a region
 */
function _brutus_bootstrap_content_span($columns = 1) {
  $class = FALSE;
  
  switch($columns) {
    case 1:
      $class = 'span12';
      break;
    case 2:
      $class = 'span9';
      break;
    case 3:
      $class = 'span6';
      break;
  }
  
  return $class;
}

/**
 * Adds the search form's submit button right after the input element.
 *
 * @ingroup themable
 */
function brutus_bootstrap_brutus_bootstrap_search_form_wrapper(&$variables) {
  $output = '<div class="input-append">';
  $output .= $variables['element']['#children'];
  $output .= '<button type="submit" class="btn">';
  $output .= '<i class="icon-search"></i>';
  $output .= '<span class="element-invisible">' . t('Search') . '</span>';
  $output .= '</button>';
  $output .= '</div>';
  return $output;
 }

function brutus_bootstrap_id_safe($string) {
  // Strip accents
  $accents = '/&([A-Za-z]{1,2})(tilde|grave|acute|circ|cedil|uml|lig);/';
  $string = preg_replace($accents, '$1', htmlentities(utf8_decode($string)));
  // Replace with dashes anything that isn't A-Z, numbers, dashes, or underscores.
  $string = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $string));
  // If the first character is not a-z, add 'n' in front.
  if (!ctype_lower($string{0})) { // Don't use ctype_alpha since its locale aware.
    $string = 'id' . $string;
  }
  return $string;
}

/**
 * Generate doctype for templates
 */
function _brutus_bootstrap_doctype() {
  return '<!DOCTYPE html>' . "\n";
}
