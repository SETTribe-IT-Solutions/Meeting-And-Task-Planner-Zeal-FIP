<?php
require_once '../../config/db.php';
require_once '../../config/session.php';

// Only Organizers can create meetings [cite: 29-30]
checkAccess(['Organizer']); 

// ... rest of your form code ...
?>