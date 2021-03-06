<?php
$rootPath = dirname(__DIR__);
$repo = "{$rootPath}/tmp/2019-nCoV";
exec("cd {$repo} && /usr/bin/git pull");

require dirname(__DIR__) . '/env.php';
$tmpPath = dirname(__DIR__) . '/osm/jhu.edu';
if(!file_exists($tmpPath)) {
    mkdir($tmpPath, 0777, true);
}
$pointPath = dirname(__DIR__) . '/data/points';
if(!file_exists($pointPath)) {
    mkdir($pointPath, 0777, true);
}
$filePath = dirname(__DIR__) . '/raw/jhu.edu';
$baseUrl = 'https://nominatim.openstreetmap.org/search?format=json&email=' . urlencode($email) . '&q=';
$last = 0;
$lastTotal = array();
foreach(glob($repo . '/csse_covid_19_data/csse_covid_19_daily_reports/*.csv') AS $csvFile) {
    $p = pathinfo($csvFile);
    if($p['filename'] === 'Notice') {
        continue;
    }
    $parts1 = explode('-', $p['filename']);
    $sheetTime = strtotime(implode('-', array($parts1[2], $parts1[0], $parts1[1])));

    $pointFile = $pointPath . '/' . date('Ymd', $sheetTime) . '.json';
    $fc = array(
        'type' => 'FeatureCollection',
        'features' => array(),
    );
    $fh = fopen($csvFile, 'r');
    $head = fgetcsv($fh, 2048);
    if('efbbbf' === bin2hex(substr($head[0], 0, 3))) {
        $head[0] = substr($head[0], 3);
    }
    $currentTotal = array(
        'Confirmed' => 0,
        'Recovered' => 0,
        'Deaths' => 0,
    );
    while($line = fgetcsv($fh, 2048)) {
        $data = array_combine($head, $line);
        $data['Confirmed'] = intval($data['Confirmed']);
        $data['Recovered'] = intval($data['Recovered']);
        $data['Deaths'] = intval($data['Deaths']);
        $currentTotal['Confirmed'] += $data['Confirmed'];
        $currentTotal['Recovered'] += $data['Recovered'];
        $currentTotal['Deaths'] += $data['Deaths'];
        if(isset($data['Country'])) {
            $data['Country/Region'] = $data['Country'];
            unset($data['Country']);
        }
        if(isset($data['Date last updated'])) {
            $data['Last Update (UTC)'] = $data['Date last updated'];
            unset($data['Date last updated']);
        }
        switch($data['Country/Region']) {
            case 'Mainland China':
            case 'Macau':
                $data['Country/Region'] = 'China';
            break;
        }
        $f = array(
            'type' => 'Feature',
            'properties' => $data,
            'geometry' => array(
                'type' => 'Point',
                'coordinates' => array(),
            ),
        );
        $cacheFile = "{$tmpPath}/{$data['Province/State']}_{$data['Country/Region']}.json";
        if(!file_exists($cacheFile)) {
            $qUrl = $baseUrl . urlencode("{$data['Province/State']}, {$data['Country/Region']}");
            file_put_contents($cacheFile, file_get_contents($qUrl));
        }
        $json = json_decode(file_get_contents($cacheFile), true);
        if(!empty($json[0]['lat'])) {
            $f['geometry']['coordinates'] = array($json[0]['lon'], $json[0]['lat']);
            $fc['features'][] = $f;
        }
    }
    file_put_contents($pointFile, json_encode($fc,  JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));

    if($sheetTime > $last) {
        $last = $sheetTime;
        $lastTotal = $currentTotal;
    }
}
$meta = json_decode(file_get_contents(dirname(__DIR__) . '/data/meta.json'), true);
$meta['points'] = date('Ymd', $last);
foreach($lastTotal AS $k => $v) {
    $meta[$k] = $v;
}
file_put_contents(dirname(__DIR__) . '/data/meta.json', json_encode($meta, JSON_PRETTY_PRINT));