<?php

namespace App\Traits;
use App\Models\Tablerates;
use App\Traits\FetchShippingZoneTrate;

trait TableRateTrait
{
    use FetchShippingZoneTrate;

    public function tableRateShipping($country_code, $province_code, $post_code, $price, $weightInGram, $quantity, $lineItem)
    {
        $zone = json_decode($this->getShippingZones($country_code, $province_code, $post_code));
        if(!empty($zone)) {

            return json_encode($this->calCulateTableRate($zone->id, $price, $weightInGram, $quantity, $lineItem));
            
        }else{

            return response()->json([]);

        }
    }

    public function calCulateTableRate($zoneID, $price, $weightInGram, $quantity, $lineItem)
    {
        $tableRates = Tablerates::with('table_rate_options')->where('zone_id', $zoneID)->where('status', 1)->get();

        $result = [];
        // dd($tableRates->toArray());
        foreach ($tableRates as $tableRate) {
                // echo"<pre>";
                // print_r($tableRate->toArray());
                // echo"</pre>";
            if (count($tableRate->table_rate_options)) {
                // echo"<pre>";
                // print_r($tableRate->table_rate_options->toArray());
                // echo"</pre>";

                if ($tableRate->calculation_type == "per_order") {
                    array_push($result, $this->calCulateTableRateOptions($tableRate, $tableRate->table_rate_options, $price, $weightInGram, $quantity, $tableRate->title, $tableRate->description));
                    // return $this->calCulateTableRateOptions($tableRate, $tableRate->table_rate_options, $price, $weightInGram, $quantity, $tableRate->title);
                } else  if ($tableRate->calculation_type == "item") {
                    array_push($result, $this->calCulateTableRateOptionsPERITEMS($tableRate, $tableRate->table_rate_options, $price, $weightInGram, $quantity, $tableRate->title, $tableRate->description));
                    // return $this->calCulateTableRateOptionsPERITEMS($tableRate, $tableRate->table_rate_options, $price, $weightInGram, $quantity, $tableRate->title);
                } else {
                    array_push($result, $this->calCulateTableRatePerLINEITEMS($tableRate, $tableRate->table_rate_options, $price, $weightInGram, $lineItem, $tableRate->title, $tableRate->description));
                    // return $this->calCulateTableRatePerLINEITEMS($tableRate, $tableRate->table_rate_options, $price, $weightInGram, $lineItem, $tableRate->title);
                }
            } else {

                if ($tableRate->calculation_type == "per_order") {
                    $ship = $tableRate->handling_fee + $tableRate->handlingfee_peritem;
                    array_push($result, $this->tableRateShippingPERORDER($tableRate, $ship, $tableRate->title, $tableRate->description));
                    // return $this->tableRateShippingPERORDER($tableRate, $ship, $tableRate->title);
                } else  if ($tableRate->calculation_type == "item") {
                    $ship = $tableRate->handling_fee + ($tableRate->handlingfee_peritem * floatval($quantity));
                    array_push($result, $this->tableRateShippingPERITEMS($tableRate, $ship, $tableRate->title, $tableRate->description, $quantity));
                    // return $this->tableRateShippingPERITEMS($tableRate, $ship, $tableRate->title, $quantity);
                } else {
                    $ship = $tableRate->handling_fee + ($tableRate->handlingfee_peritem * floatval($lineItem));
                    array_push($result, $this->tableRateShippingPERLINeITEMS($tableRate, $ship, $tableRate->title, $tableRate->description, $lineItem));
                    // return $this->tableRateShippingPERLINeITEMS($tableRate, $ship, $tableRate->title, $lineItem);
                }
            }
            
        }
        return $result;
    }

