jQuery(document).ready(function($) {

  // Hide the status message after a while.
  //$('#status-message').delay(5555).slideUp(777);

  $('.is-duplicate, .is-circular').parent().css({
    'background-color': '#A20003'
  });

  // Set up all help links to display the correct tab in the help.
  $('.show-help').each(function(index, el) {
    var classes = $(this).attr('class').split(/\s+/);
    var prefix = 'show-help-';

    // Get the correct tab.
    var tab = '';
    $.each( classes, function(index, c){
      if ( c.substring(0, prefix.length) === prefix ) {
        tab = c.substring(prefix.length);
        return;
      }
    });

    // Make sure the tab exists.
    if ( '' != tab && $('#tab-link-'+tab).length ) {
      $(this).on('click', function(event) {
        // Drop down the help window if it isn't open already.
        if ( 'false' == $('#contextual-help-link').attr('aria-expanded') ) {
          $('#contextual-help-link').click();
        }
        // Select the tab.
        $('#tab-link-'+tab).children('a').click();
      });
    }
  });


  // Click "Search" button when enter key is pressed in search field.
  $('#redirectman-search-input').keypress(function(e) {
    // Enter pressed?
    if ( 10 == e.which || 13 == e.which ) {
      $('#search-submit').click();
    }
  });


  // Click "Save All" button when enter key is pressed in any input field.
  $('#the-list').find('input').keypress(function(e) {
    // Enter pressed?
    if ( 10 == e.which || 13 == e.which ) {
      $('#save-redirections').click();
    }
  });


  $('.redirection-changed').each(function(index, el) {

    var classes = $(this).attr('class').split(/\s+/);
    var prefix = 'redirection-is-';

    // Get the correct class name.
    var op_class = '';
    $.each( classes, function(index, c){
      if ( c.substring(0, prefix.length) === prefix ) {
        op_class = c;
        return;
      }
    });

    var row_inputs = $(this).closest('tr').find('input, select');
    $(row_inputs ).addClass(op_class);
    setTimeout(function() {
      $(row_inputs).addClass('redirection-changed').removeClass(op_class);
    }, 2000);
  });


  // Add new redirection.
  $('#add-new-redirection').on('click', function(event) {
    $(this).hide();
    $('#status-message').hide();
    $('.wp-list-table-new').show();
    $('#new-redirection-buttons').show();
    $('[name="redirectman[-1][redirect_from]"]').focus();
  });

  $('#save-new-redirection').on('click', function(event) {
    var ok                = true;
    var redirect_from     = $('[name="redirectman[-1][redirect_from]"]');
    var redirect_from_val = $.trim($(redirect_from).val());
    var redirect_to       = $('[name="redirectman[-1][redirect_to]"]');
    var redirect_to_val   = $.trim($(redirect_to).val());

    if ( '' == redirect_from_val ) {
      $(redirect_from).addClass('missing-input').focus();
      ok = false;
    } else {
      $(redirect_from).removeClass('missing-input');
    }
    if ( '' == redirect_to_val ) {
      $(redirect_to).addClass('missing-input');
      if ( ok) {
        $(redirect_to).focus();
      }
      ok = false;
    } else {
      $(redirect_to).removeClass('missing-input');
    }
    if ( ! ok ) {
      $('#new-redirection-message').text(redirection_str.missing_input);
    }

    if ( ok && redirect_from_val == redirect_to_val ) {
      ok = false;
      $('#new-redirection-message').text(redirection_str.same_source_dest);
      $(redirect_from).addClass('missing-input');
      $(redirect_to).addClass('missing-input');
    }

    return ok;
  });

  $('#cancel-new-redirection').on('click', function(event) {
    $('[name="redirectman[-1][redirect_from]"]').removeClass('missing-input').val('');
    $('[name="redirectman[-1][redirect_to]"]').removeClass('missing-input').val('');
    $('[name="redirectman[-1][status_code]"]').selectedIndex = -1;
    $('.wp-list-table-new').hide();
    $('#new-redirection-buttons').hide();
    $('#new-redirection-message').text('');
    $('#add-new-redirection').show();
    return false;
  });

  // Clicking the top bulk action "Apply" button.
  $('#doaction').on('click', function(event) {
    var action = $('select[name="action"]');
    var action2 = $('select[name="action2"]');

    if ( -1 == $(action).val() ) {
      if ( -1 == $(action2).val() ) {
        // If neither action has been set.
        return false;
      }
      $(action).val($(action2).val());
    }

    var ret = ( 'delete' != $(action).val() || confirm(redirection_str.are_you_sure) );
    if ( ret ) {
      $(action2).val($(action).val());
    }
    return ret;
  });

  // Clicking the bottom bulk action "Apply" button.
  $('#doaction2').on('click', function(event) {
    var action = $('select[name="action"]');
    var action2 = $('select[name="action2"]');

    if ( -1 == $(action2).val() ) {
      if ( -1 == $(action).val() ) {
        // If neither action has been set.
        return false;
      }
      $(action2).val($(action).val());
    }

    var ret = ( 'delete' != $(action2).val() || confirm(redirection_str.are_you_sure) );
    if ( ret ) {
      $(action).val($(action2).val());
    }
    return ret;
  });

});