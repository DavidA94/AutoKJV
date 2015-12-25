<?php

// Book class for holding each book of the Bible
class Book{
    private $m_bookId;        // The ID (1-base position) of the book in the Bible (e.g. Genesis = 1)
    private $m_fullName;    // The full name of the book (e.g. Genesis, Song of Solomon, etc)
    private $m_altList;
    
    // CTOR, Takes in the book's ID, name, and any alternate spellings/abbreviations
    public function __construct($bookId, $fullName, $altList = []){
        $this->m_bookId = $bookId;
        $this->m_fullName = $fullName;
        $this->m_altList = $altList;
    }
    
    // Returns the full name of the book (used for printing / hashing)
    public function __toString(){
        return $this->m_fullName;
    }
    
    // Checks if $book (string) matches the current object
    public function isMatch($book){
        // We're doing a case insensative check, so make this lowercase
        $book = strtolower($book);
        
        // If the passed in name is, or can be found in, the full name, then we're good
        // E.g. Genesis -- Genesis and Gen match.
        // E.g. Matthew -- Matthew and Matt match.
        if(strpos(strtolower($this->m_fullName), $book) !== false){
            return true;
        }
        
        // Otherwise, we need to look at any abbreviations
        foreach($this->m_altList as $alt){
            // Same idea as above, but using any alternate spellings/abbreviations
            // E.g. Matthew -- Mt will match (assuming Mt was passed in to the CTOR)
            // E.g. I John -- 1 John will match (assuming ...)
            if(strpos(strtolower($alt), $book) !== false){
                return true;
            }
        }
        
        // If we make it this far, then this isn't the book we're looking for
        return false;
    }
    
    public function getID(){
        return $this->m_bookId;
    }
}

?>