<?php

function create_manifest_file($theFilePath, $theCollectedForms) {
    $aMeta = array();
    $aMetaRegex = '/\[(.*)\].*: ([A-Z]{3}[0-9]{3}) -(.*)- ([0-9].*) Fase -?(.*) \((.*)\)/mi';

    foreach($theCollectedForms as $aFormId => $aFormTitle) {
        preg_match_all($aMetaRegex, $aFormTitle, $aMatches, PREG_SET_ORDER, 0);

        if(count($aMatches) == 0) {
            echo '[WARN] Problem getting meta data from ' . $aFormTitle . "\n";
            continue;
        }

        $aMeta[$aFormId] = array(
            'form_id'            => $aFormId,
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

    write_csv($theFilePath, $aMeta);
}

function create_questions_file($theFilePath, $aCollectedQuestions) {
    $aMeta = array();
    
    foreach($aCollectedQuestions as $aNumber => $aTitle) {
        $aMeta[] = array(
            'question_number' => $aNumber,
            'question_title' => $aTitle
        );
    }

    write_csv($theFilePath, $aMeta);
}


// Convert CamelCase to snake_case in column names. From: https://stackoverflow.com/a/56560603/29827
function camelcase_to_snakecase($theString) {
    $aNewString = strtolower(preg_replace("/([a-z])([A-Z])/", "$1_$2", $theString));
    return $aNewString;
} 

function load_csv($theFilePath) {
    if(!file_exists($theFilePath)) {
        return false;
    }

    $aLines = array();
    $aHeader = array();
    $aHandle = fopen($theFilePath, 'r');

    while (($aData = fgetcsv($aHandle)) !== false) {
        if(count($aHeader) == 0) {
            $aHeader = $aData;
            continue;
        }

        $aLine = array();

        foreach($aData as $aIndex => $aContent) {
            $aColumnName = $aHeader[$aIndex];
            $aLine[$aColumnName] = iconv(mb_detect_encoding($aContent, mb_detect_order(), true), "UTF-8", $aContent);
        }

        $aLines[] = $aLine;
    }

    return $aLines;
}

function write_csv($theFilePath, $theArray) {
    $aHasProducedHeader = false;
    $aFile = fopen($theFilePath, 'w');

    if($aFile === false) {
        echo 'Unable to open file ' . $theFilePath . "\n";
        exit(7);
    }

    foreach($theArray as $aKey => $aLine) {
        if(!$aHasProducedHeader) {
            $aHasProducedHeader = true;
            fputcsv($aFile, array_keys($aLine));
        }
        fputcsv($aFile, array_values($aLine));
    }

    fclose($aFile);
}

function find_unique_manifest_entries($theManifestArray, $theField = 'course_responsible', $aFieldsAdd = array())  {
    $aUniqueEntries = array();

    foreach($theManifestArray as $aKey => $aEntry) {
        $aName = $aEntry[$theField];
        $aKey = strtolower(remove_accents(str_replace(' ', '_', $aName)));
    
        if(!isset($aUniqueEntries[$aKey])) {
            $aUniqueEntries[$aKey] = array(
                'name' => $aName,
                'key' => $aKey
            );

            foreach($aFieldsAdd as $aAddKey => $aAddValue) {
                $aUniqueEntries[$aKey][$aAddKey] = $aAddValue;
            }
        }
    }

    return $aUniqueEntries;
}

function create_latex_report($theLatexTemplate, $theWorkDir, $theEntry, $theManifest, $theQuestions) {
    if(!file_exists($theLatexTemplate)) {
        return false;
    }

    $aOk = xcopy($theLatexTemplate, $theWorkDir);

    $aReportFilePath = $theWorkDir . '/report.tex';
    $aReport = file_get_contents($aReportFilePath);

    $aChapterIntro = latex_report_chapter_intro($theWorkDir, $theEntry, $theManifest, $theQuestions);
    $aChapterAvaliation = latex_report_chapter_avaliation($theWorkDir, $theEntry, $theManifest, $theQuestions);

    $aContent = '';

    $aContent .= '\\chapter{Introdução}' . "\n";
    $aContent .= $aChapterIntro . "\n";
    
    $aContent .= '\\chapter{Avaliação dos Componentes Curriculares}' . "\n";
    $aContent .= $aChapterAvaliation . "\n";

    $aCharts = find_files($theWorkDir . '/charts', true);
    
    foreach($aCharts as $aFormId) {
        $aChartInfo = get_manifest_entry_by_formid($aFormId, $theManifest);

        $aContent .= '\\onecolumn' . "\n";

        if($aChartInfo == false) {
            $aContent .= '\\chapter{Avaliação Geral}' . "\n";
            $aContent .= '\\vspace*{5mm}' . "\n";
            $aContent .= '\\section{'.$theEntry['name'].'}' . "\n";
            $aContent .= "Essa seção apresenta uma compilação de todas as respostas dadas pelos discentes, agrupadas por pergunta, independente de CCR.\n";
        } else {
            $aContent .= '\\vspace*{5mm}' . "\n";            
            $aContent .= '\\section{'.$aChartInfo['course_name'].'}' . "\n";
            $aContent .= "Essa seção apresenta a avaliação do CCR \\emph{".$aChartInfo['course_name']."}, da ".$aChartInfo['course_period']." fase ".$aChartInfo['course_modality'].", sob responsabilidade de ".$aChartInfo['course_responsible'].", ministrado em ".$aChartInfo['season'].".\n";
        }

        $aContent .= '\\twocolumn' . "\n";

        foreach($theQuestions as $aQuestionInfo) {
            $aQuestionNumber = $aQuestionInfo['question_number'];
            $aQuestionTitle = $aQuestionInfo['question_title'];

            if($aQuestionNumber == 18) {
                continue;
            }

            $aCaption = '';
            $aBaseFilePath = 'charts/' . $aFormId . '/' . $aQuestionNumber . '.pdf';
            $aRealFilePath = $theWorkDir . '/' . $aBaseFilePath;

            if(file_exists($aRealFilePath)) {
                if($aChartInfo == false) {
                    $aCaption = $aQuestionTitle;
                } else {
                    $aCaption = $aChartInfo['course_name'] . ' ('.$aChartInfo['course_period'].' Fase '.$aChartInfo['course_modality'].') - ' . $aQuestionTitle;
                }

                $aContent .= '\\begin{figure}' . "\n";
                $aContent .= '\\centering' . "\n";
                $aContent .= '\\includegraphics[width=0.5\\textwidth]{'.$aBaseFilePath.'}' . "\n";
                $aContent .= '\\caption{'.$aCaption.'}' . "\n";
                $aContent .= '\\end{figure}' . "\n";
                $aContent .= "\n";
            }
        }

        $aContent .= '\\onecolumn' . "\n";

        $aContent .= '\\vspace*{5mm}' . "\n";            
        $aContent .= '\\subsection{Críticas e sugestões}' . "\n";
        $aContent .= "Essa seção apresenta os textos informados pelos discentes referentes a críticas e sugestões. Há um gráfico e uma nuvem de palavras mostrando os termos mais frequentes mencionados pelos discentes. É importante mencionar que os discentes não foram obrigados a prover críticas ou sugestões de forma escrita. Por essa razão, o número de respondentes pode ser ainda menor.\n";

        foreach($theQuestions as $aQuestionInfo) {
            $aQuestionNumber = $aQuestionInfo['question_number'];
            $aQuestionTitle = $aQuestionInfo['question_title'];
            
            if($aQuestionNumber != 18) {
                continue;
            }

            $aBaseQuestionChartFile = 'charts/' . $aFormId . '/' . $aQuestionNumber;

            $aQuestionCharts = array(
                $aBaseQuestionChartFile . '.pdf',
                $aBaseQuestionChartFile . '-a.pdf',
            );
            
            $aCaption = '';
                
            foreach($aQuestionCharts as $aEntry) {
                $aFilePath = $theWorkDir . '/' . $aEntry;

                if(file_exists($aFilePath)) {
                    if($aChartInfo == false) {
                        $aCaption = $aQuestionTitle;
                    } else {
                        $aCaption = $aChartInfo['course_name'] . ' ('.$aChartInfo['course_period'].' Fase '.$aChartInfo['course_modality'].') - ' . $aQuestionTitle;
                    }

                    $aContent .= '\\begin{center}' . "\n";
                    $aContent .= '\\begin{figure}[h!]' . "\n";
                    $aContent .= '\\includegraphics[width=0.9\\textwidth]{'.$aEntry.'}' . "\n";
                    $aContent .= '\\caption{'.$aCaption.'}' . "\n";
                    $aContent .= '\\end{figure}' . "\n";
                    $aContent .= '\\end{center}' . "\n";
                    $aContent .= "\n";
                }
            }

            $aQuestionTextFile = $theWorkDir . '/charts/' . $aFormId . '/' . $aQuestionNumber . '.csv';

            if(file_exists($aQuestionTextFile)) {
                $aTextResponses = load_csv($aQuestionTextFile);

                $aContent .= '\\begin{center}' . "\n";
                $aContent .= '\\begin{longtable}{| p{16cm} |}' . "\n";
                $aContent .= '\\hline' . "\n";
                $aContent .= ' \textbf{Comentário discente} \\\\' . "\n";
                $aContent .= '\\hline' . "\n";

                foreach($aTextResponses as $aInfo) {
                    if(empty($aInfo['response'])) {
                        continue;
                    }
                    $aContent .= latex_special_chars($aInfo['response']).' \footnote{'.latex_special_chars($aInfo['form_title']).'} \\\\' . "\n";
                    $aContent .= '\\hline' . "\n";
                }

                $aContent .= '\\hline' . "\n";
                $aContent .= '\\caption{'.$aCaption.'}' . "\n";
                $aContent .= '\\end{longtable}' . "\n";
                $aContent .= '\\end{center}' . "\n";
            }
        }

        $aContent .= "\n";
    }

    $aCoverTitle = latex_report_cover_title($theWorkDir, $theEntry, $theManifest, $theQuestions);
    $aCoverSubtitle = latex_report_cover_subtitle($theWorkDir, $theEntry, $theManifest, $theQuestions);
    $aCoverAuthor = latex_report_cover_author($theWorkDir, $theEntry, $theManifest, $theQuestions);
    $aCoverNotice = latex_report_cover_notice($theWorkDir, $theEntry, $theManifest, $theQuestions);

    $aReport = str_replace('[TITLE]', $aCoverTitle, $aReport);
    $aReport = str_replace('[SUBTITLE]', $aCoverSubtitle, $aReport);
    $aReport = str_replace('[AUTHOR]', $aCoverAuthor, $aReport);
    $aReport = str_replace('[NOTICE]', $aCoverNotice, $aReport);
    $aReport = str_replace('[CONTENT]', $aContent, $aReport);

    file_put_contents($aReportFilePath, $aReport);

    return $aOk;
}

function latex_report_chapter_intro($theWorkDir, $theEntry, $theManifest, $theQuestions) {
    $aText = '';
    $aCharts = find_files($theWorkDir . '/charts', true);
    $aTotalCharts = count($aCharts) - 1;

    $aText .= 'Esse relatório foi elaborado pela Coordenação do Curso de Ciência da Computação da Universidade Federal da Fronteira Sul, campus Chapecó, SC. ';
    $aText .= 'Ele contém um compilado das respostas dalas pelos discentes do curso durante a Semana de Avaliação de 2019/2. Cada discente respondeu um questionário online para cada Componente Curricular Regular (CCR) que estava participando. Um total de '.$aTotalCharts.' formulários online foram criados, um para cada um dos CCRs existentes no período.' . "\n\n\n";
    $aText .= 'O conteúdo deste documento apresenta as avaliações feitas no(s) formulário(s) relacinado(s) a(o) \textbf{"'.$theEntry['name'].'"}.' . "\n\n\n";

    $aText .= '\vspace{1cm}' . "\n\n";

    $aText .= '\begin{center}' . "\n";
    $aText .= 'Fernando Bevilacqua \newline \texttt{<fernando.bevilacqua@uffs.edu.br>} \newline \textbf{Coordenador}' . "\n\n";
    $aText .= '\vspace{0.2cm}' . "\n\n";    
    $aText .= 'Luciano Lores Caimi \newline \texttt{<lcaimi@uffs.edu.br>} \newline \textbf{Coordenador Adjunto}' . "\n";
    $aText .= '\vspace{5cm}' . "\n\n";
    $aText .= '\textbf{Data:} 11/02/2020' . "\n\n";    
    $aText .= '\end{center}' . "\n";    

    return $aText;
}

function latex_report_chapter_avaliation($theWorkDir, $theEntry, $theManifest, $theQuestions) {
    $aCharts = find_files($theWorkDir . '/charts', true);
    $aCourses = array();

    foreach($aCharts as $aFormId) {
        $aChartInfo = get_manifest_entry_by_formid($aFormId, $theManifest);

        if($aChartInfo != false) {
            $aCourses[] = $aChartInfo;
        }
    }

    $aText = '';
    $aText .= 'Esse capítulo mostra as avaliações feitas pelos discentes do curso referente aos Componentes Curriculares Regulares listados na Tabela \\ref{tab:courses-manifest}.' . "\n\n";
    
    $aText .= '\\begin{center}' . "\n";
    $aText .= '\\begin{longtable}{| p{6cm} | p{4cm} | c | c | }' . "\n";
    $aText .= '\\hline' . "\n";
    $aText .= '\textbf{CCR} & \textbf{Docente} & \textbf{Fase} & \textbf{Turno} \\\\' . "\n";

    foreach($aCourses as $aInfo) {
        $aText .= latex_special_chars($aInfo['course_name']).' & ';
        $aText .= latex_special_chars($aInfo['course_responsible']).' & ';
        $aText .= latex_special_chars($aInfo['course_period']).' & ';
        $aText .= latex_special_chars($aInfo['course_modality']);
        $aText .= ' \\\\' . "\n";
        $aText .= '\\hline' . "\n";
    }

    $aText .= '\\caption{Componentes Curriculares Regulares cujas avaliações estão presentes nesse relatório.}' . "\n";
    $aText .= '\\label{tab:courses-manifest}' . "\n";
    $aText .= '\\end{longtable}' . "\n";
    $aText .= '\\end{center}' . "\n";

    return $aText;
}

function latex_report_cover_title($theWorkDir, $theEntry, $theManifest, $theQuestions) {
    return 'Relatório de Avaliação (2019.2)';
}

function latex_report_cover_subtitle($theWorkDir, $theEntry, $theManifest, $theQuestions) {
    return $theEntry['name'];
}

function latex_report_cover_author($theWorkDir, $theEntry, $theManifest, $theQuestions) {
    if(isset($theEntry['filter']) && !empty($theEntry['filter'])) {
        $aCharts = find_files($theWorkDir . '/charts', true);
        $aCourses = array();
    
        foreach($aCharts as $aFormId) {
            $aChartInfo = get_manifest_entry_by_formid($aFormId, $theManifest);
    
            if($aChartInfo != false) {
                $aCourses[] = $aChartInfo['course_name'];
            }
        }
        return implode(' \and ', $aCourses);
    } else {
        return '';
    }
}

function latex_report_cover_notice($theWorkDir, $theEntry, $theManifest, $theQuestions) {
    return '\textbf{IMPORTANTE:} esse documento é CONFIDENCIAL e destinado ao docente envolvido ou à administração da Universidade Federal da Fronteira Sul. Se você deseja uma versão pública, escreva para \texttt{computacao.ch@uffs.edu.br}.';
}

function get_manifest_entry_by_formid($theFormId, $theManifest) {
    foreach($theManifest as $aIndex => $aManifestEntry) {
        if($aManifestEntry['form_id'] == $theFormId) {
            return $aManifestEntry;
        }
    }

    return false;
}


function find_files($thePath, $theDirsOnly = false) {
    $aRet = array();

    if($aHandle = opendir($thePath)) {
        while(($aEntry = readdir($aHandle)) !== false) {
            $aEntryPath = $thePath . '/' . $aEntry;

            if ($aEntry == '.' || $aEntry == '..' || ($theDirsOnly && !is_dir($aEntryPath))) {
                continue;
            }

            $aRet[] = $aEntry;
        }

        closedir($aHandle);
    }

    return $aRet;
}

function compile_latex_report($theWorkDir, $theMainFile = 'report', $theCompiler = 'pdflatex') {
    $aOutput = array();
    $aReturnVar = -1;
    $aCmd = 'cd "'.$theWorkDir.'" && '.$theCompiler.' -jobname=STRING '.$theMainFile;
    @exec($aCmd, $aOutput, $aReturnVar);

    return $aReturnVar == 0;
}

/**
 * Copy a file, or recursively copy a folder and its contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @param       int      $permissions New folder creation permissions
 * @return      bool     Returns true on success, false on failure
 */
function xcopy($source, $dest, $permissions = 0755)
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest, $permissions);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        xcopy("$source/$entry", "$dest/$entry", $permissions);
    }

    // Clean up
    $dir->close();
    return true;
}

