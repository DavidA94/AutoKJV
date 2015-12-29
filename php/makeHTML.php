<?php
require_once("book.php");
require_once("databaseVars.php");

/****************************************************
** makePassageHTML function definition              *
** Takes in a book and reference information, gets  *
** the Bible verses from the database, and then     *
** puts it into an HTML template.                   *
-- ------------------ Parameters ------------------ -
** book (Book): A Book of the Bible                 *
** chapterStr (int): The (starting) chapter to get  *
** chapterStr2 (int): The ending chapter to get,    *
**      or null if there is no second chapter       *
** verses(int[]): The verses to get, based on if:   *
**      [] (empty): Full chapter or chapter range   *
**      [2]: When chapterStr2 is not null, then     *
**          the staring and ending verses to match  *
**          the staring and ending chapters.        *
**      [n]: A list of individual verses for a      *
**          given chapter.                          *
-- --------------- Returns (string) --------------- -
** A string of HTML filled with the requested       *
** information. An empty string means a bad request *
****************************************************/
function makePassageHTML($book, $chapterStr, $chapterStr2, array $verses) {
    // If we don't have two chapters, then get the pretty verses
    if($chapterStr2 == null){
        // Make sure the verses are in order
        sort($verses);
        $prettyVerses = makePrettyVerses($verses);
    }

    // Holds what we put into the passage
    $versesHTML = array();

    // If we have two chapters
    if($chapterStr2 != null){
        // If we have no verses, we want to get a range of chapters
        if(count($verses) == 0){
            // Get the verses from the DB
            list($numVerses, $chVerses) = getChapterRange($book->getID(), $chapterStr, $chapterStr2);

            // If we didn't get anything back, then this was a bad request
            // so return an empty string.
            if(count($chVerses) == 0){
                return '';
            }

            // We need to keep track of which verse we're on. How many verses
            // there are in each chapter is returned in the $numVerses array, so
            // we need to know which index (chapter) we're on.
            $verseCounter = 1; // Start on the first verse
            $verseCounterIdx = 0; // in the first chapter
            
            // Loop through all the verses we got back
            foreach($chVerses as $verseTxt){
                // Add the verse to the array
                array_push($versesHTML, makeVerse($verseCounter, $verseTxt));

                // Up the counter
                ++$verseCounter;

                // If we've gone over how many verses there are for this chapter,
                // and we're not on the last element, then
                if($verseCounter > $numVerses[$verseCounterIdx] && $verseTxt !== end($chVerses)){
                    // Reset to verse 1
                    $verseCounter = 1;
                    // Set the index to the next chapter
                    ++$verseCounterIdx;
                    // And add a chapter separator
                    array_push($versesHTML, makeChapterSeparator(((int)$chapterStr) + $verseCounterIdx));
                }
            }

            // Then make prettyVerses be a null so there is no colon
            $prettyVerses = null;
        }
        // Otherwise, we need to get between chapters (cross chapter range)
        else{
            // Get the verses from the database
            list($numVerses, $chVerses) = getChapterVerseRange($book->getID(), $chapterStr, $chapterStr2, $verses);

            // If we didn't get anything back, then this was a bad request
            // so return an empty string.
            if(count($chVerses) == 0){
                return '';
            }

            // This works the same as above
            $verseCounter = 1;
            $verseCounterIdx = 0;
            foreach($chVerses as $verseTxt){
                array_push($versesHTML, makeVerse($verseCounter, $verseTxt));

                ++$verseCounter;
                if($verseCounter > $numVerses[$verseCounterIdx] && $verseTxt !== end($chVerses)){
                    $verseCounter = 1;
                    ++$verseCounterIdx;
                    array_push($versesHTML, makeChapterSeparator(((int)$chapterStr) + $verseCounterIdx));
                }
            }

            // We want pretty verses to be an array with the starting and
            // ending numbers, so when we make the passage, they can be put in
            $prettyVerses = array($verses[0], $verses[1]);
        }
    }
    // Otherwise, If we're given any verses, then get those
    else if(count($verses) > 0){
        // Get the verses from the database
        $chVerses = getVerses($book->getID(), $chapterStr, $verses);

        // If we didn't get anything back, then this was a bad request
        // so return an empty string.
        if(count($chVerses) == 0){
            return '';
        }
        
        // Loop through all the verses we got back
        for($i = 0; $i < count($chVerses); ++$i){
            // If any of them are an empty string, then we
            // made a bad request, so return an empty string
            if($chVerses[$i] == ''){
                return '';
            }
            
            // Otherwise, add the verse to the array
            array_push($versesHTML, makeVerse($verses[$i], $chVerses[$i]));
        }
    }
    // If we make it this far, we must one one full chapter
    else{
        // Get the verses from the database
        $chVerses = getChapter($book->getID(), $chapterStr);
        
        // If we didn't get anything back, then this was a bad request
        // so return an empty string.
        if(count($chVerses) == 0){
            return '';
        }

        // Otherwise, add each verse to the array
        for($vNum = 1; $vNum <= count($chVerses); ++$vNum){
            array_push($versesHTML, makeVerse($vNum, $chVerses[$vNum - 1]));
        }

        // And make prettyVerses be a null so there is no colon
        $prettyVerses = null;
    }
    
    // Then make the passage from the verses we created
    return makePassage($book, $chapterStr, $chapterStr2, $prettyVerses, $versesHTML);
}

