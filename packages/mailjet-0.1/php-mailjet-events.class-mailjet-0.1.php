<?php
/**
 * Mailjet Event Trigger API
 *
 * @package		API v0.1
 * @author		Mailjet
 * @link		https://www.mailjet.com/docs/event_tracking
 *
 */


# Catch Event
$post = trim(file_get_contents('php://input'));

# No Event sent
if(empty($post)) {
	header('HTTP/1.1 421 No event');
	// => do action
	return;
}

# Decode Trigger Informations
$t = json_decode($post, true);

# No Informations sent with the Event
if(!is_array($t) || !isset($t['event'])) {
	header('HTTP/1.1 422 Not ok');
	// => do action
	return;
}


/* 
 *	Event handler 
 *	- please check https://www.mailjet.com/docs/event_tracking for further informations.
 */

switch($t['event']) {
	case 'open':
		// => do action
		// If an error occurs, tell Mailjet to retry later: header('HTTP/1.1 400 Error');
		// If it works, tell Mailjet it's OK
		header('HTTP/1.1 200 Ok');
		break;
	case 'click':
		// => do action
		break;
	
	case 'bounce':
		// => do action
		break;

	case 'spam':
		// => do action
		break;

	case 'blocked':
		// => do action
		break;

	case 'unsub':
		// => do action
		break;

	case 'typofix':
		// => do action
		break;
		
	# No handler
	default:
		header('HTTP/1.1 423 No handler');
		// => do action
		break;
}
