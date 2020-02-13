<?php

/**
 * Aggregates the info of a single CSV (with answers from a single form with many categories) into a CSV
 * that can be used as data source for a report.
 * 
 * Author: Fernando Bevilacqua <fernando.bevilacqua@uffs.edu.br>
 * Date: 2020-02-13
 */

require_once dirname(__FILE__) . '/common.php';

$aOptions = array(
    "input-file:",
    "output-file:",
    "help"
);

$aArgs = getopt("h", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help'])) {
    echo "Usage: \n";
    echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
    echo "Options:\n";
    echo " --input-file=<path>   Path to the file containing the raw CSV file.\n";
    echo " --output-file=<path>  Path to the file where results will be written\n";
    echo " --help, -h            Show this help.\n";
    echo "\n";
    exit(1);
}

$aInputFilePath = isset($aArgs['input-file']) ? $aArgs['input-file'] : dirname(__FILE__).'/../../data/2019.2-program/raw.csv';

$aOutputDirPath = dirname($aInputFilePath);
$aOutputFilePath = isset($aArgs['output-file']) ? $aArgs['output-file'] : $aOutputDirPath.'/from-json.csv';


if($aInputFilePath == false || !file_exists($aInputFilePath)) {
    echo 'Unable to access input file: ' . $aInputFilePath . "\n";
    exit(2);
}

if(!file_exists($aOutputDirPath)) {
    echo 'Unable to access output folder: ' . $aOutputDirPath . "\n";
    exit(3);
}

$aCollectedForms = array();
$aCollectedQuestions = array();
$aEntries = array();
$aRawData = load_csv($aInputFilePath);

if($aRawData === false) {
    echo 'Unable to load input file: ' . $aInputFilePath . "\n";
    exit(3);
}

foreach($aRawData as $aRawIndex => $aRawEntry) {
    $aQuestionNumber = 1;

    foreach($aRawEntry as $aQuestionTitle => $aResponse) {
        if($aQuestionTitle == 'Timestamp') {
            continue;
        }
        $aEntry = array(
            'respondent' =>  $aRawIndex,
            'formTitle' =>  'Avaliação do Curso (2019/02)', // TODO: get name from cmd?
            'formId' =>  '1Q_xaCbApPXgrq3c_Vux8ERL4coCFqzwWfFMxtWrty60', // TODO: get from id from cmd?
            'questionNumber' =>  $aQuestionNumber,
            'questionTitle' =>  $aQuestionTitle,
            'response' =>  $aResponse
        );

        $aEntries[] = $aEntry;

        // Keep track of all forms that we collected to create a manifest file
        $aFormId = $aEntry['formId'];
        if(!isset($aCollectedForms[$aFormId])) {
            $aCollectedForms[$aFormId] = $aEntry['formTitle'];
        }

        if(!isset($aCollectedQuestions[$aQuestionNumber])) {
            // We haven't seen this question yet.
            $aCollectedQuestions[$aQuestionNumber] = $aQuestionTitle;
        }

        $aQuestionNumber++;
    }
}

$aOutputFile = fopen($aOutputFilePath, 'w');

if($aOutputFile === false) {
    echo 'Unable to open file ' . $aOutputFile . "\n";
    exit(5);
}

if(count($aEntries) == 0) {
    echo 'No data has been collected. Anything wrong with --input-dir?' . "\n";
    exit(6);
}

// TODO: improve this?
$aColumns = array_keys($aEntries[0]);
$aHeader = array();

foreach($aColumns as $aColumnName) {
    // Convert CamelCase to snake_case in column names. From: https://stackoverflow.com/a/56560603/29827
    $aSnakeCaseColumnName = camelcase_to_snakecase($aColumnName);
    $aHeader[] = $aSnakeCaseColumnName;
}

fputcsv($aOutputFile, $aHeader);

foreach($aEntries as $aEntry) {
    $aValues = array_values($aEntry);
    fputcsv($aOutputFile, $aValues);

    // Keep track of all forms that we collected to create a manifest file
    $aFormId = $aEntry['formId'];
    if(!isset($aCollectedForms[$aFormId])) {
        $aCollectedForms[$aFormId] = $aEntry['formTitle'];
    }
}


fclose($aOutputFile);

// Generate secondary files
create_manifest_file($aOutputFilePath . '.manifest.csv', $aCollectedForms);
create_questions_file($aOutputFilePath . '.questions.csv', $aCollectedQuestions);

echo "\n";
echo 'All done!' . "\n";