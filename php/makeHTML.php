<?php

require_once("book.php");
require_once("databaseVars.php");

function makePassageHTML($book, $chapterStr, array $verses) {    
    // Make sure the verses are in order
    sort($verses);
    $prettyVerses = makePrettyVerses($verses);
    
    // Holds what we put into the passage
    $versesHTML = [];
    
    // If we're given any verses, then get just those
    if(count($verses) > 0){
        foreach($verses as $verse){
            $verseTxt = getVerse($book->getID(), $chapterStr, $verse);
            
            // If any verse doesn't exist, we have a bad reference
            if($verseTxt == ''){
                return '';
            }
            
            array_push($versesHTML, makeVerse($verse, $verseTxt));
        }
    }
    // Otherwise, we'll want the full chapter
    else{
        $chVerses = getChapter($book->getID(), $chapterStr);
        // If we asked for a bad chapter, then return an empty string
        if(count($chVerses) == 0){
            return '';
        }
        
        // Otherwise, put each verse in
        for($vNum = 1; $vNum <= count($chVerses); ++$vNum){
            array_push($versesHTML, makeVerse($vNum, $chVerses[$vNum - 1]));
        }
        
        // And make prettyVerses be a backspace to get rid of the colon
        $prettyVerses = null;
    }
    
    return makePassage($book, $chapterStr, $prettyVerses, $versesHTML);
}

function makePrettyVerses(array $verses) {
    // What we will return
    $retVal = '';
    $numVerses = count($verses);
    
    
    // Loop through the rest
    for($idx = 0; $idx < $numVerses; ++$idx){
        // If the number is +1 to the last one, then we're in a range
        if($idx + 1 < $numVerses && (int)$verses[$idx] == (int)$verses[$idx + 1] - 1){
            // Add the first number, and a comma if necessary
            $retVal .= (($retVal == '' ? '' : ', ') . (string)$verses[$idx]);
            
            // Get to the end of the range
            while($idx + 1 < $numVerses && (int)$verses[$idx] == (int)$verses[$idx + 1] - 1){
                ++$idx;
            }
            
            // Add the dash and the end number
            $retVal .= ("-" . $verses[$idx]);
        }
        // Otherwise, it's not a range, so just add the number, and a comma if necessary
        else{
            $retVal .= (($retVal == '' ? '' : ', ') . (string)$verses[$idx]);
        }
    }
    
    return $retVal;
}

function makePassage($bookName, $chapter, $versesStr, $verses){
    $passageTemplate = file_get_contents(dirname(__FILE__) . '/templates/passage.tmpl', FILE_USE_INCLUDE_PATH);
    
    $temp = str_replace('$bookName', $bookName, $passageTemplate);
    $temp = str_replace('$chapter', $chapter, $temp);
    $temp = str_replace('$versesHTML', implode("\n", $verses), $temp);
    
    if($versesStr == null){
        $temp = str_replace(':$verseNum', '', $temp);
    }
    else{
        $temp = str_replace('$verseNum', $versesStr, $temp);
    }
    
    return $temp;
}

function makeVerse($verseNum, $verseTxt){
    $verseTemplate = file_get_contents(dirname(__FILE__) . '/templates/verse.tmpl', FILE_USE_INCLUDE_PATH);
    
    return str_replace('$verseNum', $verseNum, str_replace('$verseTxt', $verseTxt, $verseTemplate));
}

function makeBadRef($refStr){
    $badRefTemplate = file_get_contents(dirname(__FILE__) . '/templates/badRef.tmpl', FILE_USE_INCLUDE_PATH);
    
    return str_replace('$badRef', $refStr, $badRefTemplate);
}

function getVerse($bookId, $chapter, $verse){
    global $host, $dbname, $user, $pass;
    
    $kjv = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");
    
    if(!$kjv){
        // For some reason we can't get to the DB, so no text will be returned.
        return '';
    }
    
    $select = $kjv->prepare("SELECT versetext FROM kjv WHERE bookid = ? AND chapterno = ? and verseno = ?");
    $select->execute(array($bookId, $chapter, $verse));
    
    // We're only expecting one row to return.
    $row = $select->fetch(PDO::FETCH_ASSOC);
    
    // Return an empty string if we asked for a bad verse,
    // Otherwise return the text of the verse.
    return $row === false ? '' : $row['versetext'];
}

function getChapter($bookId, $chapter){
    global $host, $dbname, $user, $pass;
    
    $kjv = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");
    
    $select = $kjv->prepare("SELECT versetext FROM kjv WHERE bookid = ? AND chapterno = ? ORDER BY verseno ASC");
    $select->execute(array($bookId, $chapter));
    
    // Hold what we will return
    $verses = [];
    
    // Loop through and get all of the verses text
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        array_push($verses, $row['versetext']);
    }
    
    // Return what we got
    return $verses;
}

?>