<?php

/*
TODO:
if selection also consists of two words
if several words have syntax
several possibilities for syntax
if name has several and morf one or vice versa tehn allow it!! last spot should be left empty
if several then || in syntax
syntaxile minig märk, et ta ei ole kohustuslik vaid soovitatav
*/

error_reporting(-1);
mb_internal_encoding("UTF-8");
const FRAMES_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\frames_with_morf.xml';
const TEXT_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\korpused\soccernet.snx';
const OVERALL_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet';
const RETURN_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\laused.kym';
const DO_WORD_DISAMBIGUATION = false;
//No ö, ä, ü, õ allowed in path
$morphedText = file_get_contents(TEXT_PATH);
$textRows = explode("\n", $morphedText);
$sentenceArray = readTextToArray($textRows);


if (!empty($sentenceArray)) {
	$frames = readFramesToArray(FRAMES_PATH);	
	$addedFrames = 0;
	$addedElements = 0;
	$counter = 0;
	
	foreach ($sentenceArray as $key => $sentence) {
		foreach ($frames as $frameName => $frame) {
			foreach ($frame['lexicalUnits'] as $lemma => $lemmaMorphAndNamesArray) {
				$returnData = analysiseSentenceWithFrameData($sentence, $lemma, $lemmaMorphAndNamesArray, $textRows, $sentenceArray, $frameName, $key, true);
				$frameAdded = $returnData['added'];
				$textRows = $returnData['textRows'];
				//Element adding
				if ($frameAdded === true) {
					foreach ($frame['elements'] as $elementName => $elementNamesAndMorfArray) {
						$returnData = analysiseSentenceWithFrameData($sentence, $elementName, $elementNamesAndMorfArray, $textRows, $sentenceArray, $elementName, $key, false);
						$textRows = $returnData['textRows'];
					}
				}
			}
		}	
	}
	
	//add frames back to text
	$morphedText = implode("\n", $textRows);
	echo 'Lisati ' . $addedFrames . ' freim(i).';
	echo 'Lisati ' . $addedElements . ' element(i).';
	file_put_contents(RETURN_PATH, $morphedText);
	die;
}
else {
	echo 'Vale tee';
	exit;
}
exit;


