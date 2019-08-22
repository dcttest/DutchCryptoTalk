<?php


include (__DIR__ . '/vendor/autoload.php');
include("advies.php");
include("config.php");

$telegram = new Telegram($telegramId);

$antwoordenArray = json_decode(file_get_contents("snorBotAntwoorden.json"));
$weetjesArray = json_decode(file_get_contents("https://raw.githubusercontent.com/geensnor/weetjes/master/snorBotWeetjes.json"));


$text = $telegram->Text();
$chat_id = $telegram->ChatID();

$losseWoorden = explode(" ", $text);
$antwoord = "";
$send = FALSE;
    

    if($telegram->Location()){
    	$locatieGebruiker = $telegram->Location();
		//$adviesJson = json_decode(file_get_contents("https://advies.geensnor.nl/list.php?output=json&lat=".$locatieGebruiker["latitude"]."&lon=".$locatieGebruiker["longitude"]));
		$adviesJson = getAdviesArray($locatieGebruiker["latitude"], $locatieGebruiker["longitude"]);

		$contentAdviesTitel = ['chat_id' => $chat_id, 'text' => $adviesJson[0]->name." zit in de buurt:"];
		$contentAdviesToelichting = ['chat_id' => $chat_id, 'text' => "Geensnor zegt: '".$adviesJson[0]->description."'. Kijk op http://advies.geensnor.nl voor meer adviezen"];
		$contentLocation = ['chat_id' => $chat_id, 'latitude' => $adviesJson[0]->lat, 'longitude' => $adviesJson[0]->lon];
		$telegram->sendMessage($contentAdviesTitel);
		$telegram->sendLocation($contentLocation);
		$telegram->sendMessage($contentAdviesToelichting);
		$send = TRUE;

    }

    foreach ($losseWoorden as $wKey => $wValue){
    	//Dit werkt niet op heroku. Wel lokaal. Als dit uitstaat werkt dat if verhaal hierboven met location ook niet. Dus die staat gewoon aan.
    	if($losseWoorden[$wKey] == "advies" || $losseWoorden[$wKey] == "Advies"){
		
			$option = array(array($telegram->buildKeyBoardButton("Klik hier om je locatie te delen", $request_contact=false, $request_location=true)));
			$keyb = $telegram->buildKeyBoard($option, $onetime=false);
			$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "Aaaah, je wilt een advies van Geensnor. Goed idee! Druk op de knop hieronder aan te geven waar je bent.");
			$telegram->sendMessage($content);
			$send = TRUE;
		}
		if($losseWoorden[$wKey] == "weetje" || $losseWoorden[$wKey] == "Weetje"){
    		$randKey = array_rand($weetjesArray, 1);
    		$antwoord = "Interessant weetje: ".$weetjesArray[$randKey];
    		$send = TRUE;
    	}
    }


    if(!$send){
//Eerst op de hele zin/alle woorden zoeken ($text). Dit werkt voor geen meter....
		foreach ($antwoordenArray as $key => $value) {
	    	if(strstr($text, strtolower($antwoordenArray[$key]->trigger)) || strstr($text, ucfirst($antwoordenArray[$key]->trigger))){
	    		echo $text." ".$key." antwoord: ".$antwoordenArray[$key]->antwoord;
	    		$antwoord = $antwoordenArray[$key]->antwoord;
	    		$send = TRUE;	    			    		
	    	}
	    }
	    	
//Daarna kijken naar de losse woorden
    	if(!$send){
	    	foreach ($losseWoorden as $wKey => $wValue){
	 			foreach ($antwoordenArray as $key => $value) {
			    	if(strstr($losseWoorden[$wKey], strtolower($antwoordenArray[$key]->trigger)) || strstr($losseWoorden[$wKey], ucfirst($antwoordenArray[$key]->trigger))){
			    		$antwoord = $antwoordenArray[$key]->antwoord;
			    		$send = TRUE;	    			    		
			    	}
			    }
	    	}
    	}
    }	
	if(!$send){
		$antwoord = "Ik kan niets met: '".$text."'. Probeer eens een leuk weetje ofzo";
	}
	$content = ['chat_id' => $chat_id, 'text' => $antwoord];
	$telegram->sendMessage($content);


 //}