<?php
/**
 * @param $host
 * @param $service
 * @param $ds
 *
 * @return bool
 */
if(!function_exists('gauges_dashlet_gauge_exists')) {
    function gauges_dashlet_gauge_exists($host, $service, $ds)
    {
    
        $result = get_datasources($host, $service, $ds);
    
        if (count($result) > 0)
            return true;
        return false;
    }
}


/**
 * @return mixed|string
 */
if(!function_exists('get_gauge_json')) {
    function get_gauge_json()
    {
        $host = grab_request_var('host', '');
        $service = grab_request_var('service', '');
        $ds = grab_request_var('ds');

        if (empty($host) || empty($service) || empty($ds)) {
            return json_encode(get_datasources($host, $service, $ds));
        }

        $result = get_datasources($host, $service, $ds);
        foreach ($result as $services)
            foreach ($services as $service)
                foreach ($service as $ds)
                    return json_encode($ds);
    }
}

if(!function_exists('gauges_get_host_services')) {
    function gauges_get_host_services()
    {
        $host = grab_request_var('host', '');

        $backendargs = array(
            'orderby' => "host_name:a,service_description:a",
            'brevity' => 4);

        if (!empty($host)) {
            $services = array();
            $backendargs['host_name'] = $host;
            $objs = get_xml_service_status($backendargs);
            foreach ($objs->servicestatus as $o) {
                $services[] = strval($o->name);
            }
            return json_encode($services);
        } else {
            $hosts = array();
            $objs = get_xml_host_status($backendargs);
            foreach ($objs->hoststatus as $o) {
                $hosts[] = strval($o->name);
            }
            return json_encode($hosts);
        }
    }
}

/**
 * @param null $host
 * @param null $service
 * @param null $ds
 *
 * @return array
 */
if(!function_exists('get_datasources')) {
    function get_datasources($host = null, $service = null, $ds = null)
    {
        $result = array();
        $backendargs = array();

        $backendargs["orderby"] = "host_name:a,service_description:a";
        if ($host)
            $backendargs["host_name"] = $host;
        if ($service)
            $backendargs["service_description"] = $service; // service

        $services = get_xml_service_status($backendargs);
        $hosts = get_xml_host_status($backendargs);

        foreach ($services->servicestatus as $status) {
            $status = (array)$status;
            $result[$status['host_name']][$status['name']] = get_gauge_datasource($status, $ds);
            if (empty($result[$status['host_name']]))
                unset($result[$status['host_name']]);
        }
        if (empty($service) || $service == '_HOST_')
            foreach ($hosts->hoststatus as $status) {
                $status = (array)$status;
                $result[$status['name']]['_HOST_'] = get_gauge_datasource($status, $ds);
                if (empty($result[$status['name']]))
                    unset($result[$status['name']]);
            }

        return $result;
    }
}

/**
 * @param $status
 * @param $ds_label
 *
 * @return array
 */
if(!function_exists('get_gauge_datasource')) {
    function get_gauge_datasource($status, $ds_label)
    {
        $ds = array();

        if (empty($status['performance_data'])) {
            return '';
        }

        $perfdata_datasources = str_getcsv($status['performance_data'], " ", "&apos;");
        foreach ($perfdata_datasources as $perfdata_datasource) {

            $perfdata_s = explode('=', $perfdata_datasource);
            $perfdata_name = trim(str_replace("apos;", "", $perfdata_s[0]));

            // Strip bad char from key name and label (REMOVED for pnp convert function -JO)
            //$perfdata_name = str_replace('\\', '', $perfdata_name);
            //$perfdata_name = str_replace(' ', '_', $perfdata_name);
            $perfdata_name = pnp_convert_object_name($perfdata_name);
            if ($ds_label && $perfdata_name != $ds_label && $perfdata_name != pnp_convert_object_name($ds_label))
                continue;
            if (!isset($perfdata_s[1]))
                continue;
            
            //test=13; "test helo"=3; 

            $perfdata = explode(';', $perfdata_s[1]);
            $current = preg_replace("/[^0-9.]/", "", grab_array_var($perfdata, 0, 0));

            $ds[$perfdata_name]['label'] = $perfdata_name;
            $ds[$perfdata_name]['current'] = round(floatval($current), 3);
            $ds[$perfdata_name]['uom'] = str_replace($current, '', $perfdata[0]);
            $ds[$perfdata_name]['warn'] = grab_array_var($perfdata, 1, 0);
            $ds[$perfdata_name]['crit'] = grab_array_var($perfdata, 2, 0);
            $ds[$perfdata_name]['min'] = floatval(grab_array_var($perfdata, 3, "0"));
            $ds[$perfdata_name]['max'] = floatval(grab_array_var($perfdata, 4, "0"));

            // Do some guessing if max is not set
            if ($ds[$perfdata_name]['max'] == 0) {
                if ($ds[$perfdata_name]['crit'] != 0 && $ds[$perfdata_name]['crit'] > 0) {
                    $ds[$perfdata_name]['max'] = $ds[$perfdata_name]['crit'] * 1.1;
                } else if ($ds[$perfdata_name]['uom'] == '%') {
                    $ds[$perfdata_name]['max'] = 100;
                }
            }

            // Do some rounding
            $ds[$perfdata_name]['max'] = round($ds[$perfdata_name]['max'], 1);

            // Remove the item if we were not able to determine the max
            if ($ds[$perfdata_name]['max'] == 0) {
                $ds[$perfdata_name]['max'] = 100;
            }

            // add yellowZones & redZones
            if (!empty($ds[$perfdata_name]['warn'])) {
                if (strpos($ds[$perfdata_name]['warn'], ":") !== false) {
                    // We are doing a range warning threshold
                    list($end, $start) = explode(":", $ds[$perfdata_name]['warn']);
                    $ds[$perfdata_name]['yellowZones'] = array(
                        array(
                            "from" => floatval($start),
                            "to" => floatval($end)
                        )
                    );
                } else {
                    // Standard warning threshold
                    $ds[$perfdata_name]['yellowZones'] = array(
                        array(
                            "from" => floatval($ds[$perfdata_name]['warn']),
                            "to" => ($ds[$perfdata_name]['crit'] != 0) ? floatval($ds[$perfdata_name]['crit']) : $ds[$perfdata_name]['max'],
                        )
                    );
                }
            }

            if (!empty($ds[$perfdata_name]['crit'])) {
                if (strpos($ds[$perfdata_name]['crit'], ":") !== false) {
                    // We are doing a range warning threshold
                    list($end, $start) = explode(":", $ds[$perfdata_name]['crit']);
                    $ds[$perfdata_name]['redZones'] = array(
                        array(
                            "from" => floatval($start),
                            "to" => floatval($end)
                        )
                    );
                } else {
                    // Standard critical threshold
                    $ds[$perfdata_name]['redZones'] = array(
                        array(
                            "from" => floatval($ds[$perfdata_name]['crit']),
                            "to" => $ds[$perfdata_name]['max'],
                        )
                    );
                }
            }
        }

        return $ds;
    }
}

if (!function_exists('str_getcsv')) {

    /**
     * @param        $input
     * @param string $delimiter
     * @param string $enclosure
     * @param null   $escape
     * @param null   $eol
     *
     * @return array
     */
    function str_getcsv($input, $delimiter = ',', $enclosure = '"', $escape = null, $eol = null)
    {
        $temp = fopen("php://memory", "rw");
        fwrite($temp, $input);
        fseek($temp, 0);
        $r = array();
        while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
            $r[] = $data;
        }
        fclose($temp);
        return $r;
    }
}	

?>