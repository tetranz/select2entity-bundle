$(document).ready(function () {
  $.fn.select2entity = function(action) {
    var action = action || {};
    this.select2($.extend(action, {
      ajax: {
        data: function (params) {
          return {
            q: params.term
          };
        },
        processResults: function (data) {
          return {
            results: data
          };
        }
      }
    }));
    return this;
  };

  $('.select2entity').select2entity();
});
