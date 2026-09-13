<?php

require "db.php";
require "filter_helpers.php";

$conn->set_charset("utf8mb4");

$filters =
    getCarFilters();


// Märke
[
    $brandWhere,
    $brandTypes,
    $brandParams
] = buildCarWhere(
    $filters,
    "brand"
);


$brandRows = fetchRows(
    $conn,
    "
        SELECT
            brand AS value,
            COUNT(*) AS total
        FROM cars
        $brandWhere

        AND brand IS NOT NULL
        AND brand != ''

        GROUP BY brand
        ORDER BY brand ASC
    ",
    $brandTypes,
    $brandParams
);


$brandTotal = 0;

foreach ($brandRows as $row) {
    $brandTotal += (int) $row["total"];
}


$brandHtml =
    "<option value=''>"
    . "Alla märken ("
    . formatCount($brandTotal)
    . ")</option>";


$brandFound = false;


foreach ($brandRows as $row) {

    $value =
        $row["value"];

    $total =
        (int) $row["total"];


    $selected =
        $filters["brand"] === $value
            ? " selected"
            : "";


    if ($selected !== "") {
        $brandFound = true;
    }


    $brandHtml .=
        "<option value='"
        . h($value)
        . "'"
        . $selected
        . ">"
        . h($value)
        . " ("
        . formatCount($total)
        . ")"
        . "</option>";
}


// Behåll valt märke även om det ger 0 träffar
if (
    $filters["brand"] !== ""
    &&
    !$brandFound
) {

    $brandHtml .=
        "<option value='"
        . h($filters["brand"])
        . "' selected>"
        . h($filters["brand"])
        . " (0)"
        . "</option>";
}


// Modell
[
    $modelWhere,
    $modelTypes,
    $modelParams
] = buildCarWhere(
    $filters,
    "model"
);


$modelRows = fetchRows(
    $conn,
    "
        SELECT
            brand,
            model,
            COUNT(*) AS total
        FROM cars
        $modelWhere

        AND model IS NOT NULL
        AND model != ''

        GROUP BY
            brand,
            model

        ORDER BY
            model ASC,
            brand ASC
    ",
    $modelTypes,
    $modelParams
);


$modelTotal = 0;

foreach ($modelRows as $row) {
    $modelTotal += (int) $row["total"];
}


$modelHtml =
    "<option value=''>"
    . "Alla modeller ("
    . formatCount($modelTotal)
    . ")</option>";


$modelFound = false;


foreach ($modelRows as $row) {

    $model =
        $row["model"];

    $brand =
        $row["brand"];

    $total =
        (int) $row["total"];


    $selected =
        $filters["model"] === $model
            ? " selected"
            : "";


    if ($selected !== "") {
        $modelFound = true;
    }


    $modelHtml .=
        "<option value='"
        . h($model)
        . "'"
        . $selected
        . ">"
        . h($model)
        . " ("
        . h($brand)
        . ") - "
        . formatCount($total)
        . "</option>";
}


if (
    $filters["model"] !== ""
    &&
    !$modelFound
) {

    $modelHtml .=
        "<option value='"
        . h($filters["model"])
        . "' selected>"
        . h($filters["model"])
        . " (0)"
        . "</option>";
}


// Årsmodell
[
    $yearWhere,
    $yearTypes,
    $yearParams
] = buildCarWhere(
    $filters,
    "year"
);


$yearRows = fetchRows(
    $conn,
    "
        SELECT
            model_year AS value,
            COUNT(*) AS total
        FROM cars
        $yearWhere

        AND model_year > 0

        GROUP BY model_year

        ORDER BY model_year DESC
    ",
    $yearTypes,
    $yearParams
);


$yearTotal = 0;

foreach ($yearRows as $row) {
    $yearTotal += (int) $row["total"];
}


$yearHtml =
    "<option value=''>"
    . "Alla årsmodeller ("
    . formatCount($yearTotal)
    . ")</option>";


$yearFound = false;


foreach ($yearRows as $row) {

    $value =
        (string) $row["value"];

    $total =
        (int) $row["total"];


    $selected =
        $filters["year"] === $value
            ? " selected"
            : "";


    if ($selected !== "") {
        $yearFound = true;
    }


    $yearHtml .=
        "<option value='"
        . h($value)
        . "'"
        . $selected
        . ">"
        . h($value)
        . " ("
        . formatCount($total)
        . ")"
        . "</option>";
}


if (
    $filters["year"] !== ""
    &&
    !$yearFound
) {

    $yearHtml .=
        "<option value='"
        . h($filters["year"])
        . "' selected>"
        . h($filters["year"])
        . " (0)"
        . "</option>";
}


// Miltal
[
    $mileageWhere,
    $mileageTypes,
    $mileageParams
] = buildCarWhere(
    $filters,
    "mileage"
);


$mileageRows = fetchRows(
    $conn,
    "
        SELECT

            COUNT(*) AS all_count,

            SUM(
                mileage > 0
                AND mileage <= 5000
            ) AS range_1,

            SUM(
                mileage > 5000
                AND mileage <= 10000
            ) AS range_2,

            SUM(
                mileage > 10000
                AND mileage <= 15000
            ) AS range_3,

            SUM(
                mileage > 15000
                AND mileage <= 20000
            ) AS range_4,

            SUM(
                mileage > 20000
            ) AS range_5

        FROM cars
        $mileageWhere
    ",
    $mileageTypes,
    $mileageParams
);