/********************************************
** makePrettyVerses function definition     *
** Takes an array of verses, and creates a  *
** pretty string that can be place into the *
** HTML produced.                           *
-- -------------- Parameters -------------- -
** verses (int[]): An array in integers     *
-- ----------- Returns (string) ----------- -
** A string with the verses made to look    *
** pretty.                                  *
** E.g. 1,3,4,5,6,9,10 -> 1, 3-6, 9-10      *
********************************************/
function makePrettyVerses(array $verses) {
    // Holds what we return
    $retVal = '';
    
    // Save having to make this call multiple times
    $numVerses = count($verses);

    // Loop through all of the verses
    for($idx = 0; $idx < $numVerses; ++$idx){
        // If the number is +1 to the next one, then we're in a range
        if($idx + 1 < $numVerses && (int)$verses[$idx] == (int)$verses[$idx + 1] - 1){
            // Add the first number, and a comma if something has already been added
            $retVal .= (($retVal == '' ? '' : ', ') . (string)$verses[$idx]);

            // Get to the end of the range
            while($idx + 1 < $numVerses && (int)$verses[$idx] == (int)$verses[$idx + 1] - 1){
                ++$idx;
            }

            // Add the dash and the end number
            $retVal .= ("-" . $verses[$idx]);
        }
        // Otherwise, it's not a range, so just add the number, and a comma
        // if something has already been added.
        else{
            $retVal .= (($retVal == '' ? '' : ', ') . (string)$verses[$idx]);
        }
    }

    // Return what we got
    return $retVal;
}

/************************************************
** makePassage function definition              *
** Places the passed in verses into a wrapper   *
-- ---------------- Parameters ---------------- -
** bookName (string): The name of the book      *
** chapter (int): The chapter of the passage    *
** chapter2 (int) The second chapter if this is *
**      a cross chapter range                   *
** versesStr: What verses this passage contains *
**      null: No verses, this is one more full  *
**          chapters                            *
**      array(2): The starting and ending verse *
**          for a cross chapter range           *
**      string: A pre-formatted string that can *
**          be dumped in.                       *
-- ------------- Returns (string) ------------- -
** A string of HTML with verses in a wrapper    *
************************************************/
function makePassage($bookName, $chapter, $chapter2, $versesStr, $verses){
    // Get the template from the file
    $passageTemplate = file_get_contents(dirname(__FILE__) . '/templates/passage.tmpl', FILE_USE_INCLUDE_PATH);

    // Because Psalms has to be different, fix it if multiple chapters
    if($bookName == "Psalm" && $versesStr == null && $chapter2 != null){
        $bookName = "Psalms";
    }
    
    // Replace the variables in the template with the corresponding real values
    $temp = str_replace('$bookName', $bookName, $passageTemplate);
    $temp = str_replace('$refNums', makePrettyReference($chapter, $chapter2, $versesStr), $temp);
    $temp = str_replace('$versesHTML', implode("\n", $verses), $temp);
        
    // Return the string of HTML
    return $temp;
}

