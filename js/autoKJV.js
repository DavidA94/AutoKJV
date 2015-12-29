// Globals to remove hard coded IDs and classes
var OPEN_VERSE = "openVerse";
var PASSAGE_WRAPPER = "passageWrapper";

// Used for detecting a screen orientation change
var screenX = window.innerWidth;
var screenY = window.innerHeight;
var screenO = window.orientation;

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
    
    // This redoes any open verses when the screen is rotated
    window.addEventListener('resize', window_resize);
}

/********************************************
** verse_mouseover function definition      *
** This method is called whenever the mouse *
** hovers on a passageWrapper. It places    *
** verse either above or below, based on    *
** the location of the passage on the view. *
********************************************/
function verse_mouseover(event){
    // If the current target is the open element, then we can just stop
    if(document.getElementById(OPEN_VERSE) == event.currentTarget){
        return;
    }
    
    // If there is already an open verse, close it.
    if(document.getElementById(OPEN_VERSE) != null){
        document.getElementById(OPEN_VERSE).removeAttribute("id");
    }
    
    // Place the verse where it should go.
    placeVerse(event.currentTarget);
}

/********************************************
** verse_mouseout function definition       *
** This method is called whenever the mouse *
** stops hovering on a passage. It closes   *
** the currently open verse.                *
********************************************/
function verse_mouseout(event){
    if(event.currentTarget.contains(event.relatedTarget) || event.currentTarget == event.relatedTarget){
        event.stopPropagation();
        return;
    }
    
    // If there is an open verse
    if(document.getElementById(OPEN_VERSE) != null){
        // Remove any styles that may have been applied
        document.getElementById(OPEN_VERSE).getElementsByClassName('passage')[0].removeAttribute("style")
        document.getElementById(OPEN_VERSE).removeAttribute("style");
        
        // Close the passage
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

/****************************************
** placeVerse function definition       *
** Places a verse at the best location  *
** it can find.                         *
****************************************/
function placeVerse(target){
    var MAX_WIDTH = 650;
    
    // Save some typing
    var docElem = document.documentElement;
    
    // This element is what the user already can't see.
    var elem = target.getElementsByClassName('passage')[0];
    
    
    // We need to make it "visible" so its coordinates can be gotten,
    // but until it is moved, we don't want the user to actually see it.
    // Unless it's already visible (orientation change), then don't do this
    if(target != document.getElementById(OPEN_VERSE)){
        elem.style.visibility = "hidden";
    }
    else{
        elem.removeAttribute('style');
    }
    
    // Set the new target to be the open verse
    // This will also make the element be display:block instead of none.
    target.setAttribute("id", OPEN_VERSE);
    
    // We need to get where things are
    var txtLoc = target.getBoundingClientRect(); // This is where what the user can see is at
    var docHeight = docElem.clientHeight;        // This is height of the viewport
    var verseLoc = elem.getBoundingClientRect(); // This is where what the user can't see is at
    
    // If we can make better use of space by moving this left, then do so.
    if(verseLoc.right >= docElem.clientWidth - 15 && verseLoc.width < MAX_WIDTH && verseLoc.left > 0){
        // First, move it as far last as is necessary
        elem.style.left = verseLoc.left + 10 - Math.min(MAX_WIDTH - verseLoc.width, verseLoc.left) + "px";
        
        // We need to re-get verseLoc since the height will have changed.
        verseLoc = elem.getBoundingClientRect();
        
        // Then if it's been moved to far, move it back
        if(verseLoc.right < docElem.clientWidth - 15){
            elem.style.left = docElem.clientWidth - 15 - verseLoc.width + "px";
        }
        
        // We don't need to get verseLoc again, as the left is not used below
    }
    
    // If the passage will fall below the viewport
    if(docHeight < txtLoc.bottom + verseLoc.height){
        // Then see if putting it above will work
        if(txtLoc.top - verseLoc.height > 0){
            // Then place the passage above the reference
            elem.style.top = (txtLoc.top - verseLoc.height + window.pageYOffset) + "px";
        }
        // Otherwise, put it on whichever side is larger, and restrict the height
        else if(docHeight - txtLoc.bottom > txtLoc.top){
            // This means the bottom is bigger
            elem.style.height = (docHeight - txtLoc.bottom - 20) + "px";
        }
        else{
            // The top is bigger
            elem.style.top = Math.max(10, 10 + window.pageYOffset) + "px";
            elem.style.height = (target.offsetTop - window.pageYOffset - 20) + "px";
        }        
    }
    
    // And now allow the user to see the passage
    elem.style.visibility = "";
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
        // Then call touchend, which will close the currently open passage if necessary.
        verse_touchend(event);
    }
}

/********************************************
** window_resize function definition        *
** This method is called whenever the       *
** screen size is hanged. It repositions    *
** any open verse.                          *
********************************************/
function window_resize(){
    // If the window x/y size have exactly switched, OR if the 
    // orientation has changed, AND if there is a verse open
    if((((window.innerHeight == screenX && window.innerWidth == screenY)) || 
            window.orientation != screenO) &&
            document.getElementById(OPEN_VERSE) != null){
        
        // Get the open verse
        var elem = document.getElementById(OPEN_VERSE);
        
        // Place it at the bottom of the screen
        window.scroll(0, elem.offsetTop + elem.clientHeight - document.documentElement.clientHeight);
        
        // Replace the verse
        placeVerse(document.getElementById(OPEN_VERSE));
    }
    
    // Remember the new size, and orientation
    screenX = window.innerWidth;
    screenY = window.innerHeight;
    screenO = window.orientation;
}