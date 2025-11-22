<?php
/**
 * Plugin Management: Login Attempt
 * Date: 2025/11/21
 * File name: login_attempt.php
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

function ipLogActions($obj_db, $array_data) {
    global $php_self;

    $ip = urlencode($array_data[1]);
    $ip_display = $array_data[1];
    $request_url = htmlspecialchars($array_data[2]);

    $whitelist_url = $php_self . '&action=whitelist&ip=' . $ip;
    $whitelist_link = '<a href="'.$whitelist_url.'" onclick="return confirm(\''.__('Are you sure you want to Whitelist this IP? This allows permanent access and removes the IP from log.').'\');" class="btn btn-sm btn-success">'.__('Whitelist').'</a>';

    $jail_url = $php_self . '&action=jail&ip=' . $ip;
    $jail_link = '<a href="'.$jail_url.'" onclick="return confirm(\''.__('Are you sure you want to Permanently Jail (Ban) this IP? This removes the IP from log.').'\');" class="btn btn-sm btn-danger">'.__('Jail').'</a>';
    
    $_output = '<strong>'.$ip_display.'</strong><br/>';
    $_output .= $jail_link . ' ' . $whitelist_link;

    return $_output;
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
        if (!$sql_op->delete('ip_log', "id='$itemID'")) {
            $error_num++;
        }
    }

    if ($error_num == 0) {
        utility::jsAlert(__('All selected IP logs successfully deleted.'));
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$php_self.'?'.$_POST['lastQueryStr'].'\');</script>';
    } else {
        utility::jsAlert(__('Some or All Data NOT deleted successfully! Please contact system administrator'));
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$php_self.'?'.$_POST['lastQueryStr'].'\');</script>';
    }
    exit();
}

if (isset($_GET['action']) && isset($_GET['ip']) && $can_write) {
    $ip_address = $dbs->escape_string($_GET['ip']);
    $sql_op = new simbio_dbop($dbs);
    $success = false;
    $message = '';
    
    $clean_self_url = $_SERVER['PHP_SELF'] . '?action=none&mod='.$_GET['mod'].'&id='.$_GET['id'].'&ajaxload=1'; 

    if ($_GET['action'] == 'jail') {
        $query = "INSERT INTO ip_jail (ip_address, reason, banned_at) VALUES ('$ip_address', 'Manual intervention from IP Log.', NOW()) ON DUPLICATE KEY UPDATE reason=VALUES(reason)";
        $success = $dbs->query($query);
        $message = sprintf(__('IP address %s successfully jailed permanently.'), $ip_address); 

    } elseif ($_GET['action'] == 'whitelist') {
        $query = "INSERT INTO ip_whitelist (ip_address, notes, created_at) VALUES ('$ip_address', 'Manual whitelist from IP Log.', NOW()) ON DUPLICATE KEY UPDATE notes=VALUES(notes)";
        $success = $dbs->query($query);
        $message = sprintf(__('IP address %s successfully added to Whitelist.'), $ip_address);
    }

    if ($success) {
        $sql_op->delete('ip_log', "ip_address='$ip_address'");
        utility::jsToastr('Banned IP', $message, 'success');
        
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$clean_self_url.'\');</script>';
        exit(); 
    } else {
        utility::jsToastr('Banned IP', __('Action failed or IP already exists in the target list.'), 'error');
        echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$clean_self_url.'\');</script>';
        exit(); 
    }
}
?>

<div class="menuBox">
<div class="menuBoxInner masterFileIcon">
    <div class="per_title">
        <h2><?php echo __('Failed Login Attempts'); ?></h2>
    </div>
    <div class="infoBox">
        <?= __('This page displays a log of consecutive failed login attempts by IP address. IPs listed here are subject to temporary blocking.') ?>
    </div>
    <div class="sub_section">
        <form name="search" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="search" method="get" class="form-inline">
            <?php echo __('Search'); ?> 
            <input type="text" name="keywords" class="form-control col-md-3" />
            <input type="hidden" name="mod" value="<?php echo $_GET['mod']; ?>">
            <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
            <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default" />
        </form>
    </div>
</div>
</div>

<?php
$table_spec = 'ip_log AS t';
$datagrid = new simbio_datagrid();

$datagrid->setSQLColumn('t.id',
    't.ip_address AS \''.__('IP Address').'\'',
    't.ip_detail',
    't.attempt_count AS \''.__('Failed Count').'\'',
    't.last_attempt AS \''.__('Last Attempt').'\'',
    't.created_at AS \''.__('First Attempt').'\'');

$datagrid->setSQLorder('t.last_attempt DESC');

if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $keywords = utility::filterData('keywords', 'get', true, true, true);
    $datagrid->setSQLCriteria("t.ip_address LIKE '%$keywords%' OR t.request_url LIKE '%$keywords%'");
}

$datagrid->modifyColumnContent(1, 'callback{ipLogActions}');
$datagrid->invisible_fields = array(2);

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