/************************************************
** makePrettyVerses function definition         *
** Takes in chapter(s) and verses and makes     *
** a pretty reference to be shown to the user.  *
-- ---------------- Parameters ---------------- -
** chap (int): The starting chapter             *
** chap (int?): The ending chapter. Must be     *
**      present if verses is an array.          *
** verses: The verses to be used.               *
**      string: Pre-formatted, and assumes      *
**           there is only one chapter          *
**      array(2): The starting and ending verse *
**          for a cross chapter reference       *
**      null: Indicates a chapter (range)       *
-- ------------- Returns (string) ------------- -
** A string that is a pretty reference          *
** E.g: Given 1,2,[3,6] -> 1:3-2:6              *
**      Given 1,2,null  -> 1-2                  *
**      Given 1,null,7  -> 1:7                  *
************************************************/
function makePrettyReference($chap, $chap2, $verses){
    // If verses comes in as a string
    if(is_string($verses)){
        // Assume only one chapter
        return $chap . ":" . $verses;
    }
    // If it's an array, then a cross chapter range (e.g. 1:9-2:4)
    else if(is_array($verses)){
        // Assume both chapters are valid, and there are two verses in the array
        return $chap . ":" . $verses[0] . "-" . $chap2 . ":" . $verses[1];
    }
    // Otherwise, it must be null, so just the chapter(s)
    else{
        return $chap . ($chap2 != null ? "-" . $chap2 : "");
    }
}

/************************************************
** makeVerse function definition                *
** Given a verse number and text, creates an    *
** HTML verse from the verse template.          *
-- ---------------- Parameters ---------------- -
** verseNum (int): The verse number             *
** verseTxt (string): The text of the verse     *
-- ------------- Returns (string) ------------- -
** A string of HTML based on the verse template *
************************************************/
function makeVerse($verseNum, $verseTxt){
    // Get the template from the file
    $verseTemplate = file_get_contents(dirname(__FILE__) . '/templates/verse.tmpl', FILE_USE_INCLUDE_PATH);
    
    // Replace the variables with the necessary information, and return it.
    return str_replace('$verseNum', $verseNum, str_replace('$verseTxt', $verseTxt, $verseTemplate));
}

/************************************************
** makeChapterSeparator function definition     *
** Creates a chapter separator from the         *
** template and returns it.                     *
-- ---------------- Parameters ---------------- -
** chapterNum (int): The chapter that follows   *
-- ------------- Returns (string) ------------- -
** An HTML chapter separator                    *
************************************************/
function makeChapterSeparator($chapterNum){
    // Get the template from the file
    $chapterSeparatorTemplate = file_get_contents(dirname(__FILE__) . '/templates/chapterSeparator.tmpl', FILE_USE_INCLUDE_PATH);
    
    // Replace the variables with the necessary information, and return it.
    return str_replace('$chapter', $chapterNum, $chapterSeparatorTemplate);
}

/****************************************
** makePassageCount function definition *
** Creates HTML for how many passages   *
** there are.                           *
-- ------------ Parameters ------------ -
** numPassages (int): The # of passages *
-- --------- Returns (string) --------- -
** An HTML passage count                *
****************************************/
function makePassageCount($numPassages){
    // Get the template from the file
    $passageCount = file_get_contents(dirname(__FILE__) . '/templates/passageCount.tmpl', FILE_USE_INCLUDE_PATH);

    // Determine if we need an S at the end
    $word = $numPassages == 1 ? "Reference" : "References";
    
    // Replace the variables with the necessary information, and return it.
    return str_replace('$count', $numPassages, str_replace('$word', $word, $passageCount));
}

