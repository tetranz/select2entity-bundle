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

        var value = element.data('value');

        if (multiple) {

          initValue = [];

          if (value !== '') {

            var parts = value.split('|');
            var length = parts.length;

            for(var i = 0; i < length; i += 2) {
              initValue.push({id: parts[i], text: parts[i+1]})
            }

          }
        }

        else {

          if (value === '') {
            initValue = {id:'', text:''};
          }
          else {
            var parts = value.split('|');
            initValue = {id: parts[0], text: parts[1]};
          }

        }

        callback(initValue);
      }

    };

    $(this).select2(options);

    if (!multiple) {
      $(this).select2('data', initValue);
    }

  });

});
