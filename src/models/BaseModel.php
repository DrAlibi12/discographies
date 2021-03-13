<?php

namespace EnioLotero\Discographies\Models;

interface BaseModel {
    public static function mapRawResponseData(array $raw) : array;
}

?>