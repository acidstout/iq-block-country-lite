/**
 * Whoisip extension
 * 
 * @author nrekow
 */
jQuery(document).ready(function($) {
	/**
	 * Create a jQueryUI dialog 
	 */
	$('#whoisip-dialog').dialog({
		autoOpen: false,
		closeOnEscape: false,
		draggable: true,
		minWidth: 800,
		modal: true,
		resizable: true,
		title: 'Whois Information',
		buttons: [{
			text: 'OK',
			click: function() {
				$(this).dialog('close');
				return false;
			}
		}]
	});

	/**
	 * Executes AJAX call upon click on ip-address.
	 */
	$('.whoisip').unbind('click').bind('click', function(e) {
		var ip = $(this).data('ip');
		
		if (typeof whoisip_php !== 'undefined' && whoisip_php.length > 0) {
			$.ajax({
				type: 'POST',
				url: whoisip_php,
				data: 'ajax=1&ip=' + ip,
				success: function(result) {
					$('#whoisip-dialog').dialog('option', 'title', 'Whois information of ' + ip );
					result = result.replace(/\%/g, '<br/>');
					$('#whoisip-result').html(result);
					$('#whoisip-dialog').dialog('open');
					return false;
				},
				error: function(xhr, status, code) {
					console.warn('whoisip.js: AJAX call failed with status: ' + status + ', code: ' + code);
					return false;
				}
			});
		} else {
			console.warn('whoisip.js: Location of whoisi.php seems to be wrong. ');
		}
		return false;
	});
	
	return false;
});