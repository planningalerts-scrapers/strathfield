<?php
# Strafield Council scraper
require 'scraperwiki.php';
require 'simple_html_dom.php';
date_default_timezone_set('Australia/Sydney');


###
### Main code start here
###
$url_base = "http://www.strathfield.nsw.gov.au";

$da_page = $url_base . "/development/development-applications/development-notifications/";
$comment_base = "http://www.strathfield.nsw.gov.au/council/customer-service/contact-us/";

$dom = file_get_html($da_page);

# Assume it is single page, the web site doesn't allow to select period like last month
$dataset  = $dom->find("li[class=link]");

# The usual, look for the data set and if needed, save it
foreach ($dataset as $record) {
    $dahtml = file_get_html($url_base . $record->find("a",0)->href);

    $council_reference = preg_replace('/\s+/', ' ', trim(html_entity_decode($dahtml->find("div[class=white-area] div[class=content] p",0)->plaintext))); 
    $council_reference = explode("DA Number: ", $council_reference, 2);
    $council_reference = $council_reference[1];

    $address           = preg_replace('/\s+/', ' ', trim(html_entity_decode($dahtml->find("div[class=white-area] div[class=content] p",3)->plaintext)));
    $address           = explode("Address: ", $address, 2);
    $address           = $address[1] . ", NSW, Australia";

    $description       = preg_replace('/\s+/', ' ', trim(html_entity_decode($dahtml->find("div[class=white-area] div[class=content] p",5)->plaintext)));
    $description       = explode("Description: ", $description, 2);
    $description       = $description[1];

    $info_url          = $url_base . $record->find("a",0)->href;

    $tempstr    = preg_replace('/\s+/', ' ', trim(html_entity_decode($dahtml->find("div[class=white-area] div[class=content] p",1)->plaintext)));
    $tempstr    = explode("Dates of public exhibition: ", $tempstr, 2);
    $tempstr    = explode(" to ", $tempstr[1], 2);

    $on_notice_from = $tempstr[0];
    $on_notice_from = explode('/', $on_notice_from);
    $on_notice_from = "$on_notice_from[2]-$on_notice_from[1]-$on_notice_from[0]";
    $on_notice_from = date('Y-m-d', strtotime($on_notice_from));

    $on_notice_to   = $tempstr[1];
    $on_notice_to   = explode('/', $on_notice_to);
    $on_notice_to   = "$on_notice_to[2]-$on_notice_to[1]-$on_notice_to[0]";
    $on_notice_to   = date('Y-m-d', strtotime($on_notice_to));

    # Put all information in an array
    $application = array (
        'council_reference' => $council_reference,
        'address'           => $address,
        'description'       => $description,
        'info_url'          => $info_url,
        'comment_url'       => $comment_base,
        'date_scraped'      => date('Y-m-d'),
        'on_notice_from'    => $on_notice_from,
        'on_notice_to'      => $on_notice_to
    );

    print ("Saving record " . $application['council_reference'] . "\n");
    # print_r ($application);
    scraperwiki::save(array('council_reference'), $application);
}


?>
