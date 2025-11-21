<?php 
/**
 * Plugin Settings: Banned IP
 * Date: 2025/11/21
 * File name: settings.php
 *
 */


use SLiMS\DB;

require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_FILE/simbio_directory.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

if ($_SESSION['uid'] != 1) {
    die('<div class="errorBox">'.__('You are not authorized to view this section').'</div>');
}

$php_self = $_SERVER['PHP_SELF'].'?'.http_build_query($_GET);
$dbs = DB::getInstance('mysqli');


if(isset($_POST['updateData'])){
    $sql_op = new simbio_dbop($dbs);
    $settings_to_update = [];
    $settings_to_update['MAX_ATTEMPTS'] = trim($dbs->escape_string($_POST['MAX_ATTEMPTS']));
    $settings_to_update['TIME_WINDOW_MINUTES'] = trim($dbs->escape_string($_POST['TIME_WINDOW_MINUTES']));
    $settings_to_update['BLOCKING_ENABLED'] = trim($dbs->escape_string($_POST['BLOCKING_ENABLED']));
    $settings_to_update['JAIL_ENABLED'] = trim($dbs->escape_string($_POST['JAIL_ENABLED']));
    $settings_to_update['JAIL_ATTEMPTS_LIMIT'] = trim($dbs->escape_string($_POST['JAIL_ATTEMPTS_LIMIT']));

    $success = true;
    foreach ($settings_to_update as $key => $value) {
        $data = ['setting_value' => $value];
        // UPDATE berdasarkan setting_key (kunci unik)
        $update = $sql_op->update('banned_ip_settings', $data, 'setting_key=\'' . $key . '\'');
        
        if (!$update) {
            $success = false;
        }
    }

    if ($success) {
        utility::jsToastr('Banned IP', __('Settings Successfully Updated'), 'success');
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(parent.jQuery.ajaxHistory[0].url);</script>';
    } else {
        utility::jsToastr('Banned IP', __('Data FAILED to Updated. Please Contact System Administrator'), 'error');
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(parent.jQuery.ajaxHistory[0].url);</script>';
    }
}

$settings_query = $dbs->query('SELECT setting_key, setting_value, description FROM banned_ip_settings');
$settings = [];
if ($settings_query) {
    while ($config = $settings_query->fetch_assoc()) {
        $settings[$config['setting_key']] = $config;
    }
}

?>

<div class="menuBox">
    <div class="menuBoxInner systemIcon">
        <div class="per_title">
            <h2><?php echo __('Banned IP Settings'); ?></h2>
        </div>
        <div class="infoBox">
            <?= __('Modify core security parameters for temporary and permanent IP blocking.') ?>
        </div>
    </div>
</div>
<?php

$form = new simbio_form_table_AJAX('mainForm', $php_self, 'post');
$form->submit_button_attr = 'name="updateData" value="'.__('Save Settings').'" class="btn btn-default"';
$form->table_attr = 'id="dataList" class="s-table table"';
$form->table_header_attr = 'class="alterCell font-weight-bold"';
$form->table_content_attr = 'class="alterCell2"';

$options_enabled = [
    ['0', __('Disable')],
    ['1', __('Enable')]
];


$form->addSelectList('BLOCKING_ENABLED', __('Temporary Blocking'), $options_enabled, $settings['BLOCKING_ENABLED']['setting_value']??'0', 'class="form-control col-3"', __('Status of temporary blocking feature.'));

$form->addTextField('text', 'MAX_ATTEMPTS', __('Max Attempts'), $settings['MAX_ATTEMPTS']['setting_value']??'5', 'class="form-control col-3"', __('Max failed login attempts before IP is temporarily blocked (403).'));

$form->addTextField('text', 'TIME_WINDOW_MINUTES', __('Time Window (Minutes)'), $settings['TIME_WINDOW_MINUTES']['setting_value']??'30', 'class="form-control col-3"', __('Time window (in minutes) for counting failed attempts.'));

$form->addSelectList('JAIL_ENABLED', __('Permanent Jail'), $options_enabled, $settings['JAIL_ENABLED']['setting_value']??'0', 'class="form-control col-3"', __('Status of the permanent blocking (IP Jail) feature.'));

$form->addTextField('text', 'JAIL_ATTEMPTS_LIMIT', __('Jail Threshold'), $settings['JAIL_ATTEMPTS_LIMIT']['setting_value']??'15', 'class="form-control col-3"', __('Total failed attempts allowed before IP is permanently jailed.'));

// print out the form object
echo $form->printOut();