/**
	Main function that analyses sentece and frame
*/
function analysiseSentenceWithFrameData($sentence, $lemma, $baseWordArray, $textRows, $sentenceArray, $frameName, $key, $isForLemma) {
	$added = 0;
	global $addedFrames;
	global $addedElements;
	$frameAdded = false;
	$elementAdded = false;
	$wordMorph = $baseWordArray['morf'];
	unset($baseWordArray['morf']);
	foreach ($baseWordArray as $baseWordOptionsSelection) {
	
		if (strpos($wordMorph, '*') !== false) {
			$wordMorph = substr($wordMorph, 1);
			$morphWithStar = true;
		}
		else {
			$morphWithStar = false;
		}
		if (strpos($wordMorph, '|||') !== false) {
			$pieces = explode('|||', $wordMorph);
			$syntax = $pieces[1];
			$wordMorph = $pieces[0];
		}
		if (strpos($wordMorph, '||') !== false) {
			$wordMorphPieces = explode('||', $wordMorph);
			$wordPieces = explode(' ', $baseWordOptionsSelection);
			//$wordPieces something like array([] =>  pall, [] => kaotama)
			$wordsAmount = count($wordPieces);
		}
		else {
			$wordMorphPieces = array($wordMorph);
			$wordPieces = array($baseWordOptionsSelection);
			$wordsAmount = 1;
		}
		foreach ($wordMorphPieces as $tmpKey => $wordMorphPiece) {
			if (strpos($wordMorphPiece, '|') !== false) {
				$wordMorphPieces[$tmpKey] = explode('|', $wordMorphPiece);
			}
			else {
				$wordMorphPieces[$tmpKey] = array($wordMorphPiece);
			}
		}
		
		for ($pieceKey = 0; $pieceKey < $wordsAmount; $pieceKey++) {
			foreach ($sentence as $wordKey => $word) {
				$wordsMatched = 0;
				$wordsAndMorphMatched = 0;
				foreach ($word['alg'] as $meaningKey => $wordMeaning) {
					//wordMeaning something like võitis

					//If syntac not the same then continue
					if (!empty($syntax) && $syntax != false && strpos($word['morf'][$meaningKey], ' ' . $syntax . ' ') === false) {
						continue;
					}
					
					foreach ($wordMorphPieces[$pieceKey] as $optionKey => $wordMorphOneOption) {
						// $wordMorphOneOption something like _V_ main
						
						$oneWordMorphPieces = (!empty($wordMorphPieces[$pieceKey][$optionKey]) ? explode(' ', $wordMorphPieces[$pieceKey][$optionKey]) : array());
						//Something like Array([] => _V_, [] => main)
						
						if ($morphWithStar === true) {
							//if lemma = wordmeaing OR morph = wordmorph, add freim/element with luck
							
							if ($wordPieces[$pieceKey] == $wordMeaning) {
								$wordsMatched++;
								if (!empty($oneWordMorphPieces)) {
									$allIncluded = true;
									foreach ($oneWordMorphPieces as $piece) {
										if (strpos($word['morf'][$meaningKey], ' ' . $piece . ' ') === false) {
											$allIncluded = false;
											break;
										}
									}
									if ($allIncluded === true) {
										$wordsAndMorphMatched++;
									}
								}
								if ($wordsMatched == $wordsAmount) {
									$sentenceArray[$key][$wordKey]['freimiInfo'] = $frameName;
									if (substr($textRows[$word['nr']], -1) == "\r") {
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0, -2);
									}
									$withLuck = ($wordsAndMorphMatched == $wordsAmount ? '' : 'õnnega2 ');
									if ($isForLemma === true) {
										echo ++$addedFrames . '    ' .  $wordPieces[$pieceKey] . PHP_EOL;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Freim ' . $withLuck . $frameName;
										$frameAdded = true;
									}
									else {
										echo ++$addedElements . '    ' .  $wordPieces[$pieceKey] . PHP_EOL;
										//print_r(utf8_encode(' // Element  õnnega1 '));die;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Element ' . $withLuck . $frameName;
										$added++;
										$elementAdded = true;
									}
								}
							}
							else if (!empty($oneWordMorphPieces)) {
							
								//print_r($oneWordMorphPieces);die;
								$allIncluded = true;
								foreach ($oneWordMorphPieces as $piece) {
									if (strpos($word['morf'][$meaningKey], ' ' . $piece . ' ') === false) {
										$allIncluded = false;
										break;
									}
								}
								if (!empty($allIncluded) && $allIncluded === true) {
									$wordsMatched++; 
									if ($wordsMatched == $wordsAmount) {
										$sentenceArray[$key][$wordKey]['freimiInfo'] = $frameName;
										if (substr($textRows[$word['nr']], -1) == "\r") {
											$textRows[$word['nr']] = substr($textRows[$word['nr']], 0, -2);
										}
										if ($isForLemma === true) {
											echo ++$addedFrames . '    ' .  $wordPieces[$pieceKey] . PHP_EOL;
											$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Freim õnnega1 ' . $frameName;
											$frameAdded = true;
										}
										else {
											echo ++$addedElements . '    ' .  $wordPieces[$pieceKey] . PHP_EOL;
											$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Element õnnega1 ' . $frameName;
											$added++;
											$elementAdded = true;
										}
									}
								}
								else {
									continue;
								}
							}
						}
						else {
							//if lemma/element = word AND morph = wordmorph, add with certainty
							if ($wordPieces[$pieceKey] == $wordMeaning) {
								$allIncluded = true;
								foreach ($oneWordMorphPieces as $piece) {
									if (strpos($word['morf'][$meaningKey], ' ' . $piece . ' ') === false) {
										$allIncluded = false;
										break;
									}
								}
								if ($allIncluded === true) {
									$wordsMatched++; 
									if ($wordsMatched == $wordsAmount) {
										$sentenceArray[$key][$wordKey]['freimiInfo'] = $frameName;
										if (substr($textRows[$word['nr']], -1) == "\r") {
											$textRows[$word['nr']] = substr($textRows[$word['nr']], 0, strlen($textRows[$word['nr']]) - 2);
										}
										if ($isForLemma === true) {
											echo ++$addedFrames . '    ' .  $wordPieces[$pieceKey] . PHP_EOL;
											$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Freim ' . $frameName;
											$frameAdded = true;
										}
										else {
											echo ++$addedElements . '    ' .  $wordPieces[$pieceKey] . PHP_EOL;
											$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Element  ' . $frameName;
											$elementAdded = true;
											$added++;
										}
									}
								}
								else {
									continue;
								}
							}
							else {
								continue;
							}
						}
					}
				}
			}
		}
	}
	
	return array(
		'added' => $frameAdded,
		'textRows' => $textRows,
	);
}


