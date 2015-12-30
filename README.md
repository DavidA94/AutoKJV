# AutoKJV

A PHP module for allowing web designers to have Scriptures automatically placed into their page, from just a reference string.

## License

This project is licensed under the GNU GENERAL PUBLIC LICENSE, Version 2. This means that this project can be used and modified, so long as all parts remain open source.

**Note**: The KJV is one of the only public domain versions of the Bible. Using a version of the Bible that is not public domain violates this license. To use a non-public domain version, a non-open source license is available for purchase by contacting AutoKJV [At] DavidAntonucci [dot] com

## Instructions for Use

Usage after setup is simple:

- Require the kjv_parser module, 
- echo getBibleHTML to get the desired passages.
- Call `initAutoKJV()` in JS to enable the displaying of verses
- *See index.html for a full example*

**Example**

```
<?php
require('php/kjv_parser.php');

echo getBibleHTML("Genesis 1-2; Ps 117:1-118:2");
?>
```

Each reference should be separated by a semicolon (;).
Whitespace is ignored, except for numbered books (e.g. I Samuel)

Valid reference formats are:

- [Book] [Chapter]                            
- [Book] [Chapter]-[Chapter]                  
- [Book] [Chapter]:[Verse]                    
- [Book] [Chapter]:[Verse]-[Chapter]:[Verse]
- [Book] [Chapter]:[Verse]-[Verse], [Verse]

The final form for verses can be any combination of range and individual verses, with unlimited sections.

## Setup

1. Create a database in MySQL to hold the table that contains the Bible
2. Modify the php/databaseVars.php to have the correct database information. (Ensure the user has access to the database)
3. Run the php/setupDB.php file to setup the database

## File Information

- index.html -- Demo for how to use the project.
- css/autoKJV.css -- Contains all default styles for how the references appear.
- js/autoKJV.css -- Contains all functions for showing the verses via hover/touch.
- php/sql/bibledb_kjv.sql -- SQL with the full Bible. Used to setup the database.
- php/templates/* -- The templates used to create the HTML that is producted.
- php/book.php -- A class for holding a single book of the Bible
- php/databaseVars.php -- Holds variables used for connecting to the database
- php/kjv_parser.php -- Responsible for parseing the references passed to getBibleHTML
- php/makeHTML.php -- Responsible for creating all HTML
- php/setupDB.php -- Used to setup the database - Run once.