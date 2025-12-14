<?php
require_once __DIR__ . '/permissions.php';

function canEditEvent($id) { return canEditResource('event', $id); }
function canEditSermon($id) { return canEditResource('sermon', $id); }
function canEditCommunity($id) { return canEditResource('community', $id); }
function canEditBanner($id) { return canEditResource('banner', $id); }
?>