/********************************************
** makeBadRef function definition           *
** Creates HTML for a bad reference         *
-- -------------- Parameters -------------- -
** refStr (string): The original reference  *
-- ----------- Returns (string) ----------- -
** An HTML bad reference                    *
********************************************/
function makeBadRef($refStr){
    // Get the template from the file
    $badRefTemplate = file_get_contents(dirname(__FILE__) . '/templates/badRef.tmpl', FILE_USE_INCLUDE_PATH);
    
    // Replace the variables with the necessary information, and return it.
    return str_replace('$badRef', $refStr, $badRefTemplate);
}

/********************************************
** getVerses function definition            *
** Gets a set of verses from the database   *
-- -------------- Parameters -------------- -
** bookId (int): The ID (1-66) of the book  *
** chapter (int): The chapter of the book   *
** verses (int[]): The verses we want from  *
**      the book                            *
-- ---------- Returns (string[]) ---------- -
** An array of the verses requested, or an  *
** empty array if the database is unable to *
** be connected to.                         *
********************************************/
function getVerses($bookId, $chapter, $verses){
    // Declared in databaseVars.php
    global $host, $dbname, $user, $pass;
    
    // Open a connection to the database
    $kjv = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");

    // If the connection was unsuccessful
    if(!$kjv){
        // Then return an empty array
        return array();
    }

    // Build a query string for the verse numbers
    $verseQuery = 'verseno = ?';
    // The first one is already there, so now make the rest
    for($vCnt = 1; $vCnt < count($verses); ++$vCnt){
        $verseQuery .= ' OR verseno = ?';
    }
    
    // Prepare the query
    $select = $kjv->prepare("SELECT versetext FROM kjv WHERE bookid = ? AND chapterno = ? AND ($verseQuery)");
    // Execute the query
    $select->execute(array_merge(array($bookId, $chapter), $verses));

    // Make an array to hold the verses
    $versesTxt = array();

    // Put all the verses into an array
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        array_push($versesTxt, $row['versetext']);
    }

    // Return what we got
    return $versesTxt;
}

/********************************************
** getChapter function definition           *
** Gets one chapter from a specific book    *
-- -------------- Parameters -------------- -
** bookId (int): The ID (1-66) of the book  *
** chapter (int): The chapter we want       *
-- ---------- Returns (string[]) ---------- -
** An array of the verses requested, or an  *
** empty array if the database is unable to *
** be connected to.                         *
********************************************/
function getChapter($bookId, $chapter){
    // Declared in databaseVars.php
    global $host, $dbname, $user, $pass;
    
    // Open a connection to the database
    $kjv = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");
    
    // If the connection was unsuccessful
    if(!$kjv){
        // Then return an empty array
        return array();
    }
    
    // Prepare the query and execute it
    $select = $kjv->prepare("SELECT versetext FROM kjv WHERE bookid = ? AND chapterno = ? ORDER BY verseno ASC");
    $select->execute(array($bookId, $chapter));

    // Holds what we will return
    $verses = array();

    // Loop through and get all of the verses text
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        array_push($verses, $row['versetext']);
    }

    // Return what we got
    return $verses;
}

