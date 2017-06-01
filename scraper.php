<?php
# Strafield Council scraper
require_once 'vendor/autoload.php';
require_once 'vendor/openaustralia/scraperwiki/scraperwiki.php';

use PGuardiario\PGBrowser;
use Sunra\PhpSimple\HtmlDomParser;

date_default_timezone_set('Australia/Sydney');

# Default to 'thisweek', use MORPH_PERIOD to change to 'thismonth' or 'lastmonth' for data recovery
switch(getenv('MORPH_PERIOD')) {
    case 'thismonth' :
        $period = 'thismonth';
        break;
    case 'lastmonth' :
        $period = 'lastmonth';
        break;
    default          :
        $period = 'thisweek';
        break;
}
print "Getting '" .$period. "' data, changable via MORPH_PERIOD environment\n";

###
### Main code start here
###
$url_base = "http://daenquiry.strathfield.nsw.gov.au";
$da_page = $url_base. "/Pages/XC.Track/SearchApplication.aspx?d=" .$period. "&k=LodgementDate&t=";

$comment_base = "mailto:council@strathfield.nsw.gov.au";

# Agreed Terms
$browser = new PGBrowser();
$page = $browser->get($url_base . "/Common/Common/terms.aspx");
$form = $page->form();
$form->set('ctl00$ctMain$chkAgree$chk1', 'on');
$form->set('ctl00$ctMain$BtnAgree', 'I Agree');
$page = $form->submit();

# Fetch DA Page
$page = $browser->get($da_page);
$dom = HtmlDomParser::str_get_html($page->html);
$applications = $dom->find("div[class=result]");

# The usual, look for the data set and if needed, save it
foreach ($applications as $application) {
    $description       = $application->find("div", 0)->plaintext;
    $description       = preg_split("/\\r\\n|\\r|\\n/", $description);
    $description       = preg_replace('/\s+/', ' ', $description[1]);
    $description       = ucwords(strtolower(trim($description)));

    $info_url          = $application->find("a", 0)->href;
    $info_url          = str_replace("../..", "", $info_url);
    $info_url          = $url_base . $info_url;

    $date_received     = explode("Lodged:", $application->find("div", 0)->plaintext);
    if (preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/", $date_received[1], $matches)) {
        if (checkdate($matches[2], $matches[1], $matches[3])) {
            $date_received =  "$matches[3]-$matches[2]-$matches[1]";
        }
    } else {
        $date_received = null;
    }

    # Put all information in an array
    $record = [
        'council_reference' => trim($application->find("a", 0)->plaintext),
        'address'           => trim($application->find("strong", 0)->plaintext),
        'description'       => $description,
        'info_url'          => $info_url,
        'comment_url'       => $comment_base,
        'date_scraped'      => date('Y-m-d'),
        'date_received'     => $date_received
    ];

    # Check if record exist, if not, INSERT, else do nothing
    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $record['council_reference'] . "'");
    if ((count($existingRecords) == 0) && ($record['council_reference'] !== 'Not on file')) {
        print ("Saving record " . $record['council_reference'] . " - " . $record['address'] . "\n");
//         print_r ($record);
        scraperwiki::save(['council_reference'], $record);
    } else {
        print ("Skipping already saved record or ignore corrupted data - " . $record['council_reference'] . "\n");
    }
}

