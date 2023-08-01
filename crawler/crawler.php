<?php

echo str_repeat("*", 17) . "\n";
echo " suchfux crawler" . "\n";
echo str_repeat("*", 17) . "\n";

if (php_sapi_name() != "cli") {
    exit("cli only");
}

// Extract options from config file and command line arguments.
if (count($argv) < 2) {
	exit("USAGE: path/to/php -f crawler.php path/to/query.json");
}

$queryPath = $argv[1];
$suggestionsPath = (count($argv) > 2) ? $argv[2] : "suggestions.json";

if (!file_exists($queryPath)) {
	exit("Query file not found: " . $queryPath);
}

$queries = json_decode(file_get_contents($queryPath), true);
if ($queries == NULL) {
	exit("Invalid JSON syntax in query file: " . $queryPath);
}

$suggestions = array();
foreach ($queries as $query) {
	$s = google_get_suggestions($query);
	$suggestions[] = array("query" => $query, "suggestions" => $s);
}

file_put_contents($suggestionsPath, json_encode($suggestions));
exit("Success!");

function google_get_suggestions($query) {
	$raw = google_meta_query($query);

	// doing a lil' butchering, because I hate working with XML.
	$metaData = json_decode(json_encode(simplexml_load_string($raw)), true);
	
	$suggestions = array();
	foreach ($metaData["CompleteSuggestion"] as $suggestion) {
		$data = $suggestion["suggestion"]["@attributes"]["data"];

		$suggestions[] = $data;
	}

	return $suggestions;
}

function google_meta_query($query) {
	$c = curl_init("https://suggestqueries.google.com/complete/search?output=toolbar&hl=de&q=" . str_replace(" ", "%20", $query));
	curl_setopt($c, CURLOPT_ENCODING, "UTF-8");
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($c);

	if (curl_error($c)) {
		exit("Google API call failed: " . curl_error($c));
	}

	curl_close($c);

	return mb_convert_encoding($result, 'UTF-8', 'ISO-8859-1');
}

// ?>