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
    "text-questions:",
    "help"
);

$aArgs = getopt("hv", $aOptions);

if(isset($aArgs['h']) || isset($aArgs['help'])) {
    echo "Usage: \n";
    echo " php ".basename($_SERVER['PHP_SELF']) . " [options]\n\n";
    echo "Options:\n";
    echo " --dataset=<path>            Path to the CSV file with all answers data.\n";
    echo " --dataset-manifest=<path>   Path to the CSV file with answers manifest.\n";
    echo " --dataset-questions=<path>  Path to the CSV file with existing questions.\n";
    echo " --text-questions=<str>      Comma-separated list of numbers that represent\n";
    echo "                             questions whose responses must be processed as text.\n";
    echo " --filter=<str>              Name of the course responsible to filter data.\n";
    echo " --output-dir=<path>         Path to the directory where reports will be written.\n";
    echo " --verbose, -v               Output more info during processing.\n";
    echo " --help, -h                  Show this help.\n";
    echo "\n";
    exit(1);
}

$aDatasetFilePath = isset($aArgs['dataset']) ? $aArgs['dataset'] : dirname(__FILE__).'/../../data/2019p/from-json.csv';
$aManifestFilePath = isset($aArgs['dataset-manifest']) ? $aArgs['dataset-manifest'] : dirname(__FILE__).'/../../data/2019p/from-json.csv.manifest.csv';
$aQuestionsFilePath = isset($aArgs['dataset-questions']) ? $aArgs['dataset-questions'] : dirname(__FILE__).'/../../data/2019p/from-json.csv.questions.csv';
$aFilter = isset($aArgs['filter']) ? $aArgs['filter'] : '';
$aTextModeQuestions = isset($aArgs['text-questions']) ? $aArgs['text-questions'] : '18'; // 31,39,53,54
$aOutputDirPath = isset($aArgs['output-dir']) ? $aArgs['output-dir'] : dirname(__FILE__).'/../../results/2019/';

$aVerbose = isset($aArgs['v']) || isset($aArgs['verbose']);

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

if(!file_exists($aQuestionsFilePath)) {
    echo 'Unable to access dataset questions file: ' . $aQuestionsFilePath . "\n";
    exit(5);
}

$aConfig = array(
    'include_comments' => false
);

config_init($aConfig);

$aDirLatexTemplate = dirname(__FILE__).'/../report/latex/charite-template';
$aDirRScripts = dirname(__FILE__).'/../report/r';
$aCreateReportCmd = 'create-report.r';
$aManifest = load_csv($aManifestFilePath);
$aQuestions = load_csv($aQuestionsFilePath);
$aCurrentDir = getcwd();

$aManifestMeta = [
    'persons'    => find_unique_manifest_entries($aManifest, 'course_responsible'),
    'modalities' => find_unique_manifest_entries($aManifest, 'course_modality'),
    'periods'    => find_unique_manifest_entries($aManifest, 'course_period'),
    'program'    => array('cs_program' => array('name' => 'Avaliação discente da Coordenação, Infra-estrutura e do Curso de Ciência da Computação', 'key' => 'CCCCH-2019.2-relatorio-avaliacao-coordenacao-infra-curso', 'filter' => ''))
];

echo 'Report info:'. "\n";
echo ' - Date: '. date(DATE_RFC2822). "\n";
echo ' - Dataset: '. $aDatasetFilePath. "\n";
echo ' - Manifest: '. $aManifestFilePath. "\n";
echo ' - Questions: '. $aQuestionsFilePath. "\n";
echo ' - Filter: '. $aFilter. "\n";
echo ' - Text questions: '. $aTextModeQuestions. "\n";

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

echo 'Generating files:' . "\n";

foreach($aEntries as $aEntry) {
    $aName = $aEntry['name'];
    $aKey = $aEntry['key'];
    $aFilter = !isset($aEntry['filter']) ? $aName : $aEntry['filter'];
    $aDir = $aOutputDirPath . '/' . $aKey;
    $aChartsDir = $aDir . '/charts/';

    if(!empty($aFilter) && stripos($aName, $aFilter) === false) {
        continue;
    }

    echo "* " . $aName . "\n";
    echo ' - Generating charts... ';

    @mkdir($aChartsDir, 0777, true);
    
    $aOutput = array();
    $aReturnVar = -1;
    $aCmd = 'cd "'.$aDirRScripts.'" && rscript "'.$aCreateReportCmd.'" --dataset="'.$aDatasetFilePath.'" --dataset-manifest="'.$aManifestFilePath.'" --dataset-questions="'.$aQuestionsFilePath.'" --text-questions="'.$aTextModeQuestions.'" --filter="'.$aFilter.'" --output-dir="'.$aChartsDir.'"';
    
    if($aVerbose) {
        echo "\n " . $aCmd . "\n";
    }
    
    exec($aCmd, $aOutput, $aReturnVar);
    
    if($aVerbose) {
        echo '  ' . implode("\n  ", $aOutput);
    }

    if($aReturnVar != 0) {
        echo '[FAIL]' . "\n";
        continue;
    } else {
        echo '[OK]' . "\n";
    }
    
    echo ' - Generating latex reports... ';
    $aOk = create_latex_report($aDirLatexTemplate, $aDir, $aEntry, $aManifest, $aQuestions, explode(',', $aTextModeQuestions));

    if(!$aOk) {
        echo '[FAIL]' . "\n";
        continue;
    } else {
        echo '[OK]' . "\n";
    }

    if($aVerbose) {
        echo "\n";
    }
}

echo 'All files were generated!' . "\n";
echo "\n";
echo 'Compiling latex reports:' . "\n";

foreach($aEntries as $aEntry) {
    $aName = $aEntry['name'];
    $aKey = $aEntry['key'];
    $aDir = $aOutputDirPath . '/' . $aKey;
    $aOutDir = $aOutputDirPath . '/REPORTS/';

    @mkdir($aOutDir, 0777, true);

    echo "* " . $aName;
    $aOk = compile_latex_report($aDir, $aOutDir, 'report', $aKey);

    echo ' ' . ($aOk ? '[OK]' : '[FAIL]');
    echo "\n";
}

echo 'All done!' . "\n";