<?php

if (!function_exists('dateRange')) {
    /**
     *
     * @param string $startTime
     * @param string $endTime
     * @return DatePeriod
     */
    function dateRange($startTime, $endTime)
    {
        $d1 = new \DateTime($startTime);
        $d2 = new \DateTime($endTime);

        if ((date_diff($d1, $d2))->format('%a') > 30) {
            throw new \Exception('最多可以搜尋30天的區間資料');
        }

        return new \DatePeriod($d1, new \DateInterval('P1D'), $d2);
    }
}
