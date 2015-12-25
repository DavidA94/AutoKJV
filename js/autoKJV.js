// Globals to remove hard coded IDs and classes
var OPEN_VERSE = "openVerse";
var PASSAGE_WRAPPER = "passageWrapper";

/********************************************************
** initAutoKJV function definition                      *
** Initializes a page that utilizes the AutoKJV module. *
** https://github.com/DavidA94/AutoKJV                  *
********************************************************/
function initAutoKJV(){
    // Get all of the passageWrappers so we can add events to them all
    var passages = document.getElementsByClassName(PASSAGE_WRAPPER);

    // Add mouseover/mouseout and touchstart/touchend events to every passage
    for(var i = 0; i < passages.length; ++i){
        passages[i].addEventListener('mouseover', verse_mouseover);
        passages[i].addEventListener('mouseout', verse_mouseout);
        passages[i].addEventListener('touchstart', verse_touchstart);
        passages[i].addEventListener('touchend', verse_touchend);
    }
    
    // This even allows mobile users to close a passage by touching anywhere on the page
    document.documentElement.addEventListener('touchend', body_touchend);
}

/********************************************
** verse_mouseover function definition      *
** This method is called whenever the mouse *
** hovers on a passageWrapper. It places    *
** verse either above or below, based on    *
** the location of the passage on the view. *
********************************************/
function verse_mouseover(event){
    // If there is already an open verse, close it.
    if(document.getElementById(OPEN_VERSE) != null){
        document.getElementById(OPEN_VERSE).removeAttribute("id");
    }
    
    // This element is what the user already can't see.
    var elem = event.currentTarget.getElementsByClassName('passage')[0];
    // We need to make it "visible" so its coordinates can be gotten,
    // but until it is moved, we don't want the user to actually see it.
    elem.style.visibility = "hidden";
    
    // Set the new target to be the open verse
    // This will also make the element be display:block instead of none.
    event.currentTarget.setAttribute("id", OPEN_VERSE);
    
    // We need to get where things are
    var txtLoc = event.currentTarget.getBoundingClientRect();   // This is where what the user can see is at
    var docHeight = document.documentElement.clientHeight;      // This is height of the viewport
    var verseLoc = elem.getBoundingClientRect();                // This is where what the user can't see is at
    
    // If the passage will fall below the viewport, and by putting it above,
    // we won't make it be somewhat out of view
    if(docHeight < txtLoc.bottom + verseLoc.height && txtLoc.top - verseLoc.height > 0){
        // Then place the passage above the reference
        elem.style.top = (txtLoc.top - verseLoc.height) + "px";
    }
    // Otherwise, ensure that the passage will fall below
    else{
        elem.style.top = "";
    }
    
    // And now allow the user to see the passage
    elem.style.visibility = "";
}

/********************************************
** verse_mouseout function definition       *
** This method is called whenever the mouse *
** stops hovering on a passage. It closes   *
** the currently open verse.                *
********************************************/
function verse_mouseout(event){
    // If there is an open verse
    if(document.getElementById(OPEN_VERSE) != null){
        // Close it, and remove any styles applied to it
        document.getElementById(OPEN_VERSE).removeAttribute("style");
        document.getElementById(OPEN_VERSE).removeAttribute("id");
    }
}

/********************************************
** verse_touchstart function definition     *
** This method is called whenever a mobile  *
** user starts touching a reference. It     *
** Remembers where and when they touched.   *
********************************************/
function verse_touchstart(event){
    // Get the touch that the user did
    var touchevent = event.changedTouches[0];
    
    // And remember where the touch was
    verse_touchstart.xPos = touchevent.pageX;
    verse_touchstart.yPos = touchevent.pageY;
    
    // And when it happened
    verse_touchstart.time = event.timeStamp;
}

/********************************************
** verse_touchend function definition       *
** This method is called whenever a mobile  *
** user stops touching a reference. It      *
** opens a passage assuming the user        *
** didn't move too much, and the touch was  *
** short enough in duration.                *
********************************************/
function verse_touchend(event){
    // How many milliseconds a touch must be shorter than
    var TOUCH_LENGTH = 300;
    
    // Get the touch event that the user did
    var touchevent = event.changedTouches[0];
    
    // If they have touched the passage that is already open, and 
    // their touch duration was short enough
    if(document.getElementById(OPEN_VERSE) == event.currentTarget && 
            event.timeStamp - verse_touchstart.time < TOUCH_LENGTH){
                
        // Then call the mouseout method, which will close the passage
        verse_mouseout(event);
    }
    // Otherwise, if they moved less than 10px in any direction, and
    // their touch duration was short enough
    else if(Math.abs(touchevent.pageX - verse_touchstart.xPos) < 10 &&
            Math.abs(touchevent.pageY - verse_touchstart.yPos) < 10 &&
            event.timeStamp - verse_touchstart.time < TOUCH_LENGTH){
        
        // Then call the mouseover method, which will open the passage
        verse_mouseover(event);
   }
}

/********************************************
** body_touchend function definition        *
** This method is called whenever a mobile  *
** user stops touching the screen. It will  *
** close any open passage, if there is one. *
********************************************/
function body_touchend(event){
    // If there is an open verse, and the target that was hit is not a descendant
    // of the open verse (this works because this event will be called second)
    if(document.getElementById(OPEN_VERSE) != null && !document.getElementById(OPEN_VERSE).contains(event.target)){
        // Then call mouseout, which will close the currently open passage.
        verse_mouseout(event);
    }
}