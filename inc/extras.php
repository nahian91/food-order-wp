<?php
if (!defined('ABSPATH')) exit;

/*--------------------------------------------------------------
# EXTRAS - Include Files
--------------------------------------------------------------*/
require_once plugin_dir_path(__FILE__) . 'extras/extras-tabs.php';   // enqueue scripts + tabs
require_once plugin_dir_path(__FILE__) . 'extras/extras-add-edit.php';    // Add/Edit extra
require_once plugin_dir_path(__FILE__) . 'extras/extras-list.php';        // All extras + DataTable
require_once plugin_dir_path(__FILE__) . 'extras/extras-view.php';        // View single extra
