$(document).ready(function () {
    $.fn.select2entity = function (options) {
        this.each(function () {
            // Keep a reference to the element so we can keep the cache local to this instance and so we can
            // determine if caching is even enabled on this element by looking at it's data attributes since select2
            // doesn't expose its options to the transport method.
            var $s2 = $(this), cache = [];
            $s2.select2($.extend({
                ajax: {
                    transport: function (params, success, failure) {
                        // is caching enabled?
                        if ($s2.data('ajax--cache')) {
                            var key = params.data.q, cacheTimeout = $s2.data('ajax--cacheTimeout');
                            // no cache entry for 'term' or the cache has timed out?
                            if (typeof cache[key] == 'undefined' || (cacheTimeout && Date.now() >= cache[key].time)) {
                                $.ajax(params).fail(failure).done(function (data) {
                                    cache[key] = {
                                        data: data,
                                        time: cacheTimeout ? Date.now() + cacheTimeout : null
                                    };
                                    success(data);
                                });
                            } else {
                                // return cached data with no ajax request
                                success(cache[key].data);
                            }
                        } else {
                            // no caching enabled. just do the ajax request
                            $.ajax(params).fail(failure).done(success);
                        }
                    },
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
            }, options || {}));
        });
        return this;
    };

    $('.select2entity').select2entity();
});