    public function calCulateTableRateOptionsPERITEMS($tableRate, $tableRateOption, $price, $weightInGram, $quantity, $title, $description)
    {
        $result = [];
        if (!empty($tableRateOption)) 
        {
            foreach ($tableRateOption as $tblRateOption) 
            {
                $lbCostPrice = $tblRateOption->lbs_cost / 453.592;

                $handlingFee = $tableRate->handling_fee + ($tableRate->handlingfee_peritem * floatval($quantity));

                if ($tblRateOption->condition == "none") 
                {

                    $ship = $handlingFee + $tblRateOption->row_cost  + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                    return $this->tableRateShippingPERITEMS($tableRate, $ship, $title, $description, $quantity);

                }
                 else if ($tblRateOption->condition == "price") 
                 {

                    if ((floatval($price) / 100) >= $tblRateOption->ship_min  && (floatval($price) / 100) <= $tblRateOption->ship_max) 
                    {

                        $ship =  $handlingFee + $tblRateOption->row_cost + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                        return $this->tableRateShippingPERITEMS($tableRate, $ship, $title, $description, $quantity);

                    }

                }
                else if ($tblRateOption->condition == "weight") 
                {

                    if (!empty($weightInGram)) 
                    {

                        if (($weightInGram >= $tblRateOption->ship_min * 454)  && ($weightInGram <= $tblRateOption->ship_max * 454)) 
                        {

                            $ship =  $handlingFee + $tblRateOption->row_cost + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                            return $this->tableRateShippingPERITEMS($tableRate, $ship, $title, $description, $quantity);
                        }

                    }

                } 
                else if ($tblRateOption->condition == "itemcount") 
                {

                    if (!empty($quantity)) 
                    {

                        if (($quantity >= $tblRateOption->ship_min)  && ($quantity <= $tblRateOption->ship_max)) 
                        {

                            $ship = $handlingFee + $tblRateOption->row_cost  + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                            return $this->tableRateShippingPERITEMS($tableRate, $ship, $title, $description, $quantity);
                        }
                    }
                }
            }
        }
    }

