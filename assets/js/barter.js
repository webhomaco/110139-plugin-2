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
                    '<input type="text" id="' + inputId + '" class="rtcl-autocomplete wh-barter-search-input" ' +
                    'placeholder="Trade Tags..." autocomplete="off" />' +
                    '</div>' +
                    '<div id="' + suggestionsId + '" class="wh-tag-suggestions"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                // Create hidden container for form inputs (inside form, invisible)
                var hiddenInputsContainer = '<div class="wh-barter-hidden-inputs" style="display:none;"></div>';

                // Create selected tags row (below all inputs, for display only)
                var selectedTagsRow = '<div class="wh-selected-tags-row" style="' + (selectedTags.length > 0 ? '' : 'display:none;') + '">' +
                    '<span class="wh-selected-tags-label">Selected tags:</span>' +
                    '<div class="wh-selected-tags">' + selectedTagsHTML + '</div>' +
                    '</div>';

                // Insert before search button
                searchButton.before(barterFieldHTML);
                searchButton.before(hiddenInputsContainer);

                // Insert selected tags row after the entire form (as a sibling, not inside)
                searchForm.after(selectedTagsRow);

                // Copy initial tags to hidden container
                if (selectedTags.length > 0) {
                    selectedTags.forEach(function(tag) {
                        searchForm.find('.wh-barter-hidden-inputs').append(
                            '<input type="hidden" name="barter_tags[]" value="' + escapeHtml(tag) + '"/>'
                        );
                    });
                }
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
    function addSearchTag(tag, $clickedItem) {
        tag = tag.trim();
        if (!tag) return;

        // Find the wrapper and form containing the clicked suggestion item
        var $wrapper = $clickedItem.closest('.wh-barter-input-wrapper');
        var $form = $clickedItem.closest('.classima-listing-search-form');
        var $selectedTagsRow = $form.next('.wh-selected-tags-row');
        var $selectedTagsContainer = $selectedTagsRow.find('.wh-selected-tags');
        var $input = $wrapper.find('.wh-barter-search-input');
        var $suggestions = $wrapper.find('.wh-tag-suggestions');

        // Check if already added in this specific form (check hidden inputs)
        if ($form.find('.wh-barter-hidden-inputs input[value="' + tag + '"]').length > 0) {
            return;
        }

        var $tagEl = $('<span class="wh-selected-tag">' +
            escapeHtml(tag) +
            '<span class="wh-remove-search-tag" data-tag="' + escapeHtml(tag) + '">×</span>' +
            '</span>');

        // Add hidden input to the form (for submission)
        var $hiddenInput = $('<input type="hidden" name="barter_tags[]" value="' + escapeHtml(tag) + '"/>');
        $form.find('.wh-barter-hidden-inputs').append($hiddenInput);

        $selectedTagsContainer.append($tagEl);
        $selectedTagsRow.show(); // Show the row when tags are added
        $input.val('');
        $suggestions.removeClass('active').empty();

        // Refocus input after a tiny delay to ensure it works
        setTimeout(function() {
            $input.focus();
        }, 50);
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

                        // Get already selected tags in this form
                        var $form = $input.closest('.classima-listing-search-form');
                        var selectedTags = [];
                        $form.find('.wh-barter-hidden-inputs input[type="hidden"]').each(function() {
                            selectedTags.push($(this).val());
                        });

                        var hasItems = false;
                        response.data.tags.forEach(function(tag) {
                            // Skip if already selected
                            if (selectedTags.indexOf(tag) !== -1) {
                                return;
                            }

                            hasItems = true;
                            var $item = $('<div class="wh-tag-suggestion-item">' + escapeHtml(tag) + '</div>');
                            $item.on('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                addSearchTag(tag, $(this));
                            });
                            $searchSuggestions.append($item);
                        });

                        if (hasItems) {
                            $searchSuggestions.addClass('active');
                        } else {
                            $searchSuggestions.removeClass('active').empty();
                        }
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
        var $tag = $(this).closest('.wh-selected-tag');
        var $row = $(this).closest('.wh-selected-tags-row');
        var tagValue = $(this).data('tag');

        // Find and remove the corresponding hidden input from the form
        var $form = $row.prev('.classima-listing-search-form');
        $form.find('.wh-barter-hidden-inputs input[value="' + tagValue + '"]').remove();

        $tag.remove();

        // Hide the row if no tags remain
        if ($row.find('.wh-selected-tag').length === 0) {
            $row.hide();
        }
    });

    // Close search suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wh-barter-search-filter').length) {
            $('.wh-tag-suggestions').removeClass('active');
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
