<?php

/**
** Book class summary
** Holds an individual book of the Bible, including
** any abbreviations it might have.
-- ---------- Methods ----------
** __construct: Creates a new Book object
** __toString:  Used whenever the object is used as a string
** getID:       Gets the ID of the book that was given in the CTOR (typically 1-66)
** isMatch:     Checks if the passed in string could be used to identify the Book
*/
class Book{
    private $m_bookId;      // The ID of the book in the Bible (e.g. Genesis = 1)
    private $m_fullName;    // The full name of the book (e.g. Genesis, Song of Solomon, etc)
    private $m_altList;     // An array of any alternate spellings/abbreviations for this book
    
    /************************************************************************
    ** __construct function definition                                      *
    ** Creates a new Book object based on the parameters passed in.         *
    -- ---------------------------- Parameters ---------------------------- -
    ** bookId: The ID for this book (e.g. Genesis = 1)                      *
    ** fullName: The full name of the Book (e.g. Song of Solomon)           *
    ** altList (array): An array of any alternate spellings/abbreviations   *
    ************************************************************************/
    public function __construct($bookId, $fullName, $altList = array()){
        // Keep the parameters passed in
        $this->m_bookId = $bookId;
        $this->m_fullName = $fullName;
        $this->m_altList = $altList;
    }
    
    // Returns the full name of the book (used for printing / hashing)
    /****************************************************
    ** __toString function definition                   *
    ** Called whenever the object is used as a string.  *
    -- --------------- Returns (string) --------------- -
    ** The full name of the book                        *
    ****************************************************/
    public function __toString(){
        // Give back the full name of the book
        return $this->m_fullName;
    }
    
    /****************************************
    ** getID function definition            *
    -- ---------- Returns (obj) ----------- -
    ** The ID of the Book that was given in *
    ** the constructor.                     *
    ****************************************/
    public function getID(){
        // Return the book's ID
        return $this->m_bookId;
    }
    
    /****************************************
    ** getName function definition          *
    -- --------- Returns (string) --------- -
    ** The full name of the Book            *
    ****************************************/
    public function getName(){
        // Return the book's name
        return $this->m_fullName;
    }
    
    // Checks if $book (string) matches the current object
    /************************************************
    ** isMatch function definition                  *
    ** Takes the given string and does a thorough   *
    ** check to see if the book's name, or          *
    ** alternative names match it.                  *
    -- --------------- Parameters ----------------- -
    ** book (string): A string to be used to check  *
    **      if this Book is a match to.             *
    -- -------------- Returns (bool) -------------- -
    ** True if the parameter given is a match.      *
    ** False otherwise.                             *
    ************************************************/
    public function isMatch($book){
        // We're doing a case insensitive check, so make this lowercase
        $book = strtolower($book);
        
        // If the passed in name is, or can be found in, the full name, then we're good
        // E.g. Genesis -- Genesis and Gen match.
        // E.g. Matthew -- Matthew and Matt match.
        // The string matched must start at the beginning, otherwise things like
        // Eph will match zEpheniah.
        if(strpos(strtolower($this->m_fullName), $book) === 0){
            return true;
        }
        
        // Otherwise, we need to look at any abbreviations
        foreach($this->m_altList as $alt){
            // Same idea as above, but using any alternate spellings/abbreviations
            // E.g. Matthew -- Mt will match (assuming Mt was passed in to the CTOR)
            // E.g. I John -- 1 John will match (assuming ...)
            if(strpos(strtolower($alt), $book) === 0){
                return true;
            }
        }
        
        // If we make it this far, then this isn't the book we're looking for
        return false;
    }
}

?>