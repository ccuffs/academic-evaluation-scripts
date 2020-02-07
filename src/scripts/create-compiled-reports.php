<?php

/**
 * Creates Latex-powered reports using the aggregated data from all forms.
 * 
 * Author: Fernando Bevilacqua <fernando.bevilacqua@uffs.edu.br>
 * Date: 2020-02-07
 */

require_once dirname(__FILE__) . '/common.php';

$aOptions = array(
    "dataset:",
    "dataset-manifest:",
    "output-dir:",
    "help"
);

$aArgs = getopt("h", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help'])) {
    echo "Usage: \n";
    echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
    echo "Options:\n";
    echo " --dataset=<path>          Path to the CSV file with all answers data.\n";
    echo " --dataset-manifes=<path>  Path to the CSV file with answers manifest.\n";
    echo " --filter=<str>            Name of the course responsible to filter data.\n";
    echo " --output-dir=<path>       Path to the directory where reports will be written.\n";
    echo " --help, -h                Show this help.\n";
    echo "\n";
    exit(1);
}

$aDatasetFilePath = isset($aArgs['dataset']) ? $aArgs['dataset'] : dirname(__FILE__).'/../../data/2019/from-json.csv';
$aManifestFilePath = isset($aArgs['dataset-manifest']) ? $aArgs['dataset-manifest'] : dirname(__FILE__).'/../../data/2019/from-json.csv.manifest.csv';
$aFilter = isset($aArgs['filter']) ? $aArgs['filter'] : '';
$aOutputDirPath = isset($aArgs['output-dir']) ? $aArgs['output-dir'] : dirname(__FILE__).'/../../results/2019/';

if($aOutputDirPath == false || !file_exists($aOutputDirPath)) {
    echo 'Unable to access output folder: ' . $aOutputDirPath . "\n";
    exit(2);
}

if(!file_exists($aDatasetFilePath)) {
    echo 'Unable to access dataset file: ' . $aDatasetFilePath . "\n";
    exit(3);
}

if(!file_exists($aManifestFilePath)) {
    echo 'Unable to access dataset manifest file: ' . $aManifestFilePath . "\n";
    exit(4);
}

$aDirRScripts = dirname(__FILE__).'/../report/r';
$aCreateReportCmd = 'create-report.r';
$aManifest = load_csv($aManifestFilePath);
$aCurrentDir = getcwd();

$aManifestMeta = [
    'persons'    => find_unique_manifest_entries($aManifest, 'course_responsible', array('type' => 'individual')),
    'modalities' => find_unique_manifest_entries($aManifest, 'course_modality', array('type' => 'group')),
    'periods'    => find_unique_manifest_entries($aManifest, 'course_period', array('type' => 'group')),
    'program'    => array('cs_program' => array('name' => 'CS Program', 'key' => 'cs_program', 'type' => 'group', 'filter' => ''))
];

echo 'Report info:'. "\n";
echo ' - Date: '. date(DATE_RFC2822). "\n";
echo ' - Dataset: '. $aDatasetFilePath. "\n";
echo ' - Manifest: '. $aManifestFilePath. "\n";
echo ' - Filter: '. $aFilter. "\n";

echo "\n";

$aEntries = array();

foreach($aManifestMeta as $aMetaKey => $aMetaEntries) {
    $aNames = array_keys($aMetaEntries);
    echo 'Available '.$aMetaKey.' ('.count($aNames).' in total):' . "\n";
    
    foreach($aMetaEntries as $aKey => $aItem) {
        echo '- ' . $aItem['name'] . "\n"; 
    }
    echo "\n";

    $aEntries = array_merge($aEntries, $aMetaEntries);
}
 
echo 'Generating charts...' . "\n";

foreach($aEntries as $aEntry) {
    $aName = $aEntry['name'];
    $aKey = $aEntry['key'];
    $aDir = $aOutputDirPath . '/' . $aKey;
    $aChartsDir = $aDir . '/charts/';

    if(!empty($aFilter) && stripos($aName, $aFilter) === false) {
        continue;
    }

    echo "* " . $aName . "\n";

    @mkdir($aChartsDir, 0777, true);
    
    $aOutput = array();
    $aReturnVar = -1;
    $aCmd = 'cd "'.$aDirRScripts.'" && rscript "'.$aCreateReportCmd.'" --dataset="'.$aDatasetFilePath.'" --dataset-manifest="'.$aManifestFilePath.'" --filter="'.$aName.'" --output-dir="'.$aChartsDir.'" --type="'.$aEntry['type'].'"';
    
    @exec($aCmd, $aOutput, $aReturnVar);
    echo '    ' . implode("\n    ", $aOutput);

    if($aReturnVar != 0) {
        echo '[WARN] Failed to generate report!' . "\n";
    }

    echo "\n\n";
}

echo 'Generating latex report files...' . "\n";
// TODO: make this

echo 'Compiling latex reports...' . "\n";
// TODO: make this

echo "\n";
echo 'All done!' . "\n";