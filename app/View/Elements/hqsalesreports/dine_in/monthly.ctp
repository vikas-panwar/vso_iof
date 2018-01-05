<?php
$totalDineIn=0;
$datee= array('1'=>"'Jan'",'2'=>"'Feb'",'3'=>"'Mar'",'4'=>"'Apr'",'5'=>"'May'",'6'=>"'Jun'",'7'=>"'Jul'",'8'=>"'Aug'",'9'=>"'Sep'",'10'=>"'Oct'",'11'=>"'Nov'",'12'=>"'Dec'");
$tdinein = array('1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0,'6'=>0,'7'=>0,'8'=>0,'9'=>0,'10'=>0,'11'=>0,'12'=>0);
foreach($graphData as $key => $data){
   $result1[$key]=$data[0];
   unset($data); 
}
$totalCoupon = array();
if(!empty($result1)){
    foreach($result1 as $coupon){
        $datee[date('n',strtotime($coupon['order_date']))]="'".date('M',strtotime($coupon['order_date']))."'";
        if(array_key_exists($coupon['order_date'], $totalCoupon))
        {
            $totalCoupon[$coupon['order_date']] += 1;
            $tdinein[date('n',strtotime($coupon['order_date']))] += 1;
        } else {
            $totalCoupon[$coupon['order_date']] = 1;
            $tdinein[date('n',strtotime($coupon['order_date']))] += 1;
        }
        $totalDineIn += 1;
    }
}
$subTitle = '<style="font-size:14px;font-weight:bold;">Total '.$totalDineIn.' Reservations </style>';
$amntdate = implode(',',$datee);
$tdinein = implode(',',$tdinein);
//$text = 'Monthly Report for ' . str_replace('\'', '', $datee[$month]) . ' - ' . $year . ' to ' . str_replace('\'', '', $datee[$toMonth]) . ' - ' . $toYear;

$startmonth = date('m', strtotime($dateFrom));
$endmonth   = date('m', strtotime($dateTo));

$startyear  = date('Y', strtotime($dateFrom));
$endyear    = date('Y', strtotime($dateTo));

$titleText = $this->Common->dineInTitleString($startmonth, $startyear, $endmonth, $endyear);
$text = '<style="font-size:12px;"><strong>' . $titleText . '</strong></style>';

/* For Pie Chart*/
$pieData = $this->Common->dineInPieDataArrange($dineInPieData);

$totalPie = (isset($pieData['total']) ? $pieData['total'] : 0);
$finalPie = (isset($pieData['data']) ? $pieData['data'] : array());
/* End For Pie Chart*/
?>
<div class="col-lg-<?php echo (($totalPie == 0) ? '12' : '6');?>">
    <div id="container"></div>
</div>
<div class="col-lg-6  <?php echo (($totalPie == 0) ? 'hidden' : '');?>">
    <div id="reservationPie"></div>
</div>

<script>
     
$(function () {
    $('#container').highcharts({
        chart: {
            type: 'line'
        },
        title: {
            text: '<?php echo $text;?>'
        },
        subtitle: {
            text: '<?php echo $subTitle;?>'
        },
        xAxis: {
            categories: [<?php echo $amntdate;?>],
            title: {
                text: null
            },
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Reservations Count',
                align: 'middle'
            },
            labels: {
                overflow: 'justify'
            }
        },
        tooltip: {
            valueSuffix: ''
        },
        plotOptions: {
            line: {
                dataLabels: {
                    enabled: true
                },
                enableMouseTracking: true
            },
            series: {
                pointWidth: 50
            }
        },
        exporting: { enabled: false },
        series: [{
            name: 'Reservations',
            data: [<?php echo $tdinein; ?>],
            color: '#f79d54'
        }]
    });
    
    
    /* Pie Chart Start */
    Highcharts.chart('reservationPie', {
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: 'Reservation Status'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b> ({point.pointcount})'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    distance : -50,
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.1f} % ({point.pointcount})',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Reservations',
            colorByPoint: true,
            data: [
            <?php
            foreach ($finalPie as $finalPieV)
            {
                $percentage = ($finalPieV['status_count'] / $totalPie) * 100;
                ?>
                {
                    name        : '<?php echo $finalPieV['status_name']?>',
                    y           : <?php echo $percentage?>,
                    color       : '<?php echo $finalPieV['color']?>',
                    pointcount  : '<?php echo $finalPieV['status_count'];?>'
                },
                <?php
            }
            ?>
             ]
        }],
        exporting: { enabled: false }
    });
    /* Pie Chart End */
});
</script>
<div id="pagination_data_request">
    <?php echo $this->element('hqsalesreports/dine_in/pagination'); ?>
</div>