<?php
//
// linear_gauge URL Dashlet 
// Copyright (c) 2008-2022 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../dashlethelper.inc.php');
include_once(dirname(__FILE__) . '/gaugeshelper.php');

// Run the initialization function
linear_gauge_init();

function linear_gauge_init()
{
    $name = "linear_gauge";
    
    $args = array(
        DASHLET_NAME => $name,
        DASHLET_VERSION => "1.0.1",
        DASHLET_DATE => "1/27/2025",
        DASHLET_AUTHOR => "Nagios Enterprises, LLC",
        DASHLET_DESCRIPTION => _("Linear Gauge Dashlet."),
        DASHLET_COPYRIGHT => "Copyright (c) 2010-2023 Nagios Enterprises, LLC",
        DASHLET_LICENSE => "BSD",
        DASHLET_HOMEPAGE => "https://www.nagios.com",
        DASHLET_REFRESHRATE => 60,
        DASHLET_FUNCTION => "linear_gauge_func",
        DASHLET_TITLE => _("Linear Gauge Dashlet"),
        DASHLET_OUTBOARD_CLASS=> "linear_gauge_outboardclass",
        DASHLET_INBOARD_CLASS => "linear_gauge_inboardclass",
        DASHLET_PREVIEW_CLASS => "linear_gauge_previewclass",
        DASHLET_JS_FILE => "linear_gauge.js",
        DASHLET_WIDTH => "300",
        DASHLET_HEIGHT => "250",
        DASHLET_OPACITY => "1.0"
    );
        
    register_dashlet($name, $args);
}
    

function linear_gauge_func($mode=DASHLET_MODE_PREVIEW, $id="", $args=null)
{
    $output = "";

    switch ($mode) {

        case DASHLET_MODE_GETCONFIGHTML:
            //input form for dashlet vars 
            if ($args == null) {
                $output = '<script type="text/javascript">load_gauge_hosts();</script>';
                $output .= '<div class="popup-form-box"><label>' . _('Host') . '</label>
                            <div><select id="gauges_form_name" class="form-control" name="host" onchange="getgaugejson()">
                                    <option selected></option>';
                $output .= '</select> <i class="fa fa-spinner fa-spin fa-14 hide host-loader" title="'._('Loading').'"></i></div></div>';
                $output .= '<div class="popup-form-box"><label>' . _('Services') . '</label>
                                <div id="gauges_services">
                                    <select id="gauges_form_services" class="form-control" name="service" onchange="getgaugeservices()" disabled>
                                        <option selected></option>
                                    </select> <i class="fa fa-spinner fa-spin fa-14 hide service-loader" title="'._('Loading').'"></i>
                                    <div id="empty-services" class="hide">'._("No services found").'</div>
                                </div>
                            </div>';
                $output .= '<div class="popup-form-box"><label>' . _('Datasource') . '</label>
                                <div id="gauges_datasource">
                                    <select id="gauges_form_ds" class="form-control" name="ds" disabled>
                                        <option selected></option>
                                    </select> <i class="fa fa-spinner fa-spin fa-14 hide ds-loader" title="'._('Loading').'"></i>
                                    <div id="empty-ds" class="hide">'._("No data sources found").'</div>
                                </div>
                            </div>';
                $output .= '';
            }
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:

            $output = "";
            if (empty($args['ds'])) {
                $output .= "ERROR: Missing Arguments";
                break;
            }

            // Random dashlet id 
            $rand = rand();

            $n = 0;
            $ajaxargs = "{";
            foreach ($args as $idx => $val) {
                if ($n > 0)
                    $ajaxargs .= ", ";
                $ajaxargs .= "\"$idx\" : \"$val\"";
                $n++;
            }
            $ajaxargs .= "}";

            $host = grab_array_var($args, "host", "");
            $service = urldecode(grab_array_var($args, "service", ""));
            $ds = grab_array_var($args, "ds", "");

            // HTML output (heredoc string syntax)
            ob_start();
            insert_linear_gauge_html($rand);
            ?>
            <script>
                var args = [<?= json_encode($args) ?>];
                var url = "<?= get_dashlet_url_base("linear_gauge") . "/getdata.php?host={$host}&service={$service}&ds={$ds}" ?>";
				console.log(url);
				console.log("AJAX initiated");
                $.ajax({"url": url, dataType: "json",
                    "success": function(result) {
                        updateLineargauge("<?= $id ?>", result, "<?= $host ?>", "<?= $service ?>");
                    },
                });
				console.log("AJAX done");
				
				var elem = $('#dashletcontainer-<?= $id ?>');
				
			
				setTimeout(() => {
					// window.location.reload();
				}, "<?= get_dashlet_refresh_rate(60, "perfdata_chart") ?>");
				
            </script>
            <?php
            $output .= ob_get_clean();
            break;

        case DASHLET_MODE_PREVIEW:
            
            if(!is_neptune()) {
                $output="<img src='".get_dashlet_url_base("linear_gauge")."/linear_gauge1.png' alt='No Preview Available' width='50%'/>";
            } else if (get_theme() == "neptunelight") {
                $output="<img src='".get_dashlet_url_base("linear_gauge")."/linear_gauge_neptune_light_preview1.png' alt='No Preview Available' width='50%'/>";
            } else {
                $output="<img src='".get_dashlet_url_base("linear_gauge")."/neptune_preview1.png' alt='No Preview Available' width='50%'/>";
            }
            break;    
        }
        
    return $output;
}