/****************************************************
** getChapterRange function definition              *
** Gets a range of chapters from a specific book    *
-- ------------------ Parameters ------------------ -
** bookId (int): The ID (1-66) of the book          *
** chapter (int): The starting chapter we want      *
** chapter2 (int): The ending chapter we want       *
-- ------- Returns (array(int[], string[])) ------- -
** Returns an array, where the first index is an    *
** array of integers, such that each number         *
** corresponds to how many verses there are in a    *
** chapter, and the second index is an array of     *
** strings that contains the text of all the verses *
** or both arrays are empty if the database cannot  *
** be connected to.                                 *
****************************************************/
function getChapterRange($bookId, $chapter, $chapter2){
    // Declared in databaseVars.php
    global $host, $dbname, $user, $pass;
    
    // Open a connection to the database
    $kjv = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");
    
    // If the connection was unsuccessful
    if(!$kjv){
        // Then return empty arrays
        return array(array(), array());
    }

    // Prepare and execute the query
    $select = $kjv->prepare("SELECT versetext, chapterno, verseno FROM kjv WHERE bookid = ? AND " .
                            "(chapterno >= ? AND chapterno <= ?) ORDER BY chapterno, verseno ASC");
    $select->execute(array($bookId, $chapter, $chapter2));

    // Holds what we will return
    $numVerses = array();
    $verses = array();
    
    // We need to note how many of each verse goes to each chapter
    $curChapNo = (int)$chapter; // The current chapter is the starting one
    $curVerseNo = 1;            // And start on the first verse
    
    // Loop through and get all of the verses text
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        // Add the verse
        array_push($verses, $row['versetext']);

        // If we didn't change chapters, up the verse number
        if($row['chapterno'] == $curChapNo){
            ++$curVerseNo;
        }
        // Otherwise, put the verse number into the numVerses array,
        // and reset the current number to 1.
        else{
            // Minus one because the last run went one over
            array_push($numVerses, $curVerseNo - 1);
            $curVerseNo = 1;
            $curChapNo = $row['chapterno'];
        }
    }
    // Push the last chapter in.
    array_push($numVerses, $curVerseNo);

    // Return what we got
    return array($numVerses, $verses);
}

/****************************************************
** getChapterVerseRange function definition         *
** Gets verses from a cross chapter range           *
** (e.g. John 2:4-4:7)                              *
-- ------------------ Parameters ------------------ -
** bookId (int): The ID (1-66) of the book          *
** chapter (int): The starting chapter we want      *
** chapter2 (int): The ending chapter we want       *
** verses (int[2]): The starting and ending verse   *
-- ------- Returns (array(int[], string[])) ------- -
** Returns an array, where the first index is an    *
** array of integers, such that each number         *
** corresponds to how many verses there are in a    *
** chapter, and the second index is an array of     *
** strings that contains the text of all the verses *
** or both arrays are empty if the database cannot  *
** be connected to, or if verses is not an int[2]   *
****************************************************/
function getChapterVerseRange($bookId, $chapter, $chapter2, array $verses){
    // Declared in databaseVars.php
    global $host, $dbname, $user, $pass;
    
    // Open a connection to the database
    $kjv = new PDO("mysql:host=$host;dbname=$dbname", "$user", "$pass");
    
    // If we weren't given exactly two verses, or if the connection was unsuccessful
    if(count($verses) != 2 || !$kjv){
        // Then return empty arrays
        return array(array(), array());
    }
    
    // Prepare and execute the query
    $select = $kjv->prepare("SELECT versetext, chapterno, verseno FROM kjv WHERE bookid = ? AND " .
                            "((chapterno = ? AND verseno >= ?) OR " .  // Starting chapter, starting verse and up
                            "(chapterno > ? AND chapterno < ?) OR " .  // Any in-between chapters
                            "(chapterno = ? and verseno <= ?)) " .     // Ending chapter, ending verse and down
                            " ORDER BY chapterno, verseno ASC");
    $select->execute(array($bookId, $chapter, $verses[0],
                                    $chapter, $chapter2,
                                    $chapter2, $verses[1]));

    // Hold what we will return
    $numVerses = array();
    $verses = array();

    // We need to note how many of each verse goes to each chapter
    $curChapNo = (int)$chapter; // The current chapter is the starting one
    $curVerseNo = 1;            // And start on the first verse
    
    // Loop through and get all of the verses text
    while($row = $select->fetch(PDO::FETCH_ASSOC)){
        // Add the verse
        array_push($verses, $row['versetext']);

        // If we didn't change chapters, up the verse number
        if($row['chapterno'] == $curChapNo){
            ++$curVerseNo;
        }
        // Otherwise, put the verse number into the numVerses array,
        // and reset the current number to 1.
        else{
            // Minus one because the last run went one over
            array_push($numVerses, $curVerseNo - 1);
            $curVerseNo = 1;
            $curChapNo = $row['chapterno'];
        }
    }
    // Push the last chapter in.
    array_push($numVerses, $curVerseNo);

    // Return what we got
    return array($numVerses, $verses);
}

?>