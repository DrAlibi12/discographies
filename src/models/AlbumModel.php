<?php

namespace EnioLotero\Discographies\Models;

use Monolog\Logger;

class AlbumModel implements BaseModel {
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public static function mapRawResponseData(array $raw) : array {
        $albums = array();
        $album = array();

        for ($i=0; $i < count($raw); $i++) {
            $date = date_create($raw[$i]["release_date"]);;
            $formattedDate = date_format($date,"m-d-Y");
            $album = [
                "name" => $raw[$i]['name'],
                "released" => $formattedDate,
                "tracks" => $raw[$i]["total_tracks"],
                "cover" => [
                    "height" => $raw[$i]["images"][0]["height"],
                    "width" => $raw[$i]["images"][0]["width"],
                    "url" => $raw[$i]["images"][0]["url"]
                ]
            ];
            $albums[] = $album;
        }

        return $albums;
    }
}