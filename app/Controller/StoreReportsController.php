<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
App::uses('StoreAppController', 'Controller');

class StoreReportsController extends StoreAppController 
{
    public $components = array('Session', 'Cookie', 'Email', 'RequestHandler', 'Encryption', 'Dateform', 'Common');
    public $helper = array('Encryption', 'Common');
    public $uses = array('OrderPayment', 'Order', 'User', 'OrderItem', 'Segment', 'OrderItemFree', 'Store', 'OrderOffer', 'OrderTopping', 'OrderPreference', 'OrderItemFree', 'Booking');
    
    var $pageLimit = 10;
    
    public function beforeFilter() {
        parent::beforeFilter();
        
        $storeId = $this->Session->read('admin_store_id');
        $storeDate=$this->Common->getcurrentTime($storeId,1);
        $storeDateTime=  explode(" ", $storeDate);
        $this->storeDate=$storeDateTime[0];
        $this->storeTime=$storeDateTime[1];
        $this->set('storeTime',$this->storeTime);
    }

    public function index() {
        $this->layout = "admin_dashboard";
        $storeId = $this->Session->read('admin_store_id');
        $this->set('storeId', $storeId);
        $typeList = $this->Segment->OrderTypeList();
        $this->set('typeList', $typeList);
    }

    public function fetchOrderData($storeID = null, $startDate = null, $endDate = null, $orderType = null, $year = null) {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        if (!empty($startDate) && !empty($endDate)) {
            $criteria .= " AND (Order.created BETWEEN '" . $startDate . "' AND '" . $endDate . "')";
        }
        if (!empty($orderType) && $orderType != 1) {
            $criteria.=" AND Order.seqment_id=$orderType";
        } else {
            $criteria.=" AND Order.seqment_id IN ('2','3')";
        }
        if (!empty($year)) {
            $criteria.=" AND YEAR(Order.created)='" . $year . "'";
        }
        $graphOrderDetail = $this->Order->find('all', array('fields' => array('Order.amount', 'Order.created'), 'conditions' => array_merge(array($criteria), array('Order.store_id' => $storeID))));
        return $graphOrderDetail;
    }

