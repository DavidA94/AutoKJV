<?php
require_once('book.php');
require_once('makeHTML.php');

/****************************************************
** getBibleHTML function definition                 *
** Parses a string of references and gets the HTML  *
** for those verses. This will also cache any       *
** requests so that there is no need to parse, or   *
** make a database call when it is requested again. *
-- ------------------ Parameters ------------------ -
** refString: A string of references that are       *
**      separated by semicolons (;).                *
**      Possible formats                            *
**      [Book] [Chapter]                            *
**      [Book] [Chapter]-[Chapter]                  *
**      [Book] [Chapter]:[Verse]                    *
**      [Book] [Chapter]:[Verse]-[Verse], [Verse]   *
**      [Book] [Chapter]:[Verse]-[Chapter]:[Verse]  *
** showReferenceCount: Indicates if the number of   *
**      references found should be included in the  *
**      HTML.                                       *
** prefix: Any string that should appear before the *
**      first reference in the initial display.     *
**      This can be used to with postfix to wrap    *
**      the reference(s) with something like        *
**      parenthesis.                                *
** postfix: Any string that should appear after the *
**      last reference in the initial display.      *
**      This can be used to with prefix to wrap     *
**      the reference(s) with something like        *
**      parenthesis.                                *
** separator: Any string that should appear between *
**      the references, when there is more than one *
-- --------------- Returns (string) --------------- -
** A string of HTML that has all of the references, *
** along with their corresponding text inside of    *
** the templates found in the templates folder.     *
****************************************************/
function getBibleHTML($refString, $showReferenceCount = true, $prefix = "", $postfix = "", $separator = ""){
    // -- Check if we've already cached this request --
    // Get the filename of where the cached file would be.
    // The filename is the MD5 hash of the request string, so
    // that the filename won't be too long, as it easily can be.
    $filename = dirname(__FILE__) . "/cached/" . md5($refString . $prefix . $postfix . $separator) . $showReferenceCount . ".cache";

    // If the file exists, just return its contents, and we're done here.
    if(file_exists($filename)){
        return file_get_contents($filename, FILE_USE_INCLUDE_PATH);
    }

    // ---------- No cache ----------

    // Holds an array of passages that can be added to
    // before the final string is created.
    $passages = array();

    // Split at each reference
    $refs = explode(";", $refString);

    // Parse each reference
    foreach($refs as $ref){
        // Kill any side spaces
        $ref = trim($ref);

        // Check that this meets the basic requirements of a reference
        // It must have a space (for between the book and the number(s))
        // It also can't have more than two colons, but it can have that
        // many (for a cross chapter range) or fewer.
        if(strpos($ref, ' ') === false || substr_count($ref, ':') > 2){
            // If it doesn't match the basic requirements, then make a bad reference.
            array_push($passages, makeBadRef($ref));
            // Any time we make a bad ref, we need to call continue
            // to ensure that we go to the next reference, or we might
            // get a double instance of it.
            continue;
        }

        // Find where the first digit in the string is: this is where the
        // reference chapter starts. We need to start at the second character
        // as the first one may be a number for something like 2 John.
        $pos = 1;
        for(; $pos < strlen($ref); ++$pos){
            // If the character at this position is a number
            if(ctype_digit($ref[$pos])){
                // Break out of the loop
                break;
            }
        }

        // If our position is the length of the reference
        // Then we didn't find a number
        if($pos == strlen($ref)){
            // So make a bad reference and continue
            array_push($passages, makeBadRef($ref));
            continue;
        }
        else{
            // If we did get a valid position, then
            // The first bit is the book name
            $bookStr = trim(substr($ref, 0, $pos));

            // And the rest is the chapter/verse stuff that we'll break up next
            // We also don't want any spaces for that, so remove them.
            $refNumsStr = str_replace(' ', '', substr($ref, $pos));
        }

        // Scenario 1-2: No colon, thus we want the full chapter(s) (e.g. John 1 or Acts 2-4);
        if(strpos($refNumsStr, ':') === false){
            // Scenario 2: We have a dash, so we want a chapter range
            if(strpos($refNumsStr, '-') !== false){
                // Get the chapter numbers into different variables
                list($chapStr, $chapStr2) = explode('-', $refNumsStr);
            }
            // Scenario 1, we only want one chapter
            else{
                // Put that number into the common variable between scenario 1 and 2.
                $chapStr = $refNumsStr;
            }

            // Set this to null so we know we're not on a scenario 4
            $verseStr = null;
        }
        // Scenario 3 and 4
        else{
            // Scenario 4: There are two colons, so we want a cross chapter range (e.g. Joshua 1:8-2:4)
            if(substr_count($refNumsStr, ':') == 2){
                // If there's no dash, then it's a bad reference
                if(strpos($refNumsStr, '-') === false){
                    array_push($passages, makeBadRef($ref));
                    continue;
                }

                // Place each side into temporary variables
                list($ref1, $ref2) = explode('-', $refNumsStr);

                // Get the first chapter and verse
                list($chapStr, $verseStr) = explode(':', $ref1);

                // Get the second chapter and verse
                list($chapStr2, $verseStr2) = explode(':', $ref2);
            }
            // Otherwise, we just need to split at the one
            else{
                // Get the chapter and verse into the shared variable name
                list($chapStr, $verseStr) = explode(':', $refNumsStr);
            }
        }

        // Get a Book object from the book string
        $book = getBook($bookStr);

        // If the book we were given has a negative index, then
        // we have a bad book, so make a bad reference
        if($book->getID() < 0){
            array_push($passages, makeBadRef($ref));
            continue;
        }

        // Check that the chapter is an integer, and if there is a second chapter,
        // that it also is an integer. If either fail, then make a bad reference.
        if(!ctype_digit($chapStr) || (isset($chapStr2) && !ctype_digit($chapStr2))){
            array_push($passages, makeBadRef($ref));
            continue;
        }

        // Ditto, but for the verses
        if($verseStr != null && isset($verseStr2) && (!ctype_digit($verseStr) || !ctype_digit($verseStr2))){
            array_push($passages, makeBadRef($ref));
            continue;
        }
        // If verseStr2 is set, then we have Scenario 4
        else if(isset($verseStr2)){
            // Set verses to be an array with the two verses that we want between (inclusive)
            $verses = array($verseStr, $verseStr2);
        }
        // Otherwise, we have Scenario 1, 2, or 3
        else{
            // Scenario 1 and 2 will have $verseStr be null, whereas 3
            // indicates that we need to get all the verse numbers.
            // We can't just do an array with this one, as it could be
            // Genesis 1:1,3,5,7,9, so we need each individual number
            $verses = $verseStr != null ? getVerseNums($verseStr) : array();
        }

        // Get the HTML for this passage.
        // The @ will suppress any warnings if $chapStr2 isn't set
        $passageHTML = makePassageHTML($book, $chapStr, @$chapStr2, $verses);

        // unset $chapStr2 so it has to be set again to be valid (or weird things happen)
        unset($chapStr2);

        // If we were returned an empty string, then make a bad reference
        if($passageHTML == ''){
            array_push($passages, makeBadRef($ref));
            // Still doing this, even though it's at the end. Safe over sorry.
            continue;
        }
        else{
            // Otherwise, add the HTML to the passages array
            array_push($passages, $passageHTML);
        }
    }

    // This is what we will return. First add in
    // The number of references we're giving back
    $numPassages = count($passages);
    $retVal = ($showReferenceCount ? makePassageCount($numPassages) . "\n" : "");

    // Replace the $prefix holder on the first reference, and the $postfix holder
    // on the last reference. Then in all others, we will replace with nothingness.
    $passages[0] = str_replace('$prefix', $prefix, $passages[0]);
    $passages[$numPassages - 1] = str_replace('$postfix', $postfix, $passages[$numPassages - 1]);
    
    // Then add each passage separated by a line break
    foreach($passages as $passage){
        $retVal .= str_replace('$prefix', '', str_replace('$postfix', $separator, $passage)) . "\n";
    }

    // If the directory where we cache things doesn't exist yet, make it
    if(!is_dir(dirname($filename))){
        mkdir(dirname($filename), 0755, true);
    }

    // Write the cached result so we don't have to parse this one again
    $file = fopen($filename, 'w');
    fwrite($file, $retVal);

    // Return the HTML
    return $retVal;
}

