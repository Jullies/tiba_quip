<?php
require __DIR__.'/../app/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if((user()['role']??'')!=='admin'){http_response_code(403);echo json_encode(['status'=>'error','message'=>'Administrator authentication is required.']);exit;}

$currentMonth=date('Y-m');$month=trim($_GET['month']??'');
if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)||$month>$currentMonth)$month=$currentMonth;
$monthStart=$month.'-01';$monthEnd=date('Y-m-d',strtotime($monthStart.' +1 month'));$current=$month===$currentMonth;
$dayStart=$current?date('Y-m-d'):date('Y-m-d',strtotime($monthEnd.' -1 day'));$dayEnd=date('Y-m-d',strtotime($dayStart.' +1 day'));
$weekStart=max($monthStart,date('Y-m-d',strtotime($dayStart.' -6 days')));$yearStart=date('Y-m-d',strtotime($monthStart.' -11 months'));

function traffic_stats(string $start,string $end):array {
    $r=row('SELECT COUNT(*) views,COUNT(DISTINCT s.visitor_id) visitors,COUNT(DISTINCT CASE WHEN v.first_seen<? THEN s.visitor_id END) returning FROM analytics_events e JOIN analytics_sessions s ON s.id=e.session_id JOIN analytics_visitors v ON v.id=s.visitor_id WHERE e.event="page_view" AND e.created_at>=? AND e.created_at<?',[$start,$start,$end]);
    return array_map('intval',$r?:['views'=>0,'visitors'=>0,'returning'=>0]);
}
function traffic_dimension(string $expression,string $start,string $end,string $extra='1=1'):array {
    return rows("SELECT $expression label,COUNT(*) views FROM analytics_events e JOIN analytics_sessions s ON s.id=e.session_id WHERE e.event=\"page_view\" AND e.created_at>=? AND e.created_at<? AND ($extra) GROUP BY label ORDER BY views DESC,label LIMIT 8",[$start,$end]);
}
function traffic_series(string $start,string $end):array {
    $found=array_column(rows('SELECT DATE(e.created_at) day,COUNT(*) views,COUNT(DISTINCT s.visitor_id) visitors FROM analytics_events e JOIN analytics_sessions s ON s.id=e.session_id WHERE e.event="page_view" AND e.created_at>=? AND e.created_at<? GROUP BY DATE(e.created_at)',[$start,$end]),null,'day');
    $labels=[];$views=[];$visitors=[];for($day=$start;$day<$end;$day=date('Y-m-d',strtotime($day.' +1 day'))){$labels[]=date('d M',strtotime($day));$views[]=(int)($found[$day]['views']??0);$visitors[]=(int)($found[$day]['visitors']??0);}return compact('labels','views','visitors');
}

$summary=traffic_stats($dayStart,$dayEnd);$summary['live_visitors']=$current?(int)scalar('SELECT COUNT(DISTINCT visitor_id) FROM analytics_sessions WHERE last_seen>=DATE_SUB(NOW(),INTERVAL 5 MINUTE)'):0;
$dailyStart=date('Y-m-d',strtotime($dayStart.' -13 days'));
$channel="CASE WHEN s.source<>'' THEN CONCAT(UCASE(LEFT(s.source,1)),SUBSTRING(s.source,2)) WHEN s.referrer='' THEN 'Direct' WHEN s.referrer REGEXP 'google|bing|yahoo|duckduckgo' THEN 'Organic Search' WHEN s.referrer REGEXP 'facebook|instagram|linkedin|tiktok|twitter|x.com' THEN 'Social' ELSE 'Referral' END";
$dimensions=[
 'devices'=>traffic_dimension("COALESCE(NULLIF(s.device,''),'Unknown')",$monthStart,$monthEnd),
 'browsers'=>traffic_dimension("COALESCE(NULLIF(s.browser,''),'Unknown')",$monthStart,$monthEnd),
 'systems'=>traffic_dimension("COALESCE(NULLIF(s.os,''),'Unknown')",$monthStart,$monthEnd),
 'channels'=>traffic_dimension($channel,$monthStart,$monthEnd),
 'sources'=>traffic_dimension("COALESCE(NULLIF(s.source,''),NULLIF(s.referrer,''),'Direct')",$monthStart,$monthEnd),
 'campaigns'=>traffic_dimension("COALESCE(NULLIF(s.campaign,''),'Unattributed')",$monthStart,$monthEnd,"s.campaign IS NOT NULL AND s.campaign<>''"),
 'countries'=>traffic_dimension("COALESCE(NULLIF(s.country,''),'Unknown')",$monthStart,$monthEnd)
];
$topPages=rows('SELECT e.page path,COUNT(*) views FROM analytics_events e WHERE e.event="page_view" AND e.created_at>=? AND e.created_at<? GROUP BY e.page ORDER BY views DESC,e.page LIMIT 8',[$monthStart,$monthEnd]);
$products=rows('SELECT p.id,p.name,p.image,COUNT(*) views,COUNT(DISTINCT s.visitor_id) visitors,COALESCE((SELECT NULLIF(s2.country,"") FROM analytics_events e2 JOIN analytics_sessions s2 ON s2.id=e2.session_id WHERE e2.event="product_view" AND e2.resource_id=p.id AND e2.created_at>=? AND e2.created_at<? GROUP BY s2.country ORDER BY COUNT(*) DESC LIMIT 1),"Unknown") top_country FROM analytics_events e JOIN analytics_sessions s ON s.id=e.session_id JOIN products p ON p.id=e.resource_id WHERE e.event="product_view" AND e.created_at>=? AND e.created_at<? GROUP BY p.id ORDER BY views DESC,p.name LIMIT 10',[$monthStart,$monthEnd,$monthStart,$monthEnd]);
$recent=rows("SELECT e.created_at viewed_at,e.page,s.device,s.browser,s.os,COALESCE(NULLIF(s.country,''),'Unknown') country,COALESCE(NULLIF(s.source,''),NULLIF(s.referrer,''),'Direct') source,$channel channel FROM analytics_events e JOIN analytics_sessions s ON s.id=e.session_id WHERE e.event=\"page_view\" AND e.created_at>=? AND e.created_at<? ORDER BY e.id DESC LIMIT 10",[$monthStart,$monthEnd]);
$periods=['today'=>traffic_stats($dayStart,$dayEnd),'week'=>traffic_stats($weekStart,$monthEnd),'month'=>traffic_stats($monthStart,$monthEnd),'year'=>traffic_stats($yearStart,$monthEnd)];
$selection=['month'=>$month,'label'=>date('F Y',strtotime($monthStart)),'is_current'=>$current,'day_label'=>date('d M Y',strtotime($dayStart)),'period_labels'=>['today'=>date('d M',strtotime($dayStart)),'week'=>date('d M',strtotime($weekStart)).' – '.date('d M',strtotime($dayStart)),'month'=>date('M Y',strtotime($monthStart)),'year'=>'12 months to '.date('M Y',strtotime($monthStart))]];
echo json_encode(['status'=>'success','updated_at'=>date(DATE_ATOM),'selection'=>$selection,'summary'=>$summary,'periods'=>$periods,'monthly'=>traffic_series($monthStart,$current?$dayEnd:$monthEnd),'daily'=>traffic_series($dailyStart,$dayEnd),'top_pages'=>$topPages,'product_traffic'=>$products,'dimensions'=>$dimensions,'recent'=>$recent],JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG);