    public function fetchReport() 
    {
        
        $defaultTimeZone = date_default_timezone_get();
        
        $this->layout = false;
        $this->autoRender = false;
        if ($this->request->is(array('ajax'))) {
            if(isset($this->request->data))
            {
                $dataRequest = $this->request->data;
                $this->Session->write('storeReportRequest', $dataRequest);
            }
            $dataRequest    = $this->Session->read('storeReportRequest');
            $storeId        = $this->Session->read('admin_store_id');
            if (!empty($storeId)) {
                
                if (!empty($storeId) && ($storeId !== 'All')) {
                    $storeDate      = $this->Common->getcurrentTime($storeId, 1);
                    $storeDateTime  = explode(" ", $storeDate);
                    $storeDate      = $storeDateTime[0];
                    $storeTime      = $storeDateTime[1];
                    $this->set('storeTime', $storeTime);
                    $sdate          = $storeDate . " " . "00:00:00";
                    $edate          = $storeDate . " " . "23:59:59";
                    $startdate      = $storeDate;
                    $enddate        = $storeDate;
                    $expoladDate    = explode("-", $startdate);
                    $fromMonthDefault   = $expoladDate[1];
                    $fromYearDefault    = $expoladDate[0];
                    $toMonthDefault     = $expoladDate[1];
                    $toYearDefault      = $expoladDate[0];
                    
                    $timezoneStore  = array();
                    $store_data = $this->Store->fetchStoreDetail($storeId);
                    if(!empty($store_data))
                    {
                        $this->loadModel('TimeZone');
                        $timezoneStore = $this->TimeZone->find('all', array('fields' => array('TimeZone.code'), 'conditions' => array('TimeZone.id' => $store_data['Store']['time_zone_id'])));
                    }
                    
                    if(isset($timezoneStore['TimeZone']['code']) && $timezoneStore['TimeZone']['code'] != '')
                    {
                        Configure::write('Config.timezone', $timezoneStore['TimeZone']['code']);
                    } else {
                        Configure::write('Config.timezone', $defaultTimeZone);
                    }
                } else {
                    $sdate      = null;
                    $edate      = null;
                    $startdate  = null;
                    $enddate    = null;
                    $fromMonthDefault   = null;
                    $fromYearDefault    = null;
                    $toMonthDefault     = null;
                    $toYearDefault      = null;
                }
                
                $reportType         = (isset($dataRequest['reportType']) ? $dataRequest['reportType'] : 1);
                $type               = (isset($dataRequest['type']) ? $dataRequest['type'] : 1);
                $orderType          = (isset($dataRequest['orderType']) ? $dataRequest['orderType'] : 1);
                $startDate          = (isset($dataRequest['startDate']) ? $this->Dateform->formatDate($dataRequest['startDate']) : $sdate);
                $endDate            = (isset($dataRequest['endDate']) ? $this->Dateform->formatDate($dataRequest['endDate']) : $edate);
                $itemId             = (isset($dataRequest['itemId']) ? $dataRequest['itemId'] : null);
                $merchantOption     = (isset($dataRequest['merchantOption']) ? $dataRequest['merchantOption'] : null);
                $fromMonth          = (isset($dataRequest['fromMonth']) ? $dataRequest['fromMonth'] : $fromMonthDefault);
                $fromYear           = (isset($dataRequest['fromYear']) ? $dataRequest['fromYear'] : $fromYearDefault);
                $toMonth            = (isset($dataRequest['toMonth']) ? $dataRequest['toMonth'] : $toMonthDefault);
                $toYear             = (isset($dataRequest['toYear']) ? $dataRequest['toYear'] : $toYearDefault);
                $couponCode         = (isset($dataRequest['coupon_code']) ? $dataRequest['coupon_code'] : null);       
                $promoId            = (isset($dataRequest['promo_id']) ? $dataRequest['promo_id'] : null);
                $extendedOfferId    = (isset($dataRequest['extended_offer_id']) ? $dataRequest['extended_offer_id'] : null);
                $productCount    = (isset($dataRequest['product_count']) ? $dataRequest['product_count'] : null);
                
                $graphPageNumber    = (isset($dataRequest['graph_page_number']) ? $dataRequest['graph_page_number'] : 0);
                
                if(empty($startDate)){
                    $startDate  = date('Y-m-d', strtotime('-6 day'));
                }
                if(empty($endDate)){
                    $endDate    = date("Y-m-d");
                }
                
                /* For All Store Graphs */
                $graphLimit = 9;
                $storeList = $pageMerchant = array();
                $allPagesCount = 0;
                
                if(isset($stores))
                {
                    foreach ($stores as $store) {
                        $storeList[$store['Store']['id']] = $store['Store']['store_name'];
                    }
                }
                $pageMerchant = array_chunk($storeList, $graphLimit, true);
                $allPagesCount = count($pageMerchant);
                /* For All Store Graphs */
                
                if(isset($reportType))
                {
                    if($reportType == 1)
                    {
                        // Report For Sales
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) 
                            {
                                //Daily
                                $graphData = $this->orderGraphListing($storeId, $startDate, $endDate, $orderType);

                                $orderProduct = $this->orderListing($storeId, $startDate, $endDate, $orderType);

                                $this->set(compact('graphData', 'startDate', 'endDate', 'orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/daily_report_single_store');
                            }
                            else if($type == 2) 
                            {//Weekly
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                $expoladEndDate=  explode(" ", $endFrom);
                                $explodeEndYear = explode("-", $expoladEndDate[0]);
                                $endYear=$explodeEndYear[0];
                                $startweekNumber = (int)date("W", strtotime($startFrom));
                                $endWeekNumber = (int)date("W", strtotime($endFrom));
                                $data = array();
                                $weeknumbers = '';
                                $j = 0;
                                for ($i = $startweekNumber; $i <= $endWeekNumber; $i++) {
                                    $data[$i] = array();
                                    if ($j == 0) {
                                        $weeknumbers.="'Week" . $i . "'";
                                    } else {
                                        $weeknumbers.=",'Week" . $i . "'";
                                    }
                                    $j++;
                                    $time = strtotime("1 January $weekyear", time());
                                    $day = date('w', $time);
                                    $time += ((7 * $i) - $day) * 24 * 3600;
                                    $data[$i]['daywise'] = array();
                                    for ($k = 0; $k <= 6; $k++) {
                                        $time2 = $time + $k * 24 * 3600;
                                        $data[$i]['daywise'][date('Y-m-d', $time2)] = array(0);
                                        if ($k == 0) {
                                            $datestring = "'" . date('Y-m-d', $time2) . "'";
                                        } else {
                                            $datestring.=",'" . date('Y-m-d', $time2) . "'";
                                        }
                                        $data[$i]['datestring'] = $datestring;
                                    }
                                }
                                $result1 = $this->fetchWeeklyOrderToday($storeId, $startFrom, $endFrom, $orderType,$weekyear);

                                $weekarray = array();
                                $datearray = array();
                                $totalOrders = 0;
                                $totalAmount = 0;
                                foreach ($result1 as $k => $result) {
                                    if (in_array($result[0]['WEEKno'], $weekarray)) {
                                        $data[$result[0]['WEEKno']]['week']         = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totalamount']  += $result[0]['total'];
                                        $data[$result[0]['WEEKno']]['totalorders']  += 1;
                                        $totalAmount                                += $result[0]['total'];
                                    } else {
                                        $weekarray[$result[0]['WEEKno']]            = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totalamount']  = $result[0]['total'];
                                        $data[$result[0]['WEEKno']]['totalorders']  = 1;
                                        $totalAmount                                += $result[0]['total'];
                                    }
                                    if (in_array($result[0]['order_date'], $datearray)) {
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['total']       += $result[0]['total'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totalorders'] += 1;
                                    } else {
                                        $datearray[$result[0]['order_date']]                                            = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['total']       = $result[0]['total'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totalorders'] = 1;
                                    }
                                    $totalOrders += 1;
                                }
                                $graphData = $data;
                                $orderProduct = $this->orderListingweek($storeId, $startFrom, $endFrom, $orderType, $endYear);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'orderProduct', 'storeId', 'startFrom', 'endFrom', 'weekyear', 'weeknumbers', 'totalOrders', 'totalAmount'));
                                $this->render('/Elements/storeReports/dollar/weekly_report_single_store');

                            } 
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $graphData = $this->orderGraphListing($storeId, $dateFrom, $dateTo, $orderType, $year);
                                $orderProduct = $this->orderListing($storeId, $dateFrom, $dateTo, $orderType);
                                $this->set(compact('graphData', 'month', 'year', 'orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/monthly_report_single_store');
                            } 
                            else if($type == 4) 
                            {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $graphData = $this->orderGraphListing($storeId, $dateFrom, $dateTo, $orderType);
                                $orderProduct = $this->orderListing($storeId, $dateFrom, $dateTo, $orderType);
                                $this->set(compact('graphData', 'yearFrom', 'yearTo', 'orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/yearly_report_single_store');
                            }
                        } 
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }


                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                
                                $graphData = $this->orderGraphListing($storeId, $startDate, $endDate, $orderType);
                                $orderProduct = $this->orderListing($storeId, $startDate, $endDate, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/daily_report_single_store');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $graphData = $this->orderGraphListing($storeId, $dateFrom, $dateTo, $orderType);
                                $orderProduct = $this->orderListing($storeId, $dateFrom, $dateTo, $orderType);
                                $this->set(compact('graphData', 'yearFrom', 'yearTo', 'orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/life_time_report_single_store');
                            }
                        }
                    }
                    else if($reportType == 2) 
                    {
                        // Report For Product
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) 
                            {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $graphData = $this->itemListings($storeId, $startDate, $endDate, $orderType, $productCount);

                                $productData = $this->orderProductListing($storeId, $startDate, $endDate, $orderType, $productCount);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'productData', 'productCount'));
                                $this->render('/Elements/storeReports/product/index');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                $graphData = $this->itemListingsWeekly($storeId, $startFrom, $endFrom, $orderType, $weekyear, $productCount);
                                $productData = $this->orderProductListingWeekly($storeId, $startFrom, $endFrom, $orderType, $productCount);

                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'productData', 'weekyear', 'productCount', 'fromYear', 'fromMonth', 'toYear', 'toMonth'));
                                $this->render('/Elements/storeReports/product/index');
                            }
                            else if($type == 3) 
                            {
                                //Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $graphData = $this->itemListings($storeId, $dateFrom, $dateTo, $orderType, $productCount);

                                $productData = $this->orderProductListing($storeId, $dateFrom, $dateTo, $orderType, $productCount);

                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'productData', 'productCount', 'fromYear', 'fromMonth', 'toYear', 'toMonth'));
                                $this->render('/Elements/storeReports/product/index');
                            } 
                            else if($type == 4) 
                            {
                                //Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $graphData = $this->itemListings($storeId, $dateFrom, $dateTo, $orderType, $productCount);

                                $productData = $this->orderProductListing($storeId, $dateFrom, $dateTo, $orderType, $productCount);

                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'productData', 'productCount', 'fromYear', 'toYear'));
                                $this->render('/Elements/storeReports/product/index');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $graphData = $this->itemListings($storeId, $startDate, $endDate, $orderType, $productCount);

                                $productData = $this->orderProductListing($storeId, $startDate, $endDate, $orderType, $productCount);

                                $this->set(compact('graphData', 'startDate', 'endDate', 'productData', 'productCount'));
                                $this->render('/Elements/storeReports/product/index');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $graphData = $this->itemListings($storeId, $dateFrom, $dateTo, $orderType, $productCount);

                                $productData = $this->orderProductListing($storeId, $dateFrom, $dateTo, $orderType, $productCount);

                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'productData', 'productCount', 'fromYear', 'toYear'));
                                $this->render('/Elements/storeReports/product/index');
                            }
                        }
                    } 
                    else if($reportType == 3) 
                    {
                        // Customer Report Section
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) 
                            {
                                if (isset($startDate) && isset($endDate)) {
                                    $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                                    $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                                }

                                $user = array();
                                if(!empty($storeId)){
                                    $result1 = $this->User->fetchUserToday($storeId, $startdate, $enddate);
                                    foreach ($result1 as $key => $data) {
                                        $user[$key]['User']['per_day'] = $data[0]['per_day'];
                                        $user[$key]['User']['created'] = $data['User']['created'];
                                    }
                                }
                                $result = $user;
                                $userdata = $this->userListing($storeId, $startdate, $enddate);
                                $startdate = $this->Dateform->formatDate($startdate);
                                $enddate = $this->Dateform->formatDate($enddate);
                                $paginationdata = array('store_id' => $storeId, 'startdate' => $startdate, 'enddate' => $enddate, 'type' => 1, 'page' => 1);
                                $this->set(compact('page', 'userdata', 'user', 'result', 'startdate', 'enddate', 'type', 'dateFrom', 'dateTo', 'Month', 'Year', 'yearFrom', 'yearTo', 'paginationdata'));

                                $this->render('/Elements/storeReports/customer/daily');

                            } 
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                $expoladEndDate=  explode(" ", $endFrom);
                                $explodeEndYear = explode("-", $expoladEndDate[0]);
                                $endYear=$explodeEndYear[0];
                                $startweekNumber = (int)date("W", strtotime($startFrom));
                                $endWeekNumber = (int)date("W", strtotime($endFrom));
                                $data = array();
                                $return = array();
                                $weeknumbers = '';
                                $j = 0;
                                for ($i = $startweekNumber; $i <= $endWeekNumber; $i++) {
                                    $data[$i] = array();
                                    if ($j == 0) {
                                        $weeknumbers.="'Week" . $i . "'";
                                    } else {
                                        $weeknumbers.=",'Week" . $i . "'";
                                    }
                                    $j++;

                                    $time = strtotime("1 January $weekyear", time());
                                    $day = date('w', $time);
                                    $time += ((7 * $i) - $day) * 24 * 3600;
                                    $data[$i]['daywise'] = array();
                                    for ($k = 0; $k <= 6; $k++) {
                                        $time2 = $time + $k * 24 * 3600;
                                        $data[$i]['daywise'][date('Y-m-d', $time2)] = array(0);
                                        if ($k == 0) {
                                            $datestring = "'" . date('Y-m-d', $time2) . "'";
                                        } else {
                                            $datestring.=",'" . date('Y-m-d', $time2) . "'";
                                        }
                                        $data[$i]['datestring'] = $datestring;
                                    }
                                }
                                $result1 = $this->fetchWeeklyUserToday($storeId, $startFrom, $endFrom,$endYear);
                                $weekarray = array();
                                $datearray = array();
                                $totalCustomer = 0;
                                foreach ($result1 as $k => $result) {
                                    if (in_array($result[0]['WEEKno'], $weekarray)) {
                                        $data[$result[0]['WEEKno']]['week'] = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totaluser'] += $result[0]['total'];
                                        $totalCustomer                           += $result[0]['total'];
                                    } else {
                                        $weekarray[$result[0]['WEEKno']] = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totaluser'] = $result[0]['total'];
                                        $totalCustomer                           += $result[0]['total'];
                                    }
                                    
                                }
                                $userdata = $this->userListingweekly($storeId, $startFrom, $endFrom,$endYear);
                                $startFrom = $this->Dateform->formatDate($startFrom);
                                $endFrom = $this->Dateform->formatDate($endFrom);
                                $startdate = $startFrom;
                                $enddate = $endFrom;
                                $this->set(compact('userdata', 'weeknumbers', 'data', 'startFrom', 'endFrom', 'totalCustomer'));
                                $this->render('/Elements/storeReports/customer/weekly');
                            } else if($type == 3) {
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                $user = array();
                                if(!empty($storeId)){
                                    $result1 = $this->User->fetchUserToday($storeId, $dateFrom, $dateTo);
                                    foreach ($result1 as $key => $data) {
                                        $user[$key]['User']['per_day'] = $data[0]['per_day'];
                                        $user[$key]['User']['created'] = $data['User']['created'];
                                    }
                                }
                                $result = $user;
                                $userdata = $this->userListing($storeId, $dateFrom, $dateTo);
                                $paginationdata = array('store_id' => $storeId, 'startdate' => $dateFrom, 'enddate' => $dateTo, 'type' => 3);
                                $this->set(compact('page', 'userdata', 'user', 'result', 'date', 'type', 'dateFrom', 'dateTo', 'month', 'year', 'yearFrom', 'yearTo', 'paginationdata', 'order'));
                                $this->render('/Elements/storeReports/customer/monthly');
                            } else if($type == 4) {
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                $user = array();
                                if(!empty($storeId)){
                                    $result1 = $this->User->fetchUserToday($storeId, $dateFrom, $dateTo);
                                    foreach ($result1 as $key => $data) {
                                        $user[$key]['User']['per_day'] = $data[0]['per_day'];
                                        $user[$key]['User']['created'] = $data['User']['created'];
                                    }
                                }
                                $result = $user;
                                $userdata = $this->userListing($storeId, $dateFrom, $dateTo);
                                $paginationdata = array('store_id' => $storeId, 'startdate' => $dateFrom, 'enddate' => $dateTo, 'type' => 4);
                                $this->set(compact('page', 'userdata', 'user', 'result', 'date', 'type', 'dateFrom', 'dateTo', 'Month', 'Year', 'yearFrom', 'yearTo', 'paginationdata', 'order'));
                                $this->render('/Elements/storeReports/customer/yearly');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';
                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $user = array();
                                if(!empty($storeId)){
                                    $result1 = $this->User->fetchUserToday($storeId, $startDate, $endDate);
                                    foreach ($result1 as $key => $data) {
                                        $user[$key]['User']['per_day'] = $data[0]['per_day'];
                                        $user[$key]['User']['created'] = $data['User']['created'];
                                    }
                                }
                                $result = $user;
                                
                                $userdata = $this->userListing($storeId, $startDate, $endDate);
                                $startdate = $startDate;
                                $enddate = $endDate;
                                $this->set(compact('result', 'startdate', 'enddate', 'type', 'userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/daily');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $user = array();
                                if(!empty($storeId)){
                                    $result1 = $this->User->fetchUserToday($storeId, $dateFrom, $dateTo);
                                    foreach ($result1 as $key => $data) {
                                        $user[$key]['User']['per_day'] = $data[0]['per_day'];
                                        $user[$key]['User']['created'] = $data['User']['created'];
                                    }
                                }
                                $result = $user;
                                $userdata = $this->userListing($storeId, $dateFrom, $dateTo);
                                $this->set(compact('result', 'startDate', 'endDate' ,'itemId', 'userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/life_time');
                            }
                        }
                    } 
                    else if($reportType == 4) 
                    {
                        // Report For Coupon
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                
                                $graphData = $this->couponListings($storeId, $startDate, $endDate, $couponCode, $orderType);
                                $orderCoupon  = $this->orderCouponListing($storeId, $startDate, $endDate, $couponCode, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/daily');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                // For SingLe Store
                                $expoladEndDate=  explode(" ", $endFrom);
                                $explodeEndYear = explode("-", $expoladEndDate[0]);
                                $endYear=$explodeEndYear[0];
                                $startweekNumber = (int)date("W", strtotime($startFrom));
                                $endWeekNumber = (int)date("W", strtotime($endFrom));
                                $data = array();
                                $weeknumbers = '';
                                $j = 0;
                                for ($i = $startweekNumber; $i <= $endWeekNumber; $i++) {
                                    $data[$i] = array();
                                    if ($j == 0) {
                                        $weeknumbers.="'Week" . $i . "'";
                                    } else {
                                        $weeknumbers.=",'Week" . $i . "'";
                                    }
                                    $j++;
                                    $time = strtotime("1 January $weekyear", time());
                                    $day = date('w', $time);
                                    $time += ((7 * $i) - $day) * 24 * 3600;
                                    $data[$i]['daywise'] = array();
                                    for ($k = 0; $k <= 6; $k++) {
                                        $time2 = $time + $k * 24 * 3600;
                                        $data[$i]['daywise'][date('Y-m-d', $time2)] = array(0);
                                        if ($k == 0) {
                                            $datestring = "'" . date('Y-m-d', $time2) . "'";
                                        } else {
                                            $datestring.=",'" . date('Y-m-d', $time2) . "'";
                                        }
                                        $data[$i]['datestring'] = $datestring;
                                    }
                                }

                                $result1 = $this->getWeeklyCouponListing($storeId, $startFrom, $endFrom, $orderType, $couponCode);

                                $weekarray = array();
                                $datearray = array();
                                
                                $totalCoupon = 0;
                                foreach ($result1 as $k => $result) {
                                    if (in_array($result[0]['WEEKno'], $weekarray)) {
                                        $data[$result[0]['WEEKno']]['week']         = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totalcoupon']  += 1;
                                    } else {
                                        $weekarray[$result[0]['WEEKno']]            = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totalcoupon']  = 1;
                                    }
                                    if (in_array($result[0]['order_date'], $datearray)) {
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totalcoupon'] += 1;
                                    } else {
                                        $datearray[$result[0]['order_date']]                                            = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totalcoupon'] = 1;
                                    }
                                    $totalCoupon    += 1;
                                }

                                $graphData = $data;

                                $orderCoupon = $this->orderCouponWeeklyListing($storeId, $startFrom, $endFrom, $orderType, $couponCode);

                                $this->set(compact('graphData', 'startDate', 'endDate', 'orderCoupon', 'storeId', 'startFrom', 'endFrom', 'weekyear', 'weeknumbers', 'totalCoupon'));
                                $this->render('/Elements/storeReports/coupon/weekly');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $graphData = $this->couponListings($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                                $orderCoupon  = $this->orderCouponListing($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'type', 'orderCoupon', 'storeId', 'year', 'month'));
                                $this->render('/Elements/storeReports/coupon/monthly');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $graphData = $this->couponListings($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                                $orderCoupon = $this->orderCouponListing($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'couponCode', 'orderCoupon', 'storeId', 'yearFrom', 'yearTo' ));
                                $this->render('/Elements/storeReports/coupon/yearly');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $graphData = $this->couponListings($storeId, $startDate, $endDate, $couponCode, $orderType);
                                $orderCoupon = $this->orderCouponListing($storeId, $startDate, $endDate, $couponCode, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/daily');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $graphData = $this->couponListings($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                                $orderCoupon = $this->orderCouponListing($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'couponCode', 'orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/life_time');
                            }
                        }
                    }
                    else if($reportType == 5) 
                    {
                        // Report For Promotions
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $graphData = $this->promoListings($storeId, $startDate, $endDate, $promoId, $orderType);
                                $orderPromo= $this->orderPromoListing($storeId, $startDate, $endDate, $promoId, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/daily');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                // For SingLe Store
                                $expoladEndDate=  explode(" ", $endFrom);
                                $explodeEndYear = explode("-", $expoladEndDate[0]);
                                $endYear=$explodeEndYear[0];
                                $startweekNumber = (int)date("W", strtotime($startFrom));
                                $endWeekNumber = (int)date("W", strtotime($endFrom));
                                $data = array();
                                $weeknumbers = '';
                                $j = 0;
                                for ($i = $startweekNumber; $i <= $endWeekNumber; $i++) {
                                    $data[$i] = array();
                                    if ($j == 0) {
                                        $weeknumbers.="'Week" . $i . "'";
                                    } else {
                                        $weeknumbers.=",'Week" . $i . "'";
                                    }
                                    $j++;
                                    $time = strtotime("1 January $weekyear", time());
                                    $day = date('w', $time);
                                    $time += ((7 * $i) - $day) * 24 * 3600;
                                    $data[$i]['daywise'] = array();
                                    for ($k = 0; $k <= 6; $k++) {
                                        $time2 = $time + $k * 24 * 3600;
                                        $data[$i]['daywise'][date('Y-m-d', $time2)] = array(0);
                                        if ($k == 0) {
                                            $datestring = "'" . date('Y-m-d', $time2) . "'";
                                        } else {
                                            $datestring.=",'" . date('Y-m-d', $time2) . "'";
                                        }
                                        $data[$i]['datestring'] = $datestring;
                                    }
                                }

                                $result1 = $this->getWeeklyPromoListing($storeId, $startFrom, $endFrom, $orderType, $promoId);

                                $weekarray = array();
                                $datearray = array();
                                
                                $totalOffer = 0;
                                foreach ($result1 as $k => $result) {
                                    if (in_array($result[0]['WEEKno'], $weekarray)) {
                                        $data[$result[0]['WEEKno']]['week']         = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totaloffer']  += $result['OrderOffer']['quantity'];
                                    } else {
                                        $weekarray[$result[0]['WEEKno']]            = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totaloffer']   = $result['OrderOffer']['quantity'];
                                    }
                                    if (in_array($result[0]['order_date'], $datearray)) {
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totaloffer'] += $result['OrderOffer']['quantity'];
                                    } else {
                                        $datearray[$result[0]['order_date']]                                            = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totaloffer'] = $result['OrderOffer']['quantity'];
                                    }
                                    $totalOffer    += $result['OrderOffer']['quantity'];
                                }

                                $graphData = $data;

                                $orderPromo = $this->orderPromoWeeklyListing($storeId, $startFrom, $endFrom, $orderType, $promoId);

                                $this->set(compact('graphData', 'startDate', 'endDate', 'orderPromo', 'storeId', 'startFrom', 'endFrom', 'weekyear', 'weeknumbers', 'totalOffer'));
                                $this->render('/Elements/storeReports/promo/weekly');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $graphData = $this->promoListings($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                                $orderPromo  = $this->orderPromoListing($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'type', 'orderPromo', 'storeId', 'year', 'month'));
                                $this->render('/Elements/storeReports/promo/monthly');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $graphData = $this->promoListings($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                                $orderPromo = $this->orderPromoListing($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'promoId', 'orderPromo', 'storeId', 'yearFrom', 'yearTo' ));
                                $this->render('/Elements/storeReports/promo/yearly');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $graphData = $this->promoListings($storeId, $startDate, $endDate, $promoId, $orderType);
                                $orderPromo = $this->orderPromoListing($storeId, $startDate, $endDate, $promoId, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/daily');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $graphData = $this->promoListings($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                                $orderPromo = $this->orderPromoListing($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'promoId', 'orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/life_time');
                            }
                        }
                    }
                    else if($reportType == 6) 
                    {
                        // Report For Extended Offers
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $graphData = $this->extendedOfferListings($storeId, $startDate, $endDate, $extendedOfferId, $orderType);
                                $orderExtendedOffer= $this->orderExtendedOfferListing($storeId, $startDate, $endDate, $extendedOfferId, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/daily');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                // For SingLe Store
                                $expoladEndDate=  explode(" ", $endFrom);
                                $explodeEndYear = explode("-", $expoladEndDate[0]);
                                $endYear=$explodeEndYear[0];
                                $startweekNumber = (int)date("W", strtotime($startFrom));
                                $endWeekNumber = (int)date("W", strtotime($endFrom));
                                $data = array();
                                $weeknumbers = '';
                                $j = 0;
                                for ($i = $startweekNumber; $i <= $endWeekNumber; $i++) {
                                    $data[$i] = array();
                                    if ($j == 0) {
                                        $weeknumbers.="'Week" . $i . "'";
                                    } else {
                                        $weeknumbers.=",'Week" . $i . "'";
                                    }
                                    $j++;
                                    $time = strtotime("1 January $weekyear", time());
                                    $day = date('w', $time);
                                    $time += ((7 * $i) - $day) * 24 * 3600;
                                    $data[$i]['daywise'] = array();
                                    for ($k = 0; $k <= 6; $k++) {
                                        $time2 = $time + $k * 24 * 3600;
                                        $data[$i]['daywise'][date('Y-m-d', $time2)] = array(0);
                                        if ($k == 0) {
                                            $datestring = "'" . date('Y-m-d', $time2) . "'";
                                        } else {
                                            $datestring.=",'" . date('Y-m-d', $time2) . "'";
                                        }
                                        $data[$i]['datestring'] = $datestring;
                                    }
                                }

                                $result1 = $this->getWeeklyExtendedOfferListing($storeId, $startFrom, $endFrom, $orderType, $extendedOfferId);

                                $weekarray = array();
                                $datearray = array();
                                
                                $totalOffer = 0;
                                foreach ($result1 as $k => $result) {
                                    if (in_array($result[0]['WEEKno'], $weekarray)) {
                                        $data[$result[0]['WEEKno']]['week']         = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totaloffer']  += $result['OrderItemFree']['free_quantity'];
                                    } else {
                                        $weekarray[$result[0]['WEEKno']]            = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totaloffer']   = $result['OrderItemFree']['free_quantity'];
                                    }
                                    if (in_array($result[0]['order_date'], $datearray)) {
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totaloffer'] += $result['OrderItemFree']['free_quantity'];
                                    } else {
                                        $datearray[$result[0]['order_date']]                                            = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totaloffer'] = $result['OrderItemFree']['free_quantity'];
                                    }
                                    $totalOffer    += $result['OrderItemFree']['free_quantity'];
                                }

                                $graphData = $data;

                                $orderExtendedOffer = $this->orderExtendedOfferWeeklyListing($storeId, $startFrom, $endFrom, $orderType, $extendedOfferId);

                                $this->set(compact('graphData', 'startDate', 'endDate', 'orderExtendedOffer', 'storeId', 'startFrom', 'endFrom', 'weekyear', 'weeknumbers', 'totalOffer'));
                                $this->render('/Elements/storeReports/extended_promo/weekly');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $graphData = $this->extendedOfferListings($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                                $orderExtendedOffer  = $this->orderExtendedOfferListing($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'type', 'orderExtendedOffer', 'storeId', 'year', 'month'));
                                $this->render('/Elements/storeReports/extended_promo/monthly');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $graphData = $this->extendedOfferListings($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                                $orderExtendedOffer = $this->orderExtendedOfferListing($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'promoId', 'orderExtendedOffer', 'storeId', 'yearFrom', 'yearTo' ));
                                $this->render('/Elements/storeReports/extended_promo/yearly');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 day"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $graphData = $this->extendedOfferListings($storeId, $startDate, $endDate, $extendedOfferId, $orderType);
                                $orderExtendedOffer = $this->orderExtendedOfferListing($storeId, $startDate, $endDate, $extendedOfferId, $orderType);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/daily');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $graphData = $this->extendedOfferListings($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                                $orderExtendedOffer = $this->orderExtendedOfferListing($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'extendedOfferId', 'orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/life_time');
                            }
                        }
                    }
                    else if($reportType == 7) 
                    {
                        // Report For Dine In
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) 
                            {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $graphData = $this->dineInGraphListings($storeId, $startDate, $endDate);
                                $dineInData= $this->dineInListing($storeId, $startDate, $endDate);
                                $dineInPieData = $this->dineInPieListing($storeId, $startDate, $endDate);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'dineInData', 'storeId', 'dineInPieData'));
                                $this->render('/Elements/storeReports/dine_in/daily');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                // For SingLe Store
                                $expoladEndDate=  explode(" ", $endFrom);
                                $explodeEndYear = explode("-", $expoladEndDate[0]);
                                $endYear=$explodeEndYear[0];
                                $startweekNumber = (int)date("W", strtotime($startFrom));
                                $endWeekNumber = (int)date("W", strtotime($endFrom));
                                $data = array();
                                $weeknumbers = '';
                                $j = 0;
                                for ($i = $startweekNumber; $i <= $endWeekNumber; $i++) {
                                    $data[$i] = array();
                                    if ($j == 0) {
                                        $weeknumbers.="'Week" . $i . "'";
                                    } else {
                                        $weeknumbers.=",'Week" . $i . "'";
                                    }
                                    $j++;
                                    $time = strtotime("1 January $weekyear", time());
                                    $day = date('w', $time);
                                    $time += ((7 * $i) - $day) * 24 * 3600;
                                    $data[$i]['daywise'] = array();
                                    for ($k = 0; $k <= 6; $k++) {
                                        $time2 = $time + $k * 24 * 3600;
                                        $data[$i]['daywise'][date('Y-m-d', $time2)] = array(0);
                                        if ($k == 0) {
                                            $datestring = "'" . date('Y-m-d', $time2) . "'";
                                        } else {
                                            $datestring.=",'" . date('Y-m-d', $time2) . "'";
                                        }
                                        $data[$i]['datestring'] = $datestring;
                                    }
                                }

                                $result1 = $this->dineInWeeklyGraphListing($storeId, $startFrom, $endFrom);

                                $weekarray = array();
                                $datearray = array();

                                $totalDineIn = 0;
                                foreach ($result1 as $k => $result) {
                                    if (in_array($result[0]['WEEKno'], $weekarray)) {
                                        $data[$result[0]['WEEKno']]['week']         = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totalcount']  += 1;
                                    } else {
                                        $weekarray[$result[0]['WEEKno']]            = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['totalcount']   = 1;
                                    }
                                    if (in_array($result[0]['order_date'], $datearray)) {
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totalcount'] += 1;
                                    } else {
                                        $datearray[$result[0]['order_date']]                                            = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['WEEKno']      = $result[0]['WEEKno'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['order_date']  = $result[0]['order_date'];
                                        $data[$result[0]['WEEKno']]['daywise'][$result[0]['order_date']]['totalcount'] = 1;
                                    }
                                    $totalDineIn    += 1;
                                }

                                $graphData = $data;

                                $dineInData = $this->dineInWeeklyListing($storeId, $startFrom, $endFrom);
                                $dineInPieData = $this->dineInPieWeeklyListing($storeId, $startFrom, $endFrom);

                                $this->set(compact('graphData', 'startDate', 'endDate', 'dineInData', 'storeId', 'startFrom', 'endFrom', 'weekyear', 'weeknumbers', 'totalDineIn', 'dineInPieData'));
                                $this->render('/Elements/storeReports/dine_in/weekly');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $graphData = $this->dineInGraphListings($storeId, $dateFrom, $dateTo);
                                $dineInData  = $this->dineInListing($storeId, $dateFrom, $dateTo);
                                $dineInPieData = $this->dineInPieListing($storeId, $dateFrom, $dateTo);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo', 'type', 'dineInData', 'storeId', 'year', 'month', 'dineInPieData'));
                                $this->render('/Elements/storeReports/dine_in/monthly');
                            }
                            else if($type == 4) 
                            {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $graphData = $this->dineInGraphListings($storeId, $dateFrom, $dateTo);
                                $dineInData = $this->dineInListing($storeId, $dateFrom, $dateTo);
                                $dineInPieData = $this->dineInPieListing($storeId, $dateFrom, $dateTo);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'couponCode', 'dineInData', 'storeId', 'yearFrom', 'yearTo', 'dineInPieData'));
                                $this->render('/Elements/storeReports/dine_in/yearly');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 day"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                
                                $graphData = $this->dineInGraphListings($storeId, $startDate, $endDate);
                                $dineInData = $this->dineInListing($storeId, $startDate, $endDate);
                                $dineInPieData = $this->dineInPieListing($storeId, $startDate, $endDate);
                                $this->set(compact('graphData', 'startDate', 'endDate', 'type', 'dineInData', 'storeId', 'dineInPieData'));
                                $this->render('/Elements/storeReports/dine_in/daily');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $graphData = $this->dineInGraphListings($storeId, $dateFrom, $dateTo);
                                $dineInData = $this->dineInListing($storeId, $dateFrom, $dateTo);
                                $dineInPieData = $this->dineInPieListing($storeId, $dateFrom, $dateTo);
                                $this->set(compact('graphData', 'dateFrom', 'dateTo' ,'couponCode', 'dineInData', 'storeId', 'yearFrom', 'yearTo', 'dineInPieData'));
                                $this->render('/Elements/storeReports/dine_in/life_time');
                            }
                        }
                    }
                }
            }
        }
        Configure::write('Config.timezone', $defaultTimeZone);
    }
    
    /*     * ***********************
     * Function name:orderListing()
      Description:graph order list
      created:22/09/2015
     *
     * ********************* */

    public function orderListing($storeID = null, $startDate = null, $endDate = null, $orderType = null, $page = 1, $sort = '', $sort_direction = '') 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND (DATE(Order.created) >= '" . $startDate . "' AND DATE(Order.created) <= '" . $endDate . "')";
        }
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id'),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id'))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array(
            'belongsTo' => array(
                'User' => array(
                    'className' => 'User',
                    'foreignKey' => 'user_id'
                ),
                'OrderStatus' => array(
                    'className' => 'OrderStatus',
                    'foreignKey' => 'order_status_id'
                ),
                'Segment' => array(
                    'className' => 'Segment',
                    'foreignKey' => 'seqment_id'
                ),
                'DeliveryAddress' => array(
                    'className' => 'DeliveryAddress',
                    'foreignKey' => 'delivery_address_id'
                ),
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id'
                ),
            )
                ), false
        );
        if (!empty($orderType) && $orderType != 1) {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        } else {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $this->paginate = array(
                'recursive'     => 3, 
                'conditions'    => array_merge(array($criteria), array('Order.store_id' => $storeID)), 
                'page'          => $page,
                'limit'         => $this->pageLimit,
                'order'         => array($sort => $sort_direction)
            );
        $orderdetail = $this->paginate('Order');

        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:orderListingweek()
      Description:graph order list
      created:22/09/2015
     *
     * ********************* */

    public function orderListingweek($storeID = null, $startDate = null, $endDate = null, $orderType = null,$endYear=null, $page = 1, $sort = '', $sort_direction = '') {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <= WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $enddate . "')";
        }
 
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id'),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id'))), false);
        $this->Order->bindModel(
            array(
                'belongsTo' => array(
                    'User' => array(
                        'className' => 'User',
                        'foreignKey' => 'user_id'
                    ),
                    'OrderStatus' => array(
                        'className' => 'OrderStatus',
                        'foreignKey' => 'order_status_id'
                    ),
                    'Segment' => array(
                        'className' => 'Segment',
                        'foreignKey' => 'seqment_id'
                    ),
                    'DeliveryAddress' => array(
                        'className' => 'DeliveryAddress',
                        'foreignKey' => 'delivery_address_id'
                    ), 'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                ),
                'hasMany' => array(
                    'OrderItem' => array(
                        'className' => 'OrderItem',
                        'foreignKey' => 'order_id'
                    ),
                )
            ), false
        );
        
        if (!empty($orderType) && $orderType != 1) {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        } else {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $this->paginate = array(
            'recursive'     => 3,
            'conditions'    => array_merge(array($criteria), array('Order.store_id' => $storeID)),
            'order'         => array($sort => $sort_direction), 
            'group'         => array('Order.id'),
            'page'          => $page,
            'limit'         => $this->pageLimit
        );
        $orderdetail = $this->paginate('Order');
        return $orderdetail;
    }
    
    public function fetchWeeklyOrderToday($storeId = null, $start = null, $end = null, $orderType = null,$endYear=null) {
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id'
                        )
                    )
                )
        );
        $conditions = "Order.is_future_order=0 AND Order.is_active=1 AND Order.is_deleted=0 AND WEEK(Order.created) >= WEEK('" . $start . "') AND WEEK(Order.created) <= WEEK('" . $end . "') AND YEAR(Order.created) = YEAR('" . $end . "')";
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions .=" AND Segment.id= '" . $orderType . "'";
        } else {
            $conditions .=" AND Segment.id IN (2,3)";
        }
        
        $result = $this->Order->find('all', array('fields' => array('WEEK(Order.created) AS WEEKno', 'DATE(Order.created) AS order_date', '`amount`-`coupon_discount` AS total'), 'conditions' => array_merge(array($conditions), array('Order.store_id' => $storeId))));
        return $result;
    }
    
    /*     * ***********************
     * Function name:orderGraphListing()
      Description:graph order list
      created:22/09/2015
     *
     * ********************* */

    public function orderGraphListing($storeID = null, $startDate = null, $endDate = null, $orderType = null) 
    {
        
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND (DATE(Order.created) >= '" . $startDate . "' AND DATE(Order.created) <= '" . $endDate . "')";
        }
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id'),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id'))), false);
        $this->Order->bindModel(
                array(
            'belongsTo' => array(
                'User' => array(
                    'className' => 'User',
                    'foreignKey' => 'user_id'
                ),
                'OrderStatus' => array(
                    'className' => 'OrderStatus',
                    'foreignKey' => 'order_status_id'
                ),
                'Segment' => array(
                    'className' => 'Segment',
                    'foreignKey' => 'seqment_id'
                ),
                'DeliveryAddress' => array(
                    'className' => 'DeliveryAddress',
                    'foreignKey' => 'delivery_address_id'
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id'
                ),
            )
                ), false
        );
        if (!empty($orderType) && $orderType != 1) {
            $criteria .=" AND Segment.id= '" . $orderType . "'";
        } else {
            $criteria .=" AND Segment.id IN (2,3)";
        }
        $graphorderdetail = $this->Order->find('all', array('recursive' => 2, 'conditions' => array_merge(array($criteria), array('Order.store_id' => $storeID)), 'order' => array('Order.created' => 'DESC')));
        return $graphorderdetail;
    }
    
    
    function getPaginationData()
    {
        $defaultTimeZone = date_default_timezone_get();
        $this->layout = false;
        $this->autoRender = false;
        if ($this->request->is(array('ajax'))) {
            if(isset($this->request->data))
            {
                $dataRequest = $this->request->data;
                $this->Session->write('storeReportRequest', $dataRequest);
            }
            $dataRequest    = $this->Session->read('storeReportRequest');
            $storeId        = $this->Session->read('admin_store_id');
            if (!empty($storeId)) {
                if (!empty($storeId) && ($storeId !== 'All')) {
                    $storeDate      = $this->Common->getcurrentTime($storeId, 1);
                    $storeDateTime  = explode(" ", $storeDate);
                    $storeDate      = $storeDateTime[0];
                    $storeTime      = $storeDateTime[1];
                    $this->set('storeTime', $storeTime);
                    $sdate          = $storeDate . " " . "00:00:00";
                    $edate          = $storeDate . " " . "23:59:59";
                    $startdate      = $storeDate;
                    $enddate        = $storeDate;
                    $expoladDate    = explode("-", $startdate);
                    $fromMonthDefault   = $expoladDate[1];
                    $fromYearDefault    = $expoladDate[0];
                    $toMonthDefault     = $expoladDate[1];
                    $toYearDefault      = $expoladDate[0];
                    
                    $timezoneStore  = array();
                    $store_data = $this->Store->fetchStoreDetail($storeId);
                    if(!empty($store_data))
                    {
                        $this->loadModel('TimeZone');
                        $timezoneStore = $this->TimeZone->find('all', array('fields' => array('TimeZone.code'), 'conditions' => array('TimeZone.id' => $store_data['Store']['time_zone_id'])));
                    }
                    
                    if(isset($timezoneStore['TimeZone']['code']) && $timezoneStore['TimeZone']['code'] != '')
                    {
                        Configure::write('Config.timezone', $timezoneStore['TimeZone']['code']);
                    } else {
                        Configure::write('Config.timezone', $defaultTimeZone);
                    }
                } else {
                    $sdate      = null;
                    $edate      = null;
                    $startdate  = null;
                    $enddate    = null;
                    $fromMonthDefault   = null;
                    $fromYearDefault    = null;
                    $toMonthDefault     = null;
                    $toYearDefault      = null;
                }
                
                $reportType         = (isset($dataRequest['reportType']) ? $dataRequest['reportType'] : null);
                $orderType          = (isset($dataRequest['orderType']) ? $dataRequest['orderType'] :1);
                $type               = (isset($dataRequest['type']) ? $dataRequest['type'] : null);
                $merchantOption     = (isset($dataRequest['merchantOption']) ? $dataRequest['merchantOption'] : null);
                $startDate          = (isset($dataRequest['startDate']) ? $dataRequest['startDate'] : $sdate);
                $endDate            = (isset($dataRequest['endDate']) ? $dataRequest['endDate'] : $edate);
                $itemId             = (isset($dataRequest['itemId']) ? $dataRequest['itemId'] : null);
                $fromMonth          = (isset($dataRequest['fromMonth']) ? $dataRequest['fromMonth'] : $fromMonthDefault);
                $fromYear           = (isset($dataRequest['fromYear']) ? $dataRequest['fromYear'] : $fromYearDefault);
                $toMonth            = (isset($dataRequest['toMonth']) ? $dataRequest['toMonth'] : $toMonthDefault);
                $toYear             = (isset($dataRequest['toYear']) ? $dataRequest['toYear'] : $toYearDefault);
                $page               = (isset($dataRequest['page']) ? $dataRequest['page'] : 1);
                $sort               = (isset($dataRequest['sort']) ? $dataRequest['sort'] : '');
                $sort_direction     = (isset($dataRequest['sort_direction']) ? $dataRequest['sort_direction'] : 'asc');
                $couponCode         = (isset($dataRequest['coupon_code']) ? $dataRequest['coupon_code'] : null);
                $promoId            = (isset($dataRequest['promo_id']) ? $dataRequest['promo_id'] : null);
                $extendedOfferId    = (isset($dataRequest['extended_offer_id']) ? $dataRequest['extended_offer_id'] : null);
                $productCount    = (isset($dataRequest['product_count']) ? $dataRequest['product_count'] : null);
                if(isset($reportType))
                {
                    if($reportType == 1)
                    {
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));

                                $orderProduct = $this->orderListing($storeId, $startDate, $endDate, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/pagination');

                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                $orderProduct = $this->orderListingweek($storeId, $startFrom, $endFrom, $orderType, $weekyear, $page, $sort, $sort_direction);
                                $this->set(compact('orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/pagination');
                            } else if($type == 3) {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                $orderProduct = $this->orderListing($storeId, $dateFrom, $dateTo, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/pagination');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));

                                $orderProduct = $this->orderListing($storeId, $dateFrom, $dateTo, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/pagination');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }
                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $orderProduct = $this->orderListing($storeId, $startDate, $endDate, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/pagination');
                            }
                            if($merchantOption == 13)
                            {   
                                $orderProduct = $this->orderListing($storeId, $startDate, $endDate, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderProduct', 'storeId'));
                                $this->render('/Elements/storeReports/dollar/pagination');
                            }
                        }
                    }
                    else if($reportType == 3) 
                    {
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));

                                $userdata = $this->userListing($storeId, $startDate, $endDate, $page, $sort, $sort_direction);
                                $this->set(compact('userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/pagination');

                            }
                            else if($type == 2) {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                $userdata = $this->userListingweekly($storeId, $startFrom, $endFrom, $weekyear, $page, $sort, $sort_direction);
                                
                                $this->set(compact('userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/pagination');
                            } else if($type == 3) {//Monthly
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                $userdata = $this->userListing($storeId, $dateFrom, $dateTo, $page, $sort, $sort_direction);
                                $this->set(compact('userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/pagination');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));

                                $userdata = $this->userListing($storeId, $dateFrom, $dateTo, $page, $sort, $sort_direction);
                                
                                $this->set(compact('userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/pagination');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }
                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $userdata = $this->userListing($storeId, $dateFrom, $dateTo, $page, $sort, $sort_direction);
                                $this->set(compact('userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/pagination');
                            }
                            if($merchantOption == 13)
                            {   
                                $userdata = $this->userListing($storeId, $startDate, $endDate, $page, $sort, $sort_direction);
                                $this->set(compact('userdata', 'storeId'));
                                $this->render('/Elements/storeReports/customer/pagination');
                            }
                        }
                    } 
                    else if($reportType == 4) 
                    {
                        // Report For Coupon
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                $orderCoupon  = $this->orderCouponListing($storeId, $startDate, $endDate, $couponCode, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/pagination');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                $orderCoupon = $this->orderCouponWeeklyListing($storeId, $startFrom, $endFrom, $orderType, $couponCode, $page, $sort, $sort_direction);

                                $this->set(compact('orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/pagination');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                $orderCoupon  = $this->orderCouponListing($storeId, $dateFrom, $dateTo, $couponCode, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/pagination');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $orderCoupon = $this->orderCouponListing($storeId, $dateFrom, $dateTo, $couponCode, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/pagination');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $orderCoupon = $this->orderCouponListing($storeId, $startDate, $endDate, $couponCode, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/pagination');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                $orderCoupon = $this->orderCouponListing($storeId, $dateFrom, $dateTo, $couponCode, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderCoupon', 'storeId'));
                                $this->render('/Elements/storeReports/coupon/pagination');
                            }
                        }
                    }
                    else if($reportType == 5) 
                    {
                        // Report For Promotions
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $orderPromo= $this->orderPromoListing($storeId, $startDate, $endDate, $promoId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/pagination');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                $orderPromo = $this->orderPromoWeeklyListing($storeId, $startFrom, $endFrom, $orderType, $promoId, $page, $sort, $sort_direction);
                                $this->set(compact('orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/pagination');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $orderPromo  = $this->orderPromoListing($storeId, $dateFrom, $dateTo, $promoId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/pagination');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $orderPromo = $this->orderPromoListing($storeId, $dateFrom, $dateTo, $promoId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/pagination');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 days"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $orderPromo = $this->orderPromoListing($storeId, $startDate, $endDate, $promoId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/pagination');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $orderPromo = $this->orderPromoListing($storeId, $dateFrom, $dateTo, $promoId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderPromo', 'storeId'));
                                $this->render('/Elements/storeReports/promo/pagination');
                            }
                        }
                    }
                    else if($reportType == 6) 
                    {
                        // Report For Extended Offers
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) {//Daily
                                
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $orderExtendedOffer= $this->orderExtendedOfferListing($storeId, $startDate, $endDate, $extendedOfferId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/pagination');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                
                                $orderExtendedOffer = $this->orderExtendedOfferWeeklyListing($storeId, $startFrom, $endFrom, $orderType, $extendedOfferId, $page, $sort, $sort_direction);
                                $this->set(compact('orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/pagination');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $orderExtendedOffer  = $this->orderExtendedOfferListing($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/pagination');
                            } else if($type == 4) {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $orderExtendedOffer = $this->orderExtendedOfferListing($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/pagination');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 day"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                $orderExtendedOffer = $this->orderExtendedOfferListing($storeId, $startDate, $endDate, $extendedOfferId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/pagination');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                
                                $orderExtendedOffer = $this->orderExtendedOfferListing($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType, $page, $sort, $sort_direction);
                                $this->set(compact('orderExtendedOffer', 'storeId'));
                                $this->render('/Elements/storeReports/extended_promo/pagination');
                            }
                        }
                    }
                    else if($reportType == 7) 
                    {
                        // Report For Dine In
                        if(isset($type) && $merchantOption == 0)
                        {
                            if ($type == 1) 
                            {//Daily
                                $startDate = date("Y-m-d", strtotime($startDate));
                                $endDate = date("Y-m-d", strtotime($endDate));
                                
                                $dineInData= $this->dineInListing($storeId, $startDate, $endDate, $page, $sort, $sort_direction);
                                $this->set(compact('dineInData', 'storeId'));
                                $this->render('/Elements/storeReports/dine_in/pagination');
                            }
                            else if($type == 2) 
                            {
                                if($fromMonth == 1)
                                {
                                    $day = $this->Common->getStartAndEndDate(1,$fromYear);
                                } else {
                                    $day = '01';
                                }
                                $endYear = $fromYear;
                                $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                                $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                                $weekyear = $fromYear;
                                $dineInData = $this->dineInWeeklyListing($storeId, $startFrom, $endFrom, $page, $sort, $sort_direction);
                                $this->set(compact('dineInData', 'storeId'));
                                $this->render('/Elements/storeReports/dine_in/pagination');
                            }
                            else if($type == 3) 
                            {//Monthly
                                $year = $fromYear;
                                $month = $fromMonth;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                                
                                $dineInData  = $this->dineInListing($storeId, $dateFrom, $dateTo, $page, $sort, $sort_direction);
                                $this->set(compact('dineInData', 'storeId'));
                                $this->render('/Elements/storeReports/dine_in/pagination');
                            }
                            else if($type == 4) 
                            {//Yearly
                                $yearFrom = $fromYear;
                                $yearTo = $toYear;
                                $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                                $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                                
                                $dineInData = $this->dineInListing($storeId, $dateFrom, $dateTo, $page, $sort, $sort_direction);
                                $this->set(compact('dineInData', 'storeId'));
                                $this->render('/Elements/storeReports/dine_in/pagination');
                            }
                        }
                        else if(isset($merchantOption))
                        {
                            if ($merchantOption == 1) {
                                $today = date('Y-m-d');
                                $startDate = $today;
                                $endDate = $today;
                            } else if($merchantOption == 2) {
                                $yesterday = date('Y-m-d', strtotime("-1 day"));
                                $startDate = $yesterday;
                                $endDate = $yesterday;
                            } else if($merchantOption == 3) {
                                $startDate = date('Y-m-d', strtotime('last sunday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 4) {
                                $startDate = date('Y-m-d', strtotime('last monday'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 5) {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 6) {
                                $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                                $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                            } else if($merchantOption == 7) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week sunday'));
                            } else if($merchantOption == 8) {
                                $startDate = date('Y-m-d', strtotime('last week monday'));
                                $endDate = date('Y-m-d', strtotime('last week friday'));
                            } else if($merchantOption == 9) {
                                $startDate = date('Y-m-d', strtotime('-13 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 10) {
                                $startDate = date('Y-m-01');
                                $endDate = date("Y-m-t");
                            } else if($merchantOption == 11) {
                                $startDate = date('Y-m-d', strtotime('-29 days'));
                                $endDate = date('Y-m-d');
                            } else if($merchantOption == 12) {
                                $startDate = date('Y-m-d', strtotime("first day of last month"));
                                $endDate = date('Y-m-d', strtotime("last day of last month"));
                            } else if($merchantOption == 13) {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $startDate = $yearFrom . '-' . '01' . '-01';
                                $endDate = $yearTo . '-' . '12' . '-31';

                            } else {
                                $startDate = date('Y-m-d', strtotime('-6 days'));
                                $endDate = date('Y-m-d');
                            }

                            if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                            {
                                
                                $dineInData = $this->dineInListing($storeId, $startDate, $endDate, $page, $sort, $sort_direction);
                                $this->set(compact('dineInData', 'storeId'));
                                $this->render('/Elements/storeReports/dine_in/pagination');
                            }

                            if($merchantOption == 13)
                            {
                                $yearFrom = date('Y',strtotime('-5 Years'));
                                $yearTo = date('Y');
                                $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                                $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                                $dineInData = $this->dineInListing($storeId, $dateFrom, $dateTo, $page, $sort, $sort_direction);
                                $this->set(compact('dineInData', 'storeId'));
                                $this->render('/Elements/storeReports/dine_in/pagination');
                            }
                        }
                    }
                }
            }
        }
        Configure::write('Config.timezone', $defaultTimeZone);
    }

    /*     * ***********************
     * Function name:itemListing()
      Description:graph item list
      created:06/10/2015
     *
     * ********************* */

    public function itemListings($storeID = null, $startDate = null, $endDate = null, $orderType = null, $productCount = null) 
    {
        $this->OrderItem->bindModel(array('belongsTo' => array('Order','Item')));
        if ($startDate && $endDate) {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        } else {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        if (!empty($itemId)) {
            $conditions['OrderItem.item_id'] = $itemId;
        }
        $conditions = array_merge(array($conditions), array('OrderItem.store_id' => $storeID));
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $limitProduct = ($productCount != 'All') ? $productCount : '';
        $orderdetail = $this->OrderItem->find('all', array('fields' => array('Item.name', 'Count(OrderItem.created) AS number', 'sum(OrderItem.total_item_price) AS total_amount'), 'group' => array("Item.id"), 'conditions' => array($conditions), 'order' => array('total_amount' => 'DESC'), 'limit' => $limitProduct));
        return $orderdetail;
    }
    
    
    /*     * ***********************
     * Function name:itemListingsWeekly()
      Description:graph item list
      created:09/14/2017
     *
     * ********************* */

    public function itemListingsWeekly($storeID = null, $start = null, $end = null, $orderType = null, $endYear = null, $productCount = null) 
    {
        $this->OrderItem->bindModel(array('belongsTo' => array('Order','Item')));
        
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        
        if (!empty($start) && !empty($end)) 
        {
            $stratdate = $this->Dateform->formatDate($start);
            $enddate = $this->Dateform->formatDate($end);
            
            $weekQuery = "WEEK(Order.created) >= WEEK('" . $stratdate . "') AND WEEK(Order.created) <= WEEK('" . $enddate . "') AND YEAR(Order.created) = YEAR('" . $enddate . "')";
        } else {
            $weekQuery = '';
        }
        $conditions = array_merge(array($conditions), array($weekQuery) , array('OrderItem.store_id' => $storeID));
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $limitProduct = ($productCount != 'All') ? $productCount : '';
        $orderdetail = $this->OrderItem->find('all', array('fields' => array('Item.name', 'Count(OrderItem.created) AS number', 'sum(OrderItem.total_item_price) AS total_amount'), 'group' => array("Item.id"), 'conditions' => array($conditions), 'order' => array('total_amount' => 'DESC'), 'limit' => $limitProduct));
        return $orderdetail;
    }
    
    /*************************
     *Function name:orderProductListing()
      Description:graph order product list
      created:22/09/2015
     *
     * ********************* */

    public function orderProductListing($storeID, $startDate = null, $endDate = null, $orderType = null, $productCount = null, $page = 1, $sort = null, $sort_direction = null) 
    {
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                )), false);
        
        $this->OrderItem->Item->bindModel(
                array(
                'belongsTo' => array(
                    'Category' => array(
                        'className' => 'Category',
                        'foreignKey' => 'category_id',
                        'fields' => array('id', 'name')
                    )
                )
            ), false
        );
        
        if ($startDate && $endDate) {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        } else {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        if (!empty($item)) {
            $conditions['OrderItem.item_id'] = $item;
        }
       
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $conditions = array_merge(array($conditions), array('OrderItem.store_id' => $storeID));
        if (empty($sort)){
            $sort = 'total_amount';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $limitProduct = ($productCount != 'All') ? $productCount : '';
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'Item.name', 'Item.category_id', 'sum(OrderItem.quantity) AS number', 'sum(OrderItem.total_item_price) AS total_amount', '(OrderItem.total_item_price / OrderItem.quantity) as unit_price', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => array($conditions), 
                    'order'         => array($sort => $sort_direction), 
                    'group'         => array('Item.id'),
                    'limit'         => $limitProduct
           
           ));
        return $orderdetail;
    }
    
    /*************************
     *Function name:orderProductListingWeekly()
      Description:order product listing weekly
      created:15/09/2017
     *
     * ********************* */

    public function orderProductListingWeekly($storeID, $startDate = null, $endDate = null, $orderType = null, $productCount = null, $page = 1, $sort = null, $sort_direction = null) 
    {
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
              )), false);
        
        $this->OrderItem->Item->bindModel(
                array(
                'belongsTo' => array(
                    'Category' => array(
                        'className' => 'Category',
                        'foreignKey' => 'category_id',
                        'fields' => array('id', 'name')
                    )
                )
            ), false
        );
        
         $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        $conditions['Order.store_id']       = $storeID;
        $criteria = '';
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= "WEEK(Order.created) >= WEEK('" . $stratdate . "') AND WEEK(Order.created) <= WEEK('" . $enddate . "') AND YEAR(Order.created) = YEAR('" . $enddate . "')";
        }
        $conditions = array_merge($conditions, array($criteria));
        
       
        if (!empty($orderType) && $orderType != 1) 
        {
            $conditions['Order.seqment_id']     = $orderType;
        }
        else 
        {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (empty($sort)){
            $sort = 'total_amount';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        $limitProduct = ($productCount != 'All') ? $productCount : '';
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'Item.name', 'Item.category_id', 'sum(OrderItem.quantity) AS number', 'sum(OrderItem.total_item_price) AS total_amount', '(OrderItem.total_item_price / OrderItem.quantity) as unit_price', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => array($conditions), 
                    'order'         => array($sort => $sort_direction), 
                    'group'         => array('Item.id'),
                    'limit'         => $limitProduct
           
           ));
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:userListing()
      Description:graph user list
      created:05/10/2015
     *
     * ********************* */

    public function userListing($storeID = null, $startDate = null, $endDate = null, $page = 1, $sort = null, $sort_direction = null) 
    {
        $role_id=4;
        $criteria = "User.is_deleted=0 AND User.is_active=1";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND (User.created BETWEEN '" . $startDate . "' AND '" . $endDate . "')";
        }
        $this->User->bindModel(
            array(
                'belongsTo' => array(
                    'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                )
            ), false
        );
        
        if (empty($sort)){
            $sort = 'User.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        
        $this->paginate = array(
                'recursive'     => 2, 
                'conditions'    => array_merge(array($criteria), array('User.role_id' => $role_id, 'User.store_id' => $storeID)),
                'page'          => $page,
                'limit'         => $this->pageLimit,
                'order'         => array($sort => $sort_direction)
            );
        $userdetail = $this->paginate('User');
        return $userdetail;
    }
    
    
    public function fetchWeeklyUserToday($storeId = null, $start = null, $end = null,$endYear=null) 
    {
        $conditions = " User.is_active=1 AND User.is_deleted=0 AND WEEK(User.created) >= WEEK('" . $start . "') AND WEEK(User.created) <= WEEK('" . $end . "') AND YEAR(User.created) = YEAR('" . $end . "') AND User.role_id = 4 AND User.store_id = '" . $storeId . "'";

        $result = $this->User->find('all', array('group' => array('User.created'), 'fields' => array('User.id', 'WEEK(User.created) AS WEEKno', 'DATE(User.created) AS order_date', 'COUNT(User.id) as total'), 'conditions' => $conditions));
        return $result;
    }
    
    /*     * ***********************
     * Function name:userListingweekly()
      Description:graph user list
      created:05/10/2015
     *
     * ********************* */

    public function userListingweekly($storeID = null, $startDate = null, $endDate = null, $endYear=null, $page = 1, $sort = null, $sort_direction = null)
    {
        $criteria = "User.is_deleted=0 AND User.is_active=1 AND User.role_id = 4";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $stratdate = date('Y-m-d 00:00:00', strtotime($stratdate));
            $enddate = date('Y-m-d 23:59:59', strtotime($enddate));
            $criteria.= " AND WEEK(User.created) >= WEEK('" . $stratdate . "') AND WEEK(User.created) <= WEEK('" . $enddate . "') AND YEAR(User.created) = YEAR('" . $enddate . "')";
        }
        $this->User->bindModel(
            array(
                'belongsTo' => array(
                    'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                )
            ), false
        );
        
        if (empty($sort)){
            $sort = 'User.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        $this->paginate = array(
                'recursive'     => 2, 
                'conditions'    => array_merge(array($criteria), array('User.store_id' => $storeID)),
                'page'          => $page,
                'limit'         => $this->pageLimit,
                'order'         => array($sort => $sort_direction)
            );
        $userdetail = $this->paginate('User');
        return $userdetail;
    }
    
    /*     * ***********************
     * Function name:couponListings()
      Description:graph coupon list
      created:01/09/2017
     *
     * ********************* */

    public function couponListings($storeID = null, $startDate = null, $endDate = null, $couponCode = null, $orderType = null) 
    {
        if ($startDate && $endDate) {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        } else {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        if (!empty($couponCode)) {
            $conditions['Order.coupon_code'] = $couponCode;
        }
        $conditions = array_merge(array($conditions), array('Order.store_id' => $storeID, 'Order.coupon_code != ' => ''));
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $orderdetail = $this->Order->find('all', array('fields' => array('DATE(Order.created) AS order_date', 'Order.coupon_code'), 'conditions' => array($conditions), 'order' => array('Order.created' => 'DESC')));
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:getOrderCouponList()
      Description:order coupon list
      created:01/09/2017
     *
     * ********************* */
    public function getOrderCouponList(){
        
        $this->layout = false;
        $this->autoRender = false;
        $coupon = array();
        if ($this->request->is(array('ajax'))) 
        {    
            $storeId    = $this->Session->read('admin_store_id');
            $coupon     = $this->Order->find('all', array('fields' => array('Order.coupon_code as coupon'), 'group' => array('Order.coupon_code'), 'conditions' => array('Order.store_id' => $storeId, 'Order.coupon_code != ' => ''), 'order' => array('Order.created' => 'DESC')));
        }
        $coupon = json_encode($coupon);
        return $coupon;
    }
    
    /*************************
     *Function name:orderCouponListing()
      Description: graph order coupon list
      created:22/09/2015
     *
     * ********************* */

    public function orderCouponListing($storeID, $startDate = null, $endDate = null, $couponCode = null, $orderType = null, $page = 1, $sort = null, $sort_direction = null) {
        $this->Order->bindModel(
                array(
            'belongsTo' => array(
                'User' => array(
                    'className' => 'User',
                    'foreignKey' => 'user_id',
                    'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                ),
                'OrderStatus' => array(
                    'className' => 'OrderStatus',
                    'foreignKey' => 'order_status_id',
                    'fields' => array('id', 'name')
                ),
                'Segment' => array(
                    'className' => 'Segment',
                    'foreignKey' => 'seqment_id',
                    'fields' => array('id', 'name')
                ),
                'DeliveryAddress' => array(
                    'className' => 'DeliveryAddress',
                    'foreignKey' => 'delivery_address_id',
                    'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                )
            )
                ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        if ($startDate && $endDate) {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        } else {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        if (!empty($couponCode)) {
            $conditions['Order.coupon_code'] = $couponCode;
        }
       
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $conditions = array_merge(array($conditions), array('OrderItem.store_id' => $storeID, 'Order.coupon_code !=' => ''));
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        $this->paginate = array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => array($conditions), 
                    'order'         => array($sort => $sort_direction), 
                    'group'         => array('OrderItem.order_id'),
                    'page'          => $page,
                    'limit'         => $this->pageLimit
           
           );
        $orderdetail = $this->paginate('OrderItem');
        return $orderdetail;
    }
    
    public function getWeeklyCouponListing($storeId = null, $start = null, $end = null, $orderType = null, $couponCode=null) {
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id'
                        )
                    )
                )
        );
        $conditions = "Order.is_future_order=0 AND Order.is_active=1 AND Order.is_deleted=0 AND WEEK(Order.created) >= WEEK('" . $start . "') AND WEEK(Order.created) <= WEEK('" . $end . "') AND YEAR(Order.created) = YEAR('" . $end . "')";
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions .=" AND Segment.id= '" . $orderType . "'";
        } else {
            $conditions .=" AND Segment.id IN (2,3)";
        }
        
        if (!empty($couponCode)) 
        {
            $conditions .=" AND Order.coupon_code = '" . $couponCode . "'";
        }
        
        $result = $this->Order->find('all', array('fields' => array('WEEK(Order.created) AS WEEKno', 'DATE(Order.created) AS order_date', 'Order.coupon_code'), 'conditions' => array_merge(array($conditions), array('Order.store_id' => $storeId, 'Order.coupon_code != ' => ''))));
        return $result;
    }
    
    /*     * ***********************
     * Function name:orderCouponWeeklyListing()
      Description:graph order list
      created:22/09/2015
     *
     * ********************* */

    public function orderCouponWeeklyListing($storeID = null, $startDate = null, $endDate = null, $orderType = null, $couponCode = null, $page = 1, $sort = '', $sort_direction = '') 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <= WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $endDate . "')";
        }
        
        $this->Order->bindModel(
                array(
            'belongsTo' => array(
                'User' => array(
                    'className' => 'User',
                    'foreignKey' => 'user_id',
                    'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                ),
                'OrderStatus' => array(
                    'className' => 'OrderStatus',
                    'foreignKey' => 'order_status_id',
                    'fields' => array('id', 'name')
                ),
                'Segment' => array(
                    'className' => 'Segment',
                    'foreignKey' => 'seqment_id',
                    'fields' => array('id', 'name')
                ),
                'DeliveryAddress' => array(
                    'className' => 'DeliveryAddress',
                    'foreignKey' => 'delivery_address_id',
                    'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                )
            )
                ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
            array('belongsTo' => array(
                'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                ),
                'hasMany' => array(
                    'OrderItem' => array(
                        'className' => 'OrderItem',
                        'foreignKey' => 'order_id',
                        'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                    ),
                )
            ), false
        );
        
        if (!empty($orderType) && $orderType != 1) {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        } else {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        
        if (!empty($couponCode)) 
        {
            $criteria .=" AND Order.coupon_code = '" . $couponCode . "'";
        }
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $this->paginate = array(
            'recursive'     => 3,
            'conditions'    => array_merge(array($criteria), array('Order.store_id' => $storeID, 'Order.coupon_code != ' => '')),
            'order'         => array($sort => $sort_direction),
            'group'         => array('OrderItem.order_id'),
            'page'          => $page,
            'limit'         => $this->pageLimit
        );
        $orderdetail = $this->paginate('OrderItem');
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:promoListings()
      Description:graph promo list
      created:01/09/2017
     *
     * ********************* */

    public function promoListings($storeID = null, $startDate = null, $endDate = null, $promoId = null, $orderType = null) 
    {
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if ($startDate && $endDate) {
            $conditions['DATE(Order.created) >='] = $startDate;
            $conditions['DATE(Order.created) <='] = $endDate;
        }
        if (!empty($promoId)) {
            $conditions['OrderOffer.offer_id'] = $promoId;
        }
        $conditions = array_merge(array($conditions), array('Order.store_id' => $storeID));
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
        $orderdetail = $this->OrderOffer->find('all', array('fields' => array('DATE(Order.created) AS order_date', 'OrderOffer.quantity'), 'group' => array('OrderOffer.order_id','OrderOffer.offer_id'), 'conditions' => array($conditions), 'order' => array('Order.created' => 'DESC')));
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:getOrderPromoList()
      Description:order promo list
      created:01/09/2017
     *
     * ********************* */
    public function getOrderPromoList(){
        
        $this->layout = false;
        $this->autoRender = false;
        $offer = array();
        if ($this->request->is(array('ajax'))) 
        {
            $storeId    = $this->Session->read('admin_store_id');
            $this->OrderOffer->bindModel(array('belongsTo' => array('Offer')));
            $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
            $offer = $this->OrderOffer->find('all', array('fields' => array('Offer.id', 'Offer.description'), 'group' => array('OrderOffer.offer_id'), 'conditions' => array('Order.store_id' => $storeId), 'order' => array('Order.created' => 'DESC')));
            
        }
        $coupon = json_encode($offer);
        return $coupon;
    }
    
    /*************************
     *Function name:orderPromoListing()
      Description: graph order promo list
      created:22/09/2015
     *
     * ********************* */

    public function orderPromoListing($storeID, $startDate = null, $endDate = null, $promoId = null, $orderType = null, $page = 1, $sort = null, $sort_direction = null) {
        
        if ($startDate && $endDate) {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        } else {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (!empty($promoId) && $promoId != 0) {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID, 'OrderOffer.offer_id' => $promoId));
        } else {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderOffer->find('all', array('fields' => array('Order.id', 'OrderOffer.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
            ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        
        $conditions = array_merge($conditions, array('OrderItem.store_id' => $storeID));
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        $this->paginate = array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array($sort => $sort_direction), 
                    'group'         => array('OrderItem.order_id'),
                    'page'          => $page,
                    'limit'         => $this->pageLimit
           
           );
        $orderdetail = $this->paginate('OrderItem');
        return $orderdetail;
    }
    
    /*************************
     *Function name:getWeeklyPromoListing()
      Description: graph order promo list
      created:22/09/2015
     *
     * ********************* */
    
    public function getWeeklyPromoListing($storeId = null, $start = null, $end = null, $orderType = null, $promoId=null) {
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if (!empty($promoId)) {
            $conditions['OrderOffer.offer_id'] = $promoId;
        }
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        $weekconditions = '';
        if ($start && $end) {
            $weekconditions = "WEEK(Order.created) >= WEEK('" . $start . "') AND WEEK(Order.created) <= WEEK('" . $end . "') AND YEAR(Order.created) = YEAR('" . $end . "')";
        }
        $conditions = array_merge(array($conditions), array($weekconditions), array('Order.store_id' => $storeId));
        
        $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
        $result = $this->OrderOffer->find('all', array('fields' => array('WEEK(Order.created) AS WEEKno', 'DATE(Order.created) AS order_date', 'OrderOffer.quantity'), 'conditions' => array_merge(array($conditions), array('Order.store_id' => $storeId)), 'group' => array('OrderOffer.order_id', 'OrderOffer.offer_id')));
        return $result;
    }
    
    /*     * ***********************
     * Function name:orderPromoWeeklyListing()
      Description:graph order promo list
      created:22/09/2015
     *
     * ********************* */

    public function orderPromoWeeklyListing($storeID = null, $startDate = null, $endDate = null, $orderType = null, $promoId = null, $page = 1, $sort = '', $sort_direction = '') 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <= WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $endDate . "')";
        }
        
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        
        if (!empty($promoId) && $promoId != 0) {
            $offer_conditions = array_merge(array($criteria), $conditions, array('Order.store_id' => $storeID, 'OrderOffer.offer_id' => $promoId));
        } else {
            $offer_conditions = array_merge(array($criteria), $conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderOffer->find('all', array('fields' => array('Order.id', 'OrderOffer.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        
        $this->Order->bindModel(
                array(
            'belongsTo' => array(
                'User' => array(
                    'className' => 'User',
                    'foreignKey' => 'user_id',
                    'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                ),
                'OrderStatus' => array(
                    'className' => 'OrderStatus',
                    'foreignKey' => 'order_status_id',
                    'fields' => array('id', 'name')
                ),
                'Segment' => array(
                    'className' => 'Segment',
                    'foreignKey' => 'seqment_id',
                    'fields' => array('id', 'name')
                ),
                'DeliveryAddress' => array(
                    'className' => 'DeliveryAddress',
                    'foreignKey' => 'delivery_address_id',
                    'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                )
            )
                ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
            array('belongsTo' => array(
                'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                ),
                'hasMany' => array(
                    'OrderItem' => array(
                        'className' => 'OrderItem',
                        'foreignKey' => 'order_id',
                        'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                    ),
                )
            ), false
        );
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $this->paginate = array(
            'recursive'     => 3,
            'conditions'    => array_merge(array($criteria), $conditions),
            'order'         => array($sort => $sort_direction),
            'group'         => array('OrderItem.order_id'),
            'page'          => $page,
            'limit'         => $this->pageLimit
        );
        $orderdetail = $this->paginate('OrderItem');
        return $orderdetail;
    }
    
    
    /*     * ***********************
     * Function name:extendedOfferListings()
      Description:graph extended offer list
      created:04/09/2017
     *
     * ********************* */

    public function extendedOfferListings($storeID = null, $startDate = null, $endDate = null, $extendedOfferId = null, $orderType = null) 
    {
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if ($startDate && $endDate) {
            $conditions['DATE(Order.created) >='] = $startDate;
            $conditions['DATE(Order.created) <='] = $endDate;
        }
        if (!empty($extendedOfferId)) {
            $conditions['OrderItemFree.item_id'] = $extendedOfferId;
        }
        $conditions = array_merge(array($conditions), array('Order.store_id' => $storeID));
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $this->OrderItemFree->bindModel(array('belongsTo' => array('Order')));
        $orderdetail = $this->OrderItemFree->find('all', array('fields' => array('DATE(Order.created) AS order_date', 'OrderItemFree.free_quantity'), 'conditions' => array($conditions), 'order' => array('Order.created' => 'DESC')));
        return $orderdetail;
    }
    
    
    /*     * ***********************
     * Function name:getOrderExtendedOfferList()
      Description:order extended offer list
      created:04/09/2017
     *
     * ********************* */
    public function getOrderExtendedOfferList(){
        
        $this->layout = false;
        $this->autoRender = false;
        $offer = array();
        if ($this->request->is(array('ajax'))) 
        {    
            $storeId    = $this->Session->read('admin_store_id');
            $this->OrderItemFree->bindModel(array('belongsTo' => array('Order','Item')));
            $offer = $this->OrderItemFree->find('all', array('fields' => array('Item.id', 'Item.name'), 'group' => array('OrderItemFree.item_id'), 'conditions' => array('Order.store_id' => $storeId), 'order' => array('Order.created' => 'DESC')));
        }
        $coupon = json_encode($offer);
        return $coupon;
    }
    
    
    /*************************
     *Function name:orderExtendedOfferListing()
      Description: graph order extended offer list
      created:04/09/2017
     *
     * ********************* */

    public function orderExtendedOfferListing($storeID, $startDate = null, $endDate = null, $extendedOfferId = null, $orderType = null, $page = 1, $sort = null, $sort_direction = null) {
        
        if ($startDate && $endDate) {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        } else {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (!empty($extendedOfferId) && $extendedOfferId != 0) {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID, 'OrderItemFree.item_id' => $extendedOfferId));
        } else {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderItemFree->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderItemFree->find('all', array('fields' => array('Order.id', 'OrderItemFree.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
            ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        
        $conditions = array_merge($conditions, array('OrderItem.store_id' => $storeID));
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        $this->paginate = array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array($sort => $sort_direction), 
                    'group'         => array('OrderItem.order_id'),
                    'page'          => $page,
                    'limit'         => $this->pageLimit
           
           );
        $orderdetail = $this->paginate('OrderItem');
        return $orderdetail;
    }
    
    /*************************
     *Function name:getWeeklyExtendedOfferListing()
      Description: weekly graph order extended offer list
      created:04/09/2017
     *
     * ********************* */
    
    public function getWeeklyExtendedOfferListing($storeId = null, $start = null, $end = null, $orderType = null, $extendedOfferId=null) {
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if (!empty($extendedOfferId)) {
            $conditions['OrderItemFree.item_id'] = $extendedOfferId;
        }
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        $weekconditions = '';
        if ($start && $end) {
            $weekconditions = "WEEK(Order.created) >= WEEK('" . $start . "') AND WEEK(Order.created) <= WEEK('" . $end . "') AND YEAR(Order.created) = YEAR('" . $end . "')";
        }
        $conditions = array_merge(array($conditions), array($weekconditions), array('Order.store_id' => $storeId));
        
        $this->OrderItemFree->bindModel(array('belongsTo' => array('Order')));
        $result = $this->OrderItemFree->find('all', array('fields' => array('WEEK(Order.created) AS WEEKno', 'DATE(Order.created) AS order_date', 'OrderItemFree.free_quantity'), 'conditions' => array_merge(array($conditions), array('Order.store_id' => $storeId))));
        return $result;
    }
    
    /*     * ***********************
     * Function name:orderExtendedOfferWeeklyListing()
      Description:weekly graph order extended promo list
      created:04/09/2017
     *
     * ********************* */

    public function orderExtendedOfferWeeklyListing($storeID = null, $startDate = null, $endDate = null, $orderType = null, $extendedOfferId = null, $page = 1, $sort = '', $sort_direction = '') 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >=WEEK('" . $startDate . "') AND WEEK(Order.created) <=WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $endDate . "')";
        }
        
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        
        if (!empty($extendedOfferId) && $extendedOfferId != 0) {
            $offer_conditions = array_merge(array($criteria), $conditions, array('Order.store_id' => $storeID, 'OrderItemFree.item_id' => $extendedOfferId));
        } else {
            $offer_conditions = array_merge(array($criteria), $conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderItemFree->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderItemFree->find('all', array('fields' => array('Order.id', 'OrderItemFree.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        
        $this->Order->bindModel(
                array(
            'belongsTo' => array(
                'User' => array(
                    'className' => 'User',
                    'foreignKey' => 'user_id',
                    'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                ),
                'OrderStatus' => array(
                    'className' => 'OrderStatus',
                    'foreignKey' => 'order_status_id',
                    'fields' => array('id', 'name')
                ),
                'Segment' => array(
                    'className' => 'Segment',
                    'foreignKey' => 'seqment_id',
                    'fields' => array('id', 'name')
                ),
                'DeliveryAddress' => array(
                    'className' => 'DeliveryAddress',
                    'foreignKey' => 'delivery_address_id',
                    'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                )
            )
                ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
            array('belongsTo' => array(
                'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                ),
                'hasMany' => array(
                    'OrderItem' => array(
                        'className' => 'OrderItem',
                        'foreignKey' => 'order_id',
                        'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                    ),
                )
            ), false
        );
        
        if (empty($sort)){
            $sort = 'Order.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $this->paginate = array(
            'recursive'     => 3,
            'conditions'    => array_merge(array($criteria), $conditions),
            'order'         => array($sort => $sort_direction),
            'group'         => array('OrderItem.order_id'),
            'page'          => $page,
            'limit'         => $this->pageLimit
        );
        $orderdetail = $this->paginate('OrderItem');
        return $orderdetail;
    }
    
    
    /*     * ***********************
     * Function name:reportDownload()
      Description: report download
      created:07/09/2017
     *
     * ********************* */
    public function reportDownload() 
    {
        $defaultTimeZone = date_default_timezone_get();
        $dataRequest    = $this->Session->read('storeReportRequest');
        $storeId        = $this->Session->read('admin_store_id');
        
        $reportType         = (isset($dataRequest['reportType']) ? $dataRequest['reportType'] : 1);
        $type               = (isset($dataRequest['type']) ? $dataRequest['type'] : 1);
        $orderType          = (isset($dataRequest['orderType']) ? $dataRequest['orderType'] : 1);
        $startDate          = (isset($dataRequest['startDate']) ? $this->Dateform->formatDate($dataRequest['startDate']) : $sdate);
        $endDate            = (isset($dataRequest['endDate']) ? $this->Dateform->formatDate($dataRequest['endDate']) : $edate);
        $fromMonth          = (isset($dataRequest['fromMonth']) ? $dataRequest['fromMonth'] : null);
        $fromYear           = (isset($dataRequest['fromYear']) ? $dataRequest['fromYear'] : null);
        $toMonth            = (isset($dataRequest['toMonth']) ? $dataRequest['toMonth'] : null);
        $toYear             = (isset($dataRequest['toYear']) ? $dataRequest['toYear'] : null);
        $itemId             = (isset($dataRequest['itemId']) ? $dataRequest['itemId'] : null);
        $merchantOption     = (isset($dataRequest['merchantOption']) ? $dataRequest['merchantOption'] : null);
        $couponCode         = (isset($dataRequest['coupon_code']) ? $dataRequest['coupon_code'] : null);       
        $promoId            = (isset($dataRequest['promo_id']) ? $dataRequest['promo_id'] : null);
        $extendedOfferId    = (isset($dataRequest['extended_offer_id']) ? $dataRequest['extended_offer_id'] : null);
        $productCount    = (isset($dataRequest['product_count']) ? $dataRequest['product_count'] : null);
        
        if (!empty($storeId) && ($storeId !== 'All')) {
            $storeDate      = $this->Common->getcurrentTime($storeId, 1);
            $storeDateTime  = explode(" ", $storeDate);
            $storeDate      = $storeDateTime[0];
            $storeTime      = $storeDateTime[1];
            $this->set('storeTime', $storeTime);
            $sdate          = $storeDate . " " . "00:00:00";
            $edate          = $storeDate . " " . "23:59:59";
            $startdate      = $storeDate;
            $enddate        = $storeDate;
            $expoladDate    = explode("-", $startdate);
            $fromMonthDefault   = $expoladDate[1];
            $fromYearDefault    = $expoladDate[0];
            $toMonthDefault     = $expoladDate[1];
            $toYearDefault      = $expoladDate[0];

            $timezoneStore  = array();
            $store_data = $this->Store->fetchStoreDetail($storeId);
            if(!empty($store_data))
            {
                $this->loadModel('TimeZone');
                $timezoneStore = $this->TimeZone->find('all', array('fields' => array('TimeZone.code'), 'conditions' => array('TimeZone.id' => $store_data['Store']['time_zone_id'])));
            }

            if(isset($timezoneStore['TimeZone']['code']) && $timezoneStore['TimeZone']['code'] != '')
            {
                Configure::write('Config.timezone', $timezoneStore['TimeZone']['code']);
            } else {
                Configure::write('Config.timezone', $defaultTimeZone);
            }
        } else {
            $sdate      = null;
            $edate      = null;
            $startdate  = null;
            $enddate    = null;
            $fromMonthDefault   = null;
            $fromYearDefault    = null;
            $toMonthDefault     = null;
            $toYearDefault      = null;
        }

        

        if(empty($startDate)){
            $startDate  = date('Y-m-d', strtotime('-6 day'));
        }
        if(empty($endDate)){
            $endDate    = date("Y-m-d");
        }
        
        // Download File Name According To Options
        $reportArray    = array(1 => 'Order Type', 2 => 'Product', 3 => 'Customer', 4 => 'Coupon', 5 => 'Promo', 6 => 'Extended Offers', 7 => 'DineIn');
        $typeArray      = array(1 => 'Day', 2 => 'Week', 3 => 'Month', 4 => 'Year');
        $customArray    = array(0 => 'Custom', 1 => 'Today', 2 => 'Yesterday', 3 => 'This week(Sun-Today)', 4 => 'This week(Mon-Today)', 5 => 'Last 7 days', 6 => 'Last week(Sun-Sat)', 7 => 'Last week(Mon-Sun)', 8 => 'Last business week(Mon-Fri)', 9 => 'Last 14 days', 10 => 'This month', 11 => 'Last 30 days', 12 => 'Last month', 13 => 'All time');
        $orderTypeArray= array(1 => 'Both', 2 => 'Pick Up', 3 => 'Delivery');
        $customerTypeArray= array(1 => 'Both', 4 => 'Store', 5 => 'Merchant');
        
        $text = '';
        if(isset($reportType) && $reportType != 0)
        {
            $text .= $reportArray[$reportType] . '_';
        }
        if(isset($type) && $merchantOption == 0)
        {
            $text .= $typeArray[$type] . '_';
        }
        if(isset($merchantOption) && ($merchantOption > 0))
        {
            $text .= $customArray[$merchantOption] . '_';
        }
        if(isset($orderType) && ($orderType > 0))
        {
            $text .= $orderTypeArray[$orderType] . '_';
        }
        $text = trim($text, '_');
        $text .= '_Report';
        $text = preg_replace('/\s+/', '_', $text);
        // Download File Name According To Options
        
        $order = array();
        if(isset($reportType))
        {
            if($reportType == 1)
            {
                // Report For Sales
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->orderListingDownloadReport($storeId, $startdate, $enddate, $orderType);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        
                        $order = $this->orderListingWeekDownloadReport($storeId, $startFrom, $endFrom, $orderType);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        $order = $this->orderListingDownloadReport($storeId, $dateFrom, $dateTo, $orderType);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->orderListingDownloadReport($storeId, $dateFrom, $dateTo, $orderType);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        $order = $this->orderListingDownloadReport($storeId, $startDate, $endDate, $orderType);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        $order = $this->orderListingDownloadReport($storeId, $dateFrom, $dateTo, $orderType);
                    }
                }
            }
            else if($reportType == 2)
            {
                // Report For Product
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->orderProductListingDownloadReport($storeId, $startdate, $enddate, $orderType, $productCount);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        
                        $order = $this->orderProductListingWeekDownloadReport($storeId, $startFrom, $endFrom, $orderType, $productCount);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        $order = $this->orderProductListingDownloadReport($storeId, $dateFrom, $dateTo, $orderType, $productCount);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->orderProductListingDownloadReport($storeId, $dateFrom, $dateTo, $orderType, $productCount);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        $order = $this->orderProductListingDownloadReport($storeId, $startDate, $endDate, $orderType, $productCount);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        $order = $this->orderProductListingDownloadReport($storeId, $dateFrom, $dateTo, $orderType, $productCount);
                    }
                }
            }
            else if($reportType == 3)
            {
                // Report For Customer
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->customerListingDownloadReport($storeId, $startdate, $enddate);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        
                        $order = $this->customerWeekListingDownloadReport($storeId, $startFrom, $endFrom, $weekyear);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        $order = $this->customerListingDownloadReport($storeId, $dateFrom, $dateTo);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->customerListingDownloadReport($storeId, $dateFrom, $dateTo);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        $order = $this->customerListingDownloadReport($storeId, $startDate, $endDate);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        $order = $this->customerListingDownloadReport($storeId, $dateFrom, $dateTo);
                    }
                }
            }
            else if($reportType == 4)
            {
                // Report For Coupon
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->couponListingDownloadReport($storeId, $startdate, $enddate, $couponCode, $orderType);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        
                        $order = $this->couponWeeklyListingDownloadReport($storeId, $startFrom, $endFrom, $orderType, $couponCode);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        $order = $this->couponListingDownloadReport($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->couponListingDownloadReport($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        $order = $this->couponListingDownloadReport($storeId, $startDate, $endDate, $couponCode, $orderType);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        $order = $this->couponListingDownloadReport($storeId, $dateFrom, $dateTo, $couponCode, $orderType);
                    }
                }
            }
            else if($reportType == 5)
            {
                // Report For Promo
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->promoListingDownloadReport($storeId, $startdate, $enddate, $promoId, $orderType);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        
                        $order = $this->promoWeeklyListingDownloadReport($storeId, $startFrom, $endFrom, $promoId, $orderType);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        $order = $this->promoListingDownloadReport($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->promoListingDownloadReport($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        $order = $this->promoListingDownloadReport($storeId, $startDate, $endDate, $promoId, $orderType);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        $order = $this->promoListingDownloadReport($storeId, $dateFrom, $dateTo, $promoId, $orderType);
                    }
                }
            }
            else if($reportType == 6)
            {
                // Report For Extended Offers
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->extendedOfferListingDownloadReport($storeId, $startdate, $enddate, $extendedOfferId, $orderType);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        
                        $order = $this->extendedOfferWeeklyListingDownloadReport($storeId, $startFrom, $endFrom, $extendedOfferId, $orderType);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        $order = $this->extendedOfferListingDownloadReport($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->extendedOfferListingDownloadReport($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        $order = $this->extendedOfferListingDownloadReport($storeId, $startDate, $endDate, $extendedOfferId, $orderType);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        $order = $this->extendedOfferListingDownloadReport($storeId, $dateFrom, $dateTo, $extendedOfferId, $orderType);
                    }
                }
            }
            else if($reportType == 7)
            {
                // Report For Dine In
                if(isset($type) && $merchantOption == 0)
                {
                    if ($type == 1) 
                    {
                        $startdate = date('Y-m-d 00:00:00', strtotime($startDate));
                        $enddate = date('Y-m-d 23:59:59', strtotime($endDate));
                        $order = $this->dineInListingDownloadReport($storeId, $startdate, $enddate);
                    } 
                    else if($type == 2)
                    {
                        if($fromMonth == 1)
                        {
                            $day = $this->Common->getStartAndEndDate(1,$fromYear);
                        } else {
                            $day = '01';
                        }
                        $endYear = $fromYear;
                        $startFrom = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-' . $day));
                        $endFrom = date('Y-m-d', strtotime($toYear . '-' . $toMonth));
                        $weekyear = $fromYear;
                        $order = $this->dineInWeeklyListingDownloadReport($storeId, $startFrom, $endFrom);
                    }
                    else if($type == 3)
                    {
                        $year = $fromYear;
                        $month = $fromMonth;
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-' . $fromMonth . '-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-' . $toMonth));
                        
                        $order = $this->dineInListingDownloadReport($storeId, $dateFrom, $dateTo);
                    }
                    else if($type == 4)
                    {
                        $dateFrom   = date('Y-m-d', strtotime($fromYear . '-01-01'));
                        $dateTo     = date('Y-m-t', strtotime($toYear . '-12'));
                        $order = $this->dineInListingDownloadReport($storeId, $dateFrom, $dateTo);
                    }
                }
                else if(isset($merchantOption))
                {
                    if ($merchantOption == 1) {
                        $today = date('Y-m-d');
                        $startDate = $today;
                        $endDate = $today;
                    } else if($merchantOption == 2) {
                        $yesterday = date('Y-m-d', strtotime("-1 days"));
                        $startDate = $yesterday;
                        $endDate = $yesterday;
                    } else if($merchantOption == 3) {
                        $startDate = date('Y-m-d', strtotime('last sunday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 4) {
                        $startDate = date('Y-m-d', strtotime('last monday'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 5) {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 6) {
                        $startDate = date('Y-m-d', strtotime('-2 week sunday'));
                        $endDate = date('Y-m-d', strtotime('-1 week saturday'));
                    } else if($merchantOption == 7) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week sunday'));
                    } else if($merchantOption == 8) {
                        $startDate = date('Y-m-d', strtotime('last week monday'));
                        $endDate = date('Y-m-d', strtotime('last week friday'));
                    } else if($merchantOption == 9) {
                        $startDate = date('Y-m-d', strtotime('-13 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 10) {
                        $startDate = date('Y-m-01');
                        $endDate = date("Y-m-t");
                    } else if($merchantOption == 11) {
                        $startDate = date('Y-m-d', strtotime('-29 days'));
                        $endDate = date('Y-m-d');
                    } else if($merchantOption == 12) {
                        $startDate = date('Y-m-d', strtotime("first day of last month"));
                        $endDate = date('Y-m-d', strtotime("last day of last month"));
                    } else if($merchantOption == 13) {
                        $yearFrom = date('Y',strtotime('-5 Years'));
                        $yearTo = date('Y');
                        $startDate = $yearFrom . '-' . '01' . '-01';
                        $endDate = $yearTo . '-' . '12' . '-31';

                    } else {
                        $startDate = date('Y-m-d', strtotime('-6 days'));
                        $endDate = date('Y-m-d');
                    }

                    if(($merchantOption >= 1 && $merchantOption <= 9)  || $merchantOption == 11 || $merchantOption == 10 || $merchantOption == 12)
                    {
                        
                        $order = $this->dineInListingDownloadReport($storeId, $startDate, $endDate);
                    }
                    if($merchantOption == 13)
                    {
                        $dateFrom = date('Y-m-d', strtotime($yearFrom . '-' . '01' . '-01'));
                        $dateTo = date('Y-m-t', strtotime($yearTo . '-' . '12'));
                        
                        $order = $this->dineInListingDownloadReport($storeId, $dateFrom, $dateTo);
                    }
                }
            }
        }
        if(!empty($order))
        {
            Configure::write('debug', 0);
            App::import('Vendor', 'PHPExcel');
            $objPHPExcel = new PHPExcel;
            $styleArray2 = array(
                'font' => array('name' => 'Arial', 'size' => '10', 'color' => array('rgb' => '444555'), 'bold' => true),
                'fill' => array('type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'D6D6D6'))
            );
            $styleArray = array(
                'font' => array(
                    'name' => 'Arial',
                    'size' => '10',
                    'color' => array('rgb' => 'ffffff'),
                    'bold' => true,
                ),
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => '0295C9'),
                ),
            );
            ini_set('max_execution_time', 600); //increase max_execution_time to 10 min if data set is very large
            $filename = $text . date("Y-m-d") . ".xls"; //create a file
            $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
            $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            
            $text = substr($text, 0, 31);
            if($reportType == 2)
            {
                $objPHPExcel->getActiveSheet()->setTitle($text);
                $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Product Name');
                $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Category');
                $objPHPExcel->getActiveSheet()->setCellValue('C1', '# of Items');
                $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Revenue ($)');
                $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('D1')->applyFromArray($styleArray);
                $i = 2;
                foreach ($order as $data) 
                {
                    $objPHPExcel->getActiveSheet()->setCellValue("A$i", (isset($data['Item']['name']) ? $data['Item']['name'] : '-'));
                    $objPHPExcel->getActiveSheet()->setCellValue("B$i", (isset($data['Item']['Category']['name']) ? $data['Item']['Category']['name'] : '-'));
                    $objPHPExcel->getActiveSheet()->setCellValue("C$i", (isset($data[0]['number']) ? $data[0]['number'] : '-'));
                    $objPHPExcel->getActiveSheet()->setCellValue("D$i", (isset($data[0]['total_amount']) ? $this->Common->amount_format($data[0]['total_amount']) : '-'));
                    $i++;
                }
            }
            else if($reportType == 3)
            {
                $objPHPExcel->getActiveSheet()->setTitle($text);
                $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Customer Name');
                $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Email');
                $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Phone');
                $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Address');
                $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Store Name');
                $objPHPExcel->getActiveSheet()->setCellValue('F1', 'Created');
                $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('D1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('E1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('F1')->applyFromArray($styleArray);
                $i = 2;
                foreach ($order as $data) 
                {
                    $name = $data['User']['fname'] . " " . $data['User']['lname'];
                    $objPHPExcel->getActiveSheet()->setCellValue("A$i", $name);
                    $objPHPExcel->getActiveSheet()->setCellValue("B$i", $data['User']['email']);
                    $objPHPExcel->getActiveSheet()->setCellValue("C$i", $data['User']['phone']);
                    $objPHPExcel->getActiveSheet()->setCellValue("D$i", $data['User']['address']);
                    $objPHPExcel->getActiveSheet()->setCellValue("E$i", $data['Store']['store_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue("F$i", $data['User']['created']);
                    $i++;
                }
            }
            else if($reportType == 7)
            {
                $objPHPExcel->getActiveSheet()->setTitle($text);
                $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Customer Name');
                $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Reservation Date');
                $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Reservation Time');
                $objPHPExcel->getActiveSheet()->setCellValue('D1', 'No. of Persons');
                $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Special Request');
                $objPHPExcel->getActiveSheet()->setCellValue('F1', 'Phone #');
                $objPHPExcel->getActiveSheet()->setCellValue('G1', 'Email');
                $objPHPExcel->getActiveSheet()->setCellValue('H1', 'Status');
                $objPHPExcel->getActiveSheet()->setCellValue('I1', 'Created Date');
                $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('D1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('E1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('F1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('G1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('H1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('I1')->applyFromArray($styleArray);
                $i = 2;
                foreach ($order as $data) 
                {
                    $name = $data['User']['fname'] . " " . $data['User']['lname'];
                    
                    $objPHPExcel->getActiveSheet()->setCellValue("A$i", $name);
                    $objPHPExcel->getActiveSheet()->setCellValue("B$i", $this->Dateform->us_format($data['Booking']['reservation_date']));
                    $objPHPExcel->getActiveSheet()->setCellValue("C$i", date('h:i A', strtotime($data['Booking']['reservation_date'])));
                    $objPHPExcel->getActiveSheet()->setCellValue("D$i", (isset($data['Booking']['number_person']) ? $data['Booking']['number_person'] : '0'));
                    $objPHPExcel->getActiveSheet()->setCellValue("E$i", $data['Booking']['special_request']);
                    $objPHPExcel->getActiveSheet()->setCellValue("F$i", $data['User']['phone']);
                    $objPHPExcel->getActiveSheet()->setCellValue("G$i", $data['User']['email']);
                    $objPHPExcel->getActiveSheet()->setCellValue("H$i", $data['BookingStatus']['name']);
                    $objPHPExcel->getActiveSheet()->setCellValue("I$i", $this->Dateform->us_format($data['Booking']['created']));
                    $i++;
                }
            }
            else
            {
                $objPHPExcel->getActiveSheet()->setTitle($text);
                $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Order No');
                $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Customer Name');
                $objPHPExcel->getActiveSheet()->setCellValue('C1', 'Store Name');
                $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Items');
                $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Amount($)');
                $objPHPExcel->getActiveSheet()->setCellValue('F1', 'Tax($)');
                $objPHPExcel->getActiveSheet()->setCellValue('G1', 'Tip($)');
                $objPHPExcel->getActiveSheet()->setCellValue('H1', 'Phone');
                $objPHPExcel->getActiveSheet()->setCellValue('I1', 'Address');
                $objPHPExcel->getActiveSheet()->setCellValue('J1', 'Email');
                $objPHPExcel->getActiveSheet()->setCellValue('K1', 'Order Type');
                $objPHPExcel->getActiveSheet()->setCellValue('L1', 'Order Date');
                $objPHPExcel->getActiveSheet()->setCellValue('M1', 'Created');
                $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('B1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('C1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('D1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('E1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('F1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('G1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('H1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('I1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('J1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('K1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('L1')->applyFromArray($styleArray);
                $objPHPExcel->getActiveSheet()->getStyle('M1')->applyFromArray($styleArray);
                $i = 2;
                
                if($reportType == 4 || $reportType == 5 || $reportType == 6)
                {
                    foreach ($order as $key => $data) 
                    {
                        //Order Id
                        $objPHPExcel->getActiveSheet()->setCellValue("A$i", $data['Order']['order_number']);

                        //Customer Name
                        if ($data['Order']['DeliveryAddress']['name_on_bell']) {
                            $name = $data['Order']['DeliveryAddress']['name_on_bell'];
                        } else {
                            $name = $data['Order']['User']['fname'] . " " . $data['Order']['User']['lname'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("B$i", $name);

                        // Store Name
                        $storeName = (isset($data['Order']['Store']['store_name']) ? $data['Order']['Store']['store_name'] : '-');
                        $objPHPExcel->getActiveSheet()->setCellValue("C$i", $storeName);

                        // Items
                        $j = 0;
                        $items = "";
                        foreach ($data['Order']['OrderItem'] as $key => $item) {
                            if ($j == 0) {
                                $items = $item['Item']['name'];
                            } else {
                                $items.=", " . $item['Item']['name'];
                            }
                            $j++;
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("D$i", $items);

                        //Amount
                        if ($data['Order']['coupon_discount'] > 0) {
                            $total_amount = $data['Order']['amount'];
                        } else {
                            $total_amount = $data['Order']['amount'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("E$i", $total_amount);

                        // Tax
                        $taxPrice = 0;
                        if(isset($data['Order']['tax_price']))
                        {
			    $taxPrice=$data['Order']['tax_price'];
                        }
                        $taxPrice = (isset($taxPrice) ? number_format($taxPrice, 2) : '0.00');
                        $objPHPExcel->getActiveSheet()->setCellValue("F$i", $taxPrice);

                        // Tip
                        $tip = (isset($data['Order']['tip']) ? number_format($data['Order']['tip'], 2) : '0.00');
                        $objPHPExcel->getActiveSheet()->setCellValue("G$i", $tip);

                        // Phone
                        if (!empty($data['Order']['DeliveryAddress']['phone'])) {
                            $phone = $data['Order']['DeliveryAddress']['phone'];
                        } else {
                            $phone = $data['Order']['User']['phone'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("H$i", $phone);

                        // Address
                        if (!empty($data['Order']['DeliveryAddress']['address'])) {
                            $address = $data['Order']['DeliveryAddress']['address'];
                        } else {
                            $address = $data['Order']['User']['address'];
                        }
                        if ($data['Order']['Segment']['id'] == 2) {
                            $address = $data['Order']['Segment']['name'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("I$i", $address);

                        // Email
                        if (!empty($data['Order']['DeliveryAddress']['email'])) {
                            $email = $data['Order']['DeliveryAddress']['email'];
                        } else {
                            $email = $data['Order']['User']['email'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("J$i", $email);

                        // Order Type
                        $objPHPExcel->getActiveSheet()->setCellValue("K$i", $data['Order']['Segment']['name']);

                        // Order Date
                        $orderDate = '';
                        if($data['Order']['seqment_id'] == 2){
                            $pickupTime=$this->Dateform->us_format($data['Order']['pickup_time']);
                            $orderDate = ($data['Order']['pickup_time']!='0000-00-00 00:00:00' && $data['Order']['pickup_time']!='')?$pickupTime:"-";
                        }
                        if($data['Order']['seqment_id'] == 3){
                            if($data['Order']['is_pre_order'] == 0){
                                $deliveryTime = $this->Dateform->us_format($data['Order']['created']);
                                $orderDate = ($data['Order']['created']!='0000-00-00 00:00:00' && $data['Order']['created']!='')?$deliveryTime:"-";
                            }else{
                                $orderDate = ($data['Order']['pickup_time']!='0000-00-00 00:00:00' && $data['Order']['pickup_time']!='')?$this->Dateform->us_format($data['Order']['pickup_time']):"-";	
                            }
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("L$i", $orderDate);

                        // Order Created
                        $objPHPExcel->getActiveSheet()->setCellValue("M$i", $data['Order']['created']);
                        $i++;
                    }
                }
                else 
                {
                    foreach ($order as $key => $data) 
                    {
                        //Order Id
                        $objPHPExcel->getActiveSheet()->setCellValue("A$i", $data['Order']['order_number']);
                        //Customer Name
                        if ($data['DeliveryAddress']['name_on_bell']) {
                            $name = $data['DeliveryAddress']['name_on_bell'];
                        } else {
                            $name = $data['User']['fname'] . " " . $data['User']['lname'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("B$i", $name);

                        // Store Name
                        $storeName = (isset($data['Store']['store_name']) ? $data['Store']['store_name'] : '-');
                        $objPHPExcel->getActiveSheet()->setCellValue("C$i", $storeName);

                        // Items
                        $j = 0;
                        $items = "";
                        foreach ($data['OrderItem'] as $key => $item) {
                            if ($j == 0) {
                                $items = $item['Item']['name'];
                            } else {
                                $items.=", " . $item['Item']['name'];
                            }
                            $j++;
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("D$i", $items);

                        //Amount
                        if ($data['Order']['coupon_discount'] > 0) {
                            $total_amount = $data['Order']['amount'];
                        } else {
                            $total_amount = $data['Order']['amount'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("E$i", $total_amount);

                        // Tax
                        $taxPrice = 0;
			if(isset($data['Order']['tax_price']))
                        {
			    $taxPrice=$data['Order']['tax_price'];
                        }
                        $taxPrice = (isset($taxPrice) ? number_format($taxPrice, 2) : '0.00');
                        $objPHPExcel->getActiveSheet()->setCellValue("F$i", $taxPrice);

                        // Tip
                        $tip = (isset($data['Order']['tip']) ? number_format($data['Order']['tip'], 2) : '0.00');
                        $objPHPExcel->getActiveSheet()->setCellValue("G$i", $tip);

                        // Phone
                        if (!empty($data['DeliveryAddress']['phone'])) {
                            $phone = $data['DeliveryAddress']['phone'];
                        } else {
                            $phone = $data['User']['phone'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("H$i", $phone);

                        // Address
                        if (!empty($data['DeliveryAddress']['address'])) {
                            $address = $data['DeliveryAddress']['address'];
                        } else {
                            $address = $data['User']['address'];
                        }
                        if ($data['Segment']['id'] == 2) {
                            $address = $data['Segment']['name'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("I$i", $address);

                        // Email
                        if (!empty($data['DeliveryAddress']['email'])) {
                            $email = $data['DeliveryAddress']['email'];
                        } else {
                            $email = $data['User']['email'];
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("J$i", $email);

                        // Order Type
                        $objPHPExcel->getActiveSheet()->setCellValue("K$i", $data['Segment']['name']);

                        // Order Date
                        $orderDate = '';
                        if($data['Order']['seqment_id'] == 2){
                            $pickupTime=$this->Dateform->us_format($data['Order']['pickup_time']);
                            $orderDate = ($data['Order']['pickup_time']!='0000-00-00 00:00:00' && $data['Order']['pickup_time']!='')?$pickupTime:"-";
                        }
                        if($data['Order']['seqment_id'] == 3){
                            if($data['Order']['is_pre_order'] == 0){
                                $deliveryTime = $this->Dateform->us_format($data['Order']['created']);
                                $orderDate = ($data['Order']['created']!='0000-00-00 00:00:00' && $data['Order']['created']!='')?$deliveryTime:"-";
                            }else{
                                $orderDate = ($data['Order']['pickup_time']!='0000-00-00 00:00:00' && $data['Order']['pickup_time']!='')?$this->Dateform->us_format($data['Order']['pickup_time']):"-";	
                            }
                        }
                        $objPHPExcel->getActiveSheet()->setCellValue("L$i", $orderDate);

                        // Order Created
                        $objPHPExcel->getActiveSheet()->setCellValue("M$i", $data['Order']['created']);
                        $i++;
                    }
                }
            }
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename=' . $filename);
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        }
        else 
        {
            $this->Session->setFlash(__('Record not Found.'), 'alert_failed');
            $this->redirect('/storeReports/');
        }
        Configure::write('Config.timezone', $defaultTimeZone);
        exit;
    }
    
    /*     * ***********************
     * Function name:orderListingDownloadReport()
      Description: order listing report download
      created:07/09/2017
     *
     * ********************* */
    public function orderListingDownloadReport($storeID = null, $startDate = null, $endDate = null, $orderType = null)
    {
        $storeId        = $this->Session->read('admin_store_id');
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0 AND Order.store_id = $storeID";
        if ($startDate && $endDate) {
            $startDate = $this->Dateform->formatDate($startDate);
            $endDate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND (DATE(Order.created) >= '" . $startDate . "' AND DATE(Order.created) <= '" . $endDate . "')";
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        } else {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id'),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id'))), false);
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id'
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id'
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id'
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id'
                        ), 
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        )
                    ),
                    'hasMany' => array(
                        'OrderItem' => array(
                            'className' => 'OrderItem',
                            'foreignKey' => 'order_id'
                        ),
                    )
                ), false
            );
        
        $orderdetail = $this->Order->find('all', array('recursive' => 3, 'conditions' => array($criteria), 'order' => array('Order.created' => 'DESC')));
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:orderListingDownloadReport
      Description: order listing report download
      created:07/09/2017
     *
     * ********************* */
    public function orderListingWeekDownloadReport($storeID = null, $startDate = null, $endDate = null, $orderType = null) 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0 AND Order.store_id = $storeID";
        
        if ($startDate && $endDate) {
            $startDate = $this->Dateform->formatDate($startDate);
            $endDate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <= WEEK('" . $endDate . "')";
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        } else {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id'),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id'))), false);
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id'
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id'
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id'
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id'
                        ), 
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        )
                    ),
                    'hasMany' => array(
                        'OrderItem' => array(
                            'className' => 'OrderItem',
                            'foreignKey' => 'order_id'
                        ),
                    )
                ), false
            );
        
        $orderdetail = $this->Order->find('all', array('recursive' => 2, 'conditions' => array($criteria), 'order' => array('Order.created' => 'DESC')));
        return $orderdetail;
    }

    /*     * ***********************
     * Function name:orderProductListingDownloadReport()
      Description:order product download
      created:07/09/2017
     *
     * ********************* */

    public function orderProductListingDownloadReport($storeID = null, $startDate = null, $endDate = null, $orderType = null, $productCount = null) {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0 AND Order.store_id = $storeID";
        
        if ($startDate && $endDate) 
        {
            $startdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND ( DATE(Order.created) >= '" . $startdate . "' AND DATE(Order.created) <= '" . $enddate . "')";
        }
        
        if (!empty($orderType) && $orderType != 1)
        {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        }
        else
        {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                )), false);
        
        $this->OrderItem->Item->bindModel(
                array(
                'belongsTo' => array(
                    'Category' => array(
                        'className' => 'Category',
                        'foreignKey' => 'category_id',
                        'fields' => array('id', 'name')
                    )
                )
            ), false
        );
        
        $limitProduct = ($productCount != 'All') ? $productCount : '';
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'Item.name', 'Item.category_id', 'sum(OrderItem.quantity) AS number', 'sum(OrderItem.total_item_price) AS total_amount', '(OrderItem.total_item_price / OrderItem.quantity) as unit_price', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => array($criteria), 
                    'order'         => array('total_amount' => 'DESC'), 
                    'group'         => array('Item.id'),
                    'limit'         => $limitProduct
           
           ));
        return $orderdetail;
    }
    
    
    
    /*     * ***********************
     * Function name:orderProductListingWeekDownloadReport()
      Description:order product weekly download
      created:07/09/2017
     *
     * ********************* */

    public function orderProductListingWeekDownloadReport($storeID = null, $startDate = null, $endDate = null, $orderType = null, $productCount = null) 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0 AND Order.store_id = $storeID";
        
        if ($startDate && $endDate) 
        {
            $startdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >=WEEK('" . $startdate . "') AND WEEK(Order.created) <= WEEK('" . $enddate . "') AND YEAR(Order.created) = YEAR('" . $enddate . "')";
        }
        
        if (!empty($orderType) && $orderType != 1)
        {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        }
        else
        {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                )), false);
        
        $this->OrderItem->Item->bindModel(
                array(
                'belongsTo' => array(
                    'Category' => array(
                        'className' => 'Category',
                        'foreignKey' => 'category_id',
                        'fields' => array('id', 'name')
                    )
                )
            ), false
        );
        
        $limitProduct = ($productCount != 'All') ? $productCount : '';
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'Item.name', 'Item.category_id', 'sum(OrderItem.quantity) AS number', 'sum(OrderItem.total_item_price) AS total_amount', '(OrderItem.total_item_price / OrderItem.quantity) as unit_price', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => array($criteria), 
                    'order'         => array('total_amount' => 'DESC'), 
                    'group'         => array('Item.id'),
                    'limit'         => $limitProduct
           
           ));
        return $orderdetail;
    }
    
    
    /*     * ***********************
     * Function name:customerListingDownloadReport()
      Description: customer list download 
      created:07/09/2017
     *
     * ********************* */

    public function customerListingDownloadReport($storeID, $startDate = null, $endDate = null) {
        $criteria = "User.is_deleted=0 AND User.is_active=1 AND User.role_id=4";
        if (!empty($storeID)) {
            $criteria .= " AND User.store_id =$storeID";
        }
        if ($startDate && $endDate) 
        {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND ( DATE(User.created) >= '" . $startDate . "' AND DATE(User.created) <= '" . $endDate . "')";
        }
        
        $this->User->bindModel(
            array(
                'belongsTo' => array(
                    'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                )
            ), false
        );
        $userdetail = $this->User->find('all', array('recursive' => 2, 'conditions' => array($criteria), 'order' => array('User.created' => 'DESC')));
        return $userdetail;
    }
    
    /*     * ***********************
     * Function name:customerWeekListingDownloadReport()
      Description: customer week list download 
      created:07/09/2017
     *
     * ********************* */

    public function customerWeekListingDownloadReport($storeID = null, $startDate = null, $endDate = null, $weekyear = null) {
        $criteria = "User.is_deleted=0 AND User.is_active=1 AND User.role_id = 4";
        if (!empty($storeID)) 
        {
            $criteria .= " AND User.store_id =$storeID";
        }
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(User.created) >= WEEK('" . $startDate . "') AND WEEK(User.created) <= WEEK('" . $endDate . "') AND YEAR(User.created) = YEAR('" . $endDate . "')";
        }
        
        $this->User->bindModel(
            array(
                'belongsTo' => array(
                    'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                )
            ), false
        );
        $userdetail = $this->User->find('all', array('recursive' => 2, 'conditions' => array($criteria), 'order' => array('User.created' => 'DESC')));
        return $userdetail;
    }
    
    
    /*************************
     *Function name:couponListingDownloadReport
      Description: coupon list download
      created:07/09/2017
     *
     * ********************* */

    public function couponListingDownloadReport($storeID, $startDate = null, $endDate = null, $couponCode = null, $orderType = null) 
    {
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
                ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        )
                    ),
                    'hasMany' => array(
                        'OrderItem' => array(
                            'className' => 'OrderItem',
                            'foreignKey' => 'order_id',
                            'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                        ),
                    )
                ), false
        );
        if ($startDate && $endDate)
        {
            $conditions = array('DATE(Order.created) >=' => $startDate, 'DATE(Order.created) <=' => $endDate, 'Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        else
        {
            $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        }
        if (!empty($couponCode))
        {
            $conditions['Order.coupon_code'] = $couponCode;
        }
       
        if (!empty($orderType) && $orderType != 1)
        {
            $conditions['Order.seqment_id']= $orderType;
        }
        else
        {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        $conditions = array_merge(array($conditions), array('OrderItem.store_id' => $storeID, 'Order.coupon_code !=' => ''));
        
        
        $orderdetail = $this->OrderItem->find('all', 
                    array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => array($conditions), 
                    'order'         => array('Order.created' => 'DESC'),
                    'group'         => array('OrderItem.order_id')
                )
           );
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:couponWeeklyListingDownloadReport()
      Description: order coupon weekly list 
      created:07/09/2017
     *
     * ********************* */

    public function couponWeeklyListingDownloadReport($storeID = null, $startDate = null, $endDate = null, $orderType = null, $couponCode = null) 
    {
        $criteria = "Order.is_deleted=0 AND Order.is_active=1 AND Order.is_future_order=0";
        
        if ($startDate && $endDate) 
        {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <= WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $endDate . "')";
        }
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
                ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
            array('belongsTo' => array(
                'Store' => array(
                        'className' => 'Store',
                        'foreignKey' => 'store_id',
                        'fields' => array('id', 'store_name')
                    )
                ),
                'hasMany' => array(
                    'OrderItem' => array(
                        'className' => 'OrderItem',
                        'foreignKey' => 'order_id',
                        'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                    ),
                )
            ), false
        );
        
        if (!empty($orderType) && $orderType != 1) 
        {
            $criteria .=" AND Order.seqment_id = '" . $orderType . "'";
        }
        else 
        {
            $criteria .=" AND Order.seqment_id IN (2,3)";
        }
        
        if (!empty($couponCode)) 
        {
            $criteria .=" AND Order.coupon_code = '" . $couponCode . "'";
        }
        
        $orderdetail = 
        $this->OrderItem->find('all', array(
                    'recursive'     => 3,
                    'conditions'    => array_merge(array($criteria), array('Order.store_id' => $storeID, 'Order.coupon_code != ' => '')),
                    'order'         => array('Order.created' => 'DESC'),
                    'group'         => array('OrderItem.order_id')
            )
        );
        return $orderdetail;
    }
    
    /*************************
     *Function name:promoListingDownloadReport()
      Description: order promo list download
      created:07/09/2017
     *
     * ********************* */

    public function promoListingDownloadReport($storeID, $startDate = null, $endDate = null, $promoId = null, $orderType = null) 
    {
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if ($startDate && $endDate) 
        {
            $conditions['DATE(Order.created) >='] = $startDate;
            $conditions['DATE(Order.created) <='] = $endDate;
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (!empty($promoId) && $promoId != 0) {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID, 'OrderOffer.offer_id' => $promoId));
        } else {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderOffer->find('all', array('fields' => array('Order.id', 'OrderOffer.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
            ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        
        $conditions = array_merge($conditions, array('OrderItem.store_id' => $storeID));
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array('Order.created' => 'DESC'), 
                    'group'         => array('OrderItem.order_id', 'Order.id')
           
           ));
        return $orderdetail;
    }
    
    /*************************
     *Function name:promoWeeklyListingDownloadReport()
      Description: order promo weekly list download
      created:07/09/2017
     *
     * ********************* */

    public function promoWeeklyListingDownloadReport($storeID, $startDate = null, $endDate = null, $promoId = null, $orderType = null) 
    {
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if ($startDate && $endDate) 
        {
            $criteria = "WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <=WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $endDate . "')";
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (!empty($promoId) && $promoId != 0) {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID, 'OrderOffer.offer_id' => $promoId));
        } else {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderOffer->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderOffer->find('all', array('fields' => array('Order.id', 'OrderOffer.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
            ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        
        $conditions = array_merge($conditions, array($criteria), array('OrderItem.store_id' => $storeID));
        
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array('Order.created' => 'DESC'), 
                    'group'         => array('OrderItem.order_id')
           
           ));
        return $orderdetail;
    }
    
    
    /*************************
     *Function name:extendedOfferListingDownloadReport
      Description: extended offer list download
      created:07/09/2017
     *
     * ********************* */

    public function extendedOfferListingDownloadReport($storeID, $startDate = null, $endDate = null, $extendedOfferId = null, $orderType = null) 
    {
        
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        if ($startDate && $endDate) {
            $conditions['DATE(Order.created) >='] = $startDate;
            $conditions['DATE(Order.created) <='] = $endDate;
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (!empty($extendedOfferId) && $extendedOfferId != 0) {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID, 'OrderItemFree.item_id' => $extendedOfferId));
        } else {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderItemFree->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderItemFree->find('all', array('fields' => array('Order.id', 'OrderItemFree.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
            ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        
        $conditions = array_merge($conditions, array('OrderItem.store_id' => $storeID));
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array('Order.created' => 'DESC'), 
                    'group'         => array('OrderItem.order_id')
                )
            );
        return $orderdetail;
    }
    
    
    /*************************
     *Function name:extendedOfferWeeklyListingDownloadReport
      Description: extended offer weekly list download
      created:07/09/2017
     *
     * ********************* */

    public function extendedOfferWeeklyListingDownloadReport($storeID, $startDate = null, $endDate = null, $extendedOfferId = null, $orderType = null) 
    {
        
        $conditions = array('Order.is_active' => 1, 'Order.is_deleted' => 0, 'Order.is_future_order' => 0);
        $criteria = '';
        if ($startDate && $endDate) {
            $criteria = "WEEK(Order.created) >= WEEK('" . $startDate . "') AND WEEK(Order.created) <=WEEK('" . $endDate . "') AND YEAR(Order.created) = YEAR('" . $endDate . "')";
        }
        
        if (!empty($orderType) && $orderType != 1) {
            $conditions['Order.seqment_id']= $orderType;
        } else {
            $conditions['Order.seqment_id IN '] = array('2','3');
        }
        
        if (!empty($extendedOfferId) && $extendedOfferId != 0) {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID, 'OrderItemFree.item_id' => $extendedOfferId));
        } else {
            $offer_conditions = array_merge($conditions, array('Order.store_id' => $storeID));
        }
        
        
        $this->OrderItemFree->bindModel(array('belongsTo' => array('Order')));
        $offer = $this->OrderItemFree->find('all', array('fields' => array('Order.id', 'OrderItemFree.id'), 'conditions' => $offer_conditions, 'order' => array('Order.created' => 'DESC')));
        $offerOrderId = array();
        foreach ($offer as $offer)
        {
            if(!in_array($offer['Order']['id'], $offerOrderId))
                $offerOrderId[] = $offer['Order']['id'];
        }
        if(!empty($offerOrderId))
        {
            if(count($offerOrderId) == 1)
            {
                $conditions['Order.id'] = $offerOrderId;
            } else {
                $conditions['Order.id IN'] = (array)$offerOrderId;
            }
        } else {
            $conditions['Order.id'] = 0;
        }
        
        
        $this->Order->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone')
                        ),
                        'OrderStatus' => array(
                            'className' => 'OrderStatus',
                            'foreignKey' => 'order_status_id',
                            'fields' => array('id', 'name')
                        ),
                        'Segment' => array(
                            'className' => 'Segment',
                            'foreignKey' => 'seqment_id',
                            'fields' => array('id', 'name')
                        ),
                        'DeliveryAddress' => array(
                            'className' => 'DeliveryAddress',
                            'foreignKey' => 'delivery_address_id',
                            'fields' => array('id', 'address', 'email', 'city', 'state', 'zipcode', 'name_on_bell', 'phone')
                        )
                    )
            ), false
        );
        $this->OrderItem->bindModel(array('belongsTo' => array(
                'Order' => array('className' => 'Order', 'foreignKey' => 'order_id', 'fields' => array('id', 'store_id', 'order_number', 'seqment_id', 'order_status_id', 'user_id', 'amount', 'pickup_time', 'delivery_address_id', 'is_pre_order', 'created', 'coupon_discount', 'tip','tax_price')),
                'Item' => array('className' => 'Item', 'foreignKey' => 'item_id', 'fields' => array('id', 'name', 'category_id', 'description', 'units')),
                'Type' => array('className' => 'Type', 'foreignKey' => 'type_id'),
                'Size' => array('className' => 'Size', 'foreignKey' => 'size_id', 'fields' => array('id', 'size', 'category_id')))), false);
        $this->Store->unbindModel(array('hasOne' => array('SocialMedia'), 'belongsTo' => array('StoreTheme', 'StoreFont'), 'hasMany' => array('StoreGallery', 'StoreContent')));
        $this->Order->bindModel(
                array('belongsTo' => array(
                'Store' => array(
                    'className' => 'Store',
                    'foreignKey' => 'store_id',
                    'fields' => array('id', 'store_name')
                )
            ),
            'hasMany' => array(
                'OrderItem' => array(
                    'className' => 'OrderItem',
                    'foreignKey' => 'order_id',
                    'fields' => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created')
                ),
            )
                ), false
        );
        
        $conditions = array_merge($conditions, array($criteria), array('OrderItem.store_id' => $storeID));
        
        $orderdetail = $this->OrderItem->find('all', array(
                    'fields'        => array('id', 'order_id', 'quantity', 'item_id', 'size_id', 'type_id', 'total_item_price', 'discount', 'tax_price', 'user_id', 'created'),
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array('Order.created' => 'DESC'), 
                    'group'         => array('OrderItem.order_id')
                )
            );
        return $orderdetail;
    }
    
    /*************************
     *Function name:dineInListingDownloadReport()
      Description: dine in listing for download report
      created:03/10/2017
     *
     * ********************* */

    public function dineInListingDownloadReport($storeID, $startDate = null, $endDate = null) 
    {
        if ($startDate && $endDate) {
            $conditions = array('DATE(Booking.created) >=' => $startDate, 'DATE(Booking.created) <=' => $endDate, 'Booking.is_active' => 1, 'Booking.is_deleted' => 0);
        } else {
            $conditions = array('Booking.is_active' => 1, 'Booking.is_deleted' => 0);
        }
        $conditions['Booking.store_id'] = $storeID;
        
        $this->Booking->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone', 'address', 'email')
                        ),
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        ),
                        'BookingStatus' => array(
                            'className' => 'BookingStatus',
                            'foreignKey' => 'booking_status_id',
                            'fields' => array('id', 'name')
                        )
                    )
            ), false
        );
        
        $conditions = $conditions;
        $bookingdetail = $this->Booking->find('all', array('conditions' => $conditions, 'order' => array('Booking.created' => 'DESC')));
        return $bookingdetail;
    }
    
    /*     * ***********************
     * Function name:dineInWeeklyListingDownloadReport()
      Description: weekly dine listing for table data
      created:03/10/2017
     *
     * ********************* */

    public function dineInWeeklyListingDownloadReport($storeID = null, $startDate = null, $endDate = null) 
    {
        $criteria = "Booking.is_deleted=0 AND Booking.is_active=1";
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Booking.created) >=WEEK('" . $startDate . "') AND WEEK(Booking.created) <=WEEK('" . $endDate . "') AND YEAR(Booking.created) = YEAR('" . $endDate . "')";
        }
        $criteria .= " AND Booking.store_id =$storeID";
        
        $this->Booking->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone', 'address', 'email')
                        ),
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        ),
                        'BookingStatus' => array(
                            'className' => 'BookingStatus',
                            'foreignKey' => 'booking_status_id',
                            'fields' => array('id', 'name')
                        )
                    )
            ), false
        );
        $bookingdetail = $this->Booking->find('all', array('conditions' => $criteria, 'order' => array('Booking.created' => 'DESC')));
        return $bookingdetail;
    }
    
    
    
    /*     * ***********************
     * Function name:dineInListings()
      Description:Graph Dine In list
      created:04/10/2017
     *
     * ********************* */

    public function dineInGraphListings($storeID = null, $startDate = null, $endDate = null) 
    {
        $conditions = array('Booking.store_id' => $storeID, 'Booking.is_active' => 1, 'Booking.is_deleted' => 0);
        if ($startDate && $endDate) {
            $conditions['DATE(Booking.created) >='] = $startDate;
            $conditions['DATE(Booking.created) <='] = $endDate;
        }
        $orderdetail = $this->Booking->find('all', array('fields' => array('DATE(Booking.created) AS order_date', 'Booking.number_person'), 'conditions' => array($conditions), 'order' => array('Booking.created' => 'DESC')));
        return $orderdetail;
    }
    
    
    /*************************
     *Function name:dineInListing()
      Description: dine in listing for table data
      created:04/10/2017
     *
     * ********************* */

    public function dineInListing($storeID, $startDate = null, $endDate = null, $page = 1, $sort = null, $sort_direction = null) 
    {
        if ($startDate && $endDate) {
            $conditions = array('DATE(Booking.created) >=' => $startDate, 'DATE(Booking.created) <=' => $endDate, 'Booking.is_active' => 1, 'Booking.is_deleted' => 0);
        } else {
            $conditions = array('Booking.is_active' => 1, 'Booking.is_deleted' => 0);
        }
        
        $conditions['Booking.store_id'] = $storeID;
        $this->Booking->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone', 'address', 'email')
                        ),
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        ),
                        'BookingStatus' => array(
                            'className' => 'BookingStatus',
                            'foreignKey' => 'booking_status_id',
                            'fields' => array('id', 'name')
                        )
                    )
            ), false
        );
        
        $conditions = $conditions;
        
        if (empty($sort)){
            $sort = 'Booking.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        $this->paginate = array(
                    'recursive'     => 3, 
                    'conditions'    => $conditions, 
                    'order'         => array($sort => $sort_direction),
                    'page'          => $page,
                    'limit'         => $this->pageLimit
           
           );
        $bookingdetail = $this->paginate('Booking');
        return $bookingdetail;
    }
    
    /*************************
     *Function name:dineInWeeklyGraphListing()
      Description: weekly graph listing for Dine In
      created:04/10/2017
     *
     * ********************* */
    
    public function dineInWeeklyGraphListing($storeId = null, $start = null, $end = null) 
    {
        $conditions = array('Booking.store_id' => $storeId, 'Booking.is_active' => 1, 'Booking.is_deleted' => 0);
        
        $weekconditions = '';
        if ($start && $end) {
            $weekconditions = "WEEK(Booking.created) >= WEEK('" . $start . "') AND WEEK(Booking.created) <= WEEK('" . $end . "') AND YEAR(Booking.created) = YEAR('" . $end . "')";
        }
        $conditions = array_merge(array($conditions), array($weekconditions));
        
        $result = $this->Booking->find('all', array('fields' => array('WEEK(Booking.created) AS WEEKno', 'DATE(Booking.created) AS order_date'), 'conditions' => $conditions));
        return $result;
    }
    
    /*     * ***********************
     * Function name:dineInWeeklyListing()
      Description: weekly dine listing for table data
      created:04/10/2017
     *
     * ********************* */

    public function dineInWeeklyListing($storeID = null, $startDate = null, $endDate = null, $page = 1, $sort = '', $sort_direction = '') 
    {
        $criteria = "Booking.is_deleted=0 AND Booking.is_active=1";
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Booking.created) >=WEEK('" . $startDate . "') AND WEEK(Booking.created) <=WEEK('" . $endDate . "') AND YEAR(Booking.created) = YEAR('" . $endDate . "')";
        }
        $criteria .= " AND Booking.store_id =$storeID";
        $this->Booking->bindModel(
                array(
                    'belongsTo' => array(
                        'User' => array(
                            'className' => 'User',
                            'foreignKey' => 'user_id',
                            'fields' => array('id', 'email', 'fname', 'lname', 'phone', 'address', 'email')
                        ),
                        'Store' => array(
                            'className' => 'Store',
                            'foreignKey' => 'store_id',
                            'fields' => array('id', 'store_name')
                        ),
                        'BookingStatus' => array(
                            'className' => 'BookingStatus',
                            'foreignKey' => 'booking_status_id',
                            'fields' => array('id', 'name')
                        )
                    )
            ), false
        );
        
        
        if (empty($sort)){
            $sort = 'Booking.created';
        }
        if (empty($sort_direction)){
            $sort_direction = 'DESC';
        }
        
        $this->paginate = array(
            'recursive'     => 3,
            'conditions'    => $criteria,
            'order'         => array($sort => $sort_direction),
            'page'          => $page,
            'limit'         => $this->pageLimit
        );
        $bookingdetail = $this->paginate('Booking');
        return $bookingdetail;
    }
    
    /*     * ***********************
     * Function name:dineInPieListing()
      Description: Dine In Pie Status list
      created:03/11/2017
     *
     * ********************* */

    public function dineInPieListing($storeID = null, $startDate = null, $endDate = null) 
    {
        $merchantId = $this->Session->read('merchantId');
        $bookingCon = array();
        if($storeID == 'All')
        {
            $stores = $this->Store->getMerchantStores($merchantId);
            $storeIds = array();
            foreach ($stores as $storeId => $storeName)
            {
                $storeIds[]= $storeId;
            }
            $bookingCon['Booking.store_id in']= $storeIds;
        } else {
            $bookingCon['Booking.store_id']= $storeID;
        }
        $conditions = array_merge($bookingCon, array('Booking.is_active' => 1, 'Booking.is_deleted' => 0));
        if ($startDate && $endDate) {
            $conditions['DATE(Booking.created) >='] = $startDate;
            $conditions['DATE(Booking.created) <='] = $endDate;
        }
        $orderdetail = $this->Booking->find('all', array('fields' => array('Booking.booking_status_id', 'count(Booking.booking_status_id) as booking_count'), 'conditions' => array($conditions), 'group' => array('Booking.booking_status_id'), 'order' => array('Booking.created' => 'DESC')));
        return $orderdetail;
    }
    
    /*     * ***********************
     * Function name:dineInPieWeeklyListing()
      Description: Dine In Pie Weekly Status list
      created:03/11/2017
     *
     * ********************* */

    public function dineInPieWeeklyListing($storeID = null, $startDate = null, $endDate = null) 
    {
        $merchantId = $this->Session->read('merchantId');
        $criteria = "Booking.is_deleted=0 AND Booking.is_active=1";
        if ($startDate && $endDate) {
            $stratdate = $this->Dateform->formatDate($startDate);
            $enddate = $this->Dateform->formatDate($endDate);
            $criteria.= " AND WEEK(Booking.created) >= WEEK('" . $startDate . "') AND WEEK(Booking.created) <= WEEK('" . $endDate . "') AND YEAR(Booking.created) = YEAR('" . $endDate . "')";
        }
        
        if ($storeID == 'All') 
        {
            $storeList = $this->Store->getMerchantStores($merchantId);
            $storeIds = '';
            foreach ($storeList as $storeKey => $storeValue) {
                $storeIds .= $storeKey . ',';
            }
            $storeIds = trim($storeIds,',');
            if($storeIds && !empty($storeIds))
            {
                $criteria .= " AND Booking.store_id IN($storeIds)";
            }
        } else {
            $criteria .= " AND Booking.store_id =$storeID";
        }
        
        $orderdetail = $this->Booking->find('all', array('fields' => array('Booking.booking_status_id', 'count(Booking.booking_status_id) as booking_count'), 'conditions' => array($criteria), 'group' => array('Booking.booking_status_id'), 'order' => array('Booking.created' => 'DESC')));
        return $orderdetail;
    }
}