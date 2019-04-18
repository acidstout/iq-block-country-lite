jQuery(document).ready(function($) {
	// Call chosen
	$('select.chosen').chosen();

	// Add select/deselect all toggle to optgroups in chosen
	$(document).on('click', '.group-result', function() {
		// Get unselected items in this group
		var unselected = $(this).nextUntil('.group-result').not('.result-selected');
		if ( unselected.length ) {
			// Select all items in this group
			unselected.trigger('mouseup');
		} else {
			$(this).nextUntil('.group-result').each(function() {
				// Deselect all items in this group
				$('a.search-choice-close[data-option-array-index="' + $(this).data('option-array-index') + '"]').trigger('click');
			});
		}
	});
});