/****************************************************
** getBook function definition                      *
** Takes in a string and gets the                   *
** Book that it matches.                            *
-- ------------------ Parameters ------------------ -
** bookName (string): The name of the book we want  *
-- ---------------- Returns (Book) ---------------- -
** A Book object containing the Book asked for.     *
** If an invalid name is given, then the ID of the  *
** Book will be negative                            *
****************************************************/
function getBook($bookName) {
    // Create an array with all the books of the Bible
    $books = array(
        new Book(1, "Genesis"),
        new Book(2, "Exodus"),
        new Book(3, "Leviticus"),
        new Book(4, "Numbers", array("Nmbs")),
        new Book(5, "Deuteronomy", array("Dt")),
        new Book(6, "Joshua"),
        new Book(7, "Judges", array("Jdgs")),
        new Book(8, "Ruth"),
        new Book(9, "I Samuel", array("1 Samuel")),
        new Book(10, "II Samuel", array("2 Samuel")),
        new Book(11, "I Kings", array("1 Kings")),
        new Book(12, "II Kings", array("2 Kings")),
        new Book(13, "I Chronicles", array("1 Chronicles")),
        new Book(14, "II Chronicles", array("2 Chronicles")),
        new Book(15, "Ezra"),
        new Book(16, "Nehemiah"),
        new Book(17, "Esther"),
        new Book(18, "Job"),
        new Book(19, "Psalm"),
        new Book(20, "Proverbs", array("Prvb")),
        new Book(21, "Ecclesiastes"),
        new Book(22, "Song of Solomon", array("SS, SoS")),
        new Book(23, "Isaiah"),
        new Book(24, "Jeremiah"),
        new Book(25, "Lamentations"),
        new Book(26, "Ezekiel", array("Ezk")),
        new Book(27, "Daniel"),
        new Book(28, "Hosea"),
        new Book(29, "Joel"),
        new Book(30, "Amos"),
        new Book(31, "Obadiah"),
        new Book(32, "Jonah"),
        new Book(33, "Micah"),
        new Book(34, "Nahum"),
        new Book(35, "Habakkuk"),
        new Book(36, "Zephaniah"),
        new Book(37, "Haggai"),
        new Book(38, "Zechariah"),
        new Book(39, "Malachi"),

        new Book(40, "Matthew", array("Mt")),
        new Book(41, "Mark", array("Mk")),
        new Book(42, "Luke", array("Lk")),
        new Book(43, "John", array("Jn")),
        new Book(44, "Acts"),
        new Book(45, "Romans"),
        new Book(46, "I Corinthians", array("1 Corinthians")),
        new Book(47, "II Corinthians", array("2 Corinthians")),
        new Book(48, "Galatians"),
        new Book(49, "Ephesians"),
        new Book(50, "Philippians", array("Php")),
        new Book(51, "Colossians"),
        new Book(52, "I Thessalonians", array("1 Thessalonians")),
        new Book(53, "II Thessalonians", array("2 Thessalonians")),
        new Book(54, "I Timothy", array("1 Timothy")),
        new Book(55, "II Timothy", array("2 Timothy")),
        new Book(56, "Titus"),
        new Book(57, "Philemon", array("Phl")),
        new Book(58, "Hebrews"),
        new Book(59, "James", array("Jms", "Jas")),
        new Book(60, "I Peter", array("I Pt", "1 Peter", "1 Pt")),
        new Book(61, "II Peter", array("II Pt", "2 Peter", "2 Pt")),
        new Book(62, "I John", array("I Jn", "1 John", "1 Jn")),
        new Book(63, "II John", array("II Jn", "2 John", "2 Jn")),
        new Book(64, "III John", array("III Jn", "3 John", "3 Jn")),
        new Book(65, "Jude"),
        new Book(66, "Revelation")
    );

    // Remove any periods, in case of something like "Gen." instead of just "Gen"
    $bookName = str_replace('.', '', $bookName);
    
    // Remove any double spaces
    $bookName = str_replace('  ',  ' ', $bookName);

    // Start out with a simple search in the array. This will
    // only work if the full name, with proper capitalization
    // is passed in.
    $book = array_search($bookName, $books);

    // If we didn't find anything, we'll need to do a more thorough search
    if($book === false){
        // Loop through each book looking for a match
        foreach($books as $b){
            // If the first character isn't a number, and it doesn't match, then keep going
            // We ignore number ones because those are kept in the alternatives, so they
            // will never match
            $bookNameTmp = $b->getName();
            if(!ctype_digit($bookName[0]) && $bookName[0] != $bookNameTmp[0]){
                continue;
            }

            /// Otherwise, if it's a match
            if($b->isMatch($bookName)){
                // Remember it and get out of the loop
                $book = $b;
                break;
            }
        }

        // If we still didn't find anything, then make a new book with a negative ID
        if($book === false){
            return new Book(-1, "");
        }
    }
    // Otherwise we found something
    else{
        // So get the actual Book object from the array index we were given
        $book = $books[$book];
    }

    // Return whatever we found (or didn't)
    return $book;
}