/**
 * Morphtext to arrayobject
 * @return ([lause] => ([] => ("alg" ([] => algvorm), "morf" ([] => morph), "nr" => nth word in text))
*/
function readTextToArray($textRows) {
	
		//$textRows = explode("\n", $morphedText);
		$sentenceArray = array();
		$counter = 0;
		foreach ($textRows as $k => $row) {
			$row = trim($row);
			if (strpos($row,  '$LA$') !== false) {
			 $sentenceArray[$counter] = array();
			}
			else if (strpos($row,  '$LL$') !== false) {
			 $counter++;
			}
			else {
				$pieces = explode('    ', $row);
				$len = sizeof($pieces);
				$base = array();
				$morph = array();
				
				for ($i = 1; $i < sizeof($pieces); $i++) {
					$wordAndAnalysingParts = explode('+', $pieces[$i]);
					if (count($wordAndAnalysingParts) == 1) {
						$wordAndAnalysingParts = explode(' //', $pieces[$i]);
						$morphPart = $wordAndAnalysingParts[1] . ' ';
					}
					else {						
						$morphPart = substr($wordAndAnalysingParts[1], strpos($wordAndAnalysingParts[1], ' //') + 3);			
					}
					$mainVerb = $wordAndAnalysingParts[0];
					$replace = array('=', '_');
					$mainVerb = str_replace($replace, '', $mainVerb);
					$mainVerb .= (strpos($morphPart, '_V_ main') !== false ? 'ma' : '');
					$base[] = utf8_encode($mainVerb);
					$morph[] = $morphPart . ' ';
				}
				
				$sentenceArray[$counter][] = array(
					'alg' => $base,
					'morf' => $morph,
					'nr' => $k,
				);
			}
		}
	
	return $sentenceArray;
}

/**
 read frames.xml and put them to $frames array
	frames = (
		frameName => (
			lexicalUnits => (
				[] => lemma name and resource items names
				[morf] => morf
			),
			elements => (
				name => (
					[] => element name and resource items names
					...
					[morf] => morf
				)
			)
		),
		...
	)
*/
function readFramesToArray($framesPath) {
	
	$frames = array();
	$xml = new XMLReader();
	$xml->open($framesPath);
	while ($xml->read()) {
		//$node = new SimpleXMLElement($xml->readOuterXML());
		
		if ($xml->name == 'frame' && $xml->nodeType == XMLReader::ELEMENT) {
			$frameName =  $xml->getAttribute('name');
			$frames[$frameName] = array();
			
		}
		else if ($xml->name == 'LexicalUnits' && $xml->nodeType == XMLReader::ELEMENT) {
			$frames[$frameName]['lexicalUnits'] = array();
		}
		else if ($xml->name == 'lu' && $xml->nodeType == XMLReader::ELEMENT) {
			$selection = $xml->getAttribute('selection');
			$resource = $xml->getAttribute('resource');
			$lemma = $xml->getAttribute('lemma');
			if (!empty($selection)) {
				$frames[$frameName]['lexicalUnits'][$lemma] = array();
				$frames[$frameName]['lexicalUnits'][$lemma] = array_merge(array('morf' => $xml->getAttribute('morf')), explode(', ', $selection));
				$frames[$frameName]['lexicalUnits'][$lemma][] = $lemma;
			}
			else if ($resource == true){
				$frames[$frameName]['lexicalUnits'][$lemma] = array();
				//TODO kontrolli, kas OVERALL_PATH lõpeb /-ga
				$frames[$frameName]['lexicalUnits'][$lemma] = array_merge(array('morf' => $xml->getAttribute('morf')), getResource(OVERALL_PATH . '\\' . $name . '.txt'));
				$frames[$frameName]['lexicalUnits'][$lemma][] = $lemma;
			}
			else {
				$frames[$frameName]['lexicalUnits'][$lemma] = array();
				$frames[$frameName]['lexicalUnits'][$lemma]['morf'] = $xml->getAttribute('morf');
				$frames[$frameName]['lexicalUnits'][$lemma][] = $lemma;
			}
			//frames[$frameName]['lexicalUnits'][$lemma] = $xml->getAttribute('morf');
		}
		else if ($xml->name == 'Elements' && $xml->nodeType == XMLReader::ELEMENT) {
			$frames[$frameName]['elements'] = array();
		}
		else if ($xml->name == 'element' && $xml->nodeType == XMLReader::ELEMENT) {
			$selection = $xml->getAttribute('selection');
			$resource = $xml->getAttribute('resource');
			$name = $xml->getAttribute('name');
			if (!empty($selection)) {
				$frames[$frameName]['elements'][$name] = array();
				$frames[$frameName]['elements'][$name] = array_merge(array('morf' => $xml->getAttribute('morf')), explode(', ', $selection));
				$frames[$frameName]['elements'][$name][] = $name;
			}
			else if ($resource == true){
				$frames[$frameName]['elements'][$name] = array();
				//print_r(getResource(OVERALL_PATH . '\\' . $name . '.txt'));die;
				$frames[$frameName]['elements'][$name] = array_merge(array('morf' => $xml->getAttribute('morf')), getResource(OVERALL_PATH . '\\' . $name . '.txt'));
				$frames[$frameName]['elements'][$name][] = $name;
			}
			else {
				$frames[$frameName]['elements'][$name] = array();
				$frames[$frameName]['elements'][$name]['morf'] = $xml->getAttribute('morf');
				$frames[$frameName]['elements'][$name][] = $name;
			}
		}
	}
	return $frames;
}

function getResource($resourcePath) {
	return explode("\n", file_get_contents($resourcePath));
}
?>
