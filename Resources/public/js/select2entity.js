$(document).ready(function () {
    $.fn.select2entity = function (options) {
        this.each(function () {
            // Keep a reference to the element so we can keep the cache local to this instance and so we can
            // determine if caching is even enabled on this element by looking at it's data attributes since select2
            // doesn't expose its options to the transport method.
            var $s2 = $(this), limit = $s2.data('page-limit') || 0, prefix = Date.now(), cache = [];
            $s2.select2($.extend({
                ajax: {
                    transport: function (params, success, failure) {
                        // is caching enabled?
                        if ($s2.data('ajax--cache')) {
                            // try to make the key unique to make it less likely for a page+q to match a real query
                            var key = prefix + ' page:' + (params.data.page || 1) + ' ' + params.data.q,
                                cacheTimeout = $s2.data('ajax--cacheTimeout');
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
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        var results, more = false;
                        params.page = params.page || 1;

                        if ($.isArray(data)) {
                            // maintain BC; assume there is more if the result length == limit
                            results = data;
                            more = results.length == limit;
                        } else if (typeof data == 'object') {
                            // remote result was proper object
                            results = data.results;
                            more = data.more;
                        } else {
                            // failsafe
                            results = [];
                            more = false;
                        }

                        return {
                            results: results,
                            pagination: {
                                more: more
                            }
                        };
                    }
                }
            }, options || {}));
        });
        return this;
    };

    $('.select2entity').select2entity();
});
