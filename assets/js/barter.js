jQuery(document).ready(function($) {
    
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
    
    var $searchInput = $('#wh-barter-search-input');
    var $searchSuggestions = $('#wh-barter-search-suggestions');
    var $selectedTagsContainer = $('.wh-selected-tags');
    
    // Add search tag
    function addSearchTag(tag) {
        tag = tag.trim();
        if (!tag) return;
        
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
        $searchInput.val('');
        $searchSuggestions.removeClass('active').empty();
    }
    
    // Search input - Enter key
    $searchInput.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var tag = $(this).val();
            if (tag) {
                addSearchTag(tag);
            }
        }
    });
    
    // Search input - autocomplete
    var searchSearchTimeout;
    $searchInput.on('input', function() {
        var search = $(this).val().trim();

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
    
    // Remove search tag
    $(document).on('click', '.wh-remove-search-tag', function() {
        $(this).closest('.wh-selected-tag').remove();
    });
    
    // Close search suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wh-barter-search-filter').length) {
            $searchSuggestions.removeClass('active');
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
