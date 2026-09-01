<?php
/**
 * Magezon
 *
 * This source file is subject to the Magezon Software License, which is available at https://www.magezon.com/license
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to https://www.magezon.com for more information.
 *
 * @category  Magezon
 * @package   Magezon_PopupBuilder
 * @copyright Copyright (C) 2020 Magezon (https://www.magezon.com)
 */

namespace Magezon\PopupBuilder\Model;

class ReportManager
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_resource = $resource;
    }

    /**
     * @param $type
     * @param $popupId
     * @param $storeId
     * @param null $customerId
     */
    public function reportViews($type, $popupId, $storeId, $customerId = null)
    {
        $table      = $this->_resource->getTableName('mgz_popupbuilder_popup_views');
        $data = [
            'type'       => $type,
            'customerId' => $customerId,
            'popup_id'   => (int)$popupId,
            'store_id'   => (int)$storeId
        ];
        $this->_resource->getConnection()->insert($table, $data);
    }

    /**
     * @param $type
     * @param $popupId
     * @param $from
     * @param $to
     * @param null $storeId
     * @return array
     */
    public function getChartData($type, $popupId, $from, $to, $storeId = null)
    {
        $orgiFrom = $from;
        $orgiTo   = $to;
        $from     = date('Y-m-d 00:00:00', strtotime($from));
        $to       = date('Y-m-d 00:00:00', strtotime('+1day', strtotime($to)));

        $result     = [];
        $table      = $this->_resource->getTableName('mgz_popupbuilder_popup_report');
        $connection = $this->_resource->getConnection();
        $select     = $connection->select()
        ->from($table)
        ->where('added_at >= ?', $from)
        ->where('added_at <= ?', $to)
        ->where('type = ?', $type);

        if ($storeId) {
            $select->where('store_id = ?', $storeId);
        }
        if ($popupId!='all') {
            $select->where('popup_id = ?', $popupId);
        }

        $data = $connection->fetchAll($select);
        $i      = 0;
        $views  = [];
        $labels = [];

        $temp = [];
        foreach ($data as $key => $value) {
            $_date = date("Y-m-d", strtotime($value['added_at'])) . ' 00:00:00';
            if (isset($temp[$_date]['views'])) {
                $temp[$_date]['views'] = $temp[$_date]['views'] + 1;
            } else {
                $temp[$_date]['views'] = 1;
            }
        }
        $allData = $temp;

        $difference = (strtotime($to) - strtotime($from)) / (60*60*24);
        if ($difference > 180) {
            $denominator = $difference > 366 ? 'M Y' : 'M';
            while ($i < $difference) {
                $this_month = date($denominator, strtotime('+' . $i . 'day', strtotime($from)));
                $this_date  = date('Y-m-d 00:00:00', strtotime('+' . $i . 'day', strtotime($from)));

                if (isset($allData[$this_date])) {
                    if (in_array(date($denominator, strtotime($this_date)), $labels)) {
                        $views[array_search(date($denominator, strtotime($this_date)), $labels)] += intval($allData[$this_date]['views']);
                    } else {
                        $labels[] = date($denominator, strtotime($this_date));
                        $views[]  = intval($allData[$this_date]['views']);
                    }
                } else {
                    if (!in_array(date($denominator, strtotime($this_date)), $labels)) {
                        $labels[] = date($denominator, strtotime($this_date));
                        $views[]  = 0;
                    }
                }
                $i++;
            }

        } else {
            while ($i < $difference) {
                $this_date = date('Y-m-d 00:00:00', strtotime('+' . $i . 'day', strtotime($from)));
                if (isset($allData[$this_date])) {
                    $labels[] = date('d M', strtotime($this_date));
                    $views[] = intval($allData[$this_date]['views']);
                } else {
                    $labels[] = date('d M', strtotime($this_date));
                    $views[] = 0;
                }
                $i++;
            }
        }
        $max_points = 20;
        $nos = ceil(count($views) / $max_points);

        if ($nos > 2000) {
            $labels  = $this->compressStats($labels, $max_points, 'string');
            $views = $this->compressStats($views, $max_points, 'int');
        }

        $result['labels'] = $labels;
        $result['items']  = $views;
        return $result;
    }

    /**
     * @param $input
     * @param $max_points
     * @param $type
     * @return array
     */
    private function compressStats($input, $max_points, $type)
    {
        $x = 0;
        $nos = ceil(count($input)/$max_points);
        $newOutput = [];
        do {
            $temp1 = [];
            $temp1 = array_slice($input, $x*$nos, $nos);
            $temp2 = 0;
            foreach ($temp1 as $key => $value) {
                if ($type=='int') {
                    $temp2 = $value + $temp2;
                } else {
                    $temp2 = $temp1[0].' - '.$temp1[ count($temp1) -1 ];
                }
            }
            $newOutput[] = $temp2;
            $x++;
        } while ($x<$max_points);
        return $newOutput;
    }
}
