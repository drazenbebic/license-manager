<?php defined('ABSPATH') || exit; ?>

<div class="wrap lmfwc">
    <?php
        if ($action === 'list'
            || $action === 'activate'
            || $action === 'deactivate'
            || $action === 'delete'
        ) {
            include_once('license-instances/page-list.php');
        } elseif ($action === 'add') {
            include_once('license-instances/page-add.php');
        } elseif ($action === 'import') {
            include_once('license-instances/page-import.php');
        } elseif ($action === 'edit') {
            include_once('license-instances/page-edit.php');
        }
    ?>
</div>