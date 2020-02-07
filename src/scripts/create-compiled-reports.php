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

$aManifestPersons = array();

foreach($aManifest as $aKey => $aEntry) {
    $aName = $aEntry['course_responsible'];

    if(!isset($aManifestPersons[$aName])) {
        $aManifestPersons[$aName] = array(
            'name' => $aName,
            'type' => 'individual'
        );
    }
}

$aNames = array_keys($aManifestPersons);

echo 'Persons found in the manifest ('.count($aNames).' in total):' . "\n";
echo '- ' . implode("\n- ", $aNames);
echo "\n\n";

echo 'Generating charts...' . "\n";

foreach($aManifestPersons as $aEntry) {
    $aPerson = $aEntry['name'];
    $aNormalizedName = strtolower(str_replace(' ', '_', $aPerson));
    $aPersonDir = $aOutputDirPath . '/' . $aNormalizedName;
    $aPersonChartsDir = $aPersonDir . '/charts/';

    if(!empty($aFilter) && stripos($aPerson, $aFilter) === false) {
        continue;
    }

    echo "\n* " . $aPerson . "\n";

    @mkdir($aPersonChartsDir, 0777, true);
    
    $aOutput = array();
    $aReturnVar = -1;
    $aCmd = 'cd "'.$aDirRScripts.'" && rscript "'.$aCreateReportCmd.'" --dataset="'.$aDatasetFilePath.'" --dataset-manifest="'.$aManifestFilePath.'" --filter="'.$aPerson.'" --output-dir="'.$aPersonChartsDir.'" --type="'.$aEntry['type'].'"';
    
    @exec($aCmd, $aOutput, $aReturnVar);
    echo '    ' . implode("\n    ", $aOutput);

    if($aReturnVar != 0) {
        echo '[WARN] Failed to generate report!' . "\n";
    }
}

echo 'Generating latex report files...' . "\n";
// TODO: make this

echo 'Compiling latex reports...' . "\n";
// TODO: make this

echo "\n";
echo 'All done!' . "\n";