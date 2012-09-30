/**
 * 
 */
var TValidator =
{
	validators: new Array(),
	focusAlreadySet: false,
	formAlreadyBinded: false,
	formOnceValidated: false,
	activeGroup: false,

	setActiveGroup: function(group)
	{
		TValidator.activeGroup = group;
	},

	add: function(controlId, validatorId, validateMethod, message, cssClass, setFocus, displayMessage, validationGroup, displayMode)
	{
		var ctl = $('#'+controlId);
		if(ctl.length == 1)
		{
			var v = TValidator.validators[TValidator.validators.length] = {
				ctl: controlId,
				validator: validatorId,
				validateMethod:	validateMethod,
				message: message,
				css: cssClass,
				setFocus: setFocus,
				displayMessage: displayMessage,
				validationGroup: validationGroup,
				displayMode: displayMode
			};

			if(!TValidator.formAlreadyBinded)
			{
				$('#'+controlId).parents('form').bind('submit',function() {
					TValidator.focusAlreadySet = false;
					TValidator.formOnceValidated = true;
					return TValidator.validate();
				});
				TValidator.formAlreadyBinded = true;
			}

			var activeValidator = function() { if(TValidator.formOnceValidated) TValidator.runValidator(v); };

			switch(ctl.get(0).tagName.toLowerCase())
			{
				case 'input':
					switch(ctl.attr('type').toLowerCase())
					{
						case 'text':
						case 'password':
							ctl.bind('blur',activeValidator);
							ctl.bind('keyup',activeValidator);
							ctl.bind('keydown',activeValidator);
						break;
						case 'checkbox':
							ctl.bind('click',activeValidator);
						break;
						case 'file':
							ctl.bind('change',activeValidator);
						break;
					}
				break;
				case 'textarea':
					ctl.bind('blur',activeValidator);
					ctl.bind('keyup',activeValidator);
					ctl.bind('keydown',activeValidator);
				break;
				case 'select':
					ctl.bind('change',activeValidator)
				break;
			}
		}
	},

	causeValidation: function(evt, ctlId, group)
	{
		$('#'+ctlId).bind(evt,function() {
			TValidator.setActiveGroup(group);
		});
	},

	validate: function()
	{
		var ret = true;
		if(TValidator.activeGroup === false) return true;

		for(var i = 0; i < TValidator.validators.length; i++)
		{
			if(TValidator.activeGroup != TValidator.validators[i].validationGroup) continue;
			if(!TValidator.runValidator(TValidator.validators[i])) ret = false;
		}

		TValidator.activeGroup = false;
		return ret;
	},

	runValidator: function(v)
	{
		var ctl = $('#'+v.ctl);

		if(ctl.length == 0) return true;

		if(v.validateMethod instanceof RegExp)
		{
			var vr = ctl.val().match(v.validateMethod);
		}
		else if(v.validateMethod instanceof Function)
		{
			var vr = v.validateMethod(ctl);
		}
		else
		{
			var vr = true;
		}
	
		var ret = true;

		if(!vr)
		{
			ctl.addClass(v.css);
			if(v.displayMessage)
			{
				$('#'+v.validator).text(v.message);
				
				if(v.displayMode == 'dynamic')
				{
					$('#'+v.validator).show();
				}
				else
				{
					$('#'+v.validator).css({visibility: 'visible'});
				}
			}
			if(v.setFocus && !TValidator.focusAlreadySet)
			{
				ctl.get(0).focus();
				TValidator.focusAlreadySet = true;
			}
			ret = false;
		}
		else
		{
			ctl.removeClass(v.css);
			if(v.displayMode == 'dynamic')
			{
				$('#'+v.validator).hide();
			}
			else
			{
				$('#'+v.validator).css({visibility: 'hidden'});
			}
		}

		return ret;
	}
}