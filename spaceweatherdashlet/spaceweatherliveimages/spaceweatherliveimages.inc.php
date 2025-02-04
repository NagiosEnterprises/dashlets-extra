<?php
//
// Space Weather Live Images Dashlet
// Copyright (c) 2008-2018 Nagios Enterprises, LLC. All rights reserved.
//

include_once(dirname(__FILE__) . '/../dashlethelper.inc.php');

// Run the initialization function
spaceweatherliveimages_dashlet_init();

function spaceweatherliveimages_dashlet_init()
{
    $name = "spaceweatherliveimages";

    $args = array(
        DASHLET_NAME => $name,
        DASHLET_VERSION => "1.0.0",
        DASHLET_AUTHOR => "Nagios Enterprises, LLC",
        DASHLET_DESCRIPTION => _("Shows various space weather images from SpaceWeatherLive.com and the NOAA."),
        DASHLET_COPYRIGHT => "Dashlet Copyright &copy; 2025 Nagios Enterprises.",
        DASHLET_LICENSE => "MIT",
        DASHLET_HOMEPAGE => "https://www.nagios.com",
        DASHLET_FUNCTION => "spaceweatherliveimages_dashlet_func",
        DASHLET_TITLE => _("Space Weather Live Images"),
        DASHLET_OUTBOARD_CLASS => "spaceweatherliveimages_outboardclass",
        DASHLET_INBOARD_CLASS => "spaceweatherliveimages_inboardclass",
        DASHLET_PREVIEW_CLASS => "spaceweatherliveimages_previewclass",
        DASHLET_WIDTH => "300",
        DASHLET_HEIGHT => "300",
        DASHLET_OPACITY => "1.0",
        DASHLET_BACKGROUND => "",
    );

    register_dashlet($name, $args);
}

/**
 * @param string $mode
 * @param string $id
 * @param null   $args
 *
 * @return string
 */
function spaceweatherliveimages_dashlet_func($mode = DASHLET_MODE_PREVIEW, $id = "", $args = null)
{
    $output = "";
    $imgurls = array(
        "HMII" => "https://www.spaceweatherlive.com/images/SDO/SDO_HMIIF_512.jpg",
        "southernaurora" => "https://services.swpc.noaa.gov/images/animations/ovation/south/latest.jpg",
        "northernaurora" => "https://services.swpc.noaa.gov/images/animations/ovation/north/latest.jpg",
        "coronalhole" => "https://www.spaceweatherlive.com/images/SDO/latest_CHmap.jpg",
        "coronagraph" => "https://sohowww.nascom.nasa.gov/data/realtime/c3/512/latest.jpg",
        "thematicmap" => "https://services.swpc.noaa.gov/images/animations/suvi/secondary/map/latest.png",
        "HMIM" => "https://www.spaceweatherlive.com/images/SDO/SDO_HMI-Magnetogram_512.jpg",
        "HMI171" => "https://sdo.gsfc.nasa.gov/assets/img/latest/f_HMImag_171_256.jpg",
        "AIA094" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0094.jpg",
        "AIA131" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0131.jpg",
        "AIA171" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0171.jpg",
        "AIA193" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0193.jpg",
        "AIA211" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0211.jpg",
        "AIA304" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0304.jpg",
        "AIA335" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_0335.jpg",
        "AIA1600" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_1600.jpg",
        "AIA1700" => "https://sdo.gsfc.nasa.gov/assets/img/latest/latest_256_1700.jpg",
        "AIA094335193" => "https://sdo.gsfc.nasa.gov/assets/img/latest/f_094_335_193_256.jpg"
    );

    $selected_image = isset($args['image']) ? $args['image'] : 'HMII';
    $imgurl = isset($imgurls[$selected_image]) ? $imgurls[$selected_image] : $imgurls['HMII'];

    switch ($mode) {
        case DASHLET_MODE_GETCONFIGHTML:
            $output = '
            <div class="form-group">
                <label for="image">'._('Select Image').'</label>
                <div>
                    <select name="image" class="form-control" id="image">
                        <option value="northernaurora">'._('Northern Aurora').'</option>
                        <option value="southernaurora">'._('Southern Aurora').'</option>
                        <option value="coronalhole">'._('Coronal Hole').'</option>
                        <option value="coronagraph">'._('Lasco C3 Coronagraph').'</option>
                        <option value="thematicmap">'._('Thematic Map').'</option>
                        <option value="sunspots">'._('HMI Intensitygram').'</option>
                        <option value="HMIM">'._('HMI Magnetogram').'</option>
                        <option value="HMI171">'._('HMI Magnetogram AIA 171').'</option>
                        <option value="AIA094">'._('SDO AIA 094').'</option>
                        <option value="AIA131">'._('SDO AIA 131').'</option>
                        <option value="AIA171">'._('SDO AIA 171').'</option>
                        <option value="AIA193">'._('SDO AIA 193').'</option>
                        <option value="AIA211">'._('SDO AIA 211').'</option>
                        <option value="AIA304">'._('SDO AIA 304').'</option>
                        <option value="AIA335">'._('SDO AIA 335').'</option>
                        <option value="AIA1600">'._('SDO AIA 1600').'</option>
                        <option value="AIA1700">'._('SDO AIA 1700').'</option>
                        <option value="AIA094335193">'._('SDO AIA 094 335 193').'</option>


                    </select>
                </div>
            </div>';
            break;

        case DASHLET_MODE_OUTBOARD:
        case DASHLET_MODE_INBOARD:
            $output = '
            <div style="width: 100%; height: 100%;">
                <img src="'.$imgurl.'" alt="Space Weather Image" style="width: 100%; height: 100%; object-fit: contain;">
            </div>';
            break;

        case DASHLET_MODE_PREVIEW:
            $output = "<p><img src='" . $imgurl . "' alt='Space Weather Image' style='width: 100%; height: 100%; object-fit: contain;'></p>";
            break;
    }

    return $output;
}
