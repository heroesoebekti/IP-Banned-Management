<?php

/**
 * Plugin Management: IP Jail
 * Date: 2025/11/21
 * File name: ip_jail.php
 *
 */

use SLiMS\DB;
define('DB_ACCESS', 'fa');

require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

$can_read = utility::havePrivilege('system', 'r');
$can_write = utility::havePrivilege('system', 'w');

$php_self = $_SERVER['PHP_SELF'].'?'.http_build_query($_GET);
$dbs = DB::getInstance('mysqli');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

function ipJailActions($obj_db, $array_data) {
    global $php_self;    
    $record_id = $array_data[0];
    $ip_display = $array_data[1];
    $edit_url = $_SERVER['PHP_SELF'] . '?action=detail&itemID=' . $record_id . '&mod=' . $_GET['mod'] . '&id=' . $_GET['id'] . '&ajaxload=1';
    $edit_link = '<a href="'.$edit_url.'" class="btn btn-sm btn-info">'.__('Edit').'</a>';
    return '<strong>'.$ip_display.'</strong><br>' . $edit_link;
}


if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) {
        die();
    }

    $sql_op = new simbio_dbop($dbs);
    $error_num = 0;

    if (!is_array($_POST['itemID'])) {
        $_POST['itemID'] = array($dbs->escape_string(trim($_POST['itemID'])));
    }

    foreach ($_POST['itemID'] as $itemID) {
        $itemID = $dbs->escape_string(trim($itemID));
        if (!$sql_op->delete('ip_jail', "id='$itemID'")) {
            $error_num++;
        }
    }

    if ($error_num == 0) {
        utility::jsAlert(__('All selected IP addresses successfully unjailed.'));
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$php_self.'?'.$_POST['lastQueryStr'].'\');</script>';
    } else {
        utility::jsAlert(__('Some or All Data NOT deleted successfully! Please contact system administrator'));
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$php_self.'?'.$_POST['lastQueryStr'].'\');</script>';
    }
    exit();
}

if (isset($_POST['saveData']) AND $can_write) {
    
    $clean_self_url = $_SERVER['PHP_SELF'] . '?action=none&mod='.$_GET['mod'].'&id='.$_GET['id'].'&ajaxload=1';

    $sql_op = new simbio_dbop($dbs);
    $updateRecordID = $dbs->escape_string(trim($_POST['updateRecordID']));
    $new_reason = trim($dbs->escape_string($_POST['reason']));

    if (empty($new_reason)) {
        utility::jsAlert(__('Reason cannot be empty!'));
        exit();
    }

    $data['reason'] = $new_reason;
    
    $update = $sql_op->update('ip_jail', $data, 'id=\''.$updateRecordID.'\'');

    if ($update) {
        utility::jsToastr(__('IP Jail'), __('Data Successfully Updated'), 'success');
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$clean_self_url.'\');</script>';
    } else { 
        utility::jsToastr(__('IP Jail'), __('Data FAILED to Updated. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error, 'error'); 
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$clean_self_url.'\');</script>';
    }
    exit();
}


?>

<div class="menuBox">
<div class="menuBoxInner masterFileIcon">
    <div class="per_title">
        <h2><?php echo __('Permanently Banned IP Addresses'); ?></h2>
    </div>
    <div class="infoBox">
        <?= __('This page lists IP addresses permanently blocked from accessing the system. Deleting an entry will lift the ban.') ?>
    </div>
    
    <?php if (!isset($_GET['action']) OR $_GET['action'] != 'detail') { ?>
    <div class="sub_section">
        <form name="search" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="search" method="get" class="form-inline">
            <?php echo __('Search'); ?> 
            <input type="text" name="keywords" class="form-control col-md-3" />
            <input type="hidden" name="mod" value="<?php echo $_GET['mod']; ?>">
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
            <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default" />
        </form>
    </div>
    <?php } ?>
</div>
</div>

<?php

if (isset($_GET['action']) AND $_GET['action'] == 'detail') {
    if (!$can_write) {
        die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
    }
    $itemID = $dbs->escape_string(trim(isset($_GET['itemID'])?$_GET['itemID']:0));
    $rec_q = $dbs->query("SELECT * FROM ip_jail WHERE id='$itemID'");
    $rec_d = $rec_q->fetch_assoc();
    $form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'].'?'.http_build_query($_GET), 'post');
    $form->submit_button_attr = 'name="saveData" value="'.__('Update').'" class="s-btn btn btn-primary"';
    $form->table_attr = 'id="dataList" class="s-table table"';
    $form->table_header_attr = 'class="alterCell font-weight-bold"';
    $form->table_content_attr = 'class="alterCell2"';
    if ($rec_q->num_rows > 0) {
        $form->edit_mode = true;
        $form->record_id = $itemID;
        $form->record_title = $rec_d['ip_address'];
    }
    $form->addAnything(__('IP Address'), '<strong>'.$rec_d['ip_address'].'</strong>');
    $form->addAnything(__('Ban Date'), $rec_d['banned_at']);
    $form->addTextField('text', 'reason', __('Reason').'*', $rec_d['reason']??'', 'class="form-control col-12"', __('The reason for this permanent block.'));
    if ($form->edit_mode) {      
    echo '<div class="infoBox">'.__('You are going to edit IP').' : <b>'.$rec_d['ip_address'].' ?</b></div>';
    }    
    echo $form->printOut();

} else {

    $table_spec = 'ip_jail AS t';
    $datagrid = new simbio_datagrid();

    $datagrid->setSQLColumn('t.id',
        't.ip_address',
        't.reason AS \''.__('Reason').'\'',
        't.banned_at AS \''.__('Ban Date').'\'');

    $datagrid->setSQLorder('t.banned_at DESC');

    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $keywords = utility::filterData('keywords', 'get', true, true, true);
        $datagrid->setSQLCriteria("t.ip_address LIKE '%$keywords%' OR t.reason LIKE '%$keywords%'");
    }

    $datagrid->modifyColumnContent(1, 'callback{ipJailActions}');

    $datagrid->table_attr = 'id="dataList" class="s-table table"';
    $datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
    $datagrid->chbox_form_URL = $php_self;
    $datagrid->edit_property = false;

    $datagrid_result = $datagrid->createDataGrid($dbs, $table_spec, 20, ($can_read AND $can_write));

    if (isset($_GET['keywords']) AND $_GET['keywords']) {
        $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
        echo '<div class="infoBox">'.$msg.' : "'.htmlspecialchars($_GET['keywords']).'"</div>';
    }

    echo $datagrid_result;
}
