<?php

/*
TODO:
if lemma consists of two words then they can be separately
*/

error_reporting(-1);
//default_charset("utf-8");
mb_internal_encoding("UTF-8");
const FRAMES_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\frames_with_morf.xml';
const TEXT_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\korpused\soccernet.snx';
const OVERALL_PATH = 'C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet';
const DO_WORD_DISAMBIGUATION = false;
//No ö, ä, ü, õ allowed in path
$morphedText = file_get_contents(TEXT_PATH);
$textRows = explode("\n", $morphedText);
//print_r($textRows);die;
$sentenceArray = readTextToArray($textRows);
//print_r($sentenceArray);die;

if (!empty($sentenceArray)) {
	
	
	
	$frames = readFramesToArray(FRAMES_PATH);
	//print_r($frames);die;
		
	//$textRows = explode("\n", $morphedText);
	
	/*
	pseudo:
	käi läbi laused
		käi läbi freimid
			käi läbi lemmad
				käi läbi sõnad lauses, mille morf = lemma morf TODO lemma kaheosaline, morfis tärn, || vms
					
					kui sõna = lemma ja/või morfid ~sarnased
							kutsub mingi freimid välja, vaja kindlaks teha millise
							
							käi läbi elemendid ja vaata, mitu sobiks (ebatäpne)
								TODO hiljem optional false check
								
								kui _S_
									vaata kas leidub üldnimi
									vaata kas mõni pärisnimi
								
						
						
	
	*/
	
	
	$addedFrames = 0;
	$addedElements = 0;
	//print_r($sentenceArray);die;
	$counter = 0;
	foreach ($sentenceArray as $key => $sentence) {
		foreach ($frames as $frameName => $frame) {
			foreach ($frame['lexicalUnits'] as $lemma => $lemmaMorphAndNamesArray) {
				
				$returnData = analysiseSentenceWithFrameData($sentence, $lemma, $lemmaMorphAndNamesArray, $textRows, $sentenceArray, $frameName, $key, true);
				$frameAdded = $returnData['added'];
				$textRows = $returnData['textRows'];
				//Element adding
				if ($frameAdded === true) {
					foreach ($frame['elements'] as $elementName => $elementMorf) {
						$returnData = analysiseSentenceWithFrameData($sentence, $elementName, $elementMorf, $textRows, $sentenceArray, $elementName, $key, false);
						$textRows = $returnData['textRows'];
					}
				}
			}
		}	
	}
	//print_r($sentenceArray);die;
	//add frames back to text
	
	$morphedText = implode("\n", $textRows);
	echo 'Lisati ' . $addedFrames . ' freim(i).';
	echo 'Lisati ' . $addedElements . ' element(i).';
	file_put_contents('C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\laused.kym', $morphedText);
	die;
}
else {
	echo 'Vale tee';
	exit;
}
exit;