/**
 * Unaccent the input string string. An example string like `ÀØėÿᾜὨζὅБю`
 * will be translated to `AOeyIOzoBY`. More complete than :
 *   strtr( (string)$str,
 *          "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
 *          "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn" );
 *
 * @param $str input string
 * @param $utf8 if null, function will detect input string encoding
 * @return string input string without accent
 * @copyright https://gist.github.com/evaisse/169594
 */
function remove_accents( $str, $utf8=true ) {
    $str = (string)$str;
    if( is_null($utf8) ) {
        if( !function_exists('mb_detect_encoding') ) {
            $utf8 = (strtolower( mb_detect_encoding($str) )=='utf-8');
        } else {
            $length = strlen($str);
            $utf8 = true;
            for ($i=0; $i < $length; $i++) {
                $c = ord($str[$i]);
                if ($c < 0x80) $n = 0; # 0bbbbbbb
                elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
                elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
                elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
                elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
                elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
                else return false; # Does not match any model
                for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                    if ((++$i == $length)
                        || ((ord($str[$i]) & 0xC0) != 0x80)) {
                        $utf8 = false;
                        break;
                    }
                    
                }
            }
        }
        
    }
    
    if(!$utf8)
        $str = utf8_encode($str);

    $transliteration = array(
    'Ĳ' => 'I', 'Ö' => 'O','Œ' => 'O','Ü' => 'U','ä' => 'a','æ' => 'a',
    'ĳ' => 'i','ö' => 'o','œ' => 'o','ü' => 'u','ß' => 's','ſ' => 's',
    'À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A',
    'Æ' => 'A','Ā' => 'A','Ą' => 'A','Ă' => 'A','Ç' => 'C','Ć' => 'C',
    'Č' => 'C','Ĉ' => 'C','Ċ' => 'C','Ď' => 'D','Đ' => 'D','È' => 'E',
    'É' => 'E','Ê' => 'E','Ë' => 'E','Ē' => 'E','Ę' => 'E','Ě' => 'E',
    'Ĕ' => 'E','Ė' => 'E','Ĝ' => 'G','Ğ' => 'G','Ġ' => 'G','Ģ' => 'G',
    'Ĥ' => 'H','Ħ' => 'H','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I',
    'Ī' => 'I','Ĩ' => 'I','Ĭ' => 'I','Į' => 'I','İ' => 'I','Ĵ' => 'J',
    'Ķ' => 'K','Ľ' => 'K','Ĺ' => 'K','Ļ' => 'K','Ŀ' => 'K','Ł' => 'L',
    'Ñ' => 'N','Ń' => 'N','Ň' => 'N','Ņ' => 'N','Ŋ' => 'N','Ò' => 'O',
    'Ó' => 'O','Ô' => 'O','Õ' => 'O','Ø' => 'O','Ō' => 'O','Ő' => 'O',
    'Ŏ' => 'O','Ŕ' => 'R','Ř' => 'R','Ŗ' => 'R','Ś' => 'S','Ş' => 'S',
    'Ŝ' => 'S','Ș' => 'S','Š' => 'S','Ť' => 'T','Ţ' => 'T','Ŧ' => 'T',
    'Ț' => 'T','Ù' => 'U','Ú' => 'U','Û' => 'U','Ū' => 'U','Ů' => 'U',
    'Ű' => 'U','Ŭ' => 'U','Ũ' => 'U','Ų' => 'U','Ŵ' => 'W','Ŷ' => 'Y',
    'Ÿ' => 'Y','Ý' => 'Y','Ź' => 'Z','Ż' => 'Z','Ž' => 'Z','à' => 'a',
    'á' => 'a','â' => 'a','ã' => 'a','ā' => 'a','ą' => 'a','ă' => 'a',
    'å' => 'a','ç' => 'c','ć' => 'c','č' => 'c','ĉ' => 'c','ċ' => 'c',
    'ď' => 'd','đ' => 'd','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e',
    'ē' => 'e','ę' => 'e','ě' => 'e','ĕ' => 'e','ė' => 'e','ƒ' => 'f',
    'ĝ' => 'g','ğ' => 'g','ġ' => 'g','ģ' => 'g','ĥ' => 'h','ħ' => 'h',
    'ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ī' => 'i','ĩ' => 'i',
    'ĭ' => 'i','į' => 'i','ı' => 'i','ĵ' => 'j','ķ' => 'k','ĸ' => 'k',
    'ł' => 'l','ľ' => 'l','ĺ' => 'l','ļ' => 'l','ŀ' => 'l','ñ' => 'n',
    'ń' => 'n','ň' => 'n','ņ' => 'n','ŉ' => 'n','ŋ' => 'n','ò' => 'o',
    'ó' => 'o','ô' => 'o','õ' => 'o','ø' => 'o','ō' => 'o','ő' => 'o',
    'ŏ' => 'o','ŕ' => 'r','ř' => 'r','ŗ' => 'r','ś' => 's','š' => 's',
    'ť' => 't','ù' => 'u','ú' => 'u','û' => 'u','ū' => 'u','ů' => 'u',
    'ű' => 'u','ŭ' => 'u','ũ' => 'u','ų' => 'u','ŵ' => 'w','ÿ' => 'y',
    'ý' => 'y','ŷ' => 'y','ż' => 'z','ź' => 'z','ž' => 'z','Α' => 'A',
    'Ά' => 'A','Ἀ' => 'A','Ἁ' => 'A','Ἂ' => 'A','Ἃ' => 'A','Ἄ' => 'A',
    'Ἅ' => 'A','Ἆ' => 'A','Ἇ' => 'A','ᾈ' => 'A','ᾉ' => 'A','ᾊ' => 'A',
    'ᾋ' => 'A','ᾌ' => 'A','ᾍ' => 'A','ᾎ' => 'A','ᾏ' => 'A','Ᾰ' => 'A',
    'Ᾱ' => 'A','Ὰ' => 'A','ᾼ' => 'A','Β' => 'B','Γ' => 'G','Δ' => 'D',
    'Ε' => 'E','Έ' => 'E','Ἐ' => 'E','Ἑ' => 'E','Ἒ' => 'E','Ἓ' => 'E',
    'Ἔ' => 'E','Ἕ' => 'E','Ὲ' => 'E','Ζ' => 'Z','Η' => 'I','Ή' => 'I',
    'Ἠ' => 'I','Ἡ' => 'I','Ἢ' => 'I','Ἣ' => 'I','Ἤ' => 'I','Ἥ' => 'I',
    'Ἦ' => 'I','Ἧ' => 'I','ᾘ' => 'I','ᾙ' => 'I','ᾚ' => 'I','ᾛ' => 'I',
    'ᾜ' => 'I','ᾝ' => 'I','ᾞ' => 'I','ᾟ' => 'I','Ὴ' => 'I','ῌ' => 'I',
    'Θ' => 'T','Ι' => 'I','Ί' => 'I','Ϊ' => 'I','Ἰ' => 'I','Ἱ' => 'I',
    'Ἲ' => 'I','Ἳ' => 'I','Ἴ' => 'I','Ἵ' => 'I','Ἶ' => 'I','Ἷ' => 'I',
    'Ῐ' => 'I','Ῑ' => 'I','Ὶ' => 'I','Κ' => 'K','Λ' => 'L','Μ' => 'M',
    'Ν' => 'N','Ξ' => 'K','Ο' => 'O','Ό' => 'O','Ὀ' => 'O','Ὁ' => 'O',
    'Ὂ' => 'O','Ὃ' => 'O','Ὄ' => 'O','Ὅ' => 'O','Ὸ' => 'O','Π' => 'P',
    'Ρ' => 'R','Ῥ' => 'R','Σ' => 'S','Τ' => 'T','Υ' => 'Y','Ύ' => 'Y',
    'Ϋ' => 'Y','Ὑ' => 'Y','Ὓ' => 'Y','Ὕ' => 'Y','Ὗ' => 'Y','Ῠ' => 'Y',
    'Ῡ' => 'Y','Ὺ' => 'Y','Φ' => 'F','Χ' => 'X','Ψ' => 'P','Ω' => 'O',
    'Ώ' => 'O','Ὠ' => 'O','Ὡ' => 'O','Ὢ' => 'O','Ὣ' => 'O','Ὤ' => 'O',
    'Ὥ' => 'O','Ὦ' => 'O','Ὧ' => 'O','ᾨ' => 'O','ᾩ' => 'O','ᾪ' => 'O',
    'ᾫ' => 'O','ᾬ' => 'O','ᾭ' => 'O','ᾮ' => 'O','ᾯ' => 'O','Ὼ' => 'O',
    'ῼ' => 'O','α' => 'a','ά' => 'a','ἀ' => 'a','ἁ' => 'a','ἂ' => 'a',
    'ἃ' => 'a','ἄ' => 'a','ἅ' => 'a','ἆ' => 'a','ἇ' => 'a','ᾀ' => 'a',
    'ᾁ' => 'a','ᾂ' => 'a','ᾃ' => 'a','ᾄ' => 'a','ᾅ' => 'a','ᾆ' => 'a',
    'ᾇ' => 'a','ὰ' => 'a','ᾰ' => 'a','ᾱ' => 'a','ᾲ' => 'a','ᾳ' => 'a',
    'ᾴ' => 'a','ᾶ' => 'a','ᾷ' => 'a','β' => 'b','γ' => 'g','δ' => 'd',
    'ε' => 'e','έ' => 'e','ἐ' => 'e','ἑ' => 'e','ἒ' => 'e','ἓ' => 'e',
    'ἔ' => 'e','ἕ' => 'e','ὲ' => 'e','ζ' => 'z','η' => 'i','ή' => 'i',
    'ἠ' => 'i','ἡ' => 'i','ἢ' => 'i','ἣ' => 'i','ἤ' => 'i','ἥ' => 'i',
    'ἦ' => 'i','ἧ' => 'i','ᾐ' => 'i','ᾑ' => 'i','ᾒ' => 'i','ᾓ' => 'i',
    'ᾔ' => 'i','ᾕ' => 'i','ᾖ' => 'i','ᾗ' => 'i','ὴ' => 'i','ῂ' => 'i',
    'ῃ' => 'i','ῄ' => 'i','ῆ' => 'i','ῇ' => 'i','θ' => 't','ι' => 'i',
    'ί' => 'i','ϊ' => 'i','ΐ' => 'i','ἰ' => 'i','ἱ' => 'i','ἲ' => 'i',
    'ἳ' => 'i','ἴ' => 'i','ἵ' => 'i','ἶ' => 'i','ἷ' => 'i','ὶ' => 'i',
    'ῐ' => 'i','ῑ' => 'i','ῒ' => 'i','ῖ' => 'i','ῗ' => 'i','κ' => 'k',
    'λ' => 'l','μ' => 'm','ν' => 'n','ξ' => 'k','ο' => 'o','ό' => 'o',
    'ὀ' => 'o','ὁ' => 'o','ὂ' => 'o','ὃ' => 'o','ὄ' => 'o','ὅ' => 'o',
    'ὸ' => 'o','π' => 'p','ρ' => 'r','ῤ' => 'r','ῥ' => 'r','σ' => 's',
    'ς' => 's','τ' => 't','υ' => 'y','ύ' => 'y','ϋ' => 'y','ΰ' => 'y',
    'ὐ' => 'y','ὑ' => 'y','ὒ' => 'y','ὓ' => 'y','ὔ' => 'y','ὕ' => 'y',
    'ὖ' => 'y','ὗ' => 'y','ὺ' => 'y','ῠ' => 'y','ῡ' => 'y','ῢ' => 'y',
    'ῦ' => 'y','ῧ' => 'y','φ' => 'f','χ' => 'x','ψ' => 'p','ω' => 'o',
    'ώ' => 'o','ὠ' => 'o','ὡ' => 'o','ὢ' => 'o','ὣ' => 'o','ὤ' => 'o',
    'ὥ' => 'o','ὦ' => 'o','ὧ' => 'o','ᾠ' => 'o','ᾡ' => 'o','ᾢ' => 'o',
    'ᾣ' => 'o','ᾤ' => 'o','ᾥ' => 'o','ᾦ' => 'o','ᾧ' => 'o','ὼ' => 'o',
    'ῲ' => 'o','ῳ' => 'o','ῴ' => 'o','ῶ' => 'o','ῷ' => 'o','А' => 'A',
    'Б' => 'B','В' => 'V','Г' => 'G','Д' => 'D','Е' => 'E','Ё' => 'E',
    'Ж' => 'Z','З' => 'Z','И' => 'I','Й' => 'I','К' => 'K','Л' => 'L',
    'М' => 'M','Н' => 'N','О' => 'O','П' => 'P','Р' => 'R','С' => 'S',
    'Т' => 'T','У' => 'U','Ф' => 'F','Х' => 'K','Ц' => 'T','Ч' => 'C',
    'Ш' => 'S','Щ' => 'S','Ы' => 'Y','Э' => 'E','Ю' => 'Y','Я' => 'Y',
    'а' => 'A','б' => 'B','в' => 'V','г' => 'G','д' => 'D','е' => 'E',
    'ё' => 'E','ж' => 'Z','з' => 'Z','и' => 'I','й' => 'I','к' => 'K',
    'л' => 'L','м' => 'M','н' => 'N','о' => 'O','п' => 'P','р' => 'R',
    'с' => 'S','т' => 'T','у' => 'U','ф' => 'F','х' => 'K','ц' => 'T',
    'ч' => 'C','ш' => 'S','щ' => 'S','ы' => 'Y','э' => 'E','ю' => 'Y',
    'я' => 'Y','ð' => 'd','Ð' => 'D','þ' => 't','Þ' => 'T','ა' => 'a',
    'ბ' => 'b','გ' => 'g','დ' => 'd','ე' => 'e','ვ' => 'v','ზ' => 'z',
    'თ' => 't','ი' => 'i','კ' => 'k','ლ' => 'l','მ' => 'm','ნ' => 'n',
    'ო' => 'o','პ' => 'p','ჟ' => 'z','რ' => 'r','ს' => 's','ტ' => 't',
    'უ' => 'u','ფ' => 'p','ქ' => 'k','ღ' => 'g','ყ' => 'q','შ' => 's',
    'ჩ' => 'c','ც' => 't','ძ' => 'd','წ' => 't','ჭ' => 'c','ხ' => 'k',
    'ჯ' => 'j','ჰ' => 'h', 'ª' => 'a'
    );
    $str = str_replace( array_keys( $transliteration ),
                        array_values( $transliteration ),
                        $str);
    return $str;
}

function latex_special_chars($string) {
    // https://stackoverflow.com/a/5422751/29827
    $map = array( 
        "\\"=>"|",
        "#"=>"\\#",
        "$"=>"\\$",
        "%"=>"\\%",
        "&"=>"\\&",
        "~"=>"\\~{}",
        "_"=>"\\_",
        "^"=>"\\^{}",
        "{"=>"\\{",
        "}"=>"\\}",
    );

    return str_replace(array_keys($map), array_values($map), $string);
}