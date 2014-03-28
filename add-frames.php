<?php
error_reporting(-1);
//No ö, ä, ü, õ allowed in path
$morphedText = file_get_contents('C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\testkorpus.kym');
if ($morphedText !== false) {

	//print_r($_SERVER);die;
	
	/**
	 * read frames.xml and put them to $frames array
	 *	frames = (frameName => (luLemma, luPos, elements => (name => optional)), ...)
	*/
	
	$frames = array();
	$xml = new XMLReader();
	$xml->open('C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\frames.xml');
	while ($xml->read()) {
		//$node = new SimpleXMLElement($xml->readOuterXML());
		
		if ($xml->name == 'frame' && $xml->nodeType == XMLReader::ELEMENT) {
			$frameName =  htmlentities($xml->getAttribute('name'));
			$frames[$frameName] = array();
		}
		else if ($xml->name == 'lu' && $xml->nodeType == XMLReader::ELEMENT) {
			$frames[$frameName][] = htmlentities($xml->getAttribute('lemma'));
			$frames[$frameName][] = htmlentities($xml->getAttribute('pos'));
		}
		else if ($xml->name == 'Elements' && $xml->nodeType == XMLReader::ELEMENT) {
			$frames[$frameName]['elements'] = array();
		}
		else if ($xml->name == 'element' && $xml->nodeType == XMLReader::ELEMENT) {
			$frames[$frameName]['elements'][htmlentities($xml->getAttribute('name'))] = htmlentities($xml->getAttribute('optional'));
		}
	}
		
	$convert = explode("\n", $morphedText);
	
	$addedFrames = 0;
	foreach ($convert as $k => $row) {
		//eemaldab read <s> jne
		if (preg_match('/<\/?.>/', $row) != 0) {}
		elseif (strpos($row, '_V_ main') !== false) {
			$startPos = strpos($row, '    ') + 4;
			$mainVerb = substr($row, $startPos, strpos($row, '+') - $startPos) . 'ma';  
			
			foreach ($frames as $key => $i) {
				if ($i[0] == $mainVerb) {
					$addedFrames++;
					//print_r(rtrim($convert[$k], "\r"));
					$convert[$k] = rtrim($convert[$k], "\r") . ' ' . $key . "\r";
				}
			}
		}
		
	}
	$morphedText = implode("\n", $convert);
	echo 'Lisati ' . $addedFrames . ' freim(i).';
	file_put_contents('C:\Users\Lauri\Desktop\Baka asjad\gitBaka\jalgpalliFreimNet\testkorpus_fremidega.kym', $morphedText);
}
else {
	echo 'Vale tee';
	exit;
}
exit;
?>
