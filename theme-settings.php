<?php

/**
 * @file
 * Theme setting callbacks for the Brutus Bootstrap theme.
 */

include_once(dirname(__FILE__) . '/includes/bootstrap.inc');

function brutus_bootstrap_form_system_theme_settings_alter(&$form, $form_state, $form_id = NULL) {
  // Work-around for a coredd bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

}

