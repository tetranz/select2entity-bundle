(function( $ ) {
    $.fn.select2entity = function (options) {
        this.each(function () {
            // Keep a reference to the element so we can keep the cache local to this instance and so we can
            // fetch config settings since select2 doesn't expose its options to the transport method.
            var $s2 = $(this),
                limit = $s2.data('page-limit') || 0,
                scroll = $s2.data('scroll'),
                prefix = Date.now(),
                cache = [];
            // Deep-merge the options
            $s2.select2($.extend(true, {
                width: '100%',
                minimumInputLength: $s2.data('minimum-input-length'),
                ajax: {
                    url: $s2.data('ajax--url'),
                    transport: function (params, success, failure) {
                        // is caching enabled?
                        if (false && $s2.data('ajax--cache')) {
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
                    data: function (term, page) {
                        // only send the 'page' parameter if scrolling is enabled
                        if (scroll) {
                            return {
                                q: term,
                                page: page || 1
                            };
                        }

                        return {
                            q: term
                        };
                    },
                    results: function(data, page) {
                        var results, more = false, response = {};
                        // page = page || 1;

                        if ($.isArray(data)) {
                            results = data;
                        } else if (typeof data == 'object') {
                            // assume remote result was proper object
                            results = data.results;
                            more = data.more;
                        } else {
                            // failsafe
                            results = [];
                        }

                        if (scroll) {
                            response.more = more;
                        }

                        response.results = results;

                        return response;
                    }
                },
                initSelection: function(element, callback) {
                    var multiple = $(element).data('multiple');
                    var values = $(element).data('values');

                    var data = [];
                    var ids = [];
                    $.each(values, function (idx, value) {
                        data.push(value);
                        ids.push(value.id);
                    });

                    $(element).val(ids.join(','));

                    if (multiple) {
                        callback(data);
                    } else {
                        callback(data[0]);
                    }
                }
            }, options || {}));
        });
        return this;
    };
})( jQuery );

(function( $ ) {
    $(document).ready(function () {
        $('.select2entity[data-autostart="true"]').select2entity();
    });
})( jQuery );