/**
	Main function that 
*/
function analysiseSentenceWithFrameData($sentence, $lemma, $lemmaMorphAndNamesArray, $textRows, $sentenceArray, $frameName, $key, $isForLemma) {
	global $addedFrames;
	global $addedElements;
	$frameAdded = false;
	$elementAdded = false;
	$lemmaMorph = $lemmaMorphAndNamesArray['morf'];
	unset($lemmaMorphAndNamesArray['morf']);
	//print_r($lemmaMorphAndNamesArray);die;
	foreach ($lemmaMorphAndNamesArray as $lemmaKey => $lemmaSelection) {
	
		if (strpos($lemmaMorph, '*') !== false) {
			$lemmaMorph = substr($lemmaMorph, 1);
			$lemmaMorphWithStar = true;
		}
		else {
			$lemmaMorphWithStar = false;
		}
		if (strpos($lemmaMorph, '|||') !== false) {
			$lemmaPieces = explode('|||', $lemmaMorph);
			$lemmaSyntax = $lemmaPieces[1];
			$lemmaMorph = $lemmaPieces[0];
		}
		if (strpos($lemmaMorph, '||') !== false) {
			$lemmaMorphPieces = explode('||', $lemmaMorph);
			$lemmaPieces = explode(' ', $lemmaSelection);
			$lemmaWordsAmount = count($lemmaPieces);
		}
		else {
			$lemmaMorphPieces = array($lemmaMorph);
			$lemmaPieces = array($lemmaSelection);
			$lemmaWordsAmount = 1;
		}
		/*if ($lemmaWordsAmount == 2) {
			print_r($lemmaPieces);
		}*/
		$wordsMatched = 0;
		for ($lemmaPieceKey = 0; $lemmaPieceKey < $lemmaWordsAmount; $lemmaPieceKey++) {
			foreach ($sentence as $wordKey => $word) {
				foreach ($word['alg'] as $meaningKey => $wordMeaning) {
					//Adding frames
					
					if (!empty($lemmaSyntax) && $lemmaSyntax != false && strpos($word['morf'][$meaningKey], ' ' . $lemmaSyntax . ' ') === false) {
						continue;
					}
						
					$oneLemmaMorphPieces = explode(' ', $lemmaMorphPieces[$lemmaPieceKey]);
					if ($lemmaMorphWithStar === true) { /* looda morfi peale */
						//if lemma = wordmeaing VÕI morph = wordmorph, lisa freim kahtlusega
						
						if ($lemmaPieces[$lemmaPieceKey] == $wordMeaning) {
							$wordsMatched++; 
							if ($wordsMatched == $lemmaWordsAmount) {
								echo ++$addedFrames . '    ' .  $lemmaPieces[$lemmaPieceKey] . PHP_EOL;
								$sentenceArray[$key][$wordKey]['freimiInfo'] = $frameName;
								if (substr($textRows[$word['nr']], -1) == "\r") {
									$textRows[$word['nr']] = substr($textRows[$word['nr']], 0, -2);
								}
								$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . '// Freim õnnega2 ' . $frameName;
								$frameAdded = true;
							}
						}
						else {
							$allIncluded = true;
							foreach ($oneLemmaMorphPieces as $piece) {
								if (strpos($word['morf'][$meaningKey], ' ' . $piece . ' ') === false) {
									$allIncluded = false;
									break;
								}
							}
							if (!empty($allIncluded) && $allIncluded === true) {
								$wordsMatched++; 
								if ($wordsMatched == $lemmaWordsAmount) {
									$sentenceArray[$key][$wordKey]['freimiInfo'] = $frameName;
									//var_dump(substr($textRows[$word['nr']], -2));
									//print_r(strpos($textRows[$word['nr']], "\r") !== false);die;
									if (substr($textRows[$word['nr']], -1) == "\r") {
										//print_r('eeeeee');die;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0, -2);
									}
									if ($isForLemma === true) {
										echo ++$addedFrames . '    ' .  $lemmaPieces[$lemmaPieceKey] . PHP_EOL;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Freim õnnega1 ' . $frameName;
										$frameAdded = true;
									}
									else {
										echo ++$addedElements . '    ' .  $lemmaPieces[$lemmaPieceKey] . PHP_EOL;
										//print_r(utf8_encode(' // Element  õnnega1 '));die;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Element  õnnega1 ' . $frameName;
										$elementAdded = true;
									}
								}
							}
							else {
								continue;
							}
						}
					}
					else { /*exact match*/
						//if lemma = word JA morph = wordmorpüh
						if ($lemmaPieces[$lemmaPieceKey] == $wordMeaning) {
							print_r($wordMeaning);
							//print_r(++$counter . '---' .  PHP_EOL);
							$allIncluded = true;
							foreach ($oneLemmaMorphPieces as $piece) {
//									print_r($oneLemmaMorphPieces);
//										print_r($word['morf'][$meaningKey]);
								if (strpos($word['morf'][$meaningKey], ' ' . $piece . ' ') === false) {
									$allIncluded = false;
									break;
								}
							}
							if ($allIncluded === true) {
								//print_r($counter . '---' . PHP_EOL);
								$wordsMatched++; 
								if ($wordsMatched == $lemmaWordsAmount) {
									$sentenceArray[$key][$wordKey]['freimiInfo'] = $frameName;
									if (substr($textRows[$word['nr']], -1) == "\r") {
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0, strlen($textRows[$word['nr']]) - 2);
									}
									if ($isForLemma === true) {
										echo ++$addedFrames . '    ' .  $lemmaPieces[$lemmaPieceKey] . PHP_EOL;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Freim ' . $frameName;
										$frameAdded = true;
									}
									else {
										echo ++$addedElements . '    ' .  $lemmaPieces[$lemmaPieceKey] . PHP_EOL;
										$textRows[$word['nr']] = substr($textRows[$word['nr']], 0) . ' // Element  ' . $frameName;
										$elementAdded = true;
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
	return array(
		'added' => $frameAdded,
		'textRows' => $textRows,
	);
}

function addFrameInfoToText($textPath) {
	$morphedText = file_get_contents($textPath);
	if ($morphedText !== false) {
		
		$textRows = explode("\n", $morphedText);
	

	}
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
						$morphPart = $wordAndAnalysingParts[1];
					}
					else {						
						$morphPart = substr($wordAndAnalysingParts[1], strpos($wordAndAnalysingParts[1], ' //') + 3);			
					}
					$mainVerb = $wordAndAnalysingParts[0];
					$replace = array('=', '_');
					$mainVerb = str_replace($replace, '', $mainVerb);
					$mainVerb .= (strpos($morphPart, '_V_ main') !== false ? 'ma' : '');
					$base[] = utf8_encode($mainVerb);
					$morph[] = $morphPart;
				}
				/*
				$startPos = strpos($row, '    ') + 4;
				$len = (strpos($row, '+') != 0 ? strpos($row, '+'): strpos($row, '//') - 1) - $startPos;
				$mainVerb = substr($row, $startPos, $len);
				$startOfMorph = strpos($row, '//') + 2;
				$morphSub = substr($row, $startOfMorph);
				$firstOcc = strpos($morphSub, ' ') + 1;
				$morphSub2 = substr($morphSub, $firstOcc);
				$secondOcc = strpos($morphSub2, ' ');
				$morphPart = substr($morphSub, 0, $secondOcc + $firstOcc);
				*/
				//$mainVerb .= ($morphPart == '_V_ main' ? 'ma' : '');
				
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
