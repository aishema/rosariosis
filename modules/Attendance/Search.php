<?php
if(User('PROFILE')=='teacher')
	$_REQUEST['modname'] = 'Attendance/TakeAttendance.php';
elseif(User('PROFILE')=='parent' || User('PROFILE')=='student')
	$_REQUEST['modname'] = 'Attendance/StudentSummary.php';
else
	$_REQUEST['modname'] = 'Attendance/Administration.php';

$modcat = 'Attendance';
if(AllowUse($_REQUEST['modname']))
{
	echo '<SCRIPT type="text/javascript">parent.help.location="Bottom.php?modcat='.$modcat.'&modname='.$_REQUEST['modname'].'";</SCRIPT>';
//modif Francois: remove languages/English/
//	include("languages/English/$_REQUEST[modname]");
	include("modules/$_REQUEST[modname]");
}
?>