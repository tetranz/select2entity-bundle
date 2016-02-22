$(document).ready(function () {
  $.fn.select2entity = function (action) {
    var select2entityParam = {
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
        },
        cache: true
      }
    };
    $.extend(select2entityParam, action);
    this.select2(select2entityParam);
    return this;
  };
});
