<?php
/**
 * Settings Controller
 *
 * @package Premise Social Media Blogger
 */

/**
 * Models
 */
require_once PSMB_PATH . 'model/model-psmb-settings.php';

require_once PSMB_PATH . 'model/model-psmb-youtube-settings.php';

require_once PSMB_PATH . 'model/model-psmb-instagram-settings.php';

// Do logic.
$psmb = psmb();

$psmb->settings = new Premise_Social_Media_Blogger_Settings();

$psmb->settings->youtube = new Premise_Social_Media_Blogger_Youtube_Settings();

$psmb->settings->instagram = new Premise_Social_Media_Blogger_Instagram_Settings();

/**
 * View
 *
 * @see Premise_Social_Media_Blogger_Settings::plugin_settings()
 */

