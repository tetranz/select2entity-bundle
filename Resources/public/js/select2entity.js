$(document).ready(function () {

  $('.select2entity').each(function(index) {
    var initValue;
    var multiple = $(this).data('multiple');

    var options = {
      placeholder: $(this).data('placeholder'),
      allowClear: true,
      multiple: multiple,
      minimumInputLength: $(this).data('min-length'),
      ajax: {
        url: $(this).data('rpath'),
        dataType: $(this).data('data-type'),
        data: function (term, page) {
          return {
            q: term,
            page_limit: $(this).data('page-limit')
          };
        },
        results: function (data, page) {
          return {results: data};
        }
      },

      initSelection : function (element, callback) {

        // data-value contains html encoded json
        var value = element.data('value');

        if (value === '') {

          if (multiple) {
            // multiple empty is an empty array
            initValue = [];
          }
          else {
            // single empty is a blank object
            initValue = {id:'', text:''};
          }

        }
        else {
          // decode and parse json
          value = htmlDecode(value);
          initValue = $.parseJSON(value);
        }

        callback(initValue);
      }

    };

    $(this).select2(options);

    if (!multiple) {
      // Sets the data as dirty. Wthout this, the data coms through as blank if a form is submitted but the field is not changed
      $(this).select2('data', initValue);
    }

  });

  // simple htmldecode. Basic idea from underscore.js
  function htmlDecode(string)
  {
    var regex = new RegExp('(&amp;|&lt;|&gt;|&quot;|&#x27;)', 'g');

    var map = {
      '&amp;': '&',
      '&lt;': '<',
      '&gt;': '>',
      '&quot;': '"',
      '&#x27;': "'"
    };

    return ('' + string).replace(regex, function(match) {
      return map[match];
    });
  }

});
