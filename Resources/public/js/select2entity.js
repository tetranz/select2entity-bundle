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
            $s2.select2($.extend({
                // Tags support
                createTag: function (data) {
                    if ($s2.data('tags') && data.term.length > 0) {
                        var text = data.term + $s2.data('tags-text');
                        return {id: $s2.data('new-tag-prefix') + data.term, text: text};
                    }
                },
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
                        // only send the 'page' parameter if scrolling is enabled
                        if (scroll) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        }

                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data, params) {
                        var results, more = false, response = {};
                        params.page = params.page || 1;

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
                            response.pagination = {more: more};
                        }
                        response.results = results;

                        return response;
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
