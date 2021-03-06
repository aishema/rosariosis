<?php
/**
* @file $Id: CategoryBreakdown.php 507 2007-05-11 23:41:24Z focus-sis $
* @package Focus/SIS
* @copyright Copyright (C) 2006 Andrew Schmadeke. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
* Focus/SIS is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See COPYRIGHT.txt for copyright notices and details.
*/

if($_REQUEST['day_start'] && $_REQUEST['month_start'] && $_REQUEST['year_start'])
{
	while(!VerifyDate($start_date = $_REQUEST['day_start'].'-'.$_REQUEST['month_start'].'-'.$_REQUEST['year_start']))
		$_REQUEST['day_start']--;
}
else
{
	$min_date = DBGet(DBQuery("SELECT min(SCHOOL_DATE) AS MIN_DATE FROM ATTENDANCE_CALENDAR WHERE SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));
	if($min_date[1]['MIN_DATE'])
		$start_date = $min_date[1]['MIN_DATE'];
	else
		$start_date = '01-'.mb_strtoupper(date('M-y'));
}

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
{
	while(!VerifyDate($end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end']))
		$_REQUEST['day_end']--;
}
else
	$end_date = DBDate();

if($_REQUEST['day_end'] && $_REQUEST['month_end'] && $_REQUEST['year_end'])
	$end_date = $_REQUEST['day_end'].'-'.$_REQUEST['month_end'].'-'.$_REQUEST['year_end'];
else
	$end_date = DBDate();

if($_REQUEST['category_id'])
{
	$category_RET = DBGet(DBQuery("SELECT du.TITLE,du.SELECT_OPTIONS,df.DATA_TYPE FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE df.ID='".$_REQUEST['category_id']."' AND du.DISCIPLINE_FIELD_ID=df.ID AND du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."'"));
	$category_RET[1]['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$category_RET[1]['SELECT_OPTIONS']));
	$category_RET[1]['SELECT_OPTIONS'] = explode("\r",$category_RET[1]['SELECT_OPTIONS']);
}

if(!$_REQUEST['chart_type'])
	$_REQUEST['chart_type'] = 'column';

if($_REQUEST['modfunc']=='search')
{
	echo '<BR />';
	//Widgets('all');
	$extra['force_search'] = true;
	$extra['search_title'] = _('Advanced');
	$extra['action'] = "&category_id=$_REQUEST[category_id]&chart_type=".str_replace(' ','+',$_REQUEST['chart_type'])."&day_start=$_REQUEST[day_start]&day_end=$_REQUEST[day_end]&month_start=$_REQUEST[month_start]&month_end=$_REQUEST[month_end]&year_start=$_REQUEST[year_start]&year_end=$_REQUEST[year_end]&modfunc=&search_modfunc= target=body onsubmit='window.close();'";
	Search('student_id',$extra);

}

//if($_REQUEST['modfunc']=='SendChartData' || $_REQUEST['chart_type']=='list')
//{
if($_REQUEST['category_id'])
{
	$chart['chart_type'] = $_REQUEST['chart_type'];
	$chart['series_switch'] = true;
	$chart['draw_text']  = array(array('x'=>0,'y'=>5,'width'=>$width+200,'height'=>200,'h_align'=>'center','v_align'=>'top','rotation'=>0,'text'=>wordwrap($category_RET[1]['TITLE'].' '._('Breakdown'),40,"\r"),'font'=>'Arial','color'=>'000000','alpha'=>25,'size'=>24));
	
	if($category_RET[1]['DATA_TYPE']=='multiple_radio' || $category_RET[1]['DATA_TYPE']=='select')
	{
		$extra = array();
		$extra['SELECT_ONLY'] = "dr.CATEGORY_".$_REQUEST['category_id']." AS TITLE,COUNT(*) AS COUNT ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		$extra['GROUP'] = 'CATEGORY_'.$_REQUEST['category_id'];
		$extra['group'] = array('TITLE');
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);

		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		foreach($category_RET[1]['SELECT_OPTIONS'] as $option)
		{
			$chart['chart_data'][0][] = $option;
			$chart['chart_data'][1][] = (empty($totals_RET[$option][1]['COUNT']) ? 0 : $totals_RET[$option][1]['COUNT']);
		}
	}
	elseif($category_RET[1]['DATA_TYPE']=='checkbox')
	{
		$extra = array();
		$extra['SELECT_ONLY'] = "COALESCE(dr.CATEGORY_".$_REQUEST['category_id'].",'N') AS TITLE,COUNT(*) AS COUNT ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		$extra['GROUP'] = 'CATEGORY_'.$_REQUEST['category_id'];
		$extra['group'] = array('TITLE');
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);
		$extra['WHERE'] .= CustomFields('where');
		$totals_RET = GetStuList($extra);

		$chart['chart_data'][0][] = _('Yes');
		$chart['chart_data'][1][] = (empty($totals_RET['Y'][1]['COUNT']) ? 0 : $totals_RET['Y'][1]['COUNT']);
		$chart['chart_data'][0][] = _('No');
		$chart['chart_data'][1][] = (empty($totals_RET['N'][1]['COUNT']) ? 0 : $totals_RET['N'][1]['COUNT']);
	}
	elseif($category_RET[1]['DATA_TYPE']=='multiple_checkbox')
	{
		$extra['SELECT_ONLY'] = "CATEGORY_".$_REQUEST['category_id']." AS TITLE ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr ';
		$extra['WHERE'] = "AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);
		$extra['WHERE'] .= CustomFields('where');
		$referrals_RET = GetStuList($extra);

		foreach($referrals_RET as $referral)
		{
			$referral['TITLE'] = explode("||",trim($referral['TITLE'],'|'));
			foreach($referral['TITLE'] as $option)
				$options_count[$option]++;
		}

		foreach($category_RET[1]['SELECT_OPTIONS'] as $option)
		{
			$chart['chart_data'][0][] = $option;
			$chart['chart_data'][1][] = (empty($options_count[$option]) ? 0 : $options_count[$option]);
		}		
	}
	elseif($category_RET[1]['DATA_TYPE']=='numeric')
	{

		$extra['SELECT_ONLY'] = "COALESCE(max(CATEGORY_".$_REQUEST['category_id']."),0) as MAX,COALESCE(min(CATEGORY_".$_REQUEST['category_id']."),0) AS MIN ";
		$extra['FROM'] = ',DISCIPLINE_REFERRALS dr';
		$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);
		$extra['WHERE'] .= CustomFields('where');
		$max_min_RET = GetStuList($extra);

		$diff = $max_min_RET[1]['MAX'] - $max_min_RET[1]['MIN'];

		if($diff>10 && $_REQUEST['chart_type']!='column')
		{
//modif Francois: correct numeric chart
			for($i=1;$i<=10;$i++)
			{
				/*$chart['chart_data'][0][$i] = (ceil($diff/5)*($i-1)).' - '.((ceil($diff/5)*$i)-1);
				$mins[$i] = (ceil($diff/5)*($i-1));
				$chart['chart_data'][1][$i] = 0;*/
				$chart['chart_data'][0][$i] = ($max_min_RET[1]['MIN'] + (ceil($diff/10)*($i-1))).' - '.($max_min_RET[1]['MIN'] + ((ceil($diff/10)*$i)-1));
				$mins[$i] = ($max_min_RET[1]['MIN'] + (ceil($diff/10)*($i-1)));
				$chart['chart_data'][1][$i] = 0;
			}
			//$chart['chart_data'][0][$i-1] = ($max_min_RET[1]['MIN'] + (ceil($diff/5)*($i-2))).'+';
			$mins[$i] = (ceil($diff/10)*($i-1));
		} 
		else //modif Francois: transform column chart in line chart
		{ 
			$chartline = true;
		}
		
		$extra['SELECT_ONLY'] = "CATEGORY_".$_REQUEST['category_id']." AS TITLE";
		$extra['FROM'] = ",DISCIPLINE_REFERRALS dr";
		$extra['WHERE'] = " AND dr.STUDENT_ID=ssm.STUDENT_ID AND dr.SCHOOL_ID=ssm.SCHOOL_ID AND dr.ENTRY_DATE BETWEEN '$start_date' AND '$end_date' ";
		$extra['functions'] = array('TITLE'=>'_makeNumeric');
		//Widgets('all');
//modif Francois: fix Advanced Search
		$extra['WHERE'] .= appendSQL('',$extra);
		$extra['WHERE'] .= CustomFields('where');
		$referrals_RET = GetStuList($extra);
	}

	if($_ROSARIO['SearchTerms'])
		$chart['draw_text'][] = array('x'=>0,'y'=>35,'width'=>$width+200,'height'=>100,'h_align'=>'center','v_align'=>'top','rotation'=>0,'text'=>strip_tags(str_replace('<BR />',"\n",$_ROSARIO['SearchTerms'])),'font'=>'Arial','color'=>'000000','alpha'=>25,'size'=>20);
	
	if($_REQUEST['chart_type']!='list')
	{
//modif Francois: jqplot charts
?>
		<script type="text/javascript">
<?php
		if (isset($chartline))
		{
			$jsData = 'var dataline = [';
			for ($i=1; $i<=count($chart['chart_data'][0]); $i++)
			{
				if (is_numeric($chart['chart_data'][0][$i]))
					$jsData .= "[".$chart['chart_data'][0][$i].", ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";		
		}
		elseif ($_REQUEST['chart_type']=='column')
		{
			$jsData = 'var ticks = [';
			foreach ($chart['chart_data'][0] as $tick)
			{
				$jsData .= "'".$tick."', ";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 2);
			$jsData .= "];\n";
			
			$jsData .= 'var datacolumn = [';
			foreach ($chart['chart_data'][1] as $data)
			{
				$jsData .= $data.", ";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 2);
			$jsData .= "];\n";
		} 
		else //pie chart
		{
			$jsData = 'var datapie = [';
			for ($i=0; $i<=count($chart['chart_data'][0]); $i++)
			{
				//limit label to 30 char max.
				$jsData .= "['".mb_substr($chart['chart_data'][0][$i], 0, 30)."', ".$chart['chart_data'][1][$i]."],";
			}
			$jsData = mb_substr($jsData, 0, mb_strlen($jsData) - 1);
			$jsData .= "];\n";
					
		}
		echo $jsData;
?>
		</script>
<?php
	}
	if ($_ROSARIO['SearchTerms'])
		$_ROSARIO['SearchTerms'] = ' - '.strip_tags(str_replace('<BR />'," - ",mb_substr($_ROSARIO['SearchTerms'], 0, -6)));
}

if(empty($_REQUEST['modfunc']))
{
	unset($_REQUEST['PHPSESSID']);
	echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']).'" method="POST">';
	DrawHeader(ProgramTitle());
	
	$categories_RET = DBGet(DBQuery("SELECT df.ID,du.TITLE,du.SELECT_OPTIONS FROM DISCIPLINE_FIELDS df,DISCIPLINE_FIELD_USAGE du WHERE df.DATA_TYPE NOT IN ('textarea','text','date') AND du.SYEAR='".UserSyear()."' AND du.SCHOOL_ID='".UserSchool()."' AND du.DISCIPLINE_FIELD_ID=df.ID ORDER BY du.SORT_ORDER"));
	$select = '<SELECT name=category_id onchange="this.form.submit();"><OPTION value="">'._('Please choose a category').'</OPTION>';
	
	if(count($categories_RET))
	{
		foreach($categories_RET as $category)
			$select .= '<OPTION value="'.$category['ID'].'"'.(($_REQUEST['category_id']==$category['ID'])?' SELECTED="SELECTED"':'').'>'.$category['TITLE'].'</OPTION>';
	}
	$select .= '</SELECT>';
	$advanced_link = ' <A HREF="#" onclick=\'remote = window.open("Modules.php?modname='.$_REQUEST['modname'].'&modfunc=search&category_id='.$_REQUEST['category_id'].'&chart_type='.$_REQUEST['chart_type'].'&day_start='.$_REQUEST['day_start'].'&day_end='.$_REQUEST['day_end'].'&month_start='.$_REQUEST['month_start'].'&month_end='.$_REQUEST['month_end'].'&year_start='.$_REQUEST['year_start'].'&year_end='.$_REQUEST['year_end'].'&include_top=false","","scrollbars=yes,resizable=yes,width=700,height=600"); remote.opener = window;\'>'._('Advanced').'</A>';

	DrawHeader($select);
	DrawHeader(' &nbsp; &nbsp; <B>'._('Report Timeframe').': </B>'.PrepareDate($start_date,'_start').' - '.PrepareDate($end_date,'_end').$advanced_link,SubmitButton(_('Go')));

	echo '<BR />';
	if($_REQUEST['category_id'])
	{
		$tmp_REQUEST = $_REQUEST;
		unset($tmp_REQUEST['chart_type']);
		$link = PreparePHP_SELF($tmp_REQUEST);
		$tabs = array(array('title'=>_('Column'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=column',$link)),array('title'=>_('Pie'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=3d+pie',$link)),array('title'=>_('List'),'link'=>str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type=list',$link)));

		$_ROSARIO['selected_tab'] = str_replace($_REQUEST['modname'],$_REQUEST['modname'].'&amp;chart_type='.str_replace(' ','+',$_REQUEST['chart_type']),$link);
		PopTable('header',$tabs,'',0);

		if($_REQUEST['chart_type']=='list')
		{
			$chart_data = array('0'=>'');

			foreach($chart['chart_data'][1] as $key=>$value)
				$chart_data[] = array('TITLE'=>$chart['chart_data'][0][$key],'VALUE'=>$value);
			unset($chart_data[0]);
			ListOutput($chart_data,array('TITLE'=>_('Option'),'VALUE'=>_('Number of Referrals')),_('Option'),_('Options'));
		}
		else
		{
			$_REQUEST['modfunc'] = 'SendChartData';
			//$_REQUEST['_ROSARIO_PDF'] = 'true';
//modif Francois: jqplot charts
//modif Francois: colorbox
?>
			<script type="text/javascript" src="assets/js/jquery.js"></script>
			<script type="text/javascript" src="assets/js/jqplot/jquery.jqplot.min.js"></script>
			<link rel="stylesheet" type="text/css" href="assets/js/jqplot/jquery.jqplot.min.css" />
			<script type="text/javascript">	
				var saveImgText = '<?php echo _('Right Click to Save Image As...'); ?>';
			</script>
<?php
			if (isset($chartline)) //modif Francois: line chart
			{
?>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.highlighter.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){
						var plotline = $.jqplot('chart',[dataline], {
							highlighter: {
								show: true,
								showLabel: true,
								tooltipAxes: 'x',
							},
							title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
						});
					});		
				</script>
<?php
			}
			elseif($_REQUEST['chart_type']=='column')
			{
?>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){
						var plotcolumn = $.jqplot('chart', [datacolumn], {
							seriesDefaults:{
								renderer:$.jqplot.BarRenderer,
								rendererOptions: { 
									fillToZero: true,
									varyBarColor: true
								},
								pointLabels: { show: true }
							},
							axes: {
								// yaxis: { autoscale: true },
								xaxis: {
									renderer: $.jqplot.CategoryAxisRenderer,
									ticks: ticks,
									tickRenderer: $.jqplot.CanvasAxisTickRenderer,
									tickOptions:{
										angle:-20
									}
								},
							},
							title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
						});
					});		
				</script>
<?php
			} 
			else //pie chart
			{
?>		
				<script type="text/javascript" src="assets/js/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
				<script type="text/javascript">
					$(document).ready(function(){ 
						var plotpie = $.jqplot('chart', [datapie], {
							seriesDefaults:{
								renderer:$.jqplot.PieRenderer,
							},
							legend:{show:true},
							title: '<?php echo ParseMLField($category_RET[1]['TITLE']).' '._('Breakdown').$_ROSARIO['SearchTerms']; ?>'
						});
					});	
				</script>
<?php
			}	
?>
			<div id="chart" style="margin-top:20px; margin-left:20px; width:600px; height:360px;"></div>
			<script type="text/javascript" src="assets/js/colorbox/jquery.colorbox-min.js"></script>
			<link rel="stylesheet" href="assets/js/colorbox/colorbox.css" type="text/css" media="screen" />
			<script type="text/javascript" src="assets/js/jquery.jqplottocolorbox.js"></script>
<?php
			unset($_REQUEST['_ROSARIO_PDF']);
		}
		PopTable('footer');
	}
	echo '</FORM>';
}

function _makeNumeric($number,$column)
{	global $max_min_RET,$chart,$diff,$mins,$chartline;
	
	if(!$number)
		$number=0;
	if($diff==0)
	{
		$chart['chart_data'][0][1] = $number;
		$chart['chart_data'][1][1]++;
	}
	//elseif($diff<5)
	elseif($diff<10 || isset($chartline))
	{
		$chart['chart_data'][0][((int) $number - (int) $max_min_RET[1]['MIN']+1)] = (int) $number;
		$chart['chart_data'][1][((int) $number - (int) $max_min_RET[1]['MIN']+1)]++;
	}
	else
	{
		//for($i=1;$i<=5;$i++)
		for($i=1;$i<=10;$i++)
		{
			if(($number>=$mins[$i] && $number<$mins[$i+1]) || $i==10)
			{
				$chart['chart_data'][1][$i]++;
				break;
			}
		}
	}
	
	return;
}
?>
