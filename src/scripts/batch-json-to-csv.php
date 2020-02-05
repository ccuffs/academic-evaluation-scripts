<?php

/**
 * Aggregates a into a single CSV file a set of JSON files created from Google Form answers.
 * 
 * Author: Fernando Bevilacqua <fernando.bevilacqua@uffs.edu.br>
 * Date: 2020-02-04
 */

$aOptions = array(
    "input-dir:",
    "output-file:",
    "help"
);

$aArgs = getopt("h", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help'])) {
    echo "Usage: \n";
    echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
    echo "Options:\n";
    echo " --input-dir=<path>      Path to the folder containing the JSON files.\n";
    echo " --output-file=<path>    Path to the file where results will be written\n";
    echo " --help, -h              Show this help.\n";
    echo "\n";
    exit(1);
}

$aDataFolderPath = isset($aArgs['input-dir']) ? $aArgs['input-dir'] : dirname(__FILE__).'/../../data/';
$aOutputFilePath = isset($aArgs['output-file']) ? $aArgs['output-file'] : $aDataFolderPath.'/from-json.csv';
$aOutputDirPath = dirname($aOutputFilePath);

if($aDataFolderPath == false || !file_exists($aDataFolderPath)) {
    echo 'Unable to access data folder: ' . $aDataFolderPath . "\n";
    exit(2);
}

if(!file_exists($aOutputDirPath)) {
    echo 'Unable to access output folder: ' . $aOutputDirPath . "\n";
    exit(3);
}

echo 'Collecting files at: ' . $aDataFolderPath . "\n";

$aCollectedForms = array();
$aData = array();

if($aHandle = opendir($aDataFolderPath)) {
    while(($aEntry = readdir($aHandle)) !== false) {
        if ($aEntry == '.' || $aEntry == '..' || stripos($aEntry, '.json') === false) {
            continue;
        }
        
        echo ' ' . $aEntry . "\t";

        $aEntryPath = $aDataFolderPath . '/' . $aEntry;
        $aFileContent = @file_get_contents($aEntryPath);

        if($aFileContent === false) {
            echo 'Unable to open file "'.$aEntryPath.'".' . "\n";
            exit(4);
        }

        $aParsedData = json_decode($aFileContent, TRUE);
        
        if($aParsedData === NULL) {
            echo '[ERROR]';
        } else {
            echo '[OK]';
            $aData[] = $aParsedData;
        }

        echo "\n";
    }

    closedir($aHandle);
}

$aOutputFile = fopen($aOutputFilePath, 'w');

if($aOutputFile === false) {
    echo 'Unable to open file ' . $aOutputFile . "\n";
    exit(5);
}

$aTotalBatches = count($aData);
$aBatch = 1;

// TODO: improve this?
$aColumns = array_keys($aData[0][0]);
$aHeader = array();

foreach($aColumns as $aColumnName) {
    // Convert CamelCase to snake_case in column names. From: https://stackoverflow.com/a/56560603/29827
    $aSnakeCaseColumnName = strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $aColumnName));
    $aHeader[] = $aSnakeCaseColumnName;
}

fputcsv($aOutputFile, $aHeader);

foreach($aData as $aEntries) {
    echo sprintf("Writing output file... %3.1f%%\r", (float)$aBatch / $aTotalBatches * 100);
    foreach($aEntries as $aEntry) {
        $aValues = array_values($aEntry);
        fputcsv($aOutputFile, $aValues);

        // Keep track of all forms that we collected to create a manifest file
        $aFormId = $aEntry['formId'];
        if(!isset($aCollectedForms[$aFormId])) {
            $aCollectedForms[$aFormId] = $aEntry['formTitle'];
        }
    }
    $aBatch++;
}

fclose($aOutputFile);

// Generate a manifest file

$aMeta = array();
$aMetaRegex = '/\[(.*)\].*: ([A-Z]{3}[0-9]{3}) -(.*)- ([0-9].*) Fase -?(.*) \((.*)\)/mi';

foreach($aCollectedForms as $aFormId => $aFormTitle) {
    preg_match_all($aMetaRegex, $aFormTitle, $aMatches, PREG_SET_ORDER, 0);

    if(count($aMatches) == 0) {
        echo '[WARN] Problem getting meta data from ' . $aFormTitle . "\n";
        continue;
    }

    $aMeta[$aFormId] = array(
        'season'             => $aMatches[0][1],
        'course_id'          => $aMatches[0][2],
        'course_name'        => trim($aMatches[0][3]),
        'course_period'      => trim($aMatches[0][4]),
        'course_modality'    => trim($aMatches[0][5]),
        'course_responsible' => $aMatches[0][6]
    );

    // Get rid of anything wrong regarding names
    $aMeta[$aFormId]['course_period'] = trim(str_replace(' -', '', $aMeta[$aFormId]['course_period']));
}

$aHasProducedHeader = false;
$aManifestFilePath = $aOutputFilePath . '.manifest.csv';
$aManifestFile = fopen($aManifestFilePath, 'w');

if($aManifestFile === false) {
    echo 'Unable to open file ' . $aManifestFilePath . "\n";
    exit(7);
}

foreach($aMeta as $aFormId => $aFormMeta) {
    if(!$aHasProducedHeader) {
        $aHasProducedHeader = true;
        fputcsv($aManifestFile, array_keys($aFormMeta));
    }
    fputcsv($aManifestFile, array_values($aFormMeta));
}

fclose($aManifestFile);

echo "\n";
echo 'All done!' . "\n";