<?php

require __DIR__ . '/../vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

const GOOGLE_CLOUD_PROJECT_ID = 'stampippi';

$firestore = new FirestoreClient([
    'projectId' => GOOGLE_CLOUD_PROJECT_ID,
]);

$collectionReference = $firestore->collection('User');


// $documentReference = $collectionReference->document('4TW8D3rCqEhY81DlsW9B');
// $snapshot = $documentReference->snapshot();

$collectionReference->document('dummy-id-01')->set(['name' => 'fuga']);