$mileageData =
    $mileageRows[0];


$mileageHtml =
    "<option value=''>"
    . "Alla miltal ("
    . formatCount(
        (int) $mileageData["all_count"]
    )
    . ")</option>";


$mileageOptions = [

    [
        "value" => "0-5000",
        "label" => "0 - 5 000 mil",
        "count" => (int) $mileageData["range_1"]
    ],

    [
        "value" => "5000-10000",
        "label" => "5 000 - 10 000 mil",
        "count" => (int) $mileageData["range_2"]
    ],

    [
        "value" => "10000-15000",
        "label" => "10 000 - 15 000 mil",
        "count" => (int) $mileageData["range_3"]
    ],

    [
        "value" => "15000-20000",
        "label" => "15 000 - 20 000 mil",
        "count" => (int) $mileageData["range_4"]
    ],

    [
        "value" => "20000+",
        "label" => "20 000+ mil",
        "count" => (int) $mileageData["range_5"]
    ]
];


foreach ($mileageOptions as $option) {

    $isSelected =
        $filters["mileage_range"]
        === $option["value"];


    // Dölj alternativ utan träffar
    if (
        $option["count"] === 0
        &&
        !$isSelected
    ) {
        continue;
    }


    $selected =
        $isSelected
            ? " selected"
            : "";


    $mileageHtml .=
        "<option value='"
        . h($option["value"])
        . "'"
        . $selected
        . ">"
        . h($option["label"])
        . " ("
        . formatCount($option["count"])
        . ")"
        . "</option>";
}


// Bränsle
[
    $fuelWhere,
    $fuelTypes,
    $fuelParams
] = buildCarWhere(
    $filters,
    "fuel"
);


$fuelRows = fetchRows(
    $conn,
    "
        SELECT
            fuel_type AS value,
            COUNT(*) AS total
        FROM cars
        $fuelWhere

        AND fuel_type IS NOT NULL
        AND fuel_type != ''

        GROUP BY fuel_type
        ORDER BY fuel_type ASC
    ",
    $fuelTypes,
    $fuelParams
);


$fuelTotal = 0;

foreach ($fuelRows as $row) {
    $fuelTotal += (int) $row["total"];
}


$fuelHtml =
    "<option value=''>"
    . "Alla bränslen ("
    . formatCount($fuelTotal)
    . ")</option>";


$fuelFound = false;


foreach ($fuelRows as $row) {

    $value =
        $row["value"];

    $total =
        (int) $row["total"];


    $selected =
        $filters["fuel"] === $value
            ? " selected"
            : "";


    if ($selected !== "") {
        $fuelFound = true;
    }


    $fuelHtml .=
        "<option value='"
        . h($value)
        . "'"
        . $selected
        . ">"
        . h($value)
        . " ("
        . formatCount($total)
        . ")"
        . "</option>";
}


if (
    $filters["fuel"] !== ""
    &&
    !$fuelFound
) {

    $fuelHtml .=
        "<option value='"
        . h($filters["fuel"])
        . "' selected>"
        . h($filters["fuel"])
        . " (0)"
        . "</option>";
}


// Växellåda
[
    $gearboxWhere,
    $gearboxTypes,
    $gearboxParams
] = buildCarWhere(
    $filters,
    "gearbox"
);


$gearboxRows = fetchRows(
    $conn,
    "
        SELECT
            gearbox AS value,
            COUNT(*) AS total
        FROM cars
        $gearboxWhere

        AND gearbox IS NOT NULL
        AND gearbox != ''

        GROUP BY gearbox
        ORDER BY gearbox ASC
    ",
    $gearboxTypes,
    $gearboxParams
);


$gearboxTotal = 0;

foreach ($gearboxRows as $row) {
    $gearboxTotal += (int) $row["total"];
}


$gearboxHtml =
    "<option value=''>"
    . "Alla växellådor ("
    . formatCount($gearboxTotal)
    . ")</option>";


$gearboxFound = false;


foreach ($gearboxRows as $row) {

    $value =
        $row["value"];

    $total =
        (int) $row["total"];


    $selected =
        $filters["gearbox"] === $value
            ? " selected"
            : "";


    if ($selected !== "") {
        $gearboxFound = true;
    }


    $gearboxHtml .=
        "<option value='"
        . h($value)
        . "'"
        . $selected
        . ">"
        . h($value)
        . " ("
        . formatCount($total)
        . ")"
        . "</option>";
}


if (
    $filters["gearbox"] !== ""
    &&
    !$gearboxFound
) {

    $gearboxHtml .=
        "<option value='"
        . h($filters["gearbox"])
        . "' selected>"
        . h($filters["gearbox"])
        . " (0)"
        . "</option>";
}


// Skicka filteralternativen tillbaka till index.php
header(
    "Content-Type: application/json; charset=utf-8"
);


echo json_encode(
    [
        "brand" => $brandHtml,
        "model" => $modelHtml,
        "year" => $yearHtml,
        "mileage" => $mileageHtml,
        "fuel" => $fuelHtml,
        "gearbox" => $gearboxHtml
    ],
    JSON_UNESCAPED_UNICODE
);


$conn->close();

?>