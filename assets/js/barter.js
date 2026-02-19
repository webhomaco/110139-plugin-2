jQuery(document).ready(function($) {

    // ========== INJECT BARTER FIELD INTO BANNER SEARCH ==========

    // Check if banner search form exists
    if ($('.classima-listing-search-form').length > 0) {
        $('.classima-listing-search-form').each(function(index) {
            var searchForm = $(this);
            var searchButton = searchForm.find('.rtin-btn-holder');

            if (searchButton.length > 0) {
                // Create unique IDs for each form instance
                var inputId = 'wh-barter-search-input-' + index;
                var suggestionsId = 'wh-barter-search-suggestions-' + index;

                // Get selected tags from URL
                var urlParams = new URLSearchParams(window.location.search);
                var selectedTags = urlParams.getAll('barter_tags[]');

                // Build selected tags HTML
                var selectedTagsHTML = '';
                selectedTags.forEach(function(tag) {
                    selectedTagsHTML += '<span class="wh-selected-tag">' +
                        escapeHtml(tag) +
                        '<span class="wh-remove-search-tag" data-tag="' + escapeHtml(tag) + '">×</span>' +
                        '<input type="hidden" name="barter_tags[]" value="' + escapeHtml(tag) + '"/>' +
                        '</span>';
                });

                // Create barter tags field HTML with unique IDs
                var barterFieldHTML = '<div class="rtin-barter-space">' +
                    '<div class="form-group wh-barter-search-filter">' +
                    '<div class="wh-barter-input-wrapper">' +
                    '<div class="rtcl-search-input-button rtin-barter">' +
                    '<div class="wh-selected-tags">' + selectedTagsHTML + '</div>' +
                    '<input type="text" id="' + inputId + '" class="rtcl-autocomplete wh-barter-search-input" ' +
                    'placeholder="Trade Tags..." autocomplete="off" />' +
                    '</div>' +
                    '<div id="' + suggestionsId + '" class="wh-tag-suggestions"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                // Insert before search button
                searchButton.before(barterFieldHTML);
            }
        });
    }

    // ========== LISTING FORM - Tag Management ==========
    
    var selectedTags = [];
    var $tagInput = $('#wh-barter-tag-input');
    var $tagsList = $('.wh-barter-tags-list');
    var $tagsHidden = $('#wh-barter-tags-hidden');
    var $suggestions = $('#wh-barter-tag-suggestions');
    
    // Initialize from hidden field
    if ($tagsHidden.length && $tagsHidden.val()) {
        try {
            selectedTags = JSON.parse($tagsHidden.val());
        } catch(e) {
            selectedTags = [];
        }
    }
    
    // Add tag
    function addTag(tag) {
        tag = tag.trim();
        if (!tag || selectedTags.indexOf(tag) !== -1) {
            return;
        }
        
        selectedTags.push(tag);
        updateTagsDisplay();
        updateHiddenField();
        $tagInput.val('');
        $suggestions.removeClass('active').empty();
    }
    
    // Remove tag
    function removeTag(tag) {
        var index = selectedTags.indexOf(tag);
        if (index > -1) {
            selectedTags.splice(index, 1);
            updateTagsDisplay();
            updateHiddenField();
        }
    }
    
    // Update tags display
    function updateTagsDisplay() {
        $tagsList.empty();
        selectedTags.forEach(function(tag) {
            var $tagEl = $('<span class="wh-barter-tag">' + 
                escapeHtml(tag) + 
                '<span class="wh-remove-tag" data-tag="' + escapeHtml(tag) + '">×</span>' +
                '</span>');
            $tagsList.append($tagEl);
        });
    }
    
    // Update hidden field
    function updateHiddenField() {
        $tagsHidden.val(JSON.stringify(selectedTags));
    }
    
    // Tag input - Enter key
    $tagInput.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var tag = $(this).val();
            if (tag) {
                addTag(tag);
            }
        }
    });
    
    // Tag input - autocomplete
    var searchTimeout;
    $tagInput.on('input', function() {
        var search = $(this).val().trim();

        clearTimeout(searchTimeout);

        if (search.length < 2) {
            $suggestions.removeClass('active').empty();
            return;
        }

        // Show loading indicator
        $suggestions.html('<div class="wh-tag-loading">Loading...</div>').addClass('active');

        searchTimeout = setTimeout(function() {
            $.ajax({
                url: whBarter.ajax_url,
                type: 'POST',
                data: {
                    action: 'wh_sub_search_tags',
                    nonce: whBarter.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data.tags.length > 0) {
                        $suggestions.empty();
                        response.data.tags.forEach(function(tag) {
                            // Skip already selected tags
                            if (selectedTags.indexOf(tag) === -1) {
                                var $item = $('<div class="wh-tag-suggestion-item">' + escapeHtml(tag) + '</div>');
                                $item.on('click', function() {
                                    addTag(tag);
                                });
                                $suggestions.append($item);
                            }
                        });
                        if ($suggestions.children().length > 0) {
                            $suggestions.addClass('active');
                        } else {
                            $suggestions.removeClass('active');
                        }
                    } else {
                        $suggestions.removeClass('active').empty();
                    }
                },
                error: function() {
                    $suggestions.removeClass('active').empty();
                }
            });
        }, 300);
    });
    
    // Remove tag click
    $(document).on('click', '.wh-remove-tag', function() {
        var tag = $(this).data('tag');
        removeTag(tag);
    });
    
    // Close suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wh-barter-tags-wrap').length) {
            $suggestions.removeClass('active');
        }
    });
    
    
    // ========== SEARCH FORM - Tag Filter ==========

    // Add search tag (works with dynamically created elements)
    function addSearchTag(tag) {
        tag = tag.trim();
        if (!tag) return;

        var $selectedTagsContainer = $('.wh-selected-tags');

        // Check if already added
        if ($selectedTagsContainer.find('input[value="' + tag + '"]').length > 0) {
            return;
        }

        var $tagEl = $('<span class="wh-selected-tag">' +
            escapeHtml(tag) +
            '<span class="wh-remove-search-tag" data-tag="' + escapeHtml(tag) + '">×</span>' +
            '<input type="hidden" name="barter_tags[]" value="' + escapeHtml(tag) + '"/>' +
            '</span>');

        $selectedTagsContainer.append($tagEl);
        $('#wh-barter-search-input').val('');
        $('#wh-barter-search-suggestions').removeClass('active').empty();
    }

    // Search input - Prevent Enter key from submitting form
    $(document).on('keydown', '.wh-barter-search-input', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            // Don't allow manual tag entry - tags can only be added from suggestions
        }
    });

    // Search input - autocomplete (using event delegation)
    var searchSearchTimeout;
    $(document).on('input', '.wh-barter-search-input', function() {
        var $input = $(this);
        var search = $input.val().trim();
        // Find the suggestions div that's a sibling of this input's wrapper
        var $searchSuggestions = $input.closest('.wh-barter-input-wrapper').find('.wh-tag-suggestions');

        clearTimeout(searchSearchTimeout);

        if (search.length < 2) {
            $searchSuggestions.removeClass('active').empty();
            return;
        }

        // Show loading indicator
        $searchSuggestions.html('<div class="wh-tag-loading">Loading...</div>').addClass('active');

        searchSearchTimeout = setTimeout(function() {
            $.ajax({
                url: whBarter.ajax_url,
                type: 'POST',
                data: {
                    action: 'wh_sub_search_tags',
                    nonce: whBarter.nonce,
                    search: search
                },
                success: function(response) {
                    if (response.success && response.data.tags.length > 0) {
                        $searchSuggestions.empty();
                        response.data.tags.forEach(function(tag) {
                            var $item = $('<div class="wh-tag-suggestion-item">' + escapeHtml(tag) + '</div>');
                            $item.on('click', function() {
                                addSearchTag(tag);
                            });
                            $searchSuggestions.append($item);
                        });
                        $searchSuggestions.addClass('active');
                    } else {
                        $searchSuggestions.removeClass('active').empty();
                    }
                },
                error: function() {
                    $searchSuggestions.removeClass('active').empty();
                }
            });
        }, 300);
    });

    // Remove search tag (using event delegation)
    $(document).on('click', '.wh-remove-search-tag', function() {
        $(this).closest('.wh-selected-tag').remove();
    });

    // Close search suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wh-barter-search-filter').length) {
            $('#wh-barter-search-suggestions').removeClass('active');
        }
    });
    
    
    // ========== HELPER FUNCTIONS ==========
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
});