function insert_linear_gauge_html($id) {
    ?>
    <div class="linear_gauge_dashlet_<?= $id ?>">
        <div class="gaugetext">
		<h2 id="serviceName">Service Name</h2>
		<p id="value">0<span></span></p>  
        </div>
        <div class="gauge-container">
            <svg class="linear_gauge_svg">
                <!-- Background Rectangles -->
                <rect class="gauge-section critical" width="33.33%" height="100%" x="0" y="0"></rect>
                <rect class="gauge-section warning" width="33.33%" height="100%" x="33.33%" y="0"></rect>
                <rect class="gauge-section ok" width="33.34%" height="100%" x="66.66%" y="0"></rect>
                <!-- Pointer  : <polygon class="gauge-pointer" points="5,0 0,20 10,20" transform="translate(0, 0)"></polygon>-->
            </svg>
			<div class="gauge-pointer"></div>
            <div class="gauge-labels">
                <span>Critical</span>
                <span>Warning</span>
                <span>OK</span>
            </div>
        </div>
    </div>

    <style>
				/* Container for the linear gauge */
		.linear_gauge_dashlet_<?= $id ?> {
		position: relative;
		width: 100%;
		max-width: 400px;
		margin: 0 auto;
		font-family: Arial, sans-serif;
		}

		/* Text styling */
		.linear_gauge_dashlet_<?= $id ?> .gaugetext {
			display: flex;
			flex-direction: column; /* Stack elements vertically */
			justify-content: center;  /* Centers horizontally */
			align-items: center;      /* Centers vertically */
			text-align: center;       /* Ensures text alignment */
			width: 100%;              /* Ensures it spans the entire container */
		}

		/* Servicename (h2) styling */
		.linear_gauge_dashlet_<?= $id ?> .gaugetext h2 {
			font-size: 24px; /* Increased font size for emphasis */
			font-weight: bold;
			color: #555;
			margin: 0 auto;  /* Ensures it doesn't shift */
		
		}

		/* value (p) styling */
		.linear_gauge_dashlet_<?= $id ?> .gaugetext p {
			font-size: 18px; /* Adjusted font size */
			font-weight:bold;
			color: #666;
			margin: 5px 0 0 0; /* Add some spacing above the service name */
		}

		/* Gauge container */
		.linear_gauge_dashlet_<?= $id ?> .gauge-container {
		position: relative;
		width: 100%;
		height: 40px;
		}

		/* SVG container */
		.linear_gauge_dashlet_<?= $id ?> .linear_gauge_svg {
		display: block;
		width: 100%;
		height: 100%;
		border-radius: 8px;
		overflow: hidden;
		border: 1px solid #ccc;
		}

		/* Gauge sections */
		.linear_gauge_dashlet_<?= $id ?> .gauge-section {
		height: 100%;
		}

		.linear_gauge_dashlet_<?= $id ?> .gauge-section.critical {
		fill:rgb(255, 17, 0);
		}

		.linear_gauge_dashlet_<?= $id ?> .gauge-section.warning {
		fill:rgb(255, 196, 0);
		}

		.linear_gauge_dashlet_<?= $id ?> .gauge-section.ok {
		fill:rgba(9, 255, 0, 0.96);
		}

		/* Pointer styling */
		.linear_gauge_dashlet_<?= $id ?> .gauge-pointer {
			position: absolute;
            top: -10px; /* Position above the bar */
            left: 0;
            width: 20px;
            height: 20px;
            background-color: #666;
            clip-path: polygon(50% 100%, 0% 0%, 100% 0%); /* Triangle shape */
            transform: translateX(-50%); /* Center the pointer */
            transition: left 0.3s ease-in-out; /* Smooth transition for pointer movement */
		}

		/* Position the pointer correctly */
		.linear_gauge_dashlet_<?= $id ?> .gauge-container {
		position: relative;
		}


		/* Labels */
		.linear_gauge_dashlet_<?= $id ?> .gauge-labels {
		display: flex;
		justify-content: space-between;
		margin-top: 5px;
		font-size: 18px;
		font-weight:bold;
		color: #555;
		}

		/* Dimmed colors for unused zones */
		.linear_gauge_dashlet_<?= $id ?> .gauge-section.critical.dimmed {
			fill: rgba(182, 25, 13, 0.3); /* Dimmed critical color */
		}

		.linear_gauge_dashlet_<?= $id ?> .gauge-section.warning.dimmed {
			fill: rgba(172, 138, 28, 0.3); /* Dimmed warning color */
		}

		.linear_gauge_dashlet_<?= $id ?> .gauge-section.ok.dimmed {
			fill: rgba(33, 206, 39, 0.3); /* Dimmed OK color */
		}
    </style>
    <?php
}
