/**
 * remove a tag from the tag "cloud"
 *
 * @param tagString
 * @param targetStartString
 */
function removeTag(tagString,targetStartString) {
	let toRemove = document.getElementById(targetStartString + '-' + tagString);
	let saveInput = document.getElementById(targetStartString + '-save');

	if(toRemove && saveInput) {
		let newSaveValue = _removeFromCommaString(saveInput.value,tagString);
		saveInput.value = newSaveValue;
		toRemove.remove();
	}
	else {
		console.log("Delete element not found");
	}
}

/**
 * add a tag to the visible tag "cloud" and hidden form input
 * used in the form for saving
 *
 * @param e
 * @param targetStartString
 * @param allowWhiteSpace
 */
function addTag(e,targetStartString,allowWhiteSpace) {
	e = e || window.event;

	if(e.keyCode === 13) {
		let elem = e.srcElement || e.target;
		let saveInput = document.getElementById(targetStartString + '-save');
		let listBox = document.getElementById(targetStartString + '-listbox');
		let newTagTemplate = document.getElementById(targetStartString + '-template');

		let validateMethod = false;
		if (allowWhiteSpace !== undefined && allowWhiteSpace.length > 0) {
			validateMethod = allowWhiteSpace;
		}

		let checkString = _checkForSpaceString(elem.value,validateMethod);

		if(saveInput && listBox && elem && newTagTemplate && checkString) {
			let toAdd = elem.value;
			let newSaveValue = _appendToCommaString(saveInput.value,toAdd);

			let newT = newTagTemplate.cloneNode(true);
			newT = _fillTagTemplate(newT,toAdd,targetStartString);
			listBox.appendChild(newT);

			saveInput.value = newSaveValue;
		}

		elem.value = '';
		e.preventDefault();
	}
}

/**
 * add given string to given existing string seperated with a comma
 *
 * @returns {string}
 * @private
 * @param theString
 * @param toAdd
 */
function _appendToCommaString(theString,toAdd) {
	if(theString.length > 0 && toAdd.length > 0) {
		let theArray = theString.split(',');
		if(!theArray.includes(toAdd)) {
			theString = theString + "," + toAdd
		}
	}
	else if (toAdd.length > 0) {
		theString = toAdd;
	}

	return theString;
}

/**
 * add given string to given existing string seperated with a comma
 *
 * @returns {string}
 * @private
 * @param theString
 * @param toRemove
 */
function _removeFromCommaString(theString,toRemove) {
	if(theString.length > 0 && toRemove.length > 0) {
		let theArray = theString.split(',');

		if(theArray.includes(toRemove)) {
			for( let i = theArray.length-1; i >= 0; i--){
				if ( theArray[i] === toRemove) theArray.splice(i, 1);
			}

			theString = theArray.join(",");
		}
	}

	return theString;
}

/**
 * remove from given list the given value if it exists
 *
 * @private
 * @param list
 * @param value
 */
function _removeFromDatalist(list, value) {
	if(list.options.length > 0 && value && value.length > 0) {
		for (i = 0; i < list.options.length; i++) {
			if(list.options[i].value == value) {
				list.options[i].remove();
			}
		}
	}
}

/**
 * fill the tag template with the right data and js calls
 * depends on the html template created in the html code
 *
 * @returns Object the cloned el
 * @private
 * @param el
 * @param newTagString
 * @param targetStartString
 */
function _fillTagTemplate(el,newTagString,targetStartString) {
	el.removeAttribute('style');
	el.setAttribute('id',targetStartString + '-' + newTagString);

	let spanEl = el.querySelector('span');
	spanEl.innerHTML = newTagString;

	let aEl = el.querySelector('a');
	aEl.setAttribute('onclick', "removeTag('"+newTagString+"','"+targetStartString+"');");

	return el;
}

/**
 * simple check if the string is empty or contains whitespace chars
 *
 * @returns boolean
 * @private
 * @param stringTocheck
 * @param validateMethod
 */
function _checkForSpaceString(stringTocheck,validateMethod) {
	let check = stringTocheck.replace(/\s/gm,'');
	if(validateMethod && validateMethod == "allowSpace") {
		check = stringTocheck.trim();
	}

	if(check === stringTocheck && check.length > 0) {
		return true;
	}
	return false;
}
