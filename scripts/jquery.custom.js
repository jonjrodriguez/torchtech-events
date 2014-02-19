jQuery(document).ready(function($) {

	var regform = $("#registration_form"),
        cancelReg = $('#cancel_reg'),
		container = $(".entry-content"),
        org = $('#org'),
        interests = $('#interests'),
        overlay = $('<div class="modalOverlay ui-widget-overlay"></div>');

    org.autocomplete({
        source: ajaxurl + "?action=auto-tax-complete&tax=tt_event_reg_orgs",
        minLength: 0
    });
    interests
        .bind( "keydown", function( event ) {
            if ( event.keyCode === $.ui.keyCode.TAB &&
                    $( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function( request, response ) {
                $.getJSON(ajaxurl + "?action=auto-tax-complete&tax=tt_event_reg_interests", {
                    term: extractLast(request.term)
                }, response);
                   
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( ui.item.value );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( ", " );
                return false;
            }
    });

    function split( val ) {
            return val.split( /,\s*/ );
        }
    function extractLast( term ) {
        return split( term ).pop();
    }

	bindEvents();

	function bindEvents() {
		regform.validate();

		regform.on('submit', function(e){
			e.preventDefault();
			if(!regform.valid())
                return false;

            confirmRegistration();
		});

        cancelReg.on('click', function(e) {
            e.preventDefault();
            confirmCancel();
        });
	}

    function confirmRegistration() {
        $('<p title="Confirm Registration">Are you sure all the entered information is correct?</p>').dialog({
            resizable: false,
            draggable: false,
            modal: true,
            buttons: {
                'Yes': function() {
                    $( this ).dialog( "close" );
                    overlay.appendTo("body");
                    postRegInfo().done(function(results) {
                        display(results);
                    });
                },
                'No': function() {
                    $(this).dialog("close");
                }
            }
        })
    }

    function postRegInfo() {

	//console.log(ajaxurl);
	//console.log(regform.serialize());
    	return $.ajax({
    		url: ajaxurl,
    		type: 'POST',
    		data: regform.serialize()
    	});
    }

    function confirmCancel() {
        $('<p title="Confirm Cancellation">Are you sure you want to Cancel your registration?</p>').dialog({
            dialogClass   : 'wp-dialog',
            resizable: false,
            draggable: false,
            modal: true,
            buttons: {
                'Yes': function() {
                    overlay.appendTo("body");
                    $( this ).dialog( "close" );
                    cancelRegistration().done(function(results) {
                        display(results);
                    });
                },
                'No': function() {
                    $(this).dialog("close");
                }
            }
        })
    }

    function cancelRegistration() {
        return $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: cancelReg.data()
        });
    }

    function display(results){
        overlay.remove();
    	container.html(results);
        $('html, body').animate({
            scrollTop: $("#wrap").offset().top
        });
    }
});