/****************************************************
** getVerseNums function definition                 *
** Takes in a string of verses, such as             *
** 1 OR 1-5 OR 1-2, 4-7, 10, etc. and gets all of   *
** the individual numbers for the given verses and  *
** ranges. There should be no spaces in the string. *
-- ------------------ Parameters ------------------ -
** verseStr: A string of verses, such as those that *
**      are in the description.                     *
-- ---------------- Returns (int[]) --------------- -
** An array of integers that contains all of the    *
** numbers passed in, and anything in-between for   *
** ranges.                                          *
****************************************************/
function getVerseNums($verseStr){
    // Holds the return value
    $verseList = array();

    // Split at the comma, to get any sections
    $verses = explode(',', $verseStr);

    // Loop through all the verse sections (e.g. 1,3,5)
    foreach($verses as $verse){
        // If it's just a number (not a range, e.g. 1-2) then just get that verse.
        if(ctype_digit($verse)){
            array_push($verseList, $verse);
        }
        // Otherwise, we should have a range
        else{
            // Split at the dash
            $verseRange = explode('-', $verse);
            // We should only have two numbers, if any of that fails, we have an issue.
            if(count($verseRange) != 2 || !ctype_digit($verseRange[0]) || !ctype_digit($verseRange[1])){
                // If something doesn't match, then this is a bad reference, so return an empty array
                return array();
            }

            // If we're good, then loop to add the range to the array.
            for($i = (int)$verseRange[0]; $i <= (int)$verseRange[1]; ++$i){
                array_push($verseList, $i);
            }
        }
    }

    // Return all the numbers we got
    return $verseList;
}

?>