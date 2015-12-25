<?php
require_once('book.php');
require_once('makeHTML.php');

// Takes in a string and returns out HTML with the verses requested
// E.g. "Gen 1:1" -> HTML with "In the Beginning..."
// Uses template found in the template folder
// Can be passed multiple references, such as
// "Gen 1:1; John 1:1"
// This will return an mutiple elements, with a space between them.
function getBibleHTML($string){
    // Holds the return array
    $retVal = [];
    
    // Split at each reference
    $refs = explode(";", $string);
    
    /**********************************
    ** List of strings we might parse *
    ** Gen 1:1                        *
    ** Gen 1:1-8                      *
    ** Gen 1:1, 3                     *
    ** Gen 1:1, 3-5                   *
    **********************************/
    foreach($refs as $ref){
        // Kill any side spaces
        $ref = trim($ref);
        
        // Check that this meets the basic requirements of a reference
        // It must have a space between the book and the number(s)
        // It also can't have more than one colon (but none is okay)
        if(strpos($ref, ' ') === false || substr_count($ref, ':') > 1){
            array_push($retVal, makeBadRef($string));
            // Any time we make a bad ref, we need to call continue
            // to ensure that we go to the next reference, or we might
            // get a double instance of it.
            continue;
        }
        
        // Get the Book, and reference numbers (e.g. Genesis AND 1:1-2)
        list($bookStr, $refNumsStr) = explode(' ', $ref, 2);
        
        // Remove any spaces from the numbers
        $refNumsStr = str_replace(' ', '', $refNumsStr);
        
        // If there's no colon, then assume we want the full chapter
        if(strpos($refNumsStr, ':') === false){
            $chapStr = $refNumsStr;
            $verseStr = null; // Used later to determine we don't need to parse this
        }
        else{
            // Get the chapter and verses
            list($chapStr, $verseStr) = explode(':', $refNumsStr);
        }
        
        // Find the book -- If it can't be found, return a makeBadRef
        $bookStr = trim($bookStr);
        $book = getBook($bookStr);
        if($book->getID() < 1){
            array_push($retVal, makeBadRef($ref));
            continue;
        }
        
        // Check that the chapter is an integer
        if(!ctype_digit($chapStr)){
            array_push($retVal, makeBadRef($ref));
            continue;
        }
        
        // Get the verses we want, if we want verses
        // If it's not a string we made it null above
        $verses = is_string($verseStr) ? getVerses($verseStr) : [];
        
        // Get the HTML
        $passageHTML = makePassageHTML($book, $chapStr, $verses);
        
        // If we got an empty string, we have a bad reference
        if($passageHTML == ''){
            array_push($retVal, makeBadRef($ref));
            // Even though we're at the end, still make sure we restart the loop
            continue;
        }
        else{
            array_push($retVal, $passageHTML);
        }
    }
    
    $finalRetVal = "";
    
    foreach($retVal as $passage){
        $finalRetVal .= $passage . ' ';
    }
    
    return $finalRetVal;
}

function getBook($bookStr) {
    // Array of all the books
    $books = [
        new Book(1, "Genesis"),
        new Book(2, "Exodus"),
        new Book(3, "Leviticus"),
        new Book(4, "Numbers", ["Nmbs"]),
        new Book(5, "Deuteronomy", ["Dt"]),
        new Book(6, "Joshua"),
        new Book(7, "Judges", ["Jdgs"]),
        new Book(8, "Ruth"),
        new Book(9, "I Samuel", ["1 Samuel"]),
        new Book(10, "II Samuel", ["2 Samuel"]),
        new Book(11, "I Kings", ["1 Kings"]),
        new Book(12, "II Kings", ["2 Kings"]),
        new Book(13, "I Chronicles", ["1 Chronicles"]),
        new Book(14, "II Chronicles", ["2 Chronicles"]),
        new Book(15, "Ezra"),
        new Book(16, "Nehemiah"),
        new Book(17, "Esther"),
        new Book(18, "Job"),
        new Book(19, "Psalm"),
        new Book(20, "Proverbs", ["Prvb"]),
        new Book(21, "Ecclesiastes"),
        new Book(22, "Song of Solomon", ["SS, SoS"]),
        new Book(23, "Isaiah"),
        new Book(24, "Jeremiah"),
        new Book(25, "Lamentations"),
        new Book(26, "Ezekiel", ["Ezk"]),
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

        new Book(40, "Matthew", ["Mt"]),
        new Book(41, "Mark", ["Mk"]),
        new Book(42, "Luke", ["Lk"]),
        new Book(43, "John"),
        new Book(44, "Acts"),
        new Book(45, "Romans"),
        new Book(46, "I Corinthians", ["1 Corinthians"]),
        new Book(47, "II Corinthians", ["2 Corinthians"]),
        new Book(48, "Galatians"),
        new Book(49, "Ephesians"),
        new Book(50, "Philippians", ["Php"]),
        new Book(51, "Colossians"),
        new Book(52, "I Thessalonians", ["1 Thessalonians"]),
        new Book(53, "II Thessalonians", ["2 Thessalonians"]),
        new Book(54, "I Timothy", ["1 Timothy"]),
        new Book(55, "II Timothy", ["2 Timothy"]),
        new Book(56, "Titus"),
        new Book(57, "Philemon", ["Phl"]),
        new Book(58, "Hebrews"),
        new Book(59, "James", ["Jms"]),
        new Book(60, "I Peter", ["1 Peter"]),
        new Book(61, "II Peter", ["2 Peter"]),
        new Book(62, "I John", ["1 John"]),
        new Book(63, "II John", ["2 John"]),
        new Book(64, "III John", ["3 John"]),
        new Book(65, "Jude"),
        new Book(66, "Revelation")
    ];
    
    // Remove any periods, in case of something like "Gen." instead of just "Gen"
    $bookStr = str_replace('.', '', $bookStr);
    
    // Start out with a simple search in the array
    $book = array_search($bookStr, $books);
        
    // If we didn't find anything, we'll need to do a more thorough search
    if($book === false){
        // Loop through each book looking for a match
        foreach($books as $b){
            if($b->isMatch($bookStr)){
                $book = $b;
                break;
            }
        }
        
        // If we still didn't find anything, then make a new book with a negative ID
        if($book === false){
            return new Book(-1, "");
        }
    }
    else{
        // Because we don't have keys, if the first one finds something, then
        // we need to take the index we were given and turn it into a Book object.
        $book = $books[$book];
    }
    
    
    // Return whatever we found
    return $book;
}

function getVerses($verseStr){
    // Holds the return value
    $verseList = [];
    
    // Split at the comma, to get any sections
    $verses = explode(',', $verseStr);
    
    // Loop through all the verse sections (e.g. 1,3,5)
    foreach($verses as $verse){
        // If it's just a number (not a range, e.g. 1-2) then just get that verse.
        if(ctype_digit($verse)){
            array_push($verseList, $verse);
        }
        // Othersise, we should have a range
        else{
            // Split at the dash
            $verseRange = explode('-', $verse);
            // We should only have two numbers, if any of that fails, we have an issue.
            if(count($verseRange) != 2 || !ctype_digit($verseRange[0]) || !ctype_digit($verseRange[1])){
                // If something doesn't match, then this is a bad reference, so return an empty array
                return [];
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