    public function tableRateShippingPERITEMS($tableRate, $ship, $title, $description, $quantity)
    {
        $shipping = 0;
        $result = [];
        if(!empty($tableRate)) 
        { 
           
            if ($tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
            {

                if ($tableRate->maxship_cost > ($tableRate->maxcost_peritem * floatval($quantity)) && ($tableRate->maxcost_peritem * floatval($quantity)) > ($tableRate->mincost_peritem* floatval($quantity))) 
                {

                    $shipping = $tableRate->mincost_peritem* floatval($quantity);

                } 
                else if ($tableRate->maxship_cost > ($tableRate->mincost_peritem * floatval($quantity)) && ($tableRate->mincost_peritem * floatval($quantity)) > ($tableRate->maxcost_peritem * floatval($quantity))) 
                {

                    $shipping = $tableRate->maxcost_peritem * floatval($quantity);

                } 
                else 
                {

                    $shipping = $tableRate->maxship_cost;
                }

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' => $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if ($tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
            {

                if ($tableRate->maxship_cost > ($tableRate->maxcost_peritem * floatval($quantity)) && ($tableRate->maxcost_peritem * floatval($quantity)) > $ship) 
                {

                    $shipping = $ship;

                } 
                else if ($tableRate->maxcost_peritem > $ship && $ship > ($tableRate->maxship_cost * floatval($quantity))) 
                {

                    $shipping = $tableRate->maxship_cost;
                } 
                else 
                {

                    $shipping = $tableRate->maxcost_peritem * floatval($quantity);
                }

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if ($tableRate->maxship_cost > 0 && !$tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
            {


                $shipping =  ($tableRate->maxship_cost > $ship) ? $ship : $tableRate->maxship_cost;

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if(!$tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
            {

                if (($tableRate->maxcost_peritem * floatval($quantity)) > ($tableRate->mincost_peritem * floatval($quantity)) && ($tableRate->mincost_peritem * floatval($quantity)) > $ship) 
                {

                    $shipping = $tableRate->mincost_peritem * floatval($quantity);

                } 
                else if (($tableRate->maxcost_peritem * floatval($quantity)) > $ship && $ship > ($tableRate->mincost_peritem * floatval($quantity))) 
                {

                    $shipping = $ship;

                } 
                else 
                {

                    $shipping = $tableRate->maxcost_peritem * floatval($quantity);
                }

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if(!$tableRate->maxship_cost > 0 && !$tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
            {

                $shipping =  (($tableRate->mincost_peritem * floatval($quantity)) > $ship) ? $tableRate->mincost_peritem * floatval($quantity) : $ship;

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
        
            } 
            else if(!$tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
            {

                $shipping =  (($tableRate->maxcost_peritem * floatval($quantity)) > $ship) ? $ship : $tableRate->maxcost_peritem * floatval($quantity);

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
        
            } 
            else 
            {
                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $ship, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
        
            }
            return $result;
        }
    }

    public function calCulateTableRatePerLINEITEMS($tableRate, $tableRateOption, $price, $weightInGram, $lineItem, $title, $description)
    {
        $result = [];

        if (!empty($tableRateOption)) 
        {

            foreach ($tableRateOption as $tblRateOption) 
            {

                $lbCostPrice = $tblRateOption->lbs_cost / 453.592;

                $handlingFee = $tableRate->handling_fee + ($tableRate->handlingfee_peritem * floatval($lineItem));

                if ($tblRateOption->condition == "none") 
                {

                    $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($lineItem)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                    return $this->tableRateShippingPERLINeITEMS($tableRate, $ship, $title, $description, $lineItem);

                } 
                else if ($tblRateOption->condition == "price") 
                {

                    if ((floatval($price) / 100) >= $tblRateOption->ship_min  && (floatval($price) / 100) <= $tblRateOption->ship_max) 
                    {

                        $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($lineItem)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                        return $this->tableRateShippingPERLINeITEMS($tableRate, $ship, $title, $description, $lineItem);
                    }

                } 
                else if ($tblRateOption->condition == "weight") 
                {

                    if (!empty($weightInGram)) 
                    {

                        if (($weightInGram >= $tblRateOption->ship_min * 454)  && ($weightInGram <= $tblRateOption->ship_max * 454)) 
                        {

                            $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($lineItem)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                            return $this->tableRateShippingPERLINeITEMS($tableRate, $ship, $title, $description, $lineItem);

                        }

                    }

                } 
                else if ($tblRateOption->condition == "itemcount") 
                {

                    if (!empty($lineItem)) 
                    {

                        if (($lineItem >= $tblRateOption->ship_min)  && ($lineItem <= $tblRateOption->ship_max)) 
                        {

                            $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($lineItem)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                            return $this->tableRateShippingPERLINeITEMS($tableRate, $ship, $title, $description, $lineItem);

                        }

                    }

                }
            }
        }
    }

    public function tableRateShippingPERLINeITEMS($tableRate, $ship, $title, $description, $lineItem)
    {
        $shipping = 0;
        $result = [];
        if(!empty($tableRate))
        { 
            if ($tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
            {

                if ($tableRate->maxship_cost > ($tableRate->maxcost_peritem * floatval($lineItem)) && ($tableRate->maxcost_peritem * floatval($lineItem)) >= ($tableRate->mincost_peritem * floatval($lineItem))) 
                {

                    $shipping = $tableRate->mincost_peritem* floatval($lineItem);

                } 
                else if ($tableRate->maxship_cost > ($tableRate->mincost_peritem * floatval($lineItem)) && ($tableRate->mincost_peritem * floatval($lineItem)) > ($tableRate->maxcost_peritem * floatval($lineItem))) 
                {

                    $shipping = $tableRate->maxcost_peritem * floatval($lineItem);

                } 
                else 
                {

                    $shipping = $tableRate->maxship_cost;
                }

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if ($tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
            {

                if ($tableRate->maxship_cost > ($tableRate->maxcost_peritem * floatval($lineItem)) && ($tableRate->maxcost_peritem * floatval($lineItem)) > $ship) 
                {

                    $shipping = $ship;

                } 
                else if ($tableRate->maxcost_peritem > $ship && $ship > ($tableRate->maxship_cost * floatval($lineItem))) 
                {

                    $shipping = $tableRate->maxship_cost;

                } 
                else 
                {

                    $shipping = $tableRate->maxcost_peritem * floatval($lineItem);
                }

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if ($tableRate->maxship_cost > 0 && !$tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
            {

                $shipping =  ($tableRate->maxship_cost > $ship) ? $ship : $tableRate->maxship_cost;

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
            } 
            else if(!$tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
            {

                if (($tableRate->maxcost_peritem * floatval($lineItem)) > ($tableRate->mincost_peritem * floatval($lineItem)) && ($tableRate->mincost_peritem * floatval($lineItem)) > $ship) 
                {

                    $shipping = $tableRate->mincost_peritem * floatval($lineItem);

                } 
                else if (($tableRate->maxcost_peritem * floatval($lineItem)) > $ship && $ship > ($tableRate->mincost_peritem * floatval($lineItem))) 
                {

                    $shipping = $ship;

                } 
                else 
                {

                    $shipping = $tableRate->maxcost_peritem * floatval($lineItem);
                }

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem, 'ship' => $ship));
            
            } 
            else if(!$tableRate->maxship_cost > 0 && !$tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
            {

                $shipping =  (($tableRate->mincost_peritem * floatval($lineItem)) > $ship) ? $tableRate->mincost_peritem * floatval($lineItem) : $ship;

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
        
            } 
            else if(!$tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
            {

                $shipping =  (($tableRate->maxcost_peritem * floatval($lineItem)) > $ship) ? $ship : $tableRate->maxcost_peritem * floatval($lineItem);

                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
        
            } 
            else 
            {
                array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description, 'shipPrice' => $ship, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
        
            }

            return $result;
        }
    }


    public function calCulateTableRateOptions($tableRate, $tableRateOption, $price, $weightInGram, $quantity, $title, $description)
    {
        $result = [];
        // echo"<pre>";
        // print_r($tableRateOption->toArray());
        // echo"<pre>";
        if (!empty($tableRateOption)) {
            // echo"Ankit";
           

            foreach ($tableRateOption as $tblRateOption) 
            {

                $lbCostPrice = $tblRateOption->lbs_cost / 453.59;

                $handlingFee = $tableRate->handling_fee + $tableRate->handlingfee_peritem;

                if ($tblRateOption->condition == "none") 
                {

                    $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($quantity)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                    return $this->tableRateShippingPERORDER($tableRate, $ship, $title, $description);
                    // array_push($result, $this->tableRateShippingPERORDER($tableRate, $ship, $title));

                } 
                else if ($tblRateOption->condition == "price") 
                {

                    if ((floatval($price) / 100) >= $tblRateOption->ship_min  && (floatval($price) / 100) <= $tblRateOption->ship_max) 
                    {

                        $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($quantity)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                        return $this->tableRateShippingPERORDER($tableRate, $ship, $title, $description);
                        // array_push($result, $this->tableRateShippingPERORDER($tableRate, $ship, $title));

                    }

                }
                else if ($tblRateOption->condition == "weight") 
                {

                    if (!empty($weightInGram)) 
                    {

                        if (($weightInGram >= $tblRateOption->ship_min * 454)  && ($weightInGram <= $tblRateOption->ship_max * 454)) 
                        {

                            $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($quantity)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                            return $this->tableRateShippingPERORDER($tableRate, $ship, $title, $description);
                            // array_push($result, $this->tableRateShippingPERORDER($tableRate, $ship, $title));
                        }

                    }

                } 
                else if ($tblRateOption->condition == "itemcount") 
                {

                    if (!empty($quantity)) 
                    {

                        if (($quantity >= $tblRateOption->ship_min)  && ($quantity <= $tblRateOption->ship_max)) 
                        {

                            $ship = $handlingFee + $tblRateOption->row_cost + ($tblRateOption->item_cost * floatval($quantity)) + ($lbCostPrice * floatval($weightInGram)) + (floatval($price) / 100 * $tblRateOption->percent_cost / 100);

                            return $this->tableRateShippingPERORDER($tableRate, $ship, $title, $description);
                            // array_push($result, $this->tableRateShippingPERORDER($tableRate, $ship, $title));
                        }

                    }
                    
                }
            }
            // echo"<pre>";
            // print_r($result);
            // echo"</pre>";
            // return $result;
        }
        
    }

    public function tableRateShippingPERORDER($tableRate, $ship, $title, $description)
    {
        $shipping = 0;
        $result = [];
        // echo"<pre>";
        // print_r($tableRate->toArray());
        // echo"</pre>";

        if(!empty($tableRate)) 
        { 
            
            
            // foreach ($tableRate as $table) 
            // {

                // dd($table);
                if ($tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
                {

                    if ($tableRate->maxship_cost > $tableRate->maxcost_peritem && $tableRate->maxcost_peritem > $tableRate->mincost_peritem) 
                    {

                        $shipping = $tableRate->mincost_peritem;

                    } 
                    else if ($tableRate->maxship_cost > $tableRate->mincost_peritem && $tableRate->mincost_peritem > $tableRate->maxcost_peritem) 
                    {

                        $shipping = $tableRate->maxcost_peritem;

                    } 
                    else 
                    {

                        $shipping = $tableRate->maxship_cost;
                    }

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
                
                } 
                else if ($tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
                {

                    if ($tableRate->maxship_cost > $tableRate->maxcost_peritem && $tableRate->maxcost_peritem > $ship) 
                    {

                        $shipping = $tableRate->maxcost_peritem;

                    } 
                    else if ($tableRate->maxcost_peritem > $ship && $ship > $tableRate->maxship_cost) 
                    {

                        $shipping = $tableRate->maxship_cost;

                    } 
                    else 
                    {

                        $shipping = $ship;
                    }

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
                
                } 
                else if ($tableRate->maxship_cost > 0 && !$tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
                {

                    $shipping =  ($tableRate->maxship_cost > $ship) ? $ship : $tableRate->maxship_cost;

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
                
                } 
                else if(!$tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
                {

                    if ($tableRate->maxcost_peritem > $tableRate->mincost_peritem && $tableRate->mincost_peritem > $ship) 
                    {

                        $shipping = $tableRate->mincost_peritem;

                    } 
                    else if ($tableRate->maxship_cost > $ship && $ship > $tableRate->mincost_peritem) 
                    {

                        $shipping = $ship;

                    } 
                    else 
                    {

                        $shipping = $tableRate->maxcost_peritem;
                    }

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
                
                } 
                else if(!$tableRate->maxship_cost > 0 && !$tableRate->maxcost_peritem > 0 && $tableRate->mincost_peritem > 0) 
                {

                    $shipping =  ($tableRate->mincost_peritem > $ship) ? $tableRate->mincost_peritem : $ship;

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
                } 
                else if(!$tableRate->maxship_cost > 0 && $tableRate->maxcost_peritem > 0 && !$tableRate->mincost_peritem > 0) 
                {

                    $shipping =  ($tableRate->maxcost_peritem > $ship) ? $ship : $tableRate->maxcost_peritem;

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $shipping, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
                }
                else 
                {

                    array_push($result, array('status' => 1, 'service_name' => $title, 'description' =>  $description,  'shipPrice' => $ship, 'maxShipCost' => $tableRate->maxship_cost, 'minCostper' => $tableRate->mincost_peritem, 'maxCostPer' => $tableRate->maxcost_peritem));
            
                }
            // }
            // echo"<pre>";
            // print_r($result);
            // echo"</pre>";
                return $result;
            
        }
    }
}
