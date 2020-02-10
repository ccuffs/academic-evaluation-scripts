<?php

/**
 * Aggregates a into a single CSV file a set of JSON files created from Google Form answers.
 * 
 * Author: Fernando Bevilacqua <fernando.bevilacqua@uffs.edu.br>
 * Date: 2020-02-04
 */

require_once dirname(__FILE__) . '/common.php';

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

echo 'Checking input dir: ' . $aDataFolderPath . "\n";

$aCollectedForms = array();
$aCollectedQuestions = array();
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

if(count($aData) == 0) {
    echo 'No data has been collected. Anything wrong with --input-dir?' . "\n";
    exit(6);
}

$aTotalBatches = count($aData);
$aBatch = 1;

// TODO: improve this?
$aColumns = array_keys($aData[0][0]);
$aHeader = array();

foreach($aColumns as $aColumnName) {
    // Convert CamelCase to snake_case in column names. From: https://stackoverflow.com/a/56560603/29827
    $aSnakeCaseColumnName = camelcase_to_snakecase($aColumnName);
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

        // Keep track of all questions that we collected to create a questions file
        $aQuestionNumber = $aEntry['questionNumber'];
        $aQuestionTitle = $aEntry['questionTitle'];

        if(!isset($aCollectedQuestions[$aQuestionNumber])) {
            // We haven't seen this question yet.
            $aCollectedQuestions[$aQuestionNumber] = $aQuestionTitle;
        } else if($aCollectedQuestions[$aQuestionNumber] !== $aQuestionTitle) {
            // We have seen this question already, but it has a different content this time.
            echo '[WARN] Question #'.$aQuestionNumber.' found with different title!' . "\n";
            echo ' - Current: "'.$aCollectedQuestions[$aQuestionNumber].'"' . "\n";
            echo ' - New: "'.$aQuestionTitle.'"' . "\n";
        }
    }
    $aBatch++;
}

fclose($aOutputFile);

// Generate secondary files
create_manifest_file($aOutputFilePath . '.manifest.csv', $aCollectedForms);
create_questions_file($aOutputFilePath . '.questions.csv', $aCollectedQuestions);

echo "\n";
echo 'All done!' . "\